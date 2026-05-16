<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPRE_Log_Actions {
    public static function handle_log_actions() {
        if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( TPRE_Admin_Settings::PAGE_SLUG !== self::get_query_key( 'page' ) ) {
            return;
        }

        $action = self::get_query_key( 'tpre_action' );
        if ( '' === $action ) {
            return;
        }

        $basename = self::get_requested_log_basename();

        if ( 'download_log' === $action ) {
            check_admin_referer( 'tpre_download_log_file' );
            self::download_log_file( $basename );
            exit;
        }

        if ( 'delete_log' !== $action ) {
            return;
        }

        check_admin_referer( 'tpre_delete_log_file' );

        $result = self::delete_log_file( $basename );
        $args   = [
            'page' => TPRE_Admin_Settings::PAGE_SLUG,
            'tab'  => 'logs',
        ];

        if ( ! empty( $result['cleared'] ) ) {
            $args['log_cleared'] = 1;
            if ( ! empty( $result['next_file'] ) ) {
                $args['tpre_log_file'] = $result['next_file'];
            }
        } elseif ( ! empty( $result['deleted'] ) ) {
            $args['log_deleted'] = 1;
            if ( ! empty( $result['next_file'] ) ) {
                $args['tpre_log_file'] = $result['next_file'];
            }
        } else {
            $args['log_delete_error'] = isset( $result['error'] ) ? $result['error'] : 'unknown';
            if ( '' !== $basename ) {
                $args['tpre_log_file'] = $basename;
            }
        }

        wp_safe_redirect( add_query_arg( $args, admin_url( 'options-general.php' ) ) );
        exit;
    }

    public static function maybe_render_notices() {
        if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( TPRE_Admin_Settings::PAGE_SLUG !== self::get_query_key( 'page' ) ) {
            return;
        }

        if ( '' !== self::get_query_key( 'log_deleted' ) ) {
            echo '<div class="notice notice-success"><p>' . esc_html( __( '日志文件已删除。', 'langrouter-for-translatepress' ) ) . '</p></div>';
        }

        if ( '' !== self::get_query_key( 'log_cleared' ) ) {
            echo '<div class="notice notice-success"><p>' . esc_html( __( '当前日志文件已清空。若仍有新请求写入日志，文件会再次出现内容。', 'langrouter-for-translatepress' ) ) . '</p></div>';
        }

        $tpre_error = self::get_query_key( 'log_delete_error' );
        if ( '' !== $tpre_error ) {
            $tpre_map = self::get_log_delete_error_messages();
            $tpre_msg = isset( $tpre_map[ $tpre_error ] ) ? $tpre_map[ $tpre_error ] : $tpre_map['unknown'];
            echo '<div class="notice notice-error"><p>' . esc_html( $tpre_msg ) . '</p></div>';
        }

        if ( ! class_exists( 'TRP_Translate_Press' ) ) {
            echo '<div class="notice notice-warning"><p>' . esc_html( __( '未检测到已启用的 TranslatePress。路由引擎不会注册到自动翻译页面，但模型设置仍可提前保存。', 'langrouter-for-translatepress' ) ) . '</p></div>';
        }
    }

    protected static function get_log_delete_error_messages() {
        return [
            'invalid_file'  => __( '未识别到可删除的日志文件。', 'langrouter-for-translatepress' ),
            'missing_file'  => __( '日志文件不存在，可能已被删除。', 'langrouter-for-translatepress' ),
            'not_writable'  => __( '日志文件或日志目录不可写，无法删除。', 'langrouter-for-translatepress' ),
            'clear_failed'  => __( '清空当前日志文件失败，请检查服务器文件权限。', 'langrouter-for-translatepress' ),
            'unlink_failed' => __( '删除日志文件失败，请检查服务器文件权限。', 'langrouter-for-translatepress' ),
            'still_exists'  => __( '日志文件操作后仍然存在，可能被新的请求重新创建。请先暂停日志写入后再试。', 'langrouter-for-translatepress' ),
            'unknown'       => __( '删除日志文件失败。', 'langrouter-for-translatepress' ),
        ];
    }

    protected static function get_query_key( $key ) {
        if ( ! isset( $_GET[ $key ] ) || ! is_scalar( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin routing parameter.
            return '';
        }

        return sanitize_key( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sanitized before use.
    }

    protected static function get_requested_log_basename() {
        if ( ! isset( $_GET['tpre_log_file'] ) || ! is_scalar( $_GET['tpre_log_file'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is checked before log actions are processed.
            return '';
        }

        return TPRE_Logger::sanitize_log_basename( sanitize_file_name( wp_unslash( $_GET['tpre_log_file'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sanitized before path construction.
    }

    protected static function get_filesystem() {
        if ( ! function_exists( 'get_filesystem_method' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        if ( ! class_exists( 'WP_Filesystem_Direct' ) ) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
        }

        return new WP_Filesystem_Direct( false );
    }

    protected static function get_log_file_path( $basename ) {
        $basename = TPRE_Logger::sanitize_log_basename( $basename );
        if ( '' === $basename ) {
            return '';
        }

        $path = trailingslashit( TPRE_Logger::get_log_dir() ) . $basename;
        if ( ! is_file( $path ) || ! is_readable( $path ) ) {
            return '';
        }

        return $path;
    }

    protected static function download_log_file( $basename ) {
        $path = self::get_log_file_path( $basename );
        if ( '' === $path ) {
            wp_die( esc_html( __( '未找到可下载的日志文件。', 'langrouter-for-translatepress' ) ) );
        }

        $tpre_filesystem = self::get_filesystem();
        $tpre_content    = $tpre_filesystem->get_contents( $path );
        if ( false === $tpre_content || null === $tpre_content ) {
            wp_die( esc_html( __( '日志文件无法读取，无法下载。', 'langrouter-for-translatepress' ) ) );
        }

        $tpre_filename      = wp_basename( $path );
        $tpre_fallback_name = sanitize_file_name( $tpre_filename );
        if ( '' === $tpre_fallback_name ) {
            $tpre_fallback_name = 'router.log';
        }

        while ( ob_get_level() > 0 ) {
            ob_end_clean();
        }

        if ( function_exists( 'nocache_headers' ) ) {
            nocache_headers();
        }

        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: text/plain; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $tpre_fallback_name . '"; filename*=UTF-8\'\'' . rawurlencode( $tpre_filename ) );
        header( 'Content-Length: ' . (string) strlen( $tpre_content ) );
        header( 'X-Content-Type-Options: nosniff' );

        echo $tpre_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Raw file download response.
    }

    protected static function delete_log_file( $basename ) {
        $basename = TPRE_Logger::sanitize_log_basename( $basename );
        if ( '' === $basename ) {
            return [ 'deleted' => false, 'cleared' => false, 'error' => 'invalid_file' ];
        }

        $path = trailingslashit( TPRE_Logger::get_log_dir() ) . $basename;
        if ( ! is_file( $path ) ) {
            return [ 'deleted' => false, 'cleared' => false, 'error' => 'missing_file' ];
        }

        if ( ! wp_is_writable( $path ) && ! wp_is_writable( dirname( $path ) ) ) {
            return [ 'deleted' => false, 'cleared' => false, 'error' => 'not_writable' ];
        }

        $tpre_filesystem   = self::get_filesystem();
        $is_current_log    = self::is_current_log_file( $basename );

        if ( $is_current_log ) {
            $cleared = self::truncate_log_file( $path );

            clearstatcache( true, $path );
            if ( ! $cleared || ! is_file( $path ) || filesize( $path ) > 0 ) {
                return [ 'deleted' => false, 'cleared' => false, 'error' => 'clear_failed' ];
            }

            return [
                'deleted'   => false,
                'cleared'   => true,
                'next_file' => $basename,
            ];
        }

        if ( ! $tpre_filesystem->delete( $path, false, 'f' ) ) {
            return [ 'deleted' => false, 'cleared' => false, 'error' => 'unlink_failed' ];
        }

        clearstatcache( true, $path );
        if ( is_file( $path ) ) {
            return [ 'deleted' => false, 'cleared' => false, 'error' => 'still_exists' ];
        }

        return [
            'deleted'   => true,
            'cleared'   => false,
            'next_file' => class_exists( 'TPRE_Logger' ) ? TPRE_Logger::get_latest_log_basename() : '',
        ];
    }


    protected static function truncate_log_file( $path ) {
        if ( ! is_string( $path ) || '' === $path || ! is_file( $path ) ) {
            return false;
        }

        if ( ! class_exists( 'WP_Filesystem_Direct' ) ) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
        }

        if ( ! class_exists( 'WP_Filesystem_Direct' ) ) {
            return false;
        }

        $filesystem = new WP_Filesystem_Direct( null );
        $chmod      = defined( 'FS_CHMOD_FILE' ) ? FS_CHMOD_FILE : false;
        $ok         = $filesystem->put_contents( $path, '', $chmod );

        clearstatcache( true, $path );

        return $ok && is_file( $path ) && 0 === (int) filesize( $path );
    }

    protected static function is_current_log_file( $basename ) {
        if ( ! class_exists( 'TPRE_Logger' ) || ! method_exists( 'TPRE_Logger', 'get_current_log_basename' ) ) {
            return false;
        }

        return $basename === TPRE_Logger::get_current_log_basename();
    }
}
