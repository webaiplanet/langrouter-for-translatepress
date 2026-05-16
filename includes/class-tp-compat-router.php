<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPRE_TP_Compat_Router {
    const PROTECTED_ATTR = 'data-tpre-protected-encoded-html';

    /** @var bool|null */
    protected static $router_active = null;

    /** @var string|null */
    protected static $current_language_cache = null;

    /** @var array<string,array<int,string>> */
    protected static $protected_selectors_cache = [];

    public static function boot() {
        add_filter( 'trp_pre_translating_html', [ __CLASS__, 'protect_encoded_html_inside_excluded_nodes' ], 20 );
        add_filter( 'trp_translated_html', [ __CLASS__, 'restore_encoded_html_inside_excluded_nodes' ], 20, 4 );

        $enabled = (bool) apply_filters( 'tpre_enable_router_tp_compat_guards', false );
        if ( ! $enabled ) {
            return;
        }

        add_filter( 'trp_translate_encoded_html_as_html', [ __CLASS__, 'maybe_disable_recursive_encoded_html_translation' ], 20 );
        add_filter( 'trp_allow_machine_translation_for_string', [ __CLASS__, 'skip_obvious_code_strings' ], 20, 5 );
    }

    protected static function is_router_engine_active() {
        if ( null !== self::$router_active ) {
            return self::$router_active;
        }

        $settings = get_option( 'trp_machine_translation_settings', [] );
        if ( ! is_array( $settings ) ) {
            self::$router_active = false;
            return self::$router_active;
        }

        self::$router_active = isset( $settings['translation-engine'] ) && $settings['translation-engine'] === 'tpre_router_engine';

        return self::$router_active;
    }

    protected static function current_language() {
        if ( null !== self::$current_language_cache ) {
            return self::$current_language_cache;
        }

        global $TRP_LANGUAGE;
        self::$current_language_cache = is_string( $TRP_LANGUAGE ) ? $TRP_LANGUAGE : '';

        return self::$current_language_cache;
    }

    protected static function get_protected_selectors( $language = '' ) {
        $cache_key = is_string( $language ) ? $language : '';
        if ( array_key_exists( $cache_key, self::$protected_selectors_cache ) ) {
            return self::$protected_selectors_cache[ $cache_key ];
        }

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- TranslatePress core filter.
        $no_translate = apply_filters( 'trp_no_translate_selectors', [ '#wpadminbar' ], $language );
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- TranslatePress core filter.
        $no_auto      = apply_filters( 'trp_no_auto_translate_selectors', [], $language );

        $selectors = array_merge( (array) $no_translate, (array) $no_auto, [ '[data-no-translation]', '[data-no-auto-translation]' ] );
        $selectors = array_filter( array_map( 'trim', array_unique( $selectors ) ) );

        self::$protected_selectors_cache[ $cache_key ] = array_values( $selectors );

        return self::$protected_selectors_cache[ $cache_key ];
    }

    public static function protect_encoded_html_inside_excluded_nodes( $output ) {
        if ( ! self::is_router_engine_active() || ! is_string( $output ) || $output === '' || ! function_exists( 'str_get_html' ) ) {
            return $output;
        }

        if ( strpos( $output, '&lt;' ) === false || strpos( $output, '&gt;' ) === false ) {
            return $output;
        }

        if ( class_exists( 'TPRE_Translation_Safety_Utils' ) && ! TPRE_Translation_Safety_Utils::contains_encoded_tag_entities( $output ) ) {
            return $output;
        }

        $selectors = self::get_protected_selectors( self::current_language() );
        if ( empty( $selectors ) ) {
            return $output;
        }

        $html = str_get_html( $output, true, true, 'UTF-8', false, '\r\n', 'trptext', '<>"', true );
        if ( ! $html ) {
            return $output;
        }

        $changed = false;
        foreach ( $selectors as $selector ) {
            foreach ( $html->find( $selector ) as $node ) {
                if ( ! isset( $node->innertext ) || ! is_string( $node->innertext ) || $node->innertext === '' ) {
                    continue;
                }

                if ( ! TPRE_Translation_Safety_Utils::contains_encoded_tag_entities( $node->innertext ) ) {
                    continue;
                }

                $protected_innertext = str_replace(
                    [ '&lt;', '&gt;' ],
                    [ '&amp;lt;', '&amp;gt;' ],
                    $node->innertext
                );

                if ( $protected_innertext === $node->innertext ) {
                    continue;
                }

                $node->innertext = $protected_innertext;
                $node->setAttribute( self::PROTECTED_ATTR, '1' );
                $changed = true;
            }
        }

        $result = $changed ? $html->save() : $output;
        $html->clear();
        unset( $html );

        return $result;
    }

    public static function restore_encoded_html_inside_excluded_nodes( $final_html, $TRP_LANGUAGE = '', $language_code = '', $preview_mode = false ) {
        if ( ! self::is_router_engine_active() || ! is_string( $final_html ) || $final_html === '' || strpos( $final_html, self::PROTECTED_ATTR ) === false || ! function_exists( 'str_get_html' ) ) {
            return $final_html;
        }

        $html = str_get_html( $final_html, true, true, 'UTF-8', false, '\r\n', 'trptext', '<>"', true );
        if ( ! $html ) {
            return $final_html;
        }

        $changed = false;
        foreach ( $html->find( '[' . self::PROTECTED_ATTR . ']' ) as $node ) {
            if ( isset( $node->innertext ) && is_string( $node->innertext ) && $node->innertext !== '' ) {
                $node->innertext = str_replace(
                    [ '&amp;lt;', '&amp;gt;' ],
                    [ '&lt;', '&gt;' ],
                    $node->innertext
                );
            }

            $node->removeAttribute( self::PROTECTED_ATTR );
            $changed = true;
        }

        $result = $changed ? $html->save() : $final_html;
        $html->clear();
        unset( $html );

        return $result;
    }

    public static function maybe_disable_recursive_encoded_html_translation( $allow ) {
        if ( ! self::is_router_engine_active() ) {
            return $allow;
        }

        $compat_default = false;
        return (bool) apply_filters( 'tpre_allow_tp_recursive_encoded_html_translation', $compat_default, $allow );
    }

    public static function skip_obvious_code_strings( $allow, $entity_decoded_trimmed_string, $current_node_accessor_selector, $node_accessor, $row ) {
        if ( ! $allow || ! self::is_router_engine_active() ) {
            return $allow;
        }

        $s = is_string( $entity_decoded_trimmed_string ) ? trim( $entity_decoded_trimmed_string ) : '';
        if ( $s === '' ) {
            return $allow;
        }

        if ( is_string( $current_node_accessor_selector ) && $current_node_accessor_selector !== '' ) {
            if ( self::looks_like_code_snippet( $s ) ) {
                return false;
            }
            return $allow;
        }

        if ( self::looks_like_code_snippet( $s ) ) {
            return false;
        }

        return $allow;
    }

    protected static function looks_like_code_snippet( $text ) {
        if ( preg_match( '/<\?(php|=)?|<\/?[a-z][^>]*>|&lt;\?(php|=)?|&lt;\/?[a-z][^&]*&gt;/i', $text ) ) {
            return true;
        }

        if ( preg_match( '/\b(function|class|return|if|else|elseif|endif|foreach|while|switch|case|break|continue|const|let|var)\b/i', $text )
            && preg_match( '/[{};=<>]/', $text ) ) {
            return true;
        }

        if ( preg_match( '/\b(get_header|get_footer|the_permalink|the_title|the_ID|wp_head|wp_footer|add_action|add_filter|is_single|get_the_content)\s*\(/i', $text ) ) {
            return true;
        }

        if ( substr_count( $text, ';' ) >= 2 && ( substr_count( $text, '(' ) >= 1 || substr_count( $text, '{' ) >= 1 ) ) {
            return true;
        }

        if ( preg_match( '/\$[A-Za-z_][A-Za-z0-9_]*/', $text ) && preg_match( '/[{};=]/', $text ) ) {
            return true;
        }

        return false;
    }
}
