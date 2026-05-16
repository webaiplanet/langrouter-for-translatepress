<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPRE_OpenCC_Utils {
    protected static function get_request_uri() {
        if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
            return '';
        }

        return sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) );
    }
    public static function get_handling_choices() {
        return [
            'translatepress' => __( 'TranslatePress 翻译', 'langrouter-for-translatepress' ),
            'opencc'         => __( 'OpenCC 转换', 'langrouter-for-translatepress' ),
        ];
    }

    public static function get_handling_mode( array $router_settings = null ) {
        if ( null === $router_settings ) {
            $router_settings = class_exists( 'TPRE_Admin_Settings' ) ? TPRE_Admin_Settings::get_settings() : [];
        }

        $mode = isset( $router_settings['traditional_chinese_mode'] ) ? sanitize_key( (string) $router_settings['traditional_chinese_mode'] ) : 'translatepress';

        return in_array( $mode, [ 'translatepress', 'opencc' ], true ) ? $mode : 'translatepress';
    }

    public static function is_router_engine_active() {
        $settings = get_option( 'trp_machine_translation_settings', [] );
        if ( ! is_array( $settings ) ) {
            return false;
        }

        return isset( $settings['translation-engine'] ) && 'tpre_router_engine' === $settings['translation-engine'];
    }

    public static function normalize_traditional_locale( $language_code ) {
        $normalized = self::normalize_alias_key( $language_code );
        if ( '' === $normalized ) {
            return '';
        }

        $alias_map = self::get_traditional_alias_map();
        if ( isset( $alias_map[ $normalized ] ) ) {
            return $alias_map[ $normalized ];
        }

        return self::normalize_traditional_locale_fallback_from_normalized( $normalized );
    }

    public static function is_traditional_locale( $language_code ) {
        return '' !== self::normalize_traditional_locale( $language_code );
    }

    public static function get_traditional_language_aliases() {
        $aliases = [];

        foreach ( self::get_traditional_language_variants() as $variant ) {
            foreach ( [
                isset( $variant['code'] ) ? (string) $variant['code'] : '',
                isset( $variant['slug'] ) ? (string) $variant['slug'] : '',
            ] as $value ) {
                $value = trim( $value );
                if ( '' === $value ) {
                    continue;
                }

                $aliases[] = $value;
            }
        }

        $aliases = array_merge( $aliases, self::get_default_traditional_aliases_for_display() );
        $aliases = array_values( array_unique( array_filter( array_map( 'trim', $aliases ) ) ) );
        natcasesort( $aliases );

        return array_values( $aliases );
    }

    public static function should_handle_with_opencc( $language_code, array $router_settings = null ) {
        if ( 'opencc' !== self::get_handling_mode( $router_settings ) ) {
            return false;
        }

        return self::is_traditional_locale( $language_code );
    }

    public static function should_skip_machine_translation( $target_language_code, array $router_settings = null ) {
        return self::should_handle_with_opencc( $target_language_code, $router_settings );
    }

    public static function get_current_request_language_code() {
        global $TRP_LANGUAGE;

        if ( isset( $TRP_LANGUAGE ) && is_string( $TRP_LANGUAGE ) && '' !== trim( $TRP_LANGUAGE ) ) {
            return trim( $TRP_LANGUAGE );
        }

        if ( function_exists( 'apply_filters' ) ) {
            $filtered_lang = apply_filters( 'trp_current_language', '' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- TranslatePress compatibility hook.
            if ( is_string( $filtered_lang ) && '' !== trim( $filtered_lang ) ) {
                return trim( $filtered_lang );
            }
        }

        if ( class_exists( 'TRP_Translate_Press' ) && method_exists( 'TRP_Translate_Press', 'get_trp_instance' ) ) {
            $trp = TRP_Translate_Press::get_trp_instance();
            if ( $trp && method_exists( $trp, 'get_component' ) ) {
                foreach ( [ 'url_converter', 'languages' ] as $component_name ) {
                    $component = $trp->get_component( $component_name );
                    if ( ! $component ) {
                        continue;
                    }

                    foreach ( [ 'get_lang_from_url_string', 'get_current_language', 'get_current_language_code' ] as $method ) {
                        if ( ! method_exists( $component, $method ) ) {
                            continue;
                        }

                        try {
                            $detected = 'get_lang_from_url_string' === $method
                                ? $component->$method( self::get_request_uri() )
                                : $component->$method();
                        } catch ( Exception $e ) {
                            $detected = '';
                        }

                        if ( is_string( $detected ) && '' !== trim( $detected ) ) {
                            return trim( $detected );
                        }
                    }
                }
            }
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Frontend language detection reads a public language query var only.
        if ( isset( $_GET['lang'] ) ) {
            $query_lang = sanitize_text_field( wp_unslash( $_GET['lang'] ) );
            $canonical  = self::normalize_traditional_locale( $query_lang );
            if ( '' !== $canonical ) {
                return $canonical;
            }
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        $uri  = self::get_request_uri();
        $path = wp_parse_url( $uri, PHP_URL_PATH );
        if ( ! is_string( $path ) || '' === $path ) {
            return '';
        }

        $segments = preg_split( '~/+~', trim( $path, '/' ) );
        foreach ( (array) $segments as $segment ) {
            $segment   = rawurldecode( trim( (string) $segment ) );
            $canonical = self::normalize_traditional_locale( $segment );
            if ( '' !== $canonical ) {
                return $canonical;
            }
        }

        return '';
    }

    public static function get_opencc_config_for_language( $language_code ) {
        $canonical = self::normalize_traditional_locale( $language_code );

        switch ( $canonical ) {
            case 'zh_HK':
                return 's2hk.json';
            case 'zh_TW':
                return 's2twp.json';
            case 'zh_Hant':
            default:
                return 's2t.json';
        }
    }

    protected static function normalize_alias_key( $value ) {
        $value = is_scalar( $value ) ? trim( (string) $value ) : '';
        if ( '' === $value ) {
            return '';
        }

        $value = rawurldecode( $value );
        $value = preg_replace( '/\s+/u', '', $value );
        $value = str_replace( '-', '_', $value );

        return strtolower( $value );
    }

    protected static function normalize_traditional_locale_fallback_from_normalized( $normalized ) {
        if ( '' === $normalized ) {
            return '';
        }

        $direct_map = self::get_default_traditional_alias_map();
        if ( isset( $direct_map[ $normalized ] ) ) {
            return $direct_map[ $normalized ];
        }

        if ( 0 === strpos( $normalized, 'zh_hant' ) ) {
            if ( false !== strpos( $normalized, '_hk' ) || false !== strpos( $normalized, '_mo' ) ) {
                return 'zh_HK';
            }
            if ( false !== strpos( $normalized, '_tw' ) ) {
                return 'zh_TW';
            }
            return 'zh_Hant';
        }

        if ( 0 === strpos( $normalized, 'zh_tw' ) ) {
            return 'zh_TW';
        }

        if ( 0 === strpos( $normalized, 'zh_hk' ) || 0 === strpos( $normalized, 'zh_mo' ) ) {
            return 'zh_HK';
        }

        return self::infer_canonical_from_hints( $normalized );
    }

    protected static function get_default_traditional_alias_map() {
        return [
            'zh_hant'             => 'zh_Hant',
            'zh_tw'               => 'zh_TW',
            'zh_hk'               => 'zh_HK',
            'zh_mo'               => 'zh_HK',
            'zh_hant_tw'          => 'zh_TW',
            'zh_hant_hk'          => 'zh_HK',
            'zh_hant_mo'          => 'zh_HK',
            'traditionalchinese'  => 'zh_Hant',
            'traditional_chinese' => 'zh_Hant',
            'chinese_traditional' => 'zh_Hant',
            'zh_traditional'      => 'zh_Hant',
            '繁体中文'              => 'zh_Hant',
            '繁體中文'              => 'zh_Hant',
            '繁中'                 => 'zh_Hant',
        ];
    }

    protected static function get_default_traditional_aliases_for_display() {
        return [
            'zh_Hant',
            'zh-Hant',
            'zh_Hant_TW',
            'zh-Hant-TW',
            'zh_Hant_HK',
            'zh-Hant-HK',
            'zh_Hant_MO',
            'zh-Hant-MO',
            'zh_TW',
            'zh-TW',
            'zh_HK',
            'zh-HK',
            'zh_MO',
            'zh-MO',
        ];
    }

    protected static function get_traditional_alias_map() {
        $alias_map = [];

        foreach ( self::get_traditional_language_variants() as $variant ) {
            $canonical = isset( $variant['canonical'] ) ? (string) $variant['canonical'] : '';
            if ( '' === $canonical ) {
                continue;
            }

            foreach ( [
                isset( $variant['code'] ) ? (string) $variant['code'] : '',
                isset( $variant['slug'] ) ? (string) $variant['slug'] : '',
                isset( $variant['name'] ) ? (string) $variant['name'] : '',
            ] as $alias ) {
                $normalized_alias = self::normalize_alias_key( $alias );
                if ( '' === $normalized_alias ) {
                    continue;
                }

                $alias_map[ $normalized_alias ] = $canonical;
            }
        }

        foreach ( self::get_default_traditional_alias_map() as $alias => $canonical ) {
            if ( ! isset( $alias_map[ $alias ] ) ) {
                $alias_map[ $alias ] = $canonical;
            }
        }

        return $alias_map;
    }

    protected static function get_traditional_language_variants() {
        $tp_settings     = self::get_translatepress_settings();
        $language_codes  = self::get_translatepress_language_codes( $tp_settings );
        $language_names  = self::get_translatepress_language_names( $language_codes );
        $language_slugs  = self::get_translatepress_language_slugs( $language_codes, $tp_settings );
        $variants        = [];

        foreach ( $language_codes as $language_code ) {
            $language_code = trim( (string) $language_code );
            if ( '' === $language_code ) {
                continue;
            }

            $canonical = self::normalize_traditional_locale_fallback_from_normalized( self::normalize_alias_key( $language_code ) );
            $name      = isset( $language_names[ $language_code ] ) ? (string) $language_names[ $language_code ] : '';
            $slug      = isset( $language_slugs[ $language_code ] ) ? (string) $language_slugs[ $language_code ] : '';

            if ( '' === $canonical ) {
                $canonical = self::infer_canonical_from_hints( $language_code, $name, $slug );
            }

            if ( '' === $canonical ) {
                continue;
            }

            $variants[] = [
                'code'      => $language_code,
                'slug'      => $slug,
                'name'      => $name,
                'canonical' => $canonical,
            ];
        }

        return $variants;
    }

    protected static function get_translatepress_settings() {
        $settings = [];

        if ( class_exists( 'TRP_Translate_Press' ) && method_exists( 'TRP_Translate_Press', 'get_trp_instance' ) ) {
            $trp = TRP_Translate_Press::get_trp_instance();
            if ( $trp && method_exists( $trp, 'get_component' ) ) {
                $settings_component = $trp->get_component( 'settings' );
                if ( $settings_component && method_exists( $settings_component, 'get_settings' ) ) {
                    $settings = $settings_component->get_settings();
                }
            }
        }

        if ( ! is_array( $settings ) || empty( $settings ) ) {
            $settings = get_option( 'trp_settings', [] );
        }

        return is_array( $settings ) ? $settings : [];
    }

    protected static function get_translatepress_language_codes( array $tp_settings ) {
        $codes = [];

        foreach ( [ 'translation-languages', 'publish-languages' ] as $key ) {
            if ( empty( $tp_settings[ $key ] ) ) {
                continue;
            }

            $codes = array_merge( $codes, self::flatten_string_values( $tp_settings[ $key ] ) );
        }

        $codes = array_values( array_unique( array_filter( array_map( 'trim', $codes ) ) ) );

        return $codes;
    }

    protected static function get_translatepress_language_names( array $language_codes ) {
        $names = [];
        if ( empty( $language_codes ) ) {
            return $names;
        }

        if ( class_exists( 'TRP_Translate_Press' ) && method_exists( 'TRP_Translate_Press', 'get_trp_instance' ) ) {
            $trp = TRP_Translate_Press::get_trp_instance();
            if ( $trp && method_exists( $trp, 'get_component' ) ) {
                $languages_component = $trp->get_component( 'languages' );
                if ( $languages_component && method_exists( $languages_component, 'get_language_names' ) ) {
                    $raw_names = $languages_component->get_language_names( $language_codes );
                    if ( is_array( $raw_names ) ) {
                        foreach ( $raw_names as $code => $label ) {
                            if ( is_scalar( $label ) ) {
                                $names[ (string) $code ] = trim( (string) $label );
                            }
                        }
                    }
                }
            }
        }

        return $names;
    }

    protected static function get_translatepress_language_slugs( array $language_codes, array $tp_settings ) {
        $slugs = [];
        if ( empty( $language_codes ) ) {
            return $slugs;
        }

        $url_converter = null;
        if ( class_exists( 'TRP_Translate_Press' ) && method_exists( 'TRP_Translate_Press', 'get_trp_instance' ) ) {
            $trp = TRP_Translate_Press::get_trp_instance();
            if ( $trp && method_exists( $trp, 'get_component' ) ) {
                $url_converter = $trp->get_component( 'url_converter' );
            }
        }

        foreach ( $language_codes as $language_code ) {
            $language_code = trim( (string) $language_code );
            if ( '' === $language_code ) {
                continue;
            }

            $slug = '';
            if ( $url_converter && method_exists( $url_converter, 'get_url_slug' ) ) {
                $maybe_slug = $url_converter->get_url_slug( $language_code, false );
                if ( is_scalar( $maybe_slug ) ) {
                    $slug = trim( (string) $maybe_slug );
                }
            }

            if ( '' === $slug ) {
                foreach ( [ 'url-slugs', 'url_slugs', 'language-slugs', 'language_slugs' ] as $slug_key ) {
                    if ( empty( $tp_settings[ $slug_key ] ) || ! is_array( $tp_settings[ $slug_key ] ) ) {
                        continue;
                    }

                    foreach ( [
                        $language_code,
                        str_replace( '-', '_', $language_code ),
                        str_replace( '_', '-', $language_code ),
                        strtolower( str_replace( '-', '_', $language_code ) ),
                    ] as $lookup_key ) {
                        if ( isset( $tp_settings[ $slug_key ][ $lookup_key ] ) && is_scalar( $tp_settings[ $slug_key ][ $lookup_key ] ) ) {
                            $slug = trim( (string) $tp_settings[ $slug_key ][ $lookup_key ] );
                            break 2;
                        }
                    }
                }
            }

            if ( '' !== $slug ) {
                $slugs[ $language_code ] = $slug;
            }
        }

        return $slugs;
    }

    protected static function flatten_string_values( $value ) {
        $values = [];

        if ( is_scalar( $value ) ) {
            $values[] = (string) $value;
            return $values;
        }

        if ( ! is_array( $value ) ) {
            return $values;
        }

        foreach ( $value as $item ) {
            $values = array_merge( $values, self::flatten_string_values( $item ) );
        }

        return $values;
    }

    protected static function infer_canonical_from_hints( $code_or_hint, $name = '', $slug = '' ) {
        $normalized_hints = implode( '_', array_filter( [
            self::normalize_alias_key( $code_or_hint ),
            self::normalize_alias_key( $name ),
            self::normalize_alias_key( $slug ),
        ] ) );

        if ( '' === $normalized_hints ) {
            return '';
        }

        if ( self::contains_any_hint( $normalized_hints, [ 'zh_hk', 'zh_mo', 'hongkong', 'hong_kong', 'hk', 'macau', 'macao', 'mo' ] ) ) {
            return 'zh_HK';
        }

        if ( self::contains_any_hint( $normalized_hints, [ 'zh_tw', 'taiwan', 'taipei', 'tw' ] ) ) {
            return 'zh_TW';
        }

        if ( self::contains_any_hint( $normalized_hints, [ 'zh_hant', 'traditional', '繁体', '繁體', '繁中', 'traditionalchinese', 'traditional_chinese' ] ) ) {
            return 'zh_Hant';
        }

        return '';
    }

    protected static function contains_any_hint( $haystack, array $needles ) {
        foreach ( $needles as $needle ) {
            $needle = self::normalize_alias_key( $needle );
            if ( '' === $needle ) {
                continue;
            }

            if ( false !== strpos( $haystack, $needle ) ) {
                return true;
            }
        }

        return false;
    }
}
