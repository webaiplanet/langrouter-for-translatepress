<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPRE_Admin_Settings {
    const OPTION_KEY = 'tpre_router_settings';
    const PAGE_SLUG  = 'tpre-model-settings';

    public static function boot() {
        add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
        add_action( 'admin_init', [ __CLASS__, 'handle_volc_billing_refresh' ] );
        add_action( 'admin_init', [ __CLASS__, 'handle_save' ] );
        add_action( 'admin_init', [ 'TPRE_Log_Actions', 'handle_log_actions' ] );
        add_action( 'admin_notices', [ 'TPRE_Log_Actions', 'maybe_render_notices' ] );
        add_action( 'admin_notices', [ __CLASS__, 'maybe_render_validation_notices' ] );
        add_action( 'admin_enqueue_scripts', [ 'TPRE_Admin_Page', 'enqueue_admin_assets' ] );
        add_action( 'updated_option', [ __CLASS__, 'maybe_clear_deepl_supported_languages_cache' ], 10, 3 );
        add_action( 'wp_ajax_tpre_query_language_support', [ __CLASS__, 'ajax_query_language_support' ] );
    }


    public static function maybe_clear_deepl_supported_languages_cache( $option, $old_value, $value ) {
        if ( ! in_array( $option, [ self::OPTION_KEY, 'trp_settings' ], true ) ) {
            return;
        }

        if ( ! class_exists( 'TPRE_DeepL_Engine' ) || ! method_exists( 'TPRE_DeepL_Engine', 'clear_supported_languages_cache' ) ) {
            return;
        }

        TPRE_DeepL_Engine::clear_supported_languages_cache();
    }


    public static function ajax_query_language_support() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error(
                [ 'message' => __( '没有权限执行该查询。', 'langrouter-for-translatepress' ) ],
                403
            );
        }

        check_ajax_referer( 'tpre_query_language_support', 'nonce' );

        $language = isset( $_POST['language'] ) ? sanitize_text_field( wp_unslash( $_POST['language'] ) ) : '';
        $engine   = isset( $_POST['engine'] ) ? sanitize_key( wp_unslash( $_POST['engine'] ) ) : '';

        $service = new TPRE_Language_Support_Query( self::get_settings(), get_option( 'trp_settings', [] ) );
        $result  = $service->query( $language, $engine );

        if ( empty( $result['ok'] ) ) {
            wp_send_json_error(
                [ 'message' => $result['message'] ?? __( '查询失败。', 'langrouter-for-translatepress' ) ],
                400
            );
        }

        wp_send_json_success( $result );
    }

    public static function register_menu() {
        add_options_page(
            __('LangRouter 引擎', 'langrouter-for-translatepress'),
            __('LangRouter 引擎', 'langrouter-for-translatepress'),
            'manage_options',
            self::PAGE_SLUG,
            [ 'TPRE_Admin_Page', 'render_model_settings_page' ]
        );
    }

    public static function handle_save() {
        if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( ! self::get_post_flag( 'tpre_save_model_settings' ) ) {
            return;
        }

        check_admin_referer( 'tpre_save_model_settings' );

        $raw_model   = self::get_post_array( 'tpre_settings' );
        $raw_router  = self::get_post_array( 'trp_machine_translation_settings' );
        $current_tab = self::get_post_engine_slug( 'tpre_current_tab', 'volc' );

        if ( 'logs' === $current_tab ) {
            $raw_model['log_enabled'] = self::get_post_flag( 'tpre_log_enabled' ) ? 1 : 0;
            $raw_router['tpre_log_enabled'] = ! empty( $raw_model['log_enabled'] ) ? 1 : 0;
        }

        $settings = self::sanitize_model_settings( $raw_model );
        $settings = self::sanitize_router_settings(
            [ 'trp_machine_translation_settings' => $raw_router ],
            $settings
        );

        update_option( self::OPTION_KEY, $settings );
        self::log_settings_save( $settings, $current_tab, $raw_model, $raw_router );

        if ( function_exists( 'tpre_deepl_sync_runtime_with_keys_text' ) ) {
            $deepl_keys_text = isset( $settings['models']['deepl']['keys_text'] ) ? (string) $settings['models']['deepl']['keys_text'] : '';
            tpre_deepl_sync_runtime_with_keys_text( $deepl_keys_text );
        }

        if ( class_exists( 'TPRE_DeepL_Engine' ) && method_exists( 'TPRE_DeepL_Engine', 'clear_supported_languages_cache' ) ) {
            TPRE_DeepL_Engine::clear_supported_languages_cache();
        }

        wp_safe_redirect(
            add_query_arg(
                [
                    'page'    => self::PAGE_SLUG,
                    'tab'     => $current_tab,
                    'updated' => 1,
                ],
                admin_url( 'options-general.php' )
            )
        );
        exit;
    }


    protected static function build_settings_log_summary( array $settings ) {
        $post_type_rule_map = isset( $settings['post_type_rule_map'] ) && is_array( $settings['post_type_rule_map'] ) ? $settings['post_type_rule_map'] : [];
        $language_map       = isset( $settings['language_engine_map'] ) && is_array( $settings['language_engine_map'] ) ? $settings['language_engine_map'] : [];
        $fallback_map       = isset( $settings['fallback_map'] ) && is_array( $settings['fallback_map'] ) ? $settings['fallback_map'] : [];

        return [
            'default_engine'             => isset( $settings['default_engine'] ) ? (string) $settings['default_engine'] : '',
            'log_enabled'                => ! empty( $settings['log_enabled'] ) ? 1 : 0,
            'global_concurrency_limit'    => isset( $settings['global_concurrency_limit'] ) ? (int) $settings['global_concurrency_limit'] : 0,
            'post_type_rules_count'      => isset( $settings['post_type_rules'] ) && is_array( $settings['post_type_rules'] ) ? count( $settings['post_type_rules'] ) : 0,
            'post_type_rule_map_keys'    => array_values( array_keys( $post_type_rule_map ) ),
            'language_rule_count'        => count( $language_map ),
            'language_rule_keys'         => array_values( array_keys( $language_map ) ),
            'fallback_rule_count'        => count( $fallback_map ),
            'fallback_rule_keys'         => array_values( array_keys( $fallback_map ) ),
            'traditional_chinese_mode'   => isset( $settings['traditional_chinese_mode'] ) ? sanitize_key( (string) $settings['traditional_chinese_mode'] ) : 'translatepress',
        ];
    }

    protected static function log_settings_save( array $settings, $current_tab, array $raw_model = [], array $raw_router = [] ) {
        $logger = new TPRE_Logger( ! empty( $settings['log_enabled'] ) );
        if ( empty( $settings['log_enabled'] ) ) {
            return;
        }

        $logger->debug( __( '设置保存完成', 'langrouter-for-translatepress' ), [
            'current_tab'                        => sanitize_key( (string) $current_tab ),
            'model_payload_present'              => ! empty( $raw_model ) ? 1 : 0,
            'router_payload_present'             => ! empty( $raw_router ) ? 1 : 0,
            'router_default_engine_posted'       => isset( $raw_router['tpre_default_engine'] ) ? sanitize_key( (string) $raw_router['tpre_default_engine'] ) : '',
            'router_post_type_rules_present'     => isset( $raw_router['tpre_post_type_rules_present'] ) ? 1 : 0,
            'router_post_type_rules_json_length' => isset( $raw_router['tpre_post_type_rules_json'] ) ? strlen( (string) $raw_router['tpre_post_type_rules_json'] ) : 0,
            'saved_summary'                      => self::build_settings_log_summary( $settings ),
        ] );
    }

    public static function sanitize_router_settings( $raw_mt_settings, $base_settings = null ) {
        $current = is_array( $base_settings ) ? $base_settings : self::get_settings();
        $source  = is_array( $raw_mt_settings['trp_machine_translation_settings'] ?? null )
            ? $raw_mt_settings['trp_machine_translation_settings']
            : $raw_mt_settings;

        if ( isset( $source['tpre_default_engine'] ) ) {
            $current['default_engine'] = self::sanitize_engine_slug( $source['tpre_default_engine'], $current['default_engine'] );
        }

        if ( isset( $source['tpre_post_type_rules_present'] ) ) {
            $parsed_post_type_rules = self::parse_post_type_rule_rows(
                isset( $source['tpre_post_type_rule_post_types'] ) && is_array( $source['tpre_post_type_rule_post_types'] ) ? $source['tpre_post_type_rule_post_types'] : [],
                isset( $source['tpre_post_type_rule_engine'] ) && is_array( $source['tpre_post_type_rule_engine'] ) ? $source['tpre_post_type_rule_engine'] : [],
                isset( $source['tpre_post_type_rule_fallback_mode'] ) && is_array( $source['tpre_post_type_rule_fallback_mode'] ) ? $source['tpre_post_type_rule_fallback_mode'] : [],
                isset( $source['tpre_post_type_rule_use_global_chain'] ) && is_array( $source['tpre_post_type_rule_use_global_chain'] ) ? $source['tpre_post_type_rule_use_global_chain'] : []
            );

            if ( empty( $parsed_post_type_rules ) && ! empty( $source['tpre_post_type_rules_json'] ) ) {
                $parsed_post_type_rules = self::parse_post_type_rules_json( $source['tpre_post_type_rules_json'] );
            }

            $current['post_type_rules'] = $parsed_post_type_rules;
        } elseif ( isset( $source['tpre_post_type_engine_map'] ) && is_array( $source['tpre_post_type_engine_map'] ) ) {
            $current['post_type_rules'] = self::convert_post_type_engine_map_to_rules( self::parse_post_type_engine_map( $source['tpre_post_type_engine_map'] ) );
        }

        $current = self::normalize_post_type_settings( $current );

        if ( isset( $source['tpre_language_engine_map_raw'] ) ) {
            $current['language_engine_map'] = self::parse_map_textarea( $source['tpre_language_engine_map_raw'] );
        }

        if ( isset( $source['tpre_fallback_map_raw'] ) ) {
            $current['fallback_map'] = self::parse_map_textarea( $source['tpre_fallback_map_raw'] );
        }

        if ( isset( $source['tpre_log_enabled'] ) ) {
            $current['log_enabled'] = ! empty( $source['tpre_log_enabled'] ) ? 1 : 0;
        }

        if ( isset( $source['tpre_global_concurrency_limit'] ) ) {
            $current['global_concurrency_limit'] = max( 0, min( 32, absint( $source['tpre_global_concurrency_limit'] ) ) );
        }

        if ( isset( $source['tpre_traditional_chinese_mode'] ) ) {
            $current['traditional_chinese_mode'] = self::sanitize_traditional_chinese_mode( $source['tpre_traditional_chinese_mode'] );
        }

        return $current;
    }

    protected static function sanitize_volc_accounts_pool( $value ) {
        $lines = preg_split( '/\r\n|\r|\n/', (string) $value );
        $clean = [];

        foreach ( $lines as $line ) {
            $line = trim( (string) $line );
            if ( '' === $line ) {
                continue;
            }

            $parts = array_map( 'trim', explode( '|', $line ) );
            if ( count( $parts ) < 2 || '' === $parts[0] || '' === $parts[1] ) {
                continue;
            }

            $normalized = [
                sanitize_text_field( $parts[0] ),
                sanitize_text_field( $parts[1] ),
            ];

            if ( isset( $parts[2] ) ) {
                $normalized[] = sanitize_text_field( $parts[2] );
            }
            if ( isset( $parts[3] ) ) {
                $normalized[] = sanitize_text_field( $parts[3] );
            }
            if ( isset( $parts[4] ) ) {
                $normalized[] = (string) max( 0, (int) $parts[4] );
            } elseif ( count( $parts ) === 3 && is_numeric( $parts[2] ) ) {
                $normalized[2] = (string) max( 0, (int) $parts[2] );
            }

            if ( isset( $parts[5] ) ) {
                $part5 = sanitize_text_field( $parts[5] );
                $normalized[] = in_array( strtolower( $part5 ), array( 'auto', 'translation', 'chat' ), true ) ? strtolower( $part5 ) : $part5;
            }

            if ( isset( $parts[6] ) ) {
                $part6 = sanitize_text_field( $parts[6] );
                $normalized[] = in_array( strtolower( $part6 ), array( 'auto', 'translation', 'chat' ), true ) ? strtolower( $part6 ) : $part6;
            }

            $clean[] = implode( '|', $normalized );
        }

        return implode( "\n", $clean );
    }

    protected static function sanitize_deepl_keys_text( $value ) {
        $lines = preg_split( '/\r\n|\r|\n/', (string) $value );
        $clean = [];
        $seen  = [];

        foreach ( $lines as $line ) {
            $line = trim( (string) $line );
            if ( '' === $line ) {
                continue;
            }

            $type = '';
            $key  = $line;
            if ( preg_match( '/^(free|pro)\s*:\s*(.+)$/i', $line, $matches ) ) {
                $type = strtolower( $matches[1] );
                $key  = trim( $matches[2] );
            }

            $key = preg_replace( '/\s+/', '', $key );
            if ( '' === $key ) {
                continue;
            }

            $clean_key  = sanitize_text_field( $key );
            $hash_type  = '' !== $type ? $type : ( function_exists( 'tpre_deepl_infer_key_type' ) ? tpre_deepl_infer_key_type( $clean_key, 'free' ) : 'free' );
            $normalized = '' !== $type ? $type . ':' . $clean_key : $clean_key;
            $hash       = sha1( $hash_type . '|' . $clean_key );
            if ( isset( $seen[ $hash ] ) ) {
                continue;
            }
            $seen[ $hash ] = true;
            $clean[]       = $normalized;
        }

        return implode( "\n", $clean );
    }

    protected static function sanitize_traditional_chinese_mode( $value ) {
        $value = is_string( $value ) ? sanitize_key( $value ) : '';

        return in_array( $value, [ 'translatepress', 'opencc' ], true ) ? $value : 'translatepress';
    }

    public static function sanitize_volc_accounts_pool_value( $value ) {
        return self::sanitize_volc_accounts_pool( $value );
    }

    public static function sanitize_deepl_keys_text_value( $value ) {
        return self::sanitize_deepl_keys_text( $value );
    }

    public static function mask_secret_for_admin( $value, $prefix = 4, $suffix = 4 ) {
        $value  = trim( (string) $value );
        $length = strlen( $value );
        $prefix = max( 0, (int) $prefix );
        $suffix = max( 0, (int) $suffix );

        if ( '' === $value ) {
            return '';
        }

        if ( $length <= 4 ) {
            return str_repeat( '*', $length );
        }

        if ( $length <= $prefix + $suffix ) {
            $prefix = min( 2, max( 1, $length - 2 ) );
            $suffix = 1;
        }

        $masked_length = max( 3, $length - $prefix - $suffix );

        return substr( $value, 0, $prefix ) . str_repeat( '*', $masked_length ) . substr( $value, -$suffix );
    }

    public static function build_masked_deepl_keys_text( $value ) {
        $lines  = preg_split( '/\r\n|\r|\n/', (string) $value );
        $masked = [];

        foreach ( (array) $lines as $line ) {
            $line = trim( (string) $line );
            if ( '' === $line ) {
                $masked[] = '';
                continue;
            }

            $prefix = '';
            $key    = $line;
            if ( preg_match( '/^(free|pro)\s*:\s*(.+)$/i', $line, $matches ) ) {
                $prefix = strtolower( $matches[1] ) . ':';
                $key    = trim( $matches[2] );
            }

            $masked[] = $prefix . self::mask_secret_for_admin( $key, 4, 4 );
        }

        return implode( "\n", $masked );
    }

    public static function build_masked_volc_accounts_text( $value ) {
        $lines  = preg_split( '/\r\n|\r|\n/', (string) $value );
        $masked = [];

        foreach ( (array) $lines as $line ) {
            $line = trim( (string) $line );
            if ( '' === $line ) {
                continue;
            }

            $parts = array_map( 'trim', explode( '|', $line ) );
            if ( empty( $parts ) || '' === (string) $parts[0] ) {
                continue;
            }

            $normalized  = [];
            $parts_count = count( $parts );
            foreach ( $parts as $index => $part ) {
                if ( '' === $part ) {
                    $normalized[] = '';
                    continue;
                }

                if ( 0 === $index ) {
                    $normalized[] = $part;
                    continue;
                }

                $lower_part = strtolower( $part );
                $is_mode = in_array( $lower_part, array( 'auto', 'translation', 'chat' ), true );
                $is_threshold_column = is_numeric( $part ) && (
                    ( $parts_count >= 5 && 4 === $index )
                    || ( $parts_count === 3 && 2 === $index )
                );
                $is_mode_or_model_column = ( $index >= 5 );
                if ( $is_mode || $is_threshold_column || $is_mode_or_model_column ) {
                    $normalized[] = $part;
                    continue;
                }

                $normalized[] = self::mask_secret_for_admin( $part, 4, 4 );
            }

            $masked[] = implode( '|', $normalized );
        }

        return implode( "\n", $masked );
    }

    protected static function get_default_model_configs() {
        $defaults = [];

        if ( ! class_exists( 'TPRE_Engine_Registry' ) ) {
            return $defaults;
        }

        foreach ( TPRE_Engine_Registry::get_configurable_engine_slugs() as $engine_slug ) {
            $class = TPRE_Engine_Registry::get_admin_config_class( $engine_slug );
            if ( '' !== $class && class_exists( $class ) && method_exists( $class, 'defaults' ) ) {
                $defaults[ $engine_slug ] = $class::defaults();
            }
        }

        return $defaults;
    }

    protected static function sanitize_model_item( $engine_slug, array $item, array $current_item, array $context = [] ) {
        if ( ! class_exists( 'TPRE_Engine_Registry' ) ) {
            return $current_item;
        }

        $class = TPRE_Engine_Registry::get_admin_config_class( $engine_slug );
        if ( '' === $class || ! class_exists( $class ) || ! method_exists( $class, 'sanitize' ) ) {
            return $current_item;
        }

        $defaults  = method_exists( $class, 'defaults' ) ? (array) $class::defaults() : [];
        $sanitized = $class::sanitize( $item, $current_item, $context );
        if ( ! is_array( $sanitized ) ) {
            $sanitized = [];
        }

        return wp_parse_args( $sanitized, wp_parse_args( $current_item, $defaults ) );
    }


    public static function add_validation_notice( $code, $message, $type = 'error' ) {
        $code    = sanitize_key( (string) $code );
        $message = is_string( $message ) ? trim( $message ) : '';
        $type    = in_array( $type, [ 'error', 'warning', 'success', 'info' ], true ) ? $type : 'error';

        if ( '' === $code || '' === $message ) {
            return;
        }

        $notices = get_transient( 'tpre_langrouter_settings_errors' );
        if ( ! is_array( $notices ) ) {
            $notices = [];
        }

        $notices[ $code ] = [
            'code'    => $code,
            'message' => $message,
            'type'    => $type,
        ];

        set_transient( 'tpre_langrouter_settings_errors', array_values( $notices ), MINUTE_IN_SECONDS );
    }

    public static function pop_validation_notices() {
        $notices = get_transient( 'tpre_langrouter_settings_errors' );
        delete_transient( 'tpre_langrouter_settings_errors' );

        return is_array( $notices ) ? array_values( $notices ) : [];
    }

    public static function maybe_render_validation_notices() {
        if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( self::PAGE_SLUG !== self::get_query_key_from_request( 'page' ) ) {
            return;
        }

        foreach ( self::pop_validation_notices() as $notice ) {
            $type    = isset( $notice['type'] ) ? sanitize_key( (string) $notice['type'] ) : 'error';
            $message = isset( $notice['message'] ) ? (string) $notice['message'] : '';
            if ( '' === $message ) {
                continue;
            }

            echo '<div class="notice notice-' . esc_attr( $type ) . '"><p>' . esc_html( $message ) . '</p></div>';
        }
    }

    protected static function get_query_key_from_request( $key ) {
        if ( ! isset( $_GET[ $key ] ) || ! is_scalar( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin routing parameter.
            return '';
        }

        return sanitize_key( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sanitized read-only parameter.
    }

    protected static function get_query_flag( $key ) {
        return isset( $_GET[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Presence flag, nonce checked by callers before action.
    }

    protected static function get_query_string( $key ) {
        if ( ! isset( $_GET[ $key ] ) || ! is_scalar( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only value sanitized before use.
            return '';
        }

        return sanitize_text_field( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sanitized before verification or comparison.
    }

    public static function normalize_admin_multiline_value( $value ) {
        $value = str_replace( array( "\r\n", "\r" ), "\n", (string) $value );
        return trim( $value );
    }

    public static function handle_volc_billing_refresh() {
        if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( self::PAGE_SLUG !== self::get_query_key_from_request( 'page' ) ) {
            return;
        }

        if ( 'volc' !== self::get_current_model_tab() || ! self::get_query_flag( 'tpre_volc_refresh_billing' ) ) {
            return;
        }

        $nonce = self::get_query_string( 'tpre_volc_refresh_nonce' );
        if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'tpre_volc_refresh_billing' ) ) {
            self::add_validation_notice( 'tpre_volc_refresh_nonce', __( '火山方舟用量刷新失败：请求校验未通过。', 'langrouter-for-translatepress' ) );
            wp_safe_redirect( self::get_model_tab_url( 'volc' ) );
            exit;
        }

        $router_settings = self::get_settings();
        $client          = class_exists( 'TPRE_Volc_Client' ) ? TPRE_Volc_Client::create_for_admin( $router_settings, new TPRE_Logger( false ) ) : null;

        if ( ! $client || ! method_exists( $client, 'has_accounts' ) || ! $client->has_accounts() ) {
            self::add_validation_notice( 'tpre_volc_refresh_empty', __( '火山方舟用量刷新失败：当前未配置账号池。', 'langrouter-for-translatepress' ) );
            wp_safe_redirect( self::get_model_tab_url( 'volc' ) );
            exit;
        }

        if ( method_exists( $client, 'force_refresh_billing_usage_summary_rows' ) ) {
            $client->force_refresh_billing_usage_summary_rows();
            self::add_validation_notice( 'tpre_volc_refresh_done', __( '火山方舟用量已手动刷新。', 'langrouter-for-translatepress' ), 'success' );
        } else {
            self::add_validation_notice( 'tpre_volc_refresh_unavailable', __( '火山方舟用量刷新失败：当前版本不支持手动刷新。', 'langrouter-for-translatepress' ) );
        }

        wp_safe_redirect( self::get_model_tab_url( 'volc' ) );
        exit;
    }

    public static function sanitize_model_settings( array $raw ) {
        $current        = self::get_settings();
        $current_models = $current['models'];
        $raw_models     = isset( $raw['models'] ) && is_array( $raw['models'] ) ? $raw['models'] : [];

        $models = [];
        $engine_slugs = class_exists( 'TPRE_Engine_Registry' ) ? TPRE_Engine_Registry::get_configurable_engine_slugs() : array_keys( $current_models );
        foreach ( $engine_slugs as $engine_slug ) {
            $tab_submitted = isset( $raw_models[ $engine_slug ] ) && is_array( $raw_models[ $engine_slug ] );
            $item          = $tab_submitted ? $raw_models[ $engine_slug ] : [];
            $enabled_value = $tab_submitted
                ? ( ! empty( $item['enabled'] ) ? 1 : 0 )
                : ( ! empty( $current_models[ $engine_slug ]['enabled'] ) ? 1 : 0 );

            $models[ $engine_slug ] = self::sanitize_model_item(
                $engine_slug,
                $item,
                isset( $current_models[ $engine_slug ] ) && is_array( $current_models[ $engine_slug ] ) ? $current_models[ $engine_slug ] : [],
                [
                    'enabled_value' => $enabled_value,
                    'tab_submitted' => $tab_submitted,
                ]
            );
        }

        $current['models'] = $models;
        if ( array_key_exists( 'log_enabled', $raw ) ) {
            $current['log_enabled'] = ! empty( $raw['log_enabled'] ) ? 1 : 0;
        }
        return $current;
    }

    protected static function sanitize_engine_slug( $value, $default = 'volc' ) {
        $slug    = self::normalize_engine_slug( $value );
        $choices = self::get_engine_choices();
        return isset( $choices[ $slug ] ) ? $slug : $default;
    }

    protected static function normalize_engine_slug( $value ) {
        return sanitize_key( (string) $value );
    }

    protected static function parse_post_type_engine_map( array $raw_map ) {
        $choices            = self::get_engine_choices();
        $allowed_post_types = array_keys( self::get_routable_post_types() );
        $allowed_lookup     = array_fill_keys( $allowed_post_types, true );
        $result             = [];

        foreach ( $raw_map as $post_type => $engine_slug ) {
            $post_type   = sanitize_key( (string) $post_type );
            $engine_slug = self::normalize_engine_slug( $engine_slug );

            if ( '' === $post_type || ! isset( $allowed_lookup[ $post_type ] ) ) {
                continue;
            }

            if ( '' === $engine_slug || ! isset( $choices[ $engine_slug ] ) ) {
                continue;
            }

            $result[ $post_type ] = $engine_slug;
        }

        ksort( $result );

        return $result;
    }

    protected static function parse_post_type_list_value( $value ) {
        if ( is_array( $value ) ) {
            $items = $value;
        } else {
            $value = trim( (string) $value );
            if ( '' === $value ) {
                return [];
            }

            $decoded = json_decode( $value, true );
            if ( is_array( $decoded ) ) {
                $items = $decoded;
            } else {
                $items = array_map( 'trim', explode( ',', $value ) );
            }
        }

        $result = [];
        foreach ( $items as $item ) {
            $slug = sanitize_key( (string) $item );
            if ( '' === $slug || in_array( $slug, $result, true ) ) {
                continue;
            }
            $result[] = $slug;
        }

        return $result;
    }

    protected static function sanitize_post_type_fallback_mode( $value, $legacy_use_global_chain = null ) {
        $value = is_string( $value ) ? sanitize_key( $value ) : '';

        if ( in_array( $value, [ 'none', 'default_only', 'global_chain' ], true ) ) {
            return $value;
        }

        if ( null !== $legacy_use_global_chain ) {
            return ! empty( $legacy_use_global_chain ) ? 'global_chain' : 'default_only';
        }

        return 'default_only';
    }

    protected static function sanitize_post_type_rules( array $rules ) {
        $choices  = self::get_engine_choices();
        $assigned = [];
        $result   = [];

        foreach ( $rules as $rule ) {
            if ( ! is_array( $rule ) ) {
                continue;
            }

            $engine_slug = isset( $rule['engine'] ) ? self::normalize_engine_slug( $rule['engine'] ) : '';
            if ( '' === $engine_slug || ! isset( $choices[ $engine_slug ] ) ) {
                continue;
            }

            $post_types       = self::parse_post_type_list_value( $rule['post_types'] ?? [] );
            $clean_post_types = [];
            foreach ( $post_types as $post_type ) {
                $post_type = sanitize_key( (string) $post_type );
                if ( '' === $post_type || isset( $assigned[ $post_type ] ) ) {
                    continue;
                }
                $assigned[ $post_type ] = true;
                $clean_post_types[]     = $post_type;
            }

            if ( empty( $clean_post_types ) ) {
                continue;
            }

            $fallback_mode = self::sanitize_post_type_fallback_mode(
                $rule['fallback_mode'] ?? '',
                $rule['use_global_chain'] ?? null
            );

            $result[] = [
                'post_types'       => $clean_post_types,
                'engine'           => $engine_slug,
                'fallback_mode'    => $fallback_mode,
                'use_global_chain' => 'global_chain' === $fallback_mode ? 1 : 0,
            ];
        }

        return $result;
    }

    protected static function convert_post_type_engine_map_to_rules( array $map, $fallback_mode = 'global_chain' ) {
        $rules = [];
        foreach ( $map as $post_type => $engine_slug ) {
            $rules[] = [
                'post_types'    => [ sanitize_key( (string) $post_type ) ],
                'engine'        => self::normalize_engine_slug( $engine_slug ),
                'fallback_mode' => self::sanitize_post_type_fallback_mode( $fallback_mode, 1 ),
            ];
        }

        return self::sanitize_post_type_rules( $rules );
    }

    protected static function parse_post_type_rule_rows( array $post_types_payloads, array $engine_slugs, array $fallback_modes = [], array $use_global_chain_flags = [] ) {
        $row_count = max( count( $post_types_payloads ), count( $engine_slugs ), count( $fallback_modes ), count( $use_global_chain_flags ) );
        $rules     = [];

        for ( $index = 0; $index < $row_count; $index++ ) {
            $rules[] = [
                'post_types'       => isset( $post_types_payloads[ $index ] ) ? self::parse_post_type_list_value( $post_types_payloads[ $index ] ) : [],
                'engine'           => isset( $engine_slugs[ $index ] ) ? self::normalize_engine_slug( $engine_slugs[ $index ] ) : '',
                'fallback_mode'    => isset( $fallback_modes[ $index ] ) ? $fallback_modes[ $index ] : '',
                'use_global_chain' => ! empty( $use_global_chain_flags[ $index ] ) ? 1 : 0,
            ];
        }

        return self::sanitize_post_type_rules( $rules );
    }


    protected static function parse_post_type_rules_json( $value ) {
        if ( is_array( $value ) ) {
            $decoded = $value;
        } else {
            $value = trim( (string) $value );
            if ( '' === $value ) {
                return [];
            }

            $decoded = json_decode( wp_unslash( $value ), true );
            if ( ! is_array( $decoded ) ) {
                $decoded = json_decode( (string) $value, true );
            }
        }

        if ( ! is_array( $decoded ) ) {
            return [];
        }

        $rules = [];
        foreach ( $decoded as $rule ) {
            if ( ! is_array( $rule ) ) {
                continue;
            }

            $rules[] = [
                'post_types'       => self::parse_post_type_list_value( $rule['post_types'] ?? [] ),
                'engine'           => isset( $rule['engine'] ) ? self::normalize_engine_slug( $rule['engine'] ) : '',
                'fallback_mode'    => $rule['fallback_mode'] ?? '',
                'use_global_chain' => ! empty( $rule['use_global_chain'] ) ? 1 : 0,
            ];
        }

        return self::sanitize_post_type_rules( $rules );
    }

    protected static function build_post_type_rule_map( array $rules ) {
        $map = [];

        foreach ( self::sanitize_post_type_rules( $rules ) as $index => $rule ) {
            foreach ( $rule['post_types'] as $post_type ) {
                $map[ $post_type ] = [
                    'engine'           => $rule['engine'],
                    'fallback_mode'    => self::sanitize_post_type_fallback_mode( $rule['fallback_mode'] ?? '', $rule['use_global_chain'] ?? null ),
                    'use_global_chain' => ! empty( $rule['use_global_chain'] ) ? 1 : 0,
                    'group_index'      => $index,
                ];
            }
        }

        ksort( $map );

        return $map;
    }

    protected static function build_post_type_engine_map_from_rules( array $rules ) {
        $map = [];
        foreach ( self::build_post_type_rule_map( $rules ) as $post_type => $rule ) {
            $map[ $post_type ] = $rule['engine'];
        }
        return $map;
    }

    protected static function normalize_post_type_settings( array $settings ) {
        if ( array_key_exists( 'post_type_rules', $settings ) && is_array( $settings['post_type_rules'] ) ) {
            $rules = self::sanitize_post_type_rules( $settings['post_type_rules'] );
        } elseif ( array_key_exists( 'post_type_rule_map', $settings ) && is_array( $settings['post_type_rule_map'] ) ) {
            $rules = [];
            foreach ( $settings['post_type_rule_map'] as $post_type => $rule ) {
                if ( ! is_array( $rule ) ) {
                    continue;
                }
                $rules[] = [
                    'post_types'       => [ $post_type ],
                    'engine'           => $rule['engine'] ?? '',
                    'fallback_mode'    => $rule['fallback_mode'] ?? '',
                    'use_global_chain' => ! empty( $rule['use_global_chain'] ) ? 1 : 0,
                ];
            }
            $rules = self::sanitize_post_type_rules( $rules );
        } else {
            $rules = self::convert_post_type_engine_map_to_rules( isset( $settings['post_type_engine_map'] ) && is_array( $settings['post_type_engine_map'] ) ? $settings['post_type_engine_map'] : [] );
        }

        $settings['post_type_rules']      = $rules;
        $settings['post_type_rule_map']   = self::build_post_type_rule_map( $rules );
        $settings['post_type_engine_map'] = self::build_post_type_engine_map_from_rules( $rules );

        return $settings;
    }

    protected static function get_post_array( $key ) {
        if ( ! isset( $_POST[ $key ] ) || ! is_array( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified by handle_save() before this helper is used.
            return [];
        }

        return wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Raw array is sanitized by the specific settings sanitizers downstream.
    }

    protected static function get_post_flag( $key ) {
        return isset( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Presence flag; callers verify nonce before acting.
    }

    protected static function get_post_engine_slug( $key, $default = 'volc' ) {
        if ( ! isset( $_POST[ $key ] ) || ! is_scalar( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified before this helper is used.
            return $default;
        }

        return self::normalize_engine_slug( sanitize_key( wp_unslash( $_POST[ $key ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Sanitized before use.
    }

    protected static function get_query_engine_slug( $key, $default = 'volc' ) {
        if ( ! isset( $_GET[ $key ] ) || ! is_scalar( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab selection.
            return $default;
        }

        return self::normalize_engine_slug( sanitize_key( wp_unslash( $_GET[ $key ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sanitized read-only parameter.
    }

    protected static function parse_map_textarea( $text ) {
        $text   = trim( (string) $text );
        $result = [];

        if ( '' === $text ) {
            return $result;
        }

        $lines   = preg_split( '/\r\n|\r|\n/', $text );
        $choices = self::get_engine_choices();
        foreach ( $lines as $line ) {
            $line = trim( $line );
            if ( '' === $line || false === strpos( $line, '=' ) ) {
                continue;
            }

            list( $lang, $engine ) = array_map( 'trim', explode( '=', $line, 2 ) );
            $lang   = sanitize_text_field( $lang );
            $engine = self::normalize_engine_slug( $engine );
            if ( '' !== $lang && isset( $choices[ $engine ] ) ) {
                $result[ $lang ] = $engine;
            }
        }

        return $result;
    }

    public static function get_model_tabs() {
        if ( class_exists( 'TPRE_Engine_Registry' ) ) {
            return TPRE_Engine_Registry::get_model_tabs();
        }

        return [];
    }

    public static function get_routable_post_types() {
        if ( ! function_exists( 'get_post_types' ) ) {
            return [];
        }

        $objects = get_post_types( [], 'objects' );
        if ( ! is_array( $objects ) ) {
            return [];
        }

        $result = [];

        foreach ( $objects as $slug => $object ) {
            $slug = sanitize_key( (string) $slug );

            if ( '' === $slug || 'attachment' === $slug || 0 === strpos( $slug, 'wp_' ) ) {
                continue;
            }

            $is_routable = ! empty( $object->public ) || ! empty( $object->publicly_queryable );
            if ( ! $is_routable ) {
                continue;
            }

            $label = '';
            if ( isset( $object->labels->singular_name ) && '' !== trim( (string) $object->labels->singular_name ) ) {
                $label = (string) $object->labels->singular_name;
            } elseif ( isset( $object->label ) && '' !== trim( (string) $object->label ) ) {
                $label = (string) $object->label;
            } else {
                $label = $slug;
            }

            $result[ $slug ] = [
                'label'       => $label,
                'description' => isset( $object->description ) ? (string) $object->description : '',
            ];
        }

        uasort(
            $result,
            static function( $left, $right ) {
                return strnatcasecmp( $left['label'], $right['label'] );
            }
        );

        return $result;
    }

    public static function get_engine_choices() {
        if ( class_exists( 'TPRE_Engine_Registry' ) ) {
            return TPRE_Engine_Registry::get_engine_choices();
        }

        return [];
    }

    public static function get_current_model_tab() {
        $default_tab = class_exists( 'TPRE_Engine_Registry' ) ? TPRE_Engine_Registry::get_default_tab() : 'volc';
        $tab         = self::get_query_engine_slug( 'tab', $default_tab );
        $tabs        = self::get_model_tabs();
        return isset( $tabs[ $tab ] ) ? $tab : $default_tab;
    }

    public static function get_model_tab_url( $tab ) {
        return add_query_arg(
            [
                'page' => self::PAGE_SLUG,
                'tab'  => sanitize_key( $tab ),
            ],
            admin_url( 'options-general.php' )
        );
    }

    public static function get_settings() {
        $model_defaults = self::get_default_model_configs();
        $defaults       = [
            'default_engine'       => 'volc',
            'post_type_rules'      => [],
            'post_type_rule_map'   => [],
            'post_type_engine_map' => [],
            'language_engine_map'  => [ 'yue' => 'deepl', 'zh_HK' => 'deepl' ],
            'fallback_map'         => [],
            'traditional_chinese_mode' => 'translatepress',
            'log_enabled'          => 0,
            'global_concurrency_limit' => 0,
            'models'               => $model_defaults,
        ];

        $saved = get_option( self::OPTION_KEY, [] );
        if ( ! is_array( $saved ) ) {
            $saved = [];
        }

        $saved = wp_parse_args( $saved, $defaults );

        if ( empty( $saved['models'] ) || ! is_array( $saved['models'] ) ) {
            $saved['models'] = $defaults['models'];
        } else {
            $saved['models'] = wp_parse_args( $saved['models'], $defaults['models'] );
            foreach ( $defaults['models'] as $engine_slug => $engine_defaults ) {
                $saved['models'][ $engine_slug ] = wp_parse_args( $saved['models'][ $engine_slug ], $engine_defaults );
            }
        }

        return self::normalize_post_type_settings( $saved );
    }

    public static function get_model_settings_url() {
        return admin_url( 'options-general.php?page=' . self::PAGE_SLUG );
    }

    public static function get_translation_settings_url() {
        return admin_url( 'admin.php?page=trp_machine_translation' );
    }
}
