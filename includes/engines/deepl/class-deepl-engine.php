<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPRE_DeepL_Engine implements TPRE_Engine_Interface {
    protected $tp_settings;
    protected $router_settings;
    protected $logger;
    protected $translator = null;
    protected $supported_languages_payload = null;

    public function __construct( array $tp_settings, array $router_settings, TPRE_Logger $logger ) {
        $this->tp_settings     = $tp_settings;
        $this->router_settings = $router_settings;
        $this->logger          = $logger;
    }

    public function get_slug() { return 'deepl'; }
    public function get_label() { return __( 'DeepL', 'langrouter-for-translatepress' ); }

    protected function get_translator_class() {
        if ( function_exists( 'tpre_load_deepl_key_pool_translator_class' ) ) {
            tpre_load_deepl_key_pool_translator_class();
        }

        if ( class_exists( 'TPRE_DeepL_Key_Pool_Machine_Translator' ) ) {
            return 'TPRE_DeepL_Key_Pool_Machine_Translator';
        }

        $default = '';
        if ( class_exists( 'TRP_IN_Deepl_Machine_Translator' ) ) {
            $default = 'TRP_IN_Deepl_Machine_Translator';
        } elseif ( class_exists( 'TRP_Deepl_Machine_Translator' ) ) {
            $default = 'TRP_Deepl_Machine_Translator';
        }

        if ( '' === $default ) {
            return '';
        }

        $classes = apply_filters( 'trp_automatic_translation_engines_classes', [ 'deepl' => $default ] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- TranslatePress integration hook.
        $class   = isset( $classes['deepl'] ) && is_string( $classes['deepl'] ) ? $classes['deepl'] : $default;

        return class_exists( $class ) ? $class : $default;
    }

    protected function get_router_deepl_settings() {
        return isset( $this->router_settings['models']['deepl'] ) && is_array( $this->router_settings['models']['deepl'] ) ? $this->router_settings['models']['deepl'] : [];
    }

    protected function get_pool_entries_for_support_check() {
        $settings  = $this->get_router_deepl_settings();
        $keys_text = isset( $settings['keys_text'] ) ? (string) $settings['keys_text'] : '';
        if ( '' === trim( $keys_text ) ) {
            return [];
        }

        $default_type = 'pro';
        if ( function_exists( 'tpre_deepl_get_default_api_type_for_admin' ) ) {
            $default_type = (string) tpre_deepl_get_default_api_type_for_admin();
        }
        if ( 'free' !== $default_type ) {
            $default_type = 'pro';
        }

        $lines   = preg_split( '/
|
|
/', $keys_text );
        $entries = [];
        $seen    = [];

        foreach ( (array) $lines as $line_index => $line ) {
            $line = trim( (string) $line );
            if ( '' === $line ) {
                continue;
            }

            $type = $default_type;
            $key  = $line;

            if ( preg_match( '/^(free|pro)\s*:\s*(.+)$/i', $line, $matches ) ) {
                $type = strtolower( (string) $matches[1] );
                $key  = trim( (string) $matches[2] );
            } elseif ( function_exists( 'tpre_deepl_infer_key_type' ) ) {
                $type = (string) tpre_deepl_infer_key_type( $key, $default_type );
            }

            if ( '' === $key ) {
                continue;
            }

            $hash = sha1( $type . '|' . $key );
            if ( isset( $seen[ $hash ] ) ) {
                continue;
            }

            $seen[ $hash ] = true;
            $entries[]     = [
                'line_no'    => (int) $line_index + 1,
                'hash'       => $hash,
                'key'        => $key,
                'type'       => 'free' === $type ? 'free' : 'pro',
                'masked_key' => function_exists( 'tpre_deepl_mask_key' ) ? tpre_deepl_mask_key( $key ) : $key,
            ];
        }

        return $entries;
    }

    protected function get_api_key() {
        $key = trim( (string) ( $this->tp_settings['trp_machine_translation_settings']['deepl-api-key'] ?? '' ) );
        if ( '' !== $key ) {
            return $key;
        }

        $entries = $this->get_pool_entries_for_support_check();
        if ( ! empty( $entries[0]['key'] ) ) {
            return trim( (string) $entries[0]['key'] );
        }

        return '';
    }

    protected function get_api_type() {
        $type = trim( (string) ( $this->tp_settings['trp_machine_translation_settings']['deepl-api-type'] ?? '' ) );
        if ( '' !== $type ) {
            return $type;
        }

        $entries = $this->get_pool_entries_for_support_check();
        if ( ! empty( $entries[0]['type'] ) ) {
            return trim( (string) $entries[0]['type'] );
        }

        if ( function_exists( 'tpre_deepl_infer_key_type' ) ) {
            return tpre_deepl_infer_key_type( $this->get_api_key(), 'pro' );
        }

        return '';
    }

    protected function get_supported_languages_cache_identity() {
        $entries = $this->get_pool_entries_for_support_check();
        if ( ! empty( $entries ) ) {
            $identity_parts = [];
            foreach ( $entries as $entry ) {
                $identity_parts[] = trim( (string) ( $entry['key'] ?? '' ) ) . '|' . trim( (string) ( $entry['type'] ?? '' ) );
            }
            return sha1( implode( "\n", $identity_parts ) );
        }

        return md5( $this->get_api_type() . '|' . $this->get_api_key() );
    }

    protected function create_translator() {
        if ( null !== $this->translator ) {
            return $this->translator;
        }

        $translator_class = $this->get_translator_class();
        if ( '' === $translator_class ) {
            return null;
        }

        $deepl_settings = $this->tp_settings;
        if ( empty( $deepl_settings['trp_machine_translation_settings'] ) ) {
            $deepl_settings['trp_machine_translation_settings'] = [];
        }

        if ( empty( $deepl_settings['trp_machine_translation_settings']['deepl-api-key'] ) ) {
            $primary_key = $this->get_api_key();
            if ( '' !== $primary_key ) {
                $deepl_settings['trp_machine_translation_settings']['deepl-api-key'] = $primary_key;
            }
        }

        if ( empty( $deepl_settings['trp_machine_translation_settings']['deepl-api-type'] ) ) {
            $primary_type = $this->get_api_type();
            if ( '' !== $primary_type ) {
                $deepl_settings['trp_machine_translation_settings']['deepl-api-type'] = $primary_type;
            }
        }

        $deepl_settings['trp_machine_translation_settings']['translation-engine'] = 'deepl';
        $this->translator = new $translator_class( $deepl_settings );

        return $this->translator;
    }

    public function is_available() {
        return '' !== $this->get_translator_class() && '' !== $this->get_api_key();
    }

    protected function get_supported_languages_cache_key() {
        return 'tpre_deepl_supported_langs_' . $this->get_supported_languages_cache_identity();
    }

    protected function get_supported_languages_cache_ttl() {
        return (int) apply_filters( 'tpre_deepl_supported_languages_cache_ttl', 3 * DAY_IN_SECONDS );
    }

    protected function get_unknown_support_cache_ttl() {
        return (int) apply_filters( 'tpre_deepl_unknown_support_cache_ttl', 10 * MINUTE_IN_SECONDS );
    }

    public static function clear_supported_languages_cache() {
        global $wpdb;

        if ( ! isset( $wpdb ) || ! $wpdb ) {
            return 0;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Targeted cleanup of plugin-owned transient options.
        $option_names = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
                $wpdb->esc_like( '_transient_tpre_deepl_supported_langs_' ) . '%'
            )
        );

        if ( empty( $option_names ) || ! is_array( $option_names ) ) {
            return 0;
        }

        $deleted = 0;
        foreach ( $option_names as $option_name ) {
            if ( ! is_string( $option_name ) || 0 !== strpos( $option_name, '_transient_tpre_deepl_supported_langs_' ) ) {
                continue;
            }

            $transient_key = substr( $option_name, strlen( '_transient_' ) );
            if ( '' === $transient_key ) {
                continue;
            }

            delete_transient( $transient_key );
            $deleted++;
        }

        return $deleted;
    }

    protected function normalize_language_list( $languages ) {
        $normalized = [];

        foreach ( (array) $languages as $language ) {
            if ( ! is_string( $language ) ) {
                continue;
            }

            $value = strtolower( trim( str_replace( '_', '-', $language ) ) );
            if ( '' === $value ) {
                continue;
            }

            $normalized[] = $value;

            if ( false !== strpos( $value, '-' ) ) {
                $base = strtok( $value, '-' );
                if ( is_string( $base ) && '' !== $base ) {
                    $normalized[] = $base;
                }
            }
        }

        $normalized = array_values( array_unique( $normalized ) );
        sort( $normalized );

        return $normalized;
    }

    protected function get_supported_languages_payload() {
        if ( null !== $this->supported_languages_payload ) {
            return $this->supported_languages_payload;
        }

        if ( ! $this->is_available() ) {
            $this->supported_languages_payload = [
                'state'     => 'unknown',
                'languages' => [],
                'source'    => 'unavailable',
            ];
            return $this->supported_languages_payload;
        }

        $cache_key = $this->get_supported_languages_cache_key();
        $cached    = get_transient( $cache_key );
        if ( is_array( $cached ) && isset( $cached['state'], $cached['languages'] ) ) {
            $cached['source'] = $cached['source'] ?? 'cache';
            $this->supported_languages_payload = $cached;
            $this->logger->debug( __( 'DeepL 支持语言命中缓存。', 'langrouter-for-translatepress' ), [
                'cache_key'      => $cache_key,
                'state'          => $cached['state'],
                'language_count' => is_array( $cached['languages'] ) ? count( $cached['languages'] ) : 0,
            ] );
            return $this->supported_languages_payload;
        }

        $translator = $this->create_translator();
        if ( ! $translator || ! method_exists( $translator, 'get_supported_languages' ) ) {
            $this->supported_languages_payload = [
                'state'     => 'unknown',
                'languages' => [],
                'source'    => 'translator_missing_method',
            ];
            set_transient( $cache_key, $this->supported_languages_payload, $this->get_unknown_support_cache_ttl() );
            return $this->supported_languages_payload;
        }

        $languages = $translator->get_supported_languages();
        if ( ! is_array( $languages ) || empty( $languages ) ) {
            $this->supported_languages_payload = [
                'state'     => 'unknown',
                'languages' => [],
                'source'    => 'api_empty',
            ];
            set_transient( $cache_key, $this->supported_languages_payload, $this->get_unknown_support_cache_ttl() );
            $this->logger->debug( __( 'DeepL 支持语言预检查未命中：未能获取支持语言列表，本次保持运行时兜底。', 'langrouter-for-translatepress' ), [
                'translator_class' => get_class( $translator ),
            ] );
            return $this->supported_languages_payload;
        }

        $this->supported_languages_payload = [
            'state'     => 'ok',
            'languages' => $this->normalize_language_list( $languages ),
            'source'    => 'api',
        ];

        set_transient( $cache_key, $this->supported_languages_payload, $this->get_supported_languages_cache_ttl() );
        $this->logger->debug( __( 'DeepL 支持语言列表已刷新。', 'langrouter-for-translatepress' ), [
            'translator_class' => get_class( $translator ),
            'language_count'   => count( $this->supported_languages_payload['languages'] ),
        ] );

        return $this->supported_languages_payload;
    }

    protected function build_target_language_candidates( $language_code ) {
        $raw = is_string( $language_code ) ? trim( $language_code ) : '';
        if ( '' === $raw ) {
            return [];
        }

        $candidates = [
            $raw,
            strtolower( $raw ),
            str_replace( '_', '-', strtolower( $raw ) ),
            preg_replace( '/[_-].*$/', '', strtolower( $raw ) ),
        ];

        $filtered = apply_filters( 'trp_deepl_target_language', $raw, null, $raw ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- TranslatePress compatibility filter.
        if ( is_string( $filtered ) && '' !== trim( $filtered ) ) {
            $candidates[] = $filtered;
            $candidates[] = strtolower( trim( $filtered ) );
            $candidates[] = str_replace( '_', '-', strtolower( trim( $filtered ) ) );
        }

        $normalized = [];
        foreach ( $candidates as $candidate ) {
            if ( ! is_string( $candidate ) ) {
                continue;
            }

            $candidate = strtolower( trim( $candidate ) );
            if ( '' === $candidate ) {
                continue;
            }

            $normalized[] = $candidate;
        }

        return array_values( array_unique( $normalized ) );
    }

    public function get_language_support_meta( $language_code ) {
        $payload    = $this->get_supported_languages_payload();
        $candidates = $this->build_target_language_candidates( $language_code );

        if ( ! $this->is_available() ) {
            return [
                'supported'  => false,
                'status'     => 'unknown',
                'source'     => 'unavailable',
                'candidates' => $candidates,
                'message'    => __( '未检测到可用的 DeepL Key 或账号池。', 'langrouter-for-translatepress' ),
            ];
        }

        if ( 'ok' !== $payload['state'] ) {
            return [
                'supported'  => false,
                'status'     => 'unknown',
                'source'     => $payload['source'] ?? 'api_cached',
                'candidates' => $candidates,
                'message'    => __( '首次查询会触发 DeepL /languages 接口并写入缓存；当前尚未拿到有效结果。', 'langrouter-for-translatepress' ),
            ];
        }

        foreach ( $candidates as $candidate ) {
            if ( in_array( strtolower( $candidate ), $payload['languages'], true ) ) {
                return [
                    'supported'  => true,
                    'status'     => 'supported',
                    'source'     => $payload['source'] ?? 'api_cached',
                    'candidates' => $candidates,
                    'message'    => '',
                ];
            }
        }

        return [
            'supported'  => false,
            'status'     => 'unsupported',
            'source'     => $payload['source'] ?? 'api_cached',
            'candidates' => $candidates,
            'message'    => '',
        ];
    }

    public function supports_language( $language_code ) {
        if ( ! $this->is_available() ) {
            return false;
        }

        $payload = $this->get_supported_languages_payload();
        if ( 'ok' !== $payload['state'] ) {
            return null;
        }

        $supported_languages = $payload['languages'];
        if ( empty( $supported_languages ) ) {
            return null;
        }

        $candidates = $this->build_target_language_candidates( $language_code );
        if ( empty( $candidates ) ) {
            return false;
        }

        foreach ( $candidates as $candidate ) {
            if ( in_array( $candidate, $supported_languages, true ) ) {
                return true;
            }
        }

        /* translators: %s: Target language code. */
        $this->logger->debug( tpre_log_translatef( 'DeepL不支持 %s 语言，直接跳过主调用。', $language_code ), [
            'target_language' => $language_code,
            'candidates'      => $candidates,
        ] );

        return false;
    }

    public function translate( array $strings, $target_language_code, $source_language_code = null ) {
        $translator = $this->create_translator();
        if ( ! $translator ) {
            $this->logger->error( __( 'DeepL不可用：未检测到可实例化的 DeepL 翻译器类。', 'langrouter-for-translatepress' ), [ 'target_language' => $target_language_code ] );
            return [];
        }

        $this->logger->debug( __( '调用 DeepL 翻译', 'langrouter-for-translatepress' ), [
            'target_language'  => $target_language_code,
            'source_language'  => $source_language_code,
            'translator_class' => get_class( $translator ),
            'count'            => count( $strings ),
        ] );

        $result = $translator->translate_array( $strings, $target_language_code, $source_language_code );
        $this->logger->debug( __( 'DeepL 翻译返回', 'langrouter-for-translatepress' ), [
            'target_language'  => $target_language_code,
            'translator_class' => get_class( $translator ),
            'translated_count' => is_array( $result ) ? count( $result ) : 0,
        ] );

        return is_array( $result ) ? $result : [];
    }

    public function test_request() {
        $translator = $this->create_translator();
        if ( ! $translator ) {
            return [
                'response' => [ 'code' => 500 ],
                'body'     => 'deepl translator class not available',
            ];
        }

        $this->logger->debug( __( 'DeepL test_request 开始', 'langrouter-for-translatepress' ), [
            'translator_class' => get_class( $translator ),
        ] );

        if ( method_exists( $translator, 'test_request' ) ) {
            $response = $translator->test_request();
            $this->logger->debug( __( 'DeepL test_request 返回', 'langrouter-for-translatepress' ), [
                'translator_class' => get_class( $translator ),
                'code'             => is_array( $response ) ? (int) ( $response['response']['code'] ?? 0 ) : 0,
                'body'             => is_array( $response ) ? wp_strip_all_tags( (string) ( $response['body'] ?? '' ) ) : '',
            ] );
            return $response;
        }

        return [
            'response' => [ 'code' => 200 ],
            'body'     => 'deepl translator ready: ' . get_class( $translator ),
        ];
    }
}
