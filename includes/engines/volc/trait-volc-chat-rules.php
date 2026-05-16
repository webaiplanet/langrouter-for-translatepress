<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

trait TPRE_Volc_Chat_Rules_Trait {
    protected function is_volc_chat_fragile_language_pair( $source_language, $target_language ) {
        $source = strtolower( trim( str_replace( '_', '-', (string) $source_language ) ) );
        $target = strtolower( trim( str_replace( '_', '-', (string) $target_language ) ) );

        $fragile_targets = array( 'ps', 'fur', 'ug', 'ug-cn', 'sd', 'ks', 'dv' );
        foreach ( $fragile_targets as $fragile_target ) {
            if ( $target === $fragile_target || 0 === strpos( $target, $fragile_target . '-' ) ) {
                return true;
            }
        }

        if ( 'zh' === $source && ! in_array( $target, array( 'en', 'de', 'es', 'fr', 'it', 'ja', 'ko', 'nl', 'pl', 'pt', 'ru', 'tr' ), true ) ) {
            return true;
        }

        return false;
    }

    protected function get_volc_chat_strict_target_prompt_suffix( $target_language ) {
        if ( ! $this->is_volc_chat_fragile_language_pair( '', $target_language ) ) {
            return '';
        }

        return ' Use only natural ' . $this->format_chat_prompt_language_label( $target_language ) . ' in the final answer. Never answer in English, Chinese, or the source language unless the input is a URL, email, code fragment, file path, slug, placeholder, or product name that must stay unchanged.';
    }

    protected function volc_chat_target_requires_non_latin_output( $target_language ) {
        $target = strtolower( trim( str_replace( '_', '-', (string) $target_language ) ) );
        foreach ( array( 'ps', 'ug', 'ug-cn', 'sd', 'ks', 'dv', 'ar', 'fa', 'ur' ) as $prefix ) {
            if ( $target === $prefix || 0 === strpos( $target, $prefix . '-' ) ) {
                return true;
            }
        }
        return false;
    }

    protected function volc_chat_has_unexpected_latin_output( $source_text, $translated_text, $target_language ) {
        if ( ! $this->volc_chat_target_requires_non_latin_output( $target_language ) ) {
            return false;
        }

        if ( class_exists( 'TPRE_Translation_Safety_Utils' ) && TPRE_Translation_Safety_Utils::looks_like_safe_passthrough_text( $source_text ) ) {
            return false;
        }

        $plain = trim( html_entity_decode( wp_strip_all_tags( (string) $translated_text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
        if ( '' === $plain ) {
            return false;
        }

        $len = class_exists( 'TPRE_Translation_Safety_Utils' )
            ? TPRE_Translation_Safety_Utils::get_plain_text_length( $plain )
            : ( function_exists( 'mb_strlen' ) ? mb_strlen( $plain, 'UTF-8' ) : strlen( $plain ) );
        if ( $len <= 2 ) {
            return false;
        }

        preg_match_all( '/[A-Za-z]/u', $plain, $latin_matches );
        preg_match_all( '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}]/u', $plain, $arabic_matches );
        $latin_count  = isset( $latin_matches[0] ) ? count( $latin_matches[0] ) : 0;
        $arabic_count = isset( $arabic_matches[0] ) ? count( $arabic_matches[0] ) : 0;

        if ( $latin_count < 6 ) {
            return false;
        }

        return $latin_count > $arabic_count * 2;
    }
    protected function get_volc_chat_language_label_map() {
        return array(
        'ace' => 'Acehnese',
        'af' => 'Afrikaans',
        'am' => 'Amharic',
        'ar' => 'Arabic',
        'ar_sa' => 'Arabic',
        'arg' => 'Aragonese',
        'ary' => 'Moroccan Arabic',
        'as' => 'Assamese',
        'ay' => 'Aymara',
        'az' => 'Azerbaijani',
        'azb' => 'South Azerbaijani',
        'ba' => 'Bashkir',
        'be' => 'Belarusian',
        'bel' => 'Belarusian',
        'bg' => 'Bulgarian',
        'bg_bg' => 'Bulgarian',
        'bho' => 'Bhojpuri',
        'bn' => 'Bengali',
        'bn_bd' => 'Bengali (Bangladesh)',
        'bo' => 'Tibetan',
        'br' => 'Breton',
        'bs' => 'Bosnian',
        'bs_ba' => 'Bosnian',
        'ca' => 'Catalan',
        'ceb' => 'Cebuano',
        'ckb' => 'Kurdish (Sorani)',
        'cs' => 'Czech',
        'cs_cz' => 'Czech',
        'cy' => 'Welsh',
        'da' => 'Danish',
        'da_dk' => 'Danish',
        'de' => 'German',
        'de_at' => 'German (Austria)',
        'de_ch' => 'German (Switzerland)',
        'de_de' => 'German',
        'dsb' => 'Lower Sorbian',
        'dzo' => 'Dzongkha',
        'el' => 'Greek',
        'en' => 'English',
        'en_au' => 'English (Australia)',
        'en_ca' => 'English (Canada)',
        'en_gb' => 'English (UK)',
        'en_nz' => 'English (New Zealand)',
        'en_us' => 'English (United States)',
        'en_za' => 'English (South Africa)',
        'eo' => 'Esperanto',
        'es' => 'Spanish',
        'es_ar' => 'Spanish (Argentina)',
        'es_cl' => 'Spanish (Chile)',
        'es_co' => 'Spanish (Colombia)',
        'es_cr' => 'Spanish (Costa Rica)',
        'es_do' => 'Spanish (Dominican Republic)',
        'es_ec' => 'Spanish (Ecuador)',
        'es_es' => 'Spanish (Spain)',
        'es_gt' => 'Spanish (Guatemala)',
        'es_mx' => 'Spanish (Mexico)',
        'es_pe' => 'Spanish (Peru)',
        'es_pr' => 'Spanish (Puerto Rico)',
        'es_uy' => 'Spanish (Uruguay)',
        'es_ve' => 'Spanish (Venezuela)',
        'et' => 'Estonian',
        'et_ee' => 'Estonian',
        'eu' => 'Basque',
        'fa' => 'Persian',
        'fa_af' => 'Persian (Afghanistan)',
        'fa_ir' => 'Persian',
        'fi' => 'Finnish',
        'fi_fi' => 'Finnish',
        'fil' => 'Filipino',
        'fil_ph' => 'Filipino',
        'fr' => 'French',
        'fr_be' => 'French (Belgium)',
        'fr_ca' => 'French (Canada)',
        'fr_fr' => 'French (France)',
        'fur' => 'Friulian',
        'fy' => 'Frisian',
        'ga' => 'Irish',
        'gd' => 'Scottish Gaelic',
        'gl' => 'Galician',
        'gl_es' => 'Galician',
        'gn' => 'Guarani',
        'gom' => 'Konkani',
        'gu' => 'Gujarati',
        'ha' => 'Hausa',
        'haz' => 'Hazaragi',
        'he' => 'Hebrew',
        'he_il' => 'Hebrew',
        'hi' => 'Hindi',
        'hi_in' => 'Hindi',
        'hr' => 'Croatian',
        'hr_hr' => 'Croatian',
        'hsb' => 'Upper Sorbian',
        'ht' => 'Haitian Creole',
        'hu' => 'Hungarian',
        'hu_hu' => 'Hungarian',
        'hy' => 'Armenian',
        'id' => 'Indonesian',
        'id_id' => 'Indonesian',
        'ig' => 'Igbo',
        'is' => 'Icelandic',
        'is_is' => 'Icelandic',
        'it' => 'Italian',
        'it_it' => 'Italian',
        'ja' => 'Japanese',
        'ja_jp' => 'Japanese',
        'jv_id' => 'Javanese',
        'ka' => 'Georgian',
        'ka_ge' => 'Georgian',
        'kab' => 'Kabyle',
        'kir' => 'Kyrgyz',
        'kk' => 'Kazakh',
        'km' => 'Khmer',
        'km_kh' => 'Khmer',
        'kmr' => 'Kurdish (Kurmanji)',
        'kn' => 'Kannada',
        'ko' => 'Korean',
        'ko_kr' => 'Korean',
        'la' => 'Latin',
        'lb' => 'Luxembourgish',
        'lmo' => 'Lombard',
        'ln' => 'Lingala',
        'lo' => 'Lao',
        'lo_la' => 'Lao',
        'lt' => 'Lithuanian',
        'lt_lt' => 'Lithuanian',
        'lv' => 'Latvian',
        'lv_lv' => 'Latvian',
        'mai' => 'Maithili',
        'mg' => 'Malagasy',
        'mi' => 'Maori',
        'mk' => 'Macedonian',
        'mk_mk' => 'Macedonian',
        'ml' => 'Malayalam',
        'ml_in' => 'Malayalam',
        'mn' => 'Mongolian',
        'mr' => 'Marathi',
        'ms' => 'Malay',
        'ms_my' => 'Malay',
        'mt' => 'Maltese',
        'my' => 'Burmese',
        'my_mm' => 'Myanmar (Burmese)',
        'nb_no' => 'Norwegian (Bokmål)',
        'ne' => 'Nepali',
        'ne_np' => 'Nepali',
        'nl' => 'Dutch',
        'nl_be' => 'Dutch (Belgium)',
        'nl_nl' => 'Dutch',
        'nn_no' => 'Norwegian (Nynorsk)',
        'no' => 'Norwegian',
        'oci' => 'Occitan',
        'or' => 'Odia',
        'or_in' => 'Odia',
        'pa' => 'Punjabi',
        'pa_in' => 'Panjabi (India)',
        'pag' => 'Pangasinan',
        'pam' => 'Kapampangan',
        'pl' => 'Polish',
        'pl_pl' => 'Polish',
        'prs' => 'Dari',
        'ps' => 'Pashto',
        'pt' => 'Portuguese',
        'pt_ao' => 'Portuguese (Angola)',
        'pt_br' => 'Portuguese (Brazil)',
        'pt_pt' => 'Portuguese (Portugal)',
        'pt_pt_ao90' => 'Portuguese (Portugal, AO90)',
        'qu' => 'Quechua',
        'rhg' => 'Rohingya',
        'ro' => 'Romanian',
        'ro_ro' => 'Romanian',
        'ru' => 'Russian',
        'ru_ru' => 'Russian',
        'sa' => 'Sanskrit',
        'sah' => 'Sakha',
        'scn' => 'Sicilian',
        'si' => 'Sinhala',
        'si_lk' => 'Sinhala',
        'sk' => 'Slovak',
        'sk_sk' => 'Slovak',
        'skr' => 'Saraiki',
        'sl' => 'Slovenian',
        'sl_si' => 'Slovenian',
        'snd' => 'Sindhi',
        'sq' => 'Albanian',
        'sr' => 'Serbian',
        'sr_rs' => 'Serbian',
        'su' => 'Sundanese',
        'sv' => 'Swedish',
        'sv_se' => 'Swedish',
        'sw' => 'Swahili',
        'sw_ke' => 'Swahili',
        'szl' => 'Silesian',
        'ta' => 'Tamil',
        'ta_in' => 'Tamil',
        'ta_lk' => 'Tamil (Sri Lanka)',
        'tah' => 'Tahitian',
        'te' => 'Telugu',
        'tg' => 'Tajik',
        'th' => 'Thai',
        'tk' => 'Turkmen',
        'tl' => 'Tagalog',
        'tn' => 'Tswana',
        'tr' => 'Turkish',
        'tr_tr' => 'Turkish',
        'ts' => 'Tsonga',
        'tt_ru' => 'Tatar',
        'ug_cn' => 'Uighur',
        'uk' => 'Ukrainian',
        'uk_ua' => 'Ukrainian',
        'ur' => 'Urdu',
        'ur_pk' => 'Urdu',
        'uz' => 'Uzbek',
        'uz_uz' => 'Uzbek',
        'vi' => 'Vietnamese',
        'vi_vn' => 'Vietnamese',
        'wo' => 'Wolof',
        'xh' => 'Xhosa',
        'yue' => 'Cantonese',
        'yue_hk' => 'Cantonese',
        'zh' => 'Chinese',
        'zh_cn' => 'Chinese (China)',
        'zh_hant' => 'Traditional Chinese',
        'zh_hk' => 'Chinese (Hong Kong)',
        'zh_sg' => 'Chinese (Singapore)',
        'zh_tw' => 'Chinese (Taiwan)',
        'zu' => 'Zulu',
        );
    }

    protected function get_volc_chat_supported_locale_map() {
        return array(
        'ace' => 'ace',
        'af' => 'af',
        'af_za' => 'af',
        'am' => 'am',
        'ar' => 'ar',
        'ar_ar' => 'ar',
        'ar_sa' => 'ar_SA',
        'arg' => 'arg',
        'ary' => 'ary',
        'as' => 'as',
        'ay' => 'ay',
        'az' => 'az',
        'az_az' => 'az',
        'azb' => 'azb',
        'ba' => 'ba',
        'bel' => 'bel',
        'bg_bg' => 'bg_BG',
        'bho' => 'bho',
        'bn_bd' => 'bn_BD',
        'bo' => 'bo',
        'br' => 'br',
        'bs_ba' => 'bs_BA',
        'ca' => 'ca',
        'ca_es' => 'ca',
        'ceb' => 'ceb',
        'ckb' => 'ckb',
        'cs_cz' => 'cs_CZ',
        'cy' => 'cy',
        'cy_gb' => 'cy',
        'da_dk' => 'da_DK',
        'de_at' => 'de_AT',
        'de_ch' => 'de_CH',
        'de_de' => 'de_DE',
        'dsb' => 'dsb',
        'dzo' => 'dzo',
        'el' => 'el',
        'el_gr' => 'el',
        'en_au' => 'en_AU',
        'en_ca' => 'en_CA',
        'en_gb' => 'en_GB',
        'en_nz' => 'en_NZ',
        'en_us' => 'en_US',
        'en_za' => 'en_ZA',
        'eo' => 'eo',
        'es_ar' => 'es_AR',
        'es_cl' => 'es_CL',
        'es_co' => 'es_CO',
        'es_cr' => 'es_CR',
        'es_do' => 'es_DO',
        'es_ec' => 'es_EC',
        'es_es' => 'es_ES',
        'es_gt' => 'es_GT',
        'es_mx' => 'es_MX',
        'es_pe' => 'es_PE',
        'es_pr' => 'es_PR',
        'es_uy' => 'es_UY',
        'es_ve' => 'es_VE',
        'et' => 'et',
        'et_ee' => 'et_EE',
        'eu' => 'eu',
        'eu_es' => 'eu',
        'fa_af' => 'fa_AF',
        'fa_ir' => 'fa_IR',
        'fi' => 'fi',
        'fi_fi' => 'fi_FI',
        'fil_ph' => 'fil_PH',
        'fr_be' => 'fr_BE',
        'fr_ca' => 'fr_CA',
        'fr_fr' => 'fr_FR',
        'fur' => 'fur',
        'fy' => 'fy',
        'ga' => 'ga',
        'gd' => 'gd',
        'gl_es' => 'gl_ES',
        'gn' => 'gn',
        'gom' => 'gom',
        'gu' => 'gu',
        'gu_in' => 'gu',
        'ha' => 'ha',
        'haz' => 'haz',
        'he_il' => 'he_IL',
        'hi_in' => 'hi_IN',
        'hr' => 'hr',
        'hr_hr' => 'hr_HR',
        'hsb' => 'hsb',
        'ht' => 'ht',
        'hu_hu' => 'hu_HU',
        'hy' => 'hy',
        'hy_am' => 'hy',
        'id_id' => 'id_ID',
        'ig' => 'ig',
        'is_is' => 'is_IS',
        'it_it' => 'it_IT',
        'ja' => 'ja',
        'ja_jp' => 'ja_JP',
        'jv_id' => 'jv_ID',
        'ka_ge' => 'ka_GE',
        'kab' => 'kab',
        'kir' => 'kir',
        'kk' => 'kk',
        'km' => 'km',
        'km_kh' => 'km_KH',
        'kmr' => 'kmr',
        'kn' => 'kn',
        'ko_kr' => 'ko_KR',
        'la' => 'la',
        'lb' => 'lb',
        'lmo' => 'lmo',
        'ln' => 'ln',
        'lo' => 'lo',
        'lo_la' => 'lo_LA',
        'lt_lt' => 'lt_LT',
        'lv' => 'lv',
        'lv_lv' => 'lv_LV',
        'mai' => 'mai',
        'mg' => 'mg',
        'mi' => 'mi',
        'mk_mk' => 'mk_MK',
        'ml_in' => 'ml_IN',
        'mn' => 'mn',
        'mn_mn' => 'mn',
        'mr' => 'mr',
        'mr_in' => 'mr',
        'ms_my' => 'ms_MY',
        'mt' => 'mt',
        'my_mm' => 'my_MM',
        'nb_no' => 'nb_NO',
        'ne_np' => 'ne_NP',
        'nl_be' => 'nl_BE',
        'nl_nl' => 'nl_NL',
        'nn_no' => 'nn_NO',
        'oci' => 'oci',
        'or_in' => 'or_IN',
        'pa_in' => 'pa_IN',
        'pag' => 'pag',
        'pam' => 'pam',
        'pl_pl' => 'pl_PL',
        'prs' => 'prs',
        'ps' => 'ps',
        'pt_ao' => 'pt_AO',
        'pt_br' => 'pt_BR',
        'pt_pt' => 'pt_PT',
        'pt_pt_ao90' => 'pt_PT_ao90',
        'qu' => 'qu',
        'rhg' => 'rhg',
        'ro_ro' => 'ro_RO',
        'ru_ru' => 'ru_RU',
        'sa' => 'sa',
        'sah' => 'sah',
        'scn' => 'scn',
        'si_lk' => 'si_LK',
        'sk_sk' => 'sk_SK',
        'skr' => 'skr',
        'sl_si' => 'sl_SI',
        'snd' => 'snd',
        'sq' => 'sq',
        'sq_al' => 'sq',
        'sr_rs' => 'sr_RS',
        'su' => 'su',
        'sv_se' => 'sv_SE',
        'sw' => 'sw',
        'sw_ke' => 'sw_KE',
        'szl' => 'szl',
        'ta_in' => 'ta_IN',
        'ta_lk' => 'ta_LK',
        'tah' => 'tah',
        'te' => 'te',
        'te_in' => 'te',
        'tg' => 'tg',
        'th' => 'th',
        'th_th' => 'th',
        'tk' => 'tk',
        'tl' => 'tl',
        'tl_ph' => 'fil',
        'tn' => 'tn',
        'tr_tr' => 'tr_TR',
        'ts' => 'ts',
        'tt_ru' => 'tt_RU',
        'ug_cn' => 'ug_CN',
        'uk' => 'uk',
        'uk_ua' => 'uk_UA',
        'ur' => 'ur',
        'ur_pk' => 'ur_PK',
        'uz_uz' => 'uz_UZ',
        'vi' => 'vi',
        'vi_vn' => 'vi_VN',
        'wo' => 'wo',
        'xh' => 'xh',
        'yue' => 'yue',
        'yue_hk' => 'yue_HK',
        'zh_cn' => 'zh_CN',
        'zh_hant' => 'zh_Hant',
        'zh_hk' => 'zh_HK',
        'zh_sg' => 'zh_SG',
        'zh_tw' => 'zh_TW',
        );
    }

    protected function format_chat_prompt_language_label( $code ) {
        $raw = trim( (string) $code );
        if ( '' === $raw ) {
            return 'auto';
        }

        $normalized = strtolower( str_replace( '_', '-', $raw ) );
        $map        = $this->get_volc_chat_language_label_map();

        if ( isset( $map[ $normalized ] ) ) {
            return $map[ $normalized ];
        }

        $translation_normalized = $this->normalize_translation_language_code( $raw );
        if ( '' !== $translation_normalized ) {
            $translation_key = strtolower( str_replace( '_', '-', $translation_normalized ) );
            if ( isset( $map[ $translation_key ] ) ) {
                return $map[ $translation_key ];
            }
            return strtoupper( $translation_normalized );
        }

        return strtoupper( str_replace( array( '_', '-' ), ' ', $raw ) );
    }

    protected function get_volc_chat_target_prompt_suffix( $target_language ) {
        $normalized = strtolower( str_replace( '_', '-', trim( (string) $target_language ) ) );

        if ( 'yue' === $normalized ) {
            return ' Use natural written Cantonese. Prefer Traditional Chinese characters. Do not rewrite into Mandarin.';
        }

        if ( in_array( $normalized, array( 'zh-hk', 'zh-hant', 'zh-tw' ), true ) ) {
            return ' Use natural Traditional Chinese and keep the wording native for the requested locale.';
        }

        return '';
    }

    protected function extract_volc_chat_preserve_tokens( $text ) {
        $text = (string) $text;
        if ( '' === $text ) {
            return array();
        }

        $tokens = array();
        $patterns = array(
            '/wp[-_][A-Za-z0-9_-]+/u',
            '/[A-Za-z_][A-Za-z0-9_]*\(\)/u',
            '/[A-Za-z0-9_.-]+\.(?:php|js|css|html|json|xml|yml|yaml|txt|md|pot|po|mo|zip|png|jpg|jpeg|gif|svg)/i',
            '#[A-Za-z0-9_./:-]+/[A-Za-z0-9_./:-]+#u',
            '/`[^`
]{2,120}`/u',
        );

        foreach ( $patterns as $pattern ) {
            if ( preg_match_all( $pattern, $text, $matches ) && ! empty( $matches[0] ) ) {
                foreach ( $matches[0] as $match ) {
                    $token = trim( (string) $match );
                    if ( '' === $token ) {
                        continue;
                    }
                    $plain = trim( $token, "` ");
                    if ( '' === $plain ) {
                        continue;
                    }
                    if ( class_exists( 'TPRE_Translation_Safety_Utils' ) && ! TPRE_Translation_Safety_Utils::looks_like_safe_passthrough_text( $plain ) ) {
                        continue;
                    }
                    $tokens[ $token ] = strlen( $token );
                }
            }
        }

        if ( empty( $tokens ) ) {
            return array();
        }

        uksort( $tokens, static function( $a, $b ) {
            return strlen( $b ) <=> strlen( $a );
        } );

        return array_keys( $tokens );
    }

    protected function protect_volc_chat_text_for_request( $text ) {
        $text   = (string) $text;
        $tokens = $this->extract_volc_chat_preserve_tokens( $text );
        if ( empty( $tokens ) ) {
            return $text;
        }

        $index = 1;
        foreach ( $tokens as $token ) {
            $placeholder = '[[TPRE_NT_' . $index . ']]';
            $text        = str_replace( $token, $placeholder, $text );
            $index++;
        }

        return $text;
    }

    protected function restore_volc_chat_text_after_response( $source_text, $translated_text ) {
        $translated_text = (string) $translated_text;
        $tokens          = $this->extract_volc_chat_preserve_tokens( $source_text );
        if ( empty( $tokens ) || '' === $translated_text ) {
            return $translated_text;
        }

        $index = 1;
        foreach ( $tokens as $token ) {
            $placeholder    = '[[TPRE_NT_' . $index . ']]';
            $translated_text = str_replace( $placeholder, $token, $translated_text );
            $index++;
        }

        return $translated_text;
    }

    protected function get_volc_chat_prompt_payload( $source_language, $target_language, $text, $quality_retry = false ) {
        $source_label = $this->format_chat_prompt_language_label( $source_language );
        $target_label = $this->format_chat_prompt_language_label( $target_language );
        $text         = $this->protect_volc_chat_text_for_request( (string) $text );
        $has_html     = (bool) preg_match( '/<[^>]+>/', $text );
        $is_label     = class_exists( 'TPRE_Translation_Safety_Utils' )
            ? TPRE_Translation_Safety_Utils::is_label_like_text( $text )
            : ( function_exists( 'mb_strlen' ) ? mb_strlen( wp_strip_all_tags( $text ), 'UTF-8' ) <= 120 : strlen( wp_strip_all_tags( $text ) ) <= 120 );

        $target_hint = $this->get_volc_chat_target_prompt_suffix( $target_language ) . $this->get_volc_chat_strict_target_prompt_suffix( $target_language );

        if ( $is_label && ! $has_html ) {
            $instructions = sprintf(
                'Translate this website UI label from %1$s to %2$s. Output translation only in %2$s. If the text contains code or filenames such as functions.php, keep those code fragments unchanged and translate the rest.%3$s',
                $source_label,
                $target_label,
                $target_hint
            );
        } elseif ( $has_html ) {
            $instructions = sprintf(
                'Translate this HTML fragment from %1$s to %2$s. Translate text only and preserve tags, attributes, placeholders, URLs, emails, entities, and line breaks. Output the translated HTML only.%3$s',
                $source_label,
                $target_label,
                $target_hint
            );
        } else {
            $instructions = sprintf(
                'Translate this text from %1$s to %2$s. Output translation only in %2$s. Translate all natural-language words. Preserve placeholders, URLs, emails, code, file names, and entities unchanged.%3$s',
                $source_label,
                $target_label,
                $target_hint
            );
        }

        if ( $quality_retry ) {
            $instructions .= ' Use only the target language unless the source is a name, code, URL, email, or safe passthrough token.';
        }

        if ( false !== strpos( $text, '[[TPRE_NT_' ) ) {
            $instructions .= ' Preserve tokens like [[TPRE_NT_1]] exactly. Do not translate, rewrite, or remove them.';
        }

        return array(
            'instructions' => $instructions,
            'text'         => $text,
        );
    }

    protected function build_chat_request_body( $source_language, $target_language, $strings_array, $quality_retry = false ) {
        $items = array_values(
            array_map(
                function( $value ) {
                    return $this->protect_volc_chat_text_for_request( $this->sanitize_utf8_text( $value ) );
                },
                (array) $strings_array
            )
        );

        if ( empty( $items ) ) {
            return false;
        }

        if ( count( $items ) > 1 ) {
            $source_label = $this->format_chat_prompt_language_label( $source_language );
            $target_label = $this->format_chat_prompt_language_label( $target_language );
            $target_hint  = $this->get_volc_chat_target_prompt_suffix( $target_language ) . $this->get_volc_chat_strict_target_prompt_suffix( $target_language );
            $instructions = sprintf(
                'Translate this JSON array from %1$s to %2$s. Output only a valid JSON array with exactly %3$d translated strings in the same order. Preserve HTML, placeholders, URLs, emails, code, entities, and line breaks.%4$s',
                $source_label,
                $target_label,
                count( $items ),
                $target_hint
            );
            if ( $quality_retry ) {
                $instructions .= ' Use only the target language unless an item is a name, code, URL, email, or safe passthrough token.';
            }
            if ( false !== strpos( implode( "\n", $items ), '[[TPRE_NT_' ) ) {
                $instructions .= ' Preserve tokens like [[TPRE_NT_1]] exactly. Do not translate, rewrite, or remove them.';
            }
            $user_content = wp_json_encode( $items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
        } else {
            $payload      = $this->get_volc_chat_prompt_payload( $source_language, $target_language, (string) $items[0], $quality_retry );
            $instructions = $payload['instructions'];
            $user_content = $payload['text'];
        }

        return array(
            'model'       => $this->get_model( 'chat' ),
            'messages'    => array(
                array(
                    'role'    => 'system',
                    'content' => $instructions,
                ),
                array(
                    'role'    => 'user',
                    'content' => $user_content,
                ),
            ),
            'temperature' => 0,
        );
    }

    protected function get_volc_chat_cjk_target_prefixes() {
        $defaults = array( 'zh', 'yue' );
        $targets  = apply_filters( 'tpre_volc_chat_cjk_target_prefixes', $defaults, $this->settings );
        if ( ! is_array( $targets ) ) {
            $targets = $defaults;
        }

        $normalized = array();
        foreach ( $targets as $target ) {
            if ( ! is_string( $target ) ) {
                continue;
            }
            $target = strtolower( trim( str_replace( '_', '-', $target ) ) );
            if ( '' !== $target ) {
                $normalized[] = $target;
            }
        }

        return array_values( array_unique( $normalized ) );
    }

    protected function target_language_allows_cjk_passthrough_for_chat( $target_language ) {
        $target_language = strtolower( trim( str_replace( '_', '-', (string) $target_language ) ) );
        if ( '' === $target_language ) {
            return false;
        }

        foreach ( $this->get_volc_chat_cjk_target_prefixes() as $prefix ) {
            if ( 0 === strpos( $target_language, $prefix ) ) {
                return true;
            }
        }

        return false;
    }

    protected function truncate_volc_chat_log_preview( $text, $limit = 160 ) {
        $text = trim( wp_strip_all_tags( (string) $text ) );
        if ( function_exists( 'mb_strlen' ) ) {
            if ( mb_strlen( $text, 'UTF-8' ) > $limit ) {
                return mb_substr( $text, 0, $limit, 'UTF-8' ) . '...';
            }
            return $text;
        }
        return strlen( $text ) > $limit ? substr( $text, 0, $limit ) . '...' : $text;
    }

    protected function normalize_volc_chat_compare_text( $text ) {
        $text = html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $text = preg_replace( '/\s+/u', ' ', $text );
        return trim( strtolower( $text ) );
    }

    protected function can_accept_chat_same_source_output( $source_text, $translated_text, $target_language ) {
        $target_language = strtolower( str_replace( '_', '-', (string) $target_language ) );
        $plain = trim( html_entity_decode( wp_strip_all_tags( (string) $translated_text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
        if ( '' === $plain ) {
            return false;
        }

        $len = class_exists( 'TPRE_Translation_Safety_Utils' )
            ? TPRE_Translation_Safety_Utils::get_plain_text_length( $plain )
            : ( function_exists( 'mb_strlen' ) ? mb_strlen( $plain, 'UTF-8' ) : strlen( $plain ) );

        if ( class_exists( 'TPRE_Translation_Safety_Utils' ) && TPRE_Translation_Safety_Utils::looks_like_safe_passthrough_text( $source_text ) ) {
            return true;
        }

        if ( preg_match( '/^[\p{Latin}\p{N}\p{Zs}\p{P}\-_.:#+\/\\]+$/u', $plain ) ) {
            return true;
        }

        if ( ! $this->target_language_allows_cjk_passthrough_for_chat( $target_language ) ) {
            return false;
        }

        if ( $len <= 2 ) {
            return true;
        }

        if ( class_exists( 'TPRE_Translation_Safety_Utils' ) && TPRE_Translation_Safety_Utils::is_label_like_text( $source_text ) && $len <= 4 ) {
            return true;
        }

        return false;
    }


    protected function volc_chat_has_obvious_explanation_markers( $text ) {
        $text = trim( wp_strip_all_tags( (string) $text ) );
        if ( '' === $text ) {
            return false;
        }

        $patterns = array(
            '/^(?:translation|translated text|note|explanation|說明|说明|翻譯|翻译)\s*[:：-]/iu',
            '/^(?:以下|下面).{0,12}(?:翻譯|译文|譯文|內容|内容)/u',
            '/^#{1,6}\s/u',
            '/(?:^|
)\s*[-*•]\s+/u',
            '/(?:^|
)\s*\d+\.\s+/u',
            '/\*\*[^*]+\*\*/u',
            '/Here(?:\s+is|\s+are)?\s+the translation/i',
            '/The translation should/i',
            '/without any additional explanations?/i',
        );

        foreach ( $patterns as $pattern ) {
            if ( preg_match( $pattern, $text ) ) {
                return true;
            }
        }

        return false;
    }

    protected function should_reject_volc_chat_explanation_output( $source_text, $translated_text ) {
        if ( class_exists( 'TPRE_Translation_Safety_Utils' ) && TPRE_Translation_Safety_Utils::looks_like_code_or_template_fragment( $source_text ) ) {
            return false;
        }

        return $this->volc_chat_has_obvious_explanation_markers( $translated_text );
    }

    protected function get_volc_chat_validation_result( $source_language, $target_language, $source_text, $translated_text ) {
        $source_text         = trim( $this->sanitize_utf8_text( (string) $source_text ) );
        $translated_text_raw = (string) $translated_text;
        $translated_text     = trim( $this->sanitize_utf8_text( $translated_text_raw ) );
        $target_language     = strtolower( str_replace( '_', '-', (string) $target_language ) );
        $normalized_source   = $this->normalize_volc_chat_compare_text( $source_text );
        $normalized_target   = $this->normalize_volc_chat_compare_text( $translated_text );

        $result = array(
            'ok'                => true,
            'reason'            => '',
            'request_mode'      => 'chat',
            'target_language'   => (string) $target_language,
            'source_preview'    => $this->truncate_volc_chat_log_preview( $source_text ),
            'candidate_preview' => $this->truncate_volc_chat_log_preview( $translated_text_raw ),
        );

        if ( '' === $source_text || '' === $translated_text ) {
            $result['ok']     = false;
            $result['reason'] = 'empty_text';
            return $result;
        }

        if ( $this->volc_chat_has_unexpected_latin_output( $source_text, $translated_text, $target_language ) ) {
            $result['ok']     = false;
            $result['reason'] = 'has_unexpected_latin_leak';
            return $result;
        }

        if ( class_exists( 'TPRE_Translation_Safety_Utils' ) ) {
            if ( TPRE_Translation_Safety_Utils::has_dangerous_markup_mismatch( $source_text, $translated_text ) ) {
                $result['ok']     = false;
                $result['reason'] = 'has_dangerous_markup_mismatch';
                return $result;
            }

            if ( $this->should_reject_volc_chat_explanation_output( $source_text, $translated_text ) ) {
                $result['ok']     = false;
                $result['reason'] = 'looks_like_explanation_output';
                return $result;
            }

            if ( TPRE_Translation_Safety_Utils::has_excessive_label_expansion( $source_text, $translated_text ) ) {
                $result['ok']     = false;
                $result['reason'] = 'has_excessive_label_expansion';
                return $result;
            }
        }

        if ( '' !== $normalized_source && $normalized_source === $normalized_target ) {
            if ( class_exists( 'TPRE_Translation_Safety_Utils' ) && TPRE_Translation_Safety_Utils::looks_like_safe_passthrough_text( $source_text ) ) {
                $result['reason'] = 'accepted_same_as_source_passthrough';
                return $result;
            }
            if ( preg_match( '#^(?:\.?\.?/)?[A-Za-z0-9_./:%#?&=+@\-]+/?$#', $source_text ) ) {
                $result['reason'] = 'accepted_same_as_source_passthrough';
                return $result;
            }
            if ( ! $this->can_accept_chat_same_source_output( $source_text, $translated_text, $target_language ) ) {
                $result['ok']     = false;
                $result['reason'] = 'same_as_source_after_normalize';
                return $result;
            }
            $result['reason'] = 'accepted_same_as_source_passthrough';
            return $result;
        }

        if ( $this->target_language_allows_cjk_passthrough_for_chat( $target_language ) ) {
            return $result;
        }

        if ( class_exists( 'TPRE_Translation_Safety_Utils' ) ) {
            if ( TPRE_Translation_Safety_Utils::is_single_cjk_character_source( $source_text )
                && ! TPRE_Translation_Safety_Utils::is_cjk_target_language( $target_language )
                && TPRE_Translation_Safety_Utils::looks_like_short_function_word( $translated_text ) ) {
                $result['reason'] = 'accepted_short_function_word';
                return $result;
            }
            if ( TPRE_Translation_Safety_Utils::has_unexpected_cjk_leak( $source_text, $translated_text, $target_language ) ) {
                $result['ok']     = false;
                $result['reason'] = 'has_unexpected_cjk_leak';
                return $result;
            }

            if ( TPRE_Translation_Safety_Utils::is_suspicious_translation_output( $source_text, $translated_text, $target_language ) ) {
                $result['ok']     = false;
                $result['reason'] = 'is_suspicious_translation_output';
                return $result;
            }
        }

        if ( $this->is_suspect_incomplete_translation( $source_language, $target_language, $source_text, $translated_text ) ) {
            $result['ok']     = false;
            $result['reason'] = 'is_suspect_incomplete_translation';
            return $result;
        }

        return $result;
    }

    protected function is_volc_chat_result_acceptable( $source_language, $target_language, $source_text, $translated_text ) {
        $validation = $this->get_volc_chat_validation_result( $source_language, $target_language, $source_text, $translated_text );
        return ! empty( $validation['ok'] );
    }
}
