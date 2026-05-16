<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPRE_Translation_Safety_Utils {
    public static function looks_like_code_or_template_fragment( $text ) {
        $text = trim( (string) $text );
        if ( '' === $text ) {
            return false;
        }

        $decoded = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

        $strong_patterns = [
            '/^(?:<\?php|\{\s*"[^"]+"\s*:|\[\s*\{|\{\s*\[)/u',
            '/=>/',
            '/\$[A-Za-z_][A-Za-z0-9_]*/',
            '/->[A-Za-z_][A-Za-z0-9_]*/',
            '/::[A-Za-z_][A-Za-z0-9_]*/',
            '/\b(?:add_action|add_filter|apply_filters|do_action|register_sidebar|get_template_part|update_post_meta|get_post_meta|wp_enqueue_[a-z_]+|esc_html__|esc_attr__|__|_e)\s*\(/i',
            '/\bfunction\s+[A-Za-z_][A-Za-z0-9_]*\s*\(/i',
            '/\b(?:if|else|elseif|foreach|while|switch|case|break|continue|return|const|let|var|public|private|protected|class)\b/i',
            '/^[\'"]\s*,?$/',
            '/^\s*[A-Za-z0-9_\-]+\s*=>\s*[\'"]?/u',
        ];
        foreach ( $strong_patterns as $pattern ) {
            if ( preg_match( $pattern, $decoded ) ) {
                return true;
            }
        }

        if ( preg_match( '/<\?(php|=)?|<\/?[a-z][^>]*>|&lt;\?(php|=)?|&lt;\/?[a-z][^&]*&gt;/i', $text ) ) {
            return true;
        }

        $plain = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $decoded ) ) );
        $line_count = preg_match_all( '/\R/u', $decoded, $matches );
        $punct_hits = preg_match_all( '/[{};=<>]/', $decoded, $matches );
        if ( $line_count >= 2 && $punct_hits >= 4 ) {
            return true;
        }

        if ( self::contains_encoded_tag_entities( $text ) && preg_match( '/[;{}()$]/', $decoded ) ) {
            return true;
        }

        return false;
    }

    public static function contains_raw_html_tags( $text ) {
        return (bool) preg_match( '/<\/?[A-Za-z][^>]*>/', (string) $text );
    }

    public static function contains_encoded_tag_entities( $text ) {
        return (bool) preg_match( '/&lt;\/?[A-Za-z][^&]{0,200}&gt;/i', (string) $text );
    }

    public static function contains_tag_like_fragment( $text ) {
        return (bool) preg_match( '/<\/?[A-Za-z][A-Za-z0-9:-]*/', (string) $text );
    }

    public static function is_provider_response_id_value( $value ) {
        return is_scalar( $value ) && 1 === preg_match( '/^resp_[A-Za-z0-9]{8,}$/', trim( (string) $value ) );
    }

    public static function contains_provider_response_id( $text ) {
        return is_scalar( $text ) && 1 === preg_match( '/\bresp_[A-Za-z0-9]{8,}\b/', (string) $text );
    }

    public static function has_unexpected_provider_response_id( $source_text, $translated_text ) {
        $translated_text = (string) $translated_text;
        if ( '' === trim( $translated_text ) || ! self::contains_provider_response_id( $translated_text ) ) {
            return false;
        }

        return ! self::contains_provider_response_id( $source_text );
    }

    public static function should_runtime_fallback_to_source( $source_text, $translated_text ) {
        return self::has_unexpected_provider_response_id( $source_text, $translated_text );
    }

    public static function extract_raw_tag_tokens( $text ) {
        $tokens = [];
        if ( preg_match_all( '/<\/?[A-Za-z][^>]*>/', (string) $text, $matches ) ) {
            foreach ( $matches[0] as $token ) {
                $tokens[] = strtolower( preg_replace( '/\s+/u', ' ', trim( $token ) ) );
            }
        }
        return $tokens;
    }

    public static function has_unbalanced_raw_tag_pairs( $text ) {
        if ( ! self::contains_raw_html_tags( $text ) ) {
            return false;
        }

        $stack = [];
        if ( preg_match_all( '/<\/?([A-Za-z][A-Za-z0-9:-]*)\b[^>]*>/', (string) $text, $matches, PREG_SET_ORDER ) ) {
            $void = [ 'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr' ];
            foreach ( $matches as $match ) {
                $full = $match[0];
                $name = strtolower( $match[1] );
                if ( in_array( $name, $void, true ) || substr( $full, -2 ) === '/>' ) {
                    continue;
                }
                if ( strpos( $full, '</' ) === 0 ) {
                    $last = end( $stack );
                    if ( false === $last || $last !== $name ) {
                        return true;
                    }
                    array_pop( $stack );
                    continue;
                }
                $stack[] = $name;
            }
        }

        return ! empty( $stack );
    }

    public static function has_dangerous_markup_mismatch( $source_text, $translated_text ) {
        $source_text     = (string) $source_text;
        $translated_text = (string) $translated_text;

        if ( '' === trim( $translated_text ) ) {
            return false;
        }

        $source_has_raw        = self::contains_raw_html_tags( $source_text );
        $translated_has_raw    = self::contains_raw_html_tags( $translated_text );
        $source_has_encoded    = self::contains_encoded_tag_entities( $source_text );
        $translated_tag_like   = self::contains_tag_like_fragment( $translated_text );
        $source_has_tag_like   = self::contains_tag_like_fragment( $source_text );

        if ( ! $source_has_raw && $source_has_encoded && $translated_has_raw ) {
            return true;
        }

        if ( ! $source_has_raw && ! $source_has_encoded && ! $source_has_tag_like && $translated_tag_like ) {
            return true;
        }

        if ( $source_has_raw ) {
            $source_tokens     = self::extract_raw_tag_tokens( $source_text );
            $translated_tokens = self::extract_raw_tag_tokens( $translated_text );
            if ( $source_tokens !== $translated_tokens ) {
                return true;
            }
            if ( self::has_unbalanced_raw_tag_pairs( $translated_text ) ) {
                return true;
            }
        }

        return false;
    }

    protected static function source_allows_structured_output( $source_text ) {
        $source_text = trim( wp_strip_all_tags( (string) $source_text ) );
        if ( '' === $source_text ) {
            return false;
        }

        if ( preg_match( '/(?:^|\n)\s*[-*•]\s+/u', $source_text ) ) {
            return true;
        }

        if ( preg_match( '/(?:^|\n)\s*\d+\.\s+/u', $source_text ) ) {
            return true;
        }

        if ( preg_match( '/^#{1,6}\s/u', $source_text ) ) {
            return true;
        }

        return false;
    }

    protected static function count_cjk_characters( $text ) {
        if ( ! preg_match_all( '/[\x{3040}-\x{30ff}\x{3400}-\x{4dbf}\x{4e00}-\x{9fff}\x{f900}-\x{faff}]/u', (string) $text, $matches ) ) {
            return 0;
        }

        return isset( $matches[0] ) ? count( $matches[0] ) : 0;
    }

    protected static function source_is_cjk_dense( $text ) {
        $plain = html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $len   = max( 1, self::get_plain_text_length( $plain ) );
        $cjk   = self::count_cjk_characters( $plain );

        if ( $cjk < 4 ) {
            return false;
        }

        return ( $cjk / $len ) >= 0.35;
    }

    protected static function translated_is_mostly_non_cjk( $text ) {
        $plain = html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $len   = max( 1, self::get_plain_text_length( $plain ) );
        $cjk   = self::count_cjk_characters( $plain );

        return ( $cjk / $len ) <= 0.10;
    }

    public static function looks_like_explanation_output( $text ) {
        $text = trim( wp_strip_all_tags( (string) $text ) );
        if ( '' === $text ) {
            return false;
        }

        $patterns = [
            '/\bI should\b/i',
            '/\bI will\b/i',
            '/\bI\'ll\b/i',
            '/\bLet me\b/i',
            '/\bHere(?:\sis)?\s+the translation\b/i',
            '/\bThe translation should\b/i',
            '/\bwithout any additional explanations?\b/i',
            '/\bpreserve any HTML tags?\b/i',
            '/\bmake sure to follow that exactly\b/i',
            '/\bwrap the translation\b/i',
            '/\bensure nothing else is added\b/i',
            '/^(translation|translated text|note|explanation)\s*[:：-]/i',
            '/^(?:關於|关于).{0,8}(?:這個詞|这个词|此词|詞語|词语)/u',
            '/^(?:意思是|意為|意为|主な使い方|使用情境|使用場景|以下にまとめました|簡單來說|簡単に言うと|具体来说|具體來說|例如[:：]?|例[:：]|用法示例)/u',
            '/^(?:在中文里也常被使用|通常指|多用於|多用于|文脈によって意味が異なります)/u',
            '/^#{1,6}\s/u',
            '/(?:^|\n)\s*[-*•]\s+/u',
            '/(?:^|\n)\s*\d+\.\s+/u',
            '/\*\*[^*]+\*\*/u',
        ];

        foreach ( $patterns as $pattern ) {
            if ( preg_match( $pattern, $text ) ) {
                return true;
            }
        }

        return false;
    }

    public static function is_cjk_target_language( $target_language_code ) {
        $target_language_code = strtolower( str_replace( '-', '_', (string) $target_language_code ) );
        foreach ( [ 'zh', 'ja', 'ko', 'yue' ] as $prefix ) {
            if ( 0 === strpos( $target_language_code, $prefix ) ) {
                return true;
            }
        }
        return false;
    }

    public static function get_plain_text_length( $text ) {
        $plain = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
        if ( function_exists( 'mb_strlen' ) ) {
            return (int) mb_strlen( $plain, 'UTF-8' );
        }
        return strlen( $plain );
    }


    public static function looks_like_safe_passthrough_text( $text ) {
        $plain = trim( html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
        if ( '' === $plain ) {
            return true;
        }

        if ( filter_var( $plain, FILTER_VALIDATE_URL ) || filter_var( $plain, FILTER_VALIDATE_EMAIL ) ) {
            return true;
        }

        if ( preg_match( '#^(?:https?:)?//#i', $plain ) ) {
            return true;
        }

        if ( self::looks_like_code_or_template_fragment( $plain ) ) {
            return true;
        }

        if ( preg_match( '/^[A-Za-z_][A-Za-z0-9_]*\(\)$/', $plain ) ) {
            return true;
        }

        if ( preg_match( '/^[A-Za-z0-9_.-]+\.[A-Za-z0-9]{1,8}$/', $plain ) ) {
            return true;
        }

        if ( preg_match( '#^[A-Za-z0-9_./:%\#?&=+@-]+$#', $plain ) && ( false !== strpos( $plain, '/' ) || false !== strpos( $plain, '\\' ) ) ) {
            return true;
        }

        if ( preg_match( '/^[A-Za-z][A-Za-z0-9+._-]{1,31}$/', $plain ) ) {
            if ( preg_match( '/[A-Z].*[A-Z]/', $plain ) || preg_match( '/[a-z][A-Z]|[A-Z][a-z]/', $plain ) || preg_match( '/\d/', $plain ) ) {
                return true;
            }
        }

        if ( preg_match( '/^[\d\s\p{P}]+$/u', $plain ) ) {
            return true;
        }

        if ( preg_match( '/\b(?:function|class|return|var|const|let|echo|add_action|add_filter|register_setting|wp_nonce_field|dynamic_sidebar)\b/i', $plain ) ) {
            return true;
        }

        return false;
    }

    public static function is_label_like_text( $text ) {
        $text = html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $text = trim( preg_replace( '/\s+/u', ' ', $text ) );
        if ( '' === $text ) {
            return false;
        }
        if ( preg_match( '/[\r\n]/', $text ) ) {
            return false;
        }

        $len = self::get_plain_text_length( $text );
        if ( $len <= 120 ) {
            return true;
        }

        return false;
    }

    public static function has_excessive_label_expansion( $source_text, $translated_text ) {
        if ( ! self::is_label_like_text( $source_text ) ) {
            return false;
        }

        $source_len              = max( 1, self::get_plain_text_length( $source_text ) );
        $translated_len          = self::get_plain_text_length( $translated_text );
        $translated_stripped     = trim( wp_strip_all_tags( (string) $translated_text ) );
        $length_limit            = max( 72, $source_len * 6 );
        $source_allows_structure = self::source_allows_structured_output( $source_text );

        if ( self::source_is_cjk_dense( $source_text ) && self::translated_is_mostly_non_cjk( $translated_text ) ) {
            $length_limit = max( $length_limit, max( 180, $source_len * 10 ) );
        }

        if ( $translated_len > $length_limit ) {
            return true;
        }

        if ( preg_match( '/[\r\n]/', $translated_stripped ) ) {
            if ( ! $source_allows_structure ) {
                return true;
            }

            $source_lines     = preg_split( '/\R/u', trim( wp_strip_all_tags( (string) $source_text ) ) );
            $translated_lines = preg_split( '/\R/u', $translated_stripped );
            if ( count( $translated_lines ) > max( 1, count( $source_lines ) ) ) {
                return true;
            }
        }

        if ( preg_match( '/(?:^|\n)\s*[-*•]\s+/u', $translated_stripped ) ) {
            if ( ! $source_allows_structure ) {
                return true;
            }
        }

        if ( preg_match( '/(?:^|\n)\s*\d+\.\s+/u', $translated_stripped ) ) {
            if ( ! $source_allows_structure ) {
                return true;
            }
        }

        if ( preg_match( '/\*\*[^*]+\*\*/u', $translated_stripped ) ) {
            if ( ! $source_allows_structure ) {
                return true;
            }
        }

        return false;
    }

    public static function has_unexpected_cjk_leak( $source_text, $translated_text, $target_language_code ) {
        if ( self::is_cjk_target_language( $target_language_code ) ) {
            return false;
        }

        $translated_plain = html_entity_decode( wp_strip_all_tags( (string) $translated_text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        if ( ! preg_match_all( '/[\x{3040}-\x{30ff}\x{3400}-\x{4dbf}\x{4e00}-\x{9fff}\x{f900}-\x{faff}]/u', $translated_plain, $matches ) ) {
            return false;
        }

        $cjk_count = isset( $matches[0] ) ? count( $matches[0] ) : 0;
        if ( $cjk_count < 2 ) {
            return false;
        }

        $translated_len = max( 1, self::get_plain_text_length( $translated_plain ) );
        if ( $cjk_count >= 4 || ( $cjk_count / $translated_len ) >= 0.10 ) {
            return true;
        }

        return self::is_label_like_text( $source_text );
    }

    protected static function get_plain_text( $text ) {
        return trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
    }

    protected static function is_ultra_short_label_source( $text ) {
        $plain = self::get_plain_text( $text );
        if ( '' === $plain ) {
            return false;
        }

        if ( preg_match( '/\s/u', $plain ) ) {
            return false;
        }

        if ( ! preg_match( '/\p{L}/u', $plain ) ) {
            return false;
        }

        return self::get_plain_text_length( $plain ) <= 2;
    }

    protected static function count_word_like_tokens( $text ) {
        $plain = self::get_plain_text( $text );
        if ( '' === $plain ) {
            return 0;
        }

        if ( ! preg_match_all( '/[\p{L}\p{N}]+(?:[-’\'][\p{L}\p{N}]+)*/u', $plain, $matches ) ) {
            return 0;
        }

        return isset( $matches[0] ) ? count( $matches[0] ) : 0;
    }

    protected static function source_has_cjk_letter( $text ) {
        $plain = self::get_plain_text( $text );
        if ( '' === $plain ) {
            return false;
        }

        return 1 === preg_match( '/[\x{3040}-\x{30ff}\x{3400}-\x{4dbf}\x{4e00}-\x{9fff}\x{f900}-\x{faff}]/u', $plain );
    }

    public static function is_single_cjk_character_source( $text ) {
        $plain = self::get_plain_text( $text );
        if ( '' === $plain ) {
            return false;
        }

        if ( ! self::source_has_cjk_letter( $plain ) ) {
            return false;
        }

        return 1 === self::get_plain_text_length( $plain );
    }

    public static function looks_like_short_function_word( $text ) {
        $plain = self::get_plain_text( $text );
        if ( '' === $plain ) {
            return false;
        }

        if ( 1 === preg_match( '/[^\p{L}\s\-’"]/u', $plain ) ) {
            return false;
        }

        if ( 1 === preg_match( '/\s/u', $plain ) ) {
            return false;
        }

        $len = self::get_plain_text_length( $plain );
        if ( $len > 3 ) {
            return false;
        }

        $plain_lower = function_exists( 'mb_strtolower' ) ? mb_strtolower( $plain, 'UTF-8' ) : strtolower( $plain );
        $function_like_values = [
            'a','an','as','at','by','da','de','di','do','du','el','en','et','e','i','il','in','la','le','lo','na','ne','no','of','on','or','ou','to','un','y'
        ];

        if ( in_array( $plain_lower, $function_like_values, true ) ) {
            return true;
        }

        if ( 1 === preg_match( '/^[a-z]{1,2}$/', $plain_lower ) ) {
            return true;
        }

        return false;
    }

    protected static function looks_like_language_or_locale_name( $text ) {
        $plain = self::get_plain_text( $text );
        if ( '' === $plain ) {
            return false;
        }

        if ( function_exists( 'mb_strtolower' ) ) {
            $plain = mb_strtolower( $plain, 'UTF-8' );
        } else {
            $plain = strtolower( $plain );
        }

        $plain = preg_replace( '/\s+/u', ' ', $plain );
        $plain = trim( $plain, " \t\n\r\0\x0B.,;:!?()[]{}\"'“”‘’" );

        $language_like_values = [
            'chinese', 'china', 'japanese', 'japan', 'korean', 'korea', 'english', 'french', 'france', 'german', 'germany', 'spanish', 'spain', 'italian', 'italy', 'portuguese', 'portugal', 'russian', 'russia', 'arabic', 'turkish', 'polish', 'dutch', 'swedish', 'norwegian', 'danish', 'finnish', 'czech', 'hungarian', 'romanian', 'ukrainian', 'greek', 'hebrew', 'hindi', 'thai', 'vietnamese', 'indonesian', 'malay',
            'chinois', 'chinoise', 'chine', 'japonais', 'japonaise', 'japon', 'coréen', 'coréenne', 'corée', 'anglais', 'anglaise', 'français', 'francaise', 'française', 'allemand', 'allemande', 'espagnol', 'espagnole', 'italien', 'italienne', 'portugais', 'portugaise', 'russe', 'arabe', 'turc', 'turque',
            'chino', 'japonés', 'japones', 'coreano', 'inglés', 'ingles', 'francés', 'frances', 'alemán', 'aleman', 'español', 'espanol', 'italiano', 'portugués', 'portugues', 'ruso', 'árabe', 'arabe', 'turco',
            'chinesisch', 'japanisch', 'koreanisch', 'englisch', 'französisch', 'franzoesisch', 'deutsch', 'spanisch', 'italienisch', 'portugiesisch', 'russisch', 'arabisch', 'türkisch', 'tuerkisch'
        ];

        return in_array( $plain, $language_like_values, true );
    }

    public static function has_unreasonable_ultra_short_translation( $source_text, $translated_text, $target_language_code ) {
        if ( ! self::is_ultra_short_label_source( $source_text ) ) {
            return false;
        }

        $translated_plain = self::get_plain_text( $translated_text );
        if ( '' === $translated_plain ) {
            return false;
        }

        if ( preg_match( '/\R/u', (string) $translated_text ) ) {
            return true;
        }

        $source_len       = max( 1, self::get_plain_text_length( $source_text ) );
        $translated_len   = self::get_plain_text_length( $translated_plain );
        $translated_words = self::count_word_like_tokens( $translated_plain );
        $length_limit     = 1 === $source_len ? 18 : 28;
        $word_limit       = 1 === $source_len ? 3 : 4;

        if ( $translated_len > $length_limit ) {
            return true;
        }

        if ( $translated_words > $word_limit ) {
            return true;
        }

        if ( preg_match( '/[()\[\]{};:]/u', $translated_plain ) ) {
            return true;
        }

        if ( self::source_has_cjk_letter( $source_text ) && ! self::is_cjk_target_language( $target_language_code ) && self::looks_like_language_or_locale_name( $translated_plain ) ) {
            return true;
        }


        return false;
    }

    public static function is_suspicious_translation_output( $source_text, $translated_text, $target_language_code = '' ) {
        $translated_text = trim( (string) $translated_text );
        if ( '' === $translated_text ) {
            return true;
        }

        $normalized_source     = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( (string) $source_text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
        $normalized_translated = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( (string) $translated_text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );

        if ( self::has_unexpected_provider_response_id( $source_text, $translated_text ) ) {
            return true;
        }

        if ( '' !== $normalized_source && 0 === strcasecmp( $normalized_source, $normalized_translated ) && self::looks_like_safe_passthrough_text( $source_text ) ) {
            return false;
        }

        if ( ! self::source_allows_structured_output( $source_text ) && self::looks_like_explanation_output( $translated_text ) ) {
            return true;
        }

        if ( self::has_dangerous_markup_mismatch( $source_text, $translated_text ) ) {
            return true;
        }

        if ( self::has_excessive_label_expansion( $source_text, $translated_text ) ) {
            return true;
        }

        if ( self::has_unexpected_cjk_leak( $source_text, $translated_text, $target_language_code ) ) {
            return true;
        }

        if ( self::has_unreasonable_ultra_short_translation( $source_text, $translated_text, $target_language_code ) ) {
            return true;
        }

        return false;
    }
}
