<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPRE_Logger {
    protected $enabled = false;

    /** @var array<int,array<string,mixed>> */
    protected $context_stack = [];

    public function __construct( $enabled = false ) {
        $this->enabled = (bool) $enabled;
    }

    public function is_enabled() {
        return $this->enabled;
    }

    public function debug( $message, array $context = [] ) {
        if ( ! $this->enabled ) {
            return;
        }

        $this->write_log( 'DEBUG', $message, $context );
    }

    public function error( $message, array $context = [] ) {
        if ( ! $this->enabled ) {
            return;
        }

        $this->write_log( 'ERROR', $message, $context );
    }

    public function push_context( array $context = [] ) {
        if ( empty( $context ) ) {
            return;
        }

        $this->context_stack[] = $context;
    }

    public function pop_context() {
        if ( empty( $this->context_stack ) ) {
            return;
        }

        array_pop( $this->context_stack );
    }

    public function scoped( array $context, callable $callback ) {
        $this->push_context( $context );

        try {
            return $callback();
        } finally {
            $this->pop_context();
        }
    }

    protected function write_log( $level, $message, array $context = [] ) {
        $dir = self::ensure_log_dir();
        if ( '' === $dir ) {
            return;
        }

        $raw_message = self::strip_translatepress_markers( $message );
        $message = self::translate_log_value( $raw_message );
        $context = self::translate_log_value( self::sanitize_log_context( $this->merge_context( $context ) ) );

        if ( ! is_dir( $dir ) || ! wp_is_writable( $dir ) ) {
            return;
        }

        $file = trailingslashit( $dir ) . 'router-' . self::format_local_time( 'Y-m-d' ) . '.log';
        $line = sprintf(
            "[%s] [%s] %s %s
",
            self::format_local_time( 'Y-m-d H:i:s' ),
            $level,
            $message,
            ! empty( $context ) ? wp_json_encode( $context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) : ''
        );

        file_put_contents( $file, $line, FILE_APPEND | LOCK_EX );
    }


    protected static function translate_log_value( $value ) {
        if ( is_array( $value ) ) {
            foreach ( $value as $key => $item ) {
                $value[ $key ] = self::translate_log_value( $item );
            }
            return $value;
        }

        if ( ! is_string( $value ) ) {
            return $value;
        }

        $value = self::strip_translatepress_markers( $value );

        if ( function_exists( 'tpre_log_translate' ) ) {
            return tpre_log_translate( $value );
        }

        return $value;
    }

    protected static function strip_translatepress_markers( $value ) {
        if ( ! is_string( $value ) || '' === $value ) {
            return is_string( $value ) ? $value : '';
        }

        $value = preg_replace( '/#!trpst#trp-gettext[^#]*#!trpen#/', '', $value );
        $value = str_replace( [ '#!trpst#/trp-gettext#!trpen#', '#!trpen#', '#!trpst#' ], '', $value );

        return is_string( $value ) ? trim( $value ) : '';
    }

    protected function merge_context( array $context ) {
        if ( empty( $this->context_stack ) ) {
            return $context;
        }

        $merged = [];
        foreach ( $this->context_stack as $stack_context ) {
            if ( ! is_array( $stack_context ) || empty( $stack_context ) ) {
                continue;
            }
            $merged = array_merge( $merged, $stack_context );
        }

        if ( ! empty( $context ) ) {
            $merged = array_merge( $merged, $context );
        }

        return $merged;
    }

    protected static function ensure_log_dir() {
        $dir = self::get_log_dir();
        if ( '' === $dir ) {
            return '';
        }

        if ( ! is_dir( $dir ) ) {
            wp_mkdir_p( $dir );
        }

        if ( ! is_dir( $dir ) ) {
            return '';
        }

        self::write_log_dir_protection_files( $dir );

        return $dir;
    }

    protected static function write_log_dir_protection_files( $dir ) {
        $dir = is_string( $dir ) ? rtrim( $dir, "/\\" ) : '';
        if ( '' === $dir || ! is_dir( $dir ) || ! wp_is_writable( $dir ) ) {
            return;
        }

        $filesystem = self::get_filesystem();
        if ( ! $filesystem ) {
            return;
        }

        $files = [
            'index.php'  => "<?php\nif ( ! defined( 'ABSPATH' ) ) {\n    exit;\n}\n",
            '.htaccess'  => "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n",
            'web.config' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration>\n  <system.webServer>\n    <authorization>\n      <remove users=\"*\" roles=\"\" verbs=\"\" />\n      <add accessType=\"Deny\" users=\"*\" />\n    </authorization>\n  </system.webServer>\n</configuration>\n",
        ];

        $file_mode = defined( 'FS_CHMOD_FILE' ) ? FS_CHMOD_FILE : 0644;

        foreach ( $files as $filename => $content ) {
            $path = trailingslashit( $dir ) . $filename;
            if ( is_file( $path ) ) {
                continue;
            }

            if ( method_exists( $filesystem, 'put_contents' ) ) {
                $filesystem->put_contents( $path, $content, $file_mode );
            } else {
                file_put_contents( $path, $content );
            }
        }
    }

    protected static function sanitize_log_context( array $context ) {
        $sanitized = [];
        foreach ( $context as $key => $value ) {
            $sanitized[ $key ] = self::sanitize_log_value( $key, $value );
        }

        return $sanitized;
    }

    protected static function sanitize_log_value( $key, $value ) {
        $normalized_key = strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $key ) );

        if ( in_array( $normalized_key, [ 'body', 'preview', 'text_preview', 'request_body', 'response_body', 'raw_body', 'content_preview' ], true ) ) {
            return self::describe_redacted_value( $value );
        }

        if ( in_array( $normalized_key, [ 'api_key', 'secret_key', 'authorization', 'access_key', 'secret_access_key' ], true ) ) {
            return self::mask_secret_value( $value );
        }

        if ( false !== strpos( $normalized_key, 'header' ) ) {
            return self::sanitize_header_value( $value );
        }

        if ( is_array( $value ) ) {
            $sanitized = [];
            foreach ( $value as $child_key => $child_value ) {
                $sanitized[ $child_key ] = self::sanitize_log_value( $child_key, $child_value );
            }
            return $sanitized;
        }

        if ( is_object( $value ) ) {
            return self::describe_redacted_value( $value );
        }

        return $value;
    }

    protected static function sanitize_header_value( $value ) {
        if ( is_array( $value ) ) {
            $sanitized = [];
            foreach ( $value as $header_name => $header_value ) {
                $normalized_name = strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $header_name ) );
                if ( in_array( $normalized_name, [ 'authorization', 'cookie', 'xapikey', 'api_key', 'apikey', 'proxyauthorization' ], true ) ) {
                    $sanitized[ $header_name ] = self::mask_secret_value( $header_value );
                } else {
                    $sanitized[ $header_name ] = is_scalar( $header_value ) || null === $header_value
                        ? self::truncate_log_string( (string) $header_value, 120 )
                        : self::describe_redacted_value( $header_value );
                }
            }
            return $sanitized;
        }

        return self::describe_redacted_value( $value );
    }

    protected static function describe_redacted_value( $value ) {
        $length = 0;

        if ( is_string( $value ) ) {
            $length = strlen( $value );
        } elseif ( is_scalar( $value ) || null === $value ) {
            $length = strlen( (string) $value );
        } else {
            $encoded = wp_json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
            $length  = is_string( $encoded ) ? strlen( $encoded ) : 0;
        }

        return sprintf( '[redacted len=%d]', (int) $length );
    }

    protected static function mask_secret_value( $value ) {
        $value = is_scalar( $value ) || null === $value ? trim( (string) $value ) : '';
        if ( '' === $value ) {
            return '';
        }

        $length = strlen( $value );
        if ( $length <= 8 ) {
            return str_repeat( '*', $length );
        }

        return substr( $value, 0, 4 ) . str_repeat( '*', max( 0, $length - 8 ) ) . substr( $value, -4 );
    }

    protected static function truncate_log_string( $value, $max_length = 120 ) {
        $value      = (string) $value;
        $max_length = max( 16, (int) $max_length );
        if ( strlen( $value ) <= $max_length ) {
            return $value;
        }

        return substr( $value, 0, $max_length ) . '...';
    }


    protected static function get_filesystem() {
        if ( ! class_exists( 'WP_Filesystem_Direct' ) ) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
        }

        return new WP_Filesystem_Direct( false );
    }

    public static function format_local_time( $format, $timestamp = null ) {
        $format = is_string( $format ) && '' !== $format ? $format : 'Y-m-d H:i:s';

        if ( function_exists( 'wp_date' ) ) {
            if ( null === $timestamp ) {
                return wp_date( $format );
            }

            return wp_date( $format, (int) $timestamp );
        }

        if ( null === $timestamp ) {
            $timestamp = time();
        }

        return date_i18n( $format, (int) $timestamp, false );
    }

    public static function get_log_dir() {
        if ( ! function_exists( 'wp_upload_dir' ) ) {
            return '';
        }

        $uploads = wp_upload_dir();
        if ( ! is_array( $uploads ) || ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) ) {
            return '';
        }

        return trailingslashit( $uploads['basedir'] ) . 'langrouter-for-translatepress';
    }

    public static function is_globally_enabled() {
        if ( ! class_exists( 'TPRE_Admin_Settings' ) ) {
            return false;
        }

        $settings = TPRE_Admin_Settings::get_settings();
        return ! empty( $settings['log_enabled'] );
    }

    public static function quick_debug( $message, array $context = [] ) {
        $logger = new self( self::is_globally_enabled() );
        $logger->debug( $message, $context );
    }

    public static function quick_error( $message, array $context = [] ) {
        $logger = new self( self::is_globally_enabled() );
        $logger->error( $message, $context );
    }

    public static function sanitize_log_basename( $basename ) {
        $basename = is_string( $basename ) ? wp_basename( $basename ) : '';
        if ( '' === $basename ) {
            return '';
        }

        if ( ! preg_match( '/^router-\d{4}-\d{2}-\d{2}\.log$/', $basename ) ) {
            return '';
        }

        return $basename;
    }

    public static function list_log_files() {
        $dir = self::get_log_dir();
        if ( ! is_dir( $dir ) ) {
            return [];
        }

        clearstatcache();
        $files = glob( trailingslashit( $dir ) . 'router-*.log' );
        if ( ! is_array( $files ) ) {
            return [];
        }

        $items = [];
        foreach ( $files as $file ) {
            if ( ! is_file( $file ) ) {
                continue;
            }

            $basename = self::sanitize_log_basename( wp_basename( $file ) );
            if ( '' === $basename ) {
                continue;
            }

            $size = filesize( $file );
            if ( false === $size || (int) $size <= 0 ) {
                continue;
            }

            $items[ $basename ] = [
                'basename' => $basename,
                'size'     => $size,
                'mtime'    => filemtime( $file ),
            ];
        }

        uasort(
            $items,
            function( $a, $b ) {
                return (int) $b['mtime'] <=> (int) $a['mtime'];
            }
        );

        return array_values( $items );
    }

    public static function get_latest_log_basename() {
        $files = self::list_log_files();
        return ! empty( $files[0]['basename'] ) ? $files[0]['basename'] : '';
    }

    public static function get_current_log_basename() {
        return 'router-' . self::format_local_time( 'Y-m-d' ) . '.log';
    }

    public static function read_log_file( $basename, $max_bytes = 200000 ) {
        $basename = self::sanitize_log_basename( $basename );
        if ( '' === $basename ) {
            return [ 'content' => '', 'truncated' => false, 'exists' => false ];
        }

        $path = trailingslashit( self::get_log_dir() ) . $basename;
        if ( ! is_file( $path ) || ! is_readable( $path ) ) {
            return [ 'content' => '', 'truncated' => false, 'exists' => false ];
        }

        $max_bytes       = max( 1024, (int) $max_bytes );
        $tpre_filesystem = self::get_filesystem();
        $content         = $tpre_filesystem->get_contents( $path );
        if ( false === $content || null === $content || ! is_string( $content ) ) {
            return [ 'content' => '', 'truncated' => false, 'exists' => false ];
        }

        $size      = strlen( $content );
        $truncated = $size > $max_bytes;
        if ( ! $truncated ) {
            return [ 'content' => $content, 'truncated' => false, 'exists' => true ];
        }

        $tail = substr( $content, -1 * $max_bytes );

        return [
            /* translators: %d: Number of bytes shown from the end of the log file. */
            'content'   => sprintf( __( '... [仅显示最后 %d 字节]', 'langrouter-for-translatepress' ), $max_bytes ) . "
" . ltrim( (string) $tail ),
            'truncated' => true,
            'exists'    => true,
        ];
    }
}
