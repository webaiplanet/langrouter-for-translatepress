<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// phpcs:disable WordPress.WP.AlternativeFunctions -- Intentional use of cURL/cURL multi for provider-specific parallel request handling.

class TPRE_OpenAI_Client {
    const DEFAULT_TIMEOUT     = 12;
    const DEFAULT_MODEL       = 'gpt-4o-mini';
    const DEFAULT_ENDPOINT    = 'https://api.openai.com/v1/chat/completions';
    const DEFAULT_CONCURRENCY = 10;
    const COMPATIBLE_DEFAULT_TIMEOUT = 60;
    const COMPATIBLE_DEFAULT_CONCURRENCY = 4;
    const COMPATIBLE_LONG_TEXT_THRESHOLD = 1800;
    const COMPATIBLE_LONG_TEXT_CHUNK_CHARS = 1200;
    const COMPATIBLE_LONG_HTML_CHUNK_CHARS = 1600;

    protected $router_settings;
    protected $logger;
    protected $model_key;

    protected $language_names = [
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
    ];

    protected $locale_code_map = [
        'ace' => 'ace',
        'af' => 'af',
        'af_ZA' => 'af',
        'am' => 'am',
        'ar' => 'ar',
        'ar_AR' => 'ar',
        'ar_SA' => 'ar_SA',
        'arg' => 'arg',
        'ary' => 'ary',
        'as' => 'as',
        'ay' => 'ay',
        'az' => 'az',
        'az_AZ' => 'az',
        'azb' => 'azb',
        'ba' => 'ba',
        'bel' => 'bel',
        'bg_BG' => 'bg_BG',
        'bho' => 'bho',
        'bn_BD' => 'bn_BD',
        'bo' => 'bo',
        'br' => 'br',
        'bs_BA' => 'bs_BA',
        'ca' => 'ca',
        'ca_ES' => 'ca',
        'ceb' => 'ceb',
        'ckb' => 'ckb',
        'cs_CZ' => 'cs_CZ',
        'cy' => 'cy',
        'cy_GB' => 'cy',
        'da_DK' => 'da_DK',
        'de_AT' => 'de_AT',
        'de_CH' => 'de_CH',
        'de_DE' => 'de_DE',
        'dsb' => 'dsb',
        'dzo' => 'dzo',
        'el' => 'el',
        'el_GR' => 'el',
        'en_AU' => 'en_AU',
        'en_CA' => 'en_CA',
        'en_GB' => 'en_GB',
        'en_NZ' => 'en_NZ',
        'en_US' => 'en_US',
        'en_ZA' => 'en_ZA',
        'eo' => 'eo',
        'es_AR' => 'es_AR',
        'es_CL' => 'es_CL',
        'es_CO' => 'es_CO',
        'es_CR' => 'es_CR',
        'es_DO' => 'es_DO',
        'es_EC' => 'es_EC',
        'es_ES' => 'es_ES',
        'es_GT' => 'es_GT',
        'es_MX' => 'es_MX',
        'es_PE' => 'es_PE',
        'es_PR' => 'es_PR',
        'es_UY' => 'es_UY',
        'es_VE' => 'es_VE',
        'et' => 'et',
        'et_EE' => 'et_EE',
        'eu' => 'eu',
        'eu_ES' => 'eu',
        'fa_AF' => 'fa_AF',
        'fa_IR' => 'fa_IR',
        'fi' => 'fi',
        'fi_FI' => 'fi_FI',
        'fil_PH' => 'fil_PH',
        'fr_BE' => 'fr_BE',
        'fr_CA' => 'fr_CA',
        'fr_FR' => 'fr_FR',
        'fur' => 'fur',
        'fy' => 'fy',
        'ga' => 'ga',
        'gd' => 'gd',
        'gl_ES' => 'gl_ES',
        'gn' => 'gn',
        'gom' => 'gom',
        'gu' => 'gu',
        'gu_IN' => 'gu',
        'ha' => 'ha',
        'haz' => 'haz',
        'he_IL' => 'he_IL',
        'hi_IN' => 'hi_IN',
        'hr' => 'hr',
        'hr_HR' => 'hr_HR',
        'hsb' => 'hsb',
        'ht' => 'ht',
        'hu_HU' => 'hu_HU',
        'hy' => 'hy',
        'hy_AM' => 'hy',
        'id_ID' => 'id_ID',
        'ig' => 'ig',
        'is_IS' => 'is_IS',
        'it_IT' => 'it_IT',
        'ja' => 'ja',
        'ja_JP' => 'ja_JP',
        'jv_ID' => 'jv_ID',
        'ka_GE' => 'ka_GE',
        'kab' => 'kab',
        'kir' => 'kir',
        'kk' => 'kk',
        'km' => 'km',
        'km_KH' => 'km_KH',
        'kmr' => 'kmr',
        'kn' => 'kn',
        'ko_KR' => 'ko_KR',
        'la' => 'la',
        'lb' => 'lb',
        'lmo' => 'lmo',
        'ln' => 'ln',
        'lo' => 'lo',
        'lo_LA' => 'lo_LA',
        'lt_LT' => 'lt_LT',
        'lv' => 'lv',
        'lv_LV' => 'lv_LV',
        'mai' => 'mai',
        'mg' => 'mg',
        'mi' => 'mi',
        'mk_MK' => 'mk_MK',
        'ml_IN' => 'ml_IN',
        'mn' => 'mn',
        'mn_MN' => 'mn',
        'mr' => 'mr',
        'mr_IN' => 'mr',
        'ms_MY' => 'ms_MY',
        'mt' => 'mt',
        'my_MM' => 'my_MM',
        'nb_NO' => 'nb_NO',
        'ne_NP' => 'ne_NP',
        'nl_BE' => 'nl_BE',
        'nl_NL' => 'nl_NL',
        'nn_NO' => 'nn_NO',
        'oci' => 'oci',
        'or_IN' => 'or_IN',
        'pa_IN' => 'pa_IN',
        'pag' => 'pag',
        'pam' => 'pam',
        'pl_PL' => 'pl_PL',
        'prs' => 'prs',
        'ps' => 'ps',
        'pt_AO' => 'pt_AO',
        'pt_BR' => 'pt_BR',
        'pt_PT' => 'pt_PT',
        'pt_PT_ao90' => 'pt_PT_ao90',
        'qu' => 'qu',
        'rhg' => 'rhg',
        'ro_RO' => 'ro_RO',
        'ru_RU' => 'ru_RU',
        'sa' => 'sa',
        'sah' => 'sah',
        'scn' => 'scn',
        'si_LK' => 'si_LK',
        'sk_SK' => 'sk_SK',
        'skr' => 'skr',
        'sl_SI' => 'sl_SI',
        'snd' => 'snd',
        'sq' => 'sq',
        'sq_AL' => 'sq',
        'sr_RS' => 'sr_RS',
        'su' => 'su',
        'sv_SE' => 'sv_SE',
        'sw' => 'sw',
        'sw_KE' => 'sw_KE',
        'szl' => 'szl',
        'ta_IN' => 'ta_IN',
        'ta_LK' => 'ta_LK',
        'tah' => 'tah',
        'te' => 'te',
        'te_IN' => 'te',
        'tg' => 'tg',
        'th' => 'th',
        'th_TH' => 'th',
        'tk' => 'tk',
        'tl' => 'tl',
        'tl_PH' => 'fil',
        'tn' => 'tn',
        'tr_TR' => 'tr_TR',
        'ts' => 'ts',
        'tt_RU' => 'tt_RU',
        'ug_CN' => 'ug_CN',
        'uk' => 'uk',
        'uk_UA' => 'uk_UA',
        'ur' => 'ur',
        'ur_PK' => 'ur_PK',
        'uz_UZ' => 'uz_UZ',
        'vi' => 'vi',
        'vi_VN' => 'vi_VN',
        'wo' => 'wo',
        'xh' => 'xh',
        'yue' => 'yue',
        'yue_HK' => 'yue_HK',
        'zh_CN' => 'zh_CN',
        'zh_Hant' => 'zh_Hant',
        'zh_HK' => 'zh_HK',
        'zh_SG' => 'zh_SG',
        'zh_TW' => 'zh_TW',
    ];

    public function __construct( array $router_settings, TPRE_Logger $logger, $model_key = 'openai' ) {
        $this->router_settings = $router_settings;
        $this->logger          = $logger;
        $this->model_key       = sanitize_key( (string) $model_key );
        if ( '' === $this->model_key ) {
            $this->model_key = 'openai';
        }
    }

    protected function is_openai_compatible_engine() {
        return 'openai_compatible' === $this->model_key;
    }

    protected function get_default_timeout_for_current_engine() {
        return $this->is_openai_compatible_engine() ? self::COMPATIBLE_DEFAULT_TIMEOUT : self::DEFAULT_TIMEOUT;
    }

    public function get_model_settings() {
        $models   = isset( $this->router_settings['models'] ) && is_array( $this->router_settings['models'] ) ? $this->router_settings['models'] : [];
        $defaults = [
            'enabled'                           => 0,
            'endpoint'                          => '',
            'model'                             => self::DEFAULT_MODEL,
            'custom_model'                      => '',
            'api_key'                           => '',
            'timeout'                           => $this->get_default_timeout_for_current_engine(),
            'concurrency'                       => 0,
            'max_tokens'                        => 0,
            'retry_count'                       => 1,
            'short_text_merge_threshold'        => 0,
            'temperature'                       => 0,
            'top_p'                             => 1,
            'system_prompt'                     => '',
            'batch_size'                        => 0,
            'batch_max_chars'                   => 0,
            'label_max_tokens'                  => 0,
            'long_text_threshold'               => 0,
            'long_text_chunk_chars'             => 0,
            'long_html_chunk_chars'             => 0,
            'long_text_concurrency_medium'      => 0,
            'long_text_concurrency_large'       => 0,
            'long_text_concurrency_extreme'     => 0,
            'long_text_medium_threshold'        => 0,
            'long_text_large_threshold'         => 0,
            'long_text_extreme_threshold'       => 0,
            'single_request_timeout_base'       => 0,
            'single_request_timeout_step_chars' => 0,
            'single_request_timeout_step_sec'   => 0,
            'single_request_timeout_html_bonus' => 0,
            'single_request_timeout_cap'        => 0,
            'extra_body_json'                   => '',
            'note'                              => '',
        ];

        $item = isset( $models[ $this->model_key ] ) && is_array( $models[ $this->model_key ] ) ? $models[ $this->model_key ] : [];
        return wp_parse_args( $item, $defaults );
    }

    public function is_configured() {
        $item = $this->get_model_settings();
        return ! empty( $item['enabled'] ) && '' !== trim( (string) $item['api_key'] );
    }

    public function supports_language( $language_code ) {
        return '' !== $this->get_request_language_value( $language_code );
    }

    public function get_language_support_meta( $language_code ) {
        $mapped = $this->get_request_language_value( $language_code );

        return [
            'raw'        => is_string( $language_code ) ? trim( $language_code ) : '',
            'candidates' => '' !== $mapped ? [ $mapped ] : [],
            'supported'  => '' !== $mapped,
            'source'     => 'client_language_name_map',
            'model'      => $this->get_model(),
        ];
    }

    public function get_request_language_value( $trp_language_code ) {
        $trp_language_code = (string) $trp_language_code;
        if ( isset( $this->locale_code_map[ $trp_language_code ] ) ) {
            return $this->render_language_name( $this->locale_code_map[ $trp_language_code ] );
        }

        $normalized = str_replace( '-', '_', $trp_language_code );
        if ( isset( $this->locale_code_map[ $normalized ] ) ) {
            return $this->render_language_name( $this->locale_code_map[ $normalized ] );
        }

        $iso_guess = strtolower( preg_replace( '/[_-].*/', '', $trp_language_code ) );
        if ( '' !== $iso_guess ) {
            return $this->render_language_name( $iso_guess );
        }

        return '';
    }

    protected function render_language_name( $code ) {
        $code = strtolower( (string) $code );
        if ( 'zh-hant' === $code ) {
            return 'Traditional Chinese';
        }
        if ( isset( $this->language_names[ $code ] ) ) {
            return $this->language_names[ $code ];
        }

        if ( function_exists( 'locale_get_display_language' ) ) {
            $display = locale_get_display_language( str_replace( '_', '-', $code ), 'en' );
            if ( is_string( $display ) && '' !== trim( $display ) ) {
                return trim( $display );
            }
        }

        return strtoupper( $code );
    }

    protected function normalize_endpoint_url( $endpoint ) {
        $endpoint = trim( (string) $endpoint );
        if ( '' === $endpoint ) {
            return self::DEFAULT_ENDPOINT;
        }

        $endpoint = preg_replace( '#/+$#', '', $endpoint );
        if ( preg_match( '#/chat/completions$#i', $endpoint ) ) {
            return $endpoint;
        }
        if ( preg_match( '#/v1$#i', $endpoint ) ) {
            return $endpoint . '/chat/completions';
        }

        return $endpoint;
    }

    public function get_endpoint_url() {
        $item = $this->get_model_settings();
        return $this->normalize_endpoint_url( isset( $item['endpoint'] ) ? $item['endpoint'] : '' );
    }

    public function get_timeout() {
        $item    = $this->get_model_settings();
        $timeout = isset( $item['timeout'] ) ? (int) $item['timeout'] : 0;
        if ( $timeout <= 0 ) {
            $timeout = $this->get_default_timeout_for_current_engine();
        }

        return max( 5, $timeout );
    }

    protected function get_single_request_timeout( $text, $mode = 'text' ) {
        $timeout = $this->get_timeout();
        if ( 'label' === $mode ) {
            return $timeout;
        }

        $plain_length = $this->get_plain_text_length( $text );
        $has_html     = (bool) preg_match( '/<[^>]+>/', (string) $text );

        if ( $this->is_openai_compatible_engine() ) {
            $base_timeout = $this->get_compatible_int_setting( 'single_request_timeout_base', 45, 5 );
            $step_chars   = $this->get_compatible_int_setting( 'single_request_timeout_step_chars', 700, 50 );
            $step_seconds = $this->get_compatible_int_setting( 'single_request_timeout_step_sec', 10, 0 );
            $html_bonus   = $this->get_compatible_int_setting( 'single_request_timeout_html_bonus', 10, 0 );
            $timeout_cap  = $this->get_compatible_int_setting( 'single_request_timeout_cap', 180, 5 );

            $timeout = max( $timeout, $base_timeout );

            if ( $plain_length > 1200 && $step_seconds > 0 ) {
                $timeout += (int) ceil( ( $plain_length - 1200 ) / $step_chars ) * $step_seconds;
            }

            if ( $has_html && $html_bonus > 0 ) {
                $timeout += $html_bonus;
            }

            return min( $timeout_cap, $timeout );
        }

        if ( $plain_length > 2400 ) {
            $timeout += (int) ceil( ( $plain_length - 2400 ) / 1200 ) * 10;
        }

        return min( 120, $timeout );
    }


    public function get_temperature() {
        $item = $this->get_model_settings();
        return max( 0, min( 2, (float) ( $item['temperature'] ?? 0 ) ) );
    }

    public function get_top_p() {
        $item = $this->get_model_settings();
        return max( 0, min( 1, (float) ( $item['top_p'] ?? 1 ) ) );
    }


    protected function get_custom_system_prompt() {
        $item = $this->get_model_settings();
        return trim( (string) ( $item['system_prompt'] ?? '' ) );
    }

    protected function append_custom_system_prompt( $prompt ) {
        $custom_prompt = $this->get_custom_system_prompt();
        if ( '' === $custom_prompt ) {
            return $prompt;
        }

        return trim( (string) $prompt ) . "

Additional engine instructions:
" . $custom_prompt;
    }

    protected function get_compatible_int_setting( $key, $default, $min = 0, $max = 0 ) {
        $item  = $this->get_model_settings();
        $value = isset( $item[ $key ] ) ? (int) $item[ $key ] : 0;

        if ( $value <= 0 ) {
            $value = (int) $default;
        }

        if ( $max > 0 ) {
            $value = min( $max, $value );
        }

        return max( $min, $value );
    }

    public function get_retry_count() {
        $item = $this->get_model_settings();
        return max( 0, min( 3, (int) ( $item['retry_count'] ?? 1 ) ) );
    }

    public function get_short_text_merge_threshold() {
        $item = $this->get_model_settings();
        return max( 0, (int) ( $item['short_text_merge_threshold'] ?? 0 ) );
    }

    protected function get_global_concurrency_limit() {
        return isset( $this->router_settings['global_concurrency_limit'] ) ? max( 0, (int) $this->router_settings['global_concurrency_limit'] ) : 0;
    }

    protected function resolve_effective_concurrency( $default_concurrency ) {
        $default_concurrency = max( 1, (int) $default_concurrency );
        $item                = $this->get_model_settings();
        $global_limit        = $this->get_global_concurrency_limit();
        $engine_concurrency  = isset( $item['concurrency'] ) ? (int) $item['concurrency'] : 0;

        if ( $engine_concurrency > 0 ) {
            return max( 1, $engine_concurrency );
        }

        if ( $global_limit > 0 ) {
            return max( 1, $global_limit );
        }

        return $default_concurrency;
    }

    public function get_api_key() {
        $item = $this->get_model_settings();
        return trim( (string) ( $item['api_key'] ?? '' ) );
    }

    public function get_model() {
        $item         = $this->get_model_settings();
        $custom_model = trim( (string) ( $item['custom_model'] ?? '' ) );
        if ( '' !== $custom_model ) {
            return $custom_model;
        }

        $model = trim( (string) ( $item['model'] ?? self::DEFAULT_MODEL ) );
        return '' !== $model ? $model : self::DEFAULT_MODEL;
    }

    protected function get_extra_body() {
        $item = $this->get_model_settings();
        $json = isset( $item['extra_body_json'] ) ? trim( (string) $item['extra_body_json'] ) : '';
        if ( '' === $json ) {
            return [];
        }

        $decoded = json_decode( $json, true );
        return is_array( $decoded ) ? $decoded : [];
    }

    protected function get_runtime_config() {
        $model  = strtolower( $this->get_model() );
        $item   = $this->get_model_settings();
        $config = [
            'batch_size'       => 8,
            'batch_max_chars'  => 1400,
            'concurrency'      => self::DEFAULT_CONCURRENCY,
            'max_tokens'       => 2200,
            'label_max_tokens' => 64,
        ];

        if ( false !== strpos( $model, 'mini' ) ) {
            $config['concurrency']      = 12;
            $config['max_tokens']       = 2200;
            $config['label_max_tokens'] = 64;
        }

        if ( false !== strpos( $model, 'nano' ) ) {
            $config['concurrency']      = 14;
            $config['max_tokens']       = 1600;
            $config['label_max_tokens'] = 48;
        }

        if ( false !== strpos( $model, '4.1' ) && false === strpos( $model, 'mini' ) && false === strpos( $model, 'nano' ) ) {
            $config['concurrency']      = 8;
            $config['max_tokens']       = 2400;
            $config['label_max_tokens'] = 72;
        }

        if ( ! empty( $item['max_tokens'] ) ) {
            $config['max_tokens'] = max( 128, (int) $item['max_tokens'] );
        }
        if ( ! empty( $item['short_text_merge_threshold'] ) ) {
            $config['batch_size']      = 8;
            $config['batch_max_chars'] = 1400;
        }

        if ( $this->is_openai_compatible_engine() ) {
            $config['concurrency']     = self::COMPATIBLE_DEFAULT_CONCURRENCY;
            $config['batch_size']      = $this->get_compatible_int_setting( 'batch_size', 6, 1, 50 );
            $config['batch_max_chars'] = $this->get_compatible_int_setting( 'batch_max_chars', 1200, 200 );
        }

        $config['concurrency'] = $this->resolve_effective_concurrency( $config['concurrency'] );

        if ( $this->is_openai_compatible_engine() ) {
            $label_max_tokens = isset( $item['label_max_tokens'] ) ? (int) $item['label_max_tokens'] : 0;
            if ( $label_max_tokens > 0 ) {
                $config['label_max_tokens'] = max( 16, min( 512, $label_max_tokens ) );
            } else {
                $config['label_max_tokens'] = max( 32, min( 256, (int) floor( $config['max_tokens'] / 32 ) ) );
            }
        } else {
            $config['label_max_tokens'] = max( 32, min( 256, (int) floor( $config['max_tokens'] / 32 ) ) );
        }

        return $config;
    }

    protected function build_headers() {
        $headers = [
            'Authorization' => 'Bearer ' . $this->get_api_key(),
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];

        $item        = $this->get_model_settings();
        $extra_lines = preg_split( '/\r\n|\r|\n/', (string) ( $item['extra_headers'] ?? '' ) );
        foreach ( $extra_lines as $line ) {
            $line = trim( (string) $line );
            if ( '' === $line || false === strpos( $line, ':' ) ) {
                continue;
            }

            list( $name, $value ) = array_map( 'trim', explode( ':', $line, 2 ) );
            if ( '' !== $name ) {
                $headers[ $name ] = $value;
            }
        }

        return $headers;
    }

    protected function merge_extra_body( array $payload ) {
        $extra = $this->get_extra_body();
        if ( empty( $extra ) ) {
            return $payload;
        }

        $reserved = [ 'messages', 'response_format', 'stream', 'n' ];
        foreach ( $reserved as $key ) {
            if ( array_key_exists( $key, $extra ) ) {
                unset( $extra[ $key ] );
            }
        }

        return array_replace_recursive( $payload, $extra );
    }

    protected function build_batch_payload( $source_language, $target_language, array $batch ) {
        $source_label = '' !== (string) $source_language ? $source_language : 'the source language shown in the input';
        $input_map    = $this->build_batch_input_map( $batch );
        $cfg          = $this->get_runtime_config();

        $system_prompt = 'You are a website localization translation engine. Translate each JSON value from ' . $source_label . ' to ' . $target_language . '. '
            . 'Return one JSON object only, using exactly the same keys and nothing else. '
            . 'Do not omit keys. Do not add keys. Do not explain meanings. Do not define terms. Do not answer questions. Do not add notes, examples, markdown, bullets, headings, quotes, prefixes, or suffixes. '
            . 'Every value must contain only the direct translation for that input value. '
            . 'Translate UI words, taxonomy labels, breadcrumbs, buttons, menu items, and metadata labels as normal website text. '
            . 'For very short labels, table cells, levels, or single CJK characters, choose the concise website label that best fits neighboring UI labels. Do not turn them into language names, country names, ethnicity labels, route names, or explanatory phrases unless the source explicitly means that. '
            . 'Preserve HTML tags, HTML entities, placeholders, shortcode syntax, line breaks, spacing around tags, and URLs exactly when they appear. '
            . 'Keep proper nouns and product names only when they should stay unchanged in normal website localization.';

        $payload = [
            'model' => $this->get_model(),
            'messages' => [
                [
                    'role'    => 'system',
                    'content' => $this->append_custom_system_prompt( $system_prompt ),
                ],
                [
                    'role'    => 'user',
                    'content' => "Return JSON only.
INPUT_JSON:
" . wp_json_encode( $input_map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
                ],
            ],
            'temperature' => $this->get_temperature(),
            'top_p'       => $this->get_top_p(),
            'max_tokens'  => (int) $cfg['max_tokens'],
            'response_format' => [
                'type' => 'json_object',
            ],
        ];

        return $this->merge_extra_body( $payload );
    }

    protected function build_single_payload( $source_language, $target_language, $text, $mode = 'text' ) {
        $source_label = '' !== (string) $source_language ? (string) $source_language : 'the source language shown in the input';
        $cfg          = $this->get_runtime_config();
        $text         = (string) $text;
        $has_html     = (bool) preg_match( '/<[^>]+>/', $text );
        $max_tokens   = max( 512, (int) $cfg['max_tokens'] );

        if ( 'label' === $mode ) {
            $system_prompt = 'You are a website translation engine. Return translation only. Never explain. Never answer questions. Keep the output short and natural for a website UI label. For very short labels or single CJK characters, prefer concise level/category wording that fits website tables and UI. Do not output language names, country names, route names, or explanations unless the source explicitly means that.';
            $user_prompt   = 'Translate the website UI label from ' . $source_label . ' to ' . $target_language . '. Return translation only. Do not add notes, quotes, examples, Markdown, numbering, or extra punctuation. If the source is already in the target language, return it unchanged.' . "

SOURCE_TEXT:
" . $text;
            $max_tokens    = max( 32, (int) ( $cfg['label_max_tokens'] ?? 64 ) );
        } elseif ( $has_html ) {
            $system_prompt = 'You are a website translation engine. Return translation only. Preserve HTML structure exactly.';
            $user_prompt   = 'Translate the HTML fragment from ' . $source_label . ' to ' . $target_language . '. Translate readable text only. Preserve all HTML tags, attributes, URLs, shortcodes, placeholders, HTML entities, whitespace-sensitive line breaks, and formatting exactly. Return the translated HTML fragment only.' . "

HTML_FRAGMENT:
" . $text;
        } else {
            $system_prompt = 'You are a translation engine. Return translation only. Never explain, summarize, or answer the text. For very short labels or single CJK characters, prefer concise website wording that fits surrounding UI text, not language names, country names, route names, or explanations unless the source explicitly means that.';
            $user_prompt   = 'Translate the text from ' . $source_label . ' to ' . $target_language . '. Return translation only. Do not add notes, examples, Markdown, or quotation marks. If the source is already in the target language, return it unchanged.' . "

SOURCE_TEXT:
" . $text;
        }

        if ( false !== stripos( (string) $target_language, 'Japanese' ) ) {
            $user_prompt .= "

Use natural, concise Japanese website copy. Do not explain terms or add any extra commentary.";
        }

        $payload = [
            'model' => $this->get_model(),
            'messages' => [
                [
                    'role'    => 'system',
                    'content' => $this->append_custom_system_prompt( $system_prompt ),
                ],
                [
                    'role'    => 'user',
                    'content' => $user_prompt,
                ],
            ],
            'temperature' => $this->get_temperature(),
            'top_p'       => $this->get_top_p(),
            'max_tokens'  => $max_tokens,
        ];

        return $this->merge_extra_body( $payload );
    }

    protected function build_batch_input_map( array $batch ) {
        $input_map = [];
        foreach ( array_values( $batch ) as $index => $item ) {
            $input_map[ (string) $index ] = (string) $item['text'];
        }
        return $input_map;
    }

    protected function build_request_body_json( array $payload ) {
        return wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    }

    protected function send_payload( array $payload, $timeout = null ) {
        $request_timeout = null === $timeout ? $this->get_timeout() : max( 5, (int) $timeout );

        $args = [
            'timeout' => $request_timeout,
            'headers' => $this->build_headers(),
            'body'    => $this->build_request_body_json( $payload ),
        ];

        return wp_remote_post( $this->get_endpoint_url(), $args );
    }

    protected function send_batch_request( $source_language, $target_language, array $batch ) {
        return $this->send_payload( $this->build_batch_payload( $source_language, $target_language, $batch ) );
    }

    protected function send_single_request( $source_language, $target_language, $text, $mode = 'text', $timeout = null ) {
        return $this->send_payload( $this->build_single_payload( $source_language, $target_language, $text, $mode ), $timeout );
    }

    protected function looks_like_pure_url_or_email( $text ) {
        $text = trim( (string) $text );
        if ( '' === $text ) {
            return false;
        }

        if ( preg_match( '#^https?://[^\s]+$#i', $text ) ) {
            return true;
        }

        if ( preg_match( '/^[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}$/i', $text ) ) {
            return true;
        }

        return false;
    }

    protected function looks_like_technical_identifier_only( $text ) {
        $text = trim( (string) $text );
        if ( '' === $text ) {
            return false;
        }

        $plain = preg_replace( '/\s+/u', ' ', $text );
        $len   = $this->get_plain_text_length( $plain );
        if ( $len > 80 ) {
            return false;
        }

        if ( preg_match( '#^[A-Za-z0-9_.:/-]+(?:\s*\([A-Za-z0-9_.:/-]+\))?$#', $plain ) ) {
            return true;
        }

        if ( preg_match( '/^[A-Za-z_][A-Za-z0-9_]*(?:\(\))?$/', $plain ) ) {
            return true;
        }

        return false;
    }

    protected function looks_like_code_or_config_fragment( $text ) {
        $text = trim( (string) $text );
        if ( '' === $text ) {
            return false;
        }

        if ( preg_match( '/^(?:<\?php|\{\s*"[^"]+"\s*:|\[\s*\{|\{\s*\[)/u', $text ) ) {
            return true;
        }

        $strong_patterns = [
            '/=>/',
            '/\$[A-Za-z_][A-Za-z0-9_]*/',
            '/->[A-Za-z_][A-Za-z0-9_]*/',
            '/::[A-Za-z_][A-Za-z0-9_]*/',
            '/\b(?:add_action|add_filter|apply_filters|do_action|register_sidebar|get_template_part|update_post_meta|get_post_meta|wp_enqueue_[a-z_]+|esc_html__|esc_attr__|__|_e)\s*\(/i',
            '/\bfunction\s+[A-Za-z_][A-Za-z0-9_]*\s*\(/i',
            '/^[\'"]\s*,?$/',
            '/^\s*[A-Za-z0-9_\-]+\s*=>\s*[\'"]?/u',
        ];
        foreach ( $strong_patterns as $pattern ) {
            if ( preg_match( $pattern, $text ) ) {
                return true;
            }
        }

        $plain = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $text ) ) );
        $line_count = preg_match_all( '/\R/u', $text, $matches );
        if ( $line_count >= 1 ) {
            $code_hits = 0;
            $line_items = preg_split( '/\R/u', $text );
            foreach ( $line_items as $line ) {
                $line = trim( (string) $line );
                if ( '' === $line ) {
                    continue;
                }
                if ( preg_match( '/(?:=>|\$[A-Za-z_]|->[A-Za-z_]|::[A-Za-z_]|;\s*$|^\s*[{}()\[\]]+\s*$|__\s*\(|_e\s*\()/u', $line ) ) {
                    $code_hits++;
                }
            }
            if ( $code_hits >= 2 ) {
                return true;
            }
        }

        if ( preg_match( '/^[A-Za-z0-9_\-]+(?:\s+[A-Za-z0-9_\-]+){0,3}$/', $plain ) ) {
            $keywords = [
                'before_title','after_title','before_widget','after_widget','widgets_init','sidebar','shortcode','oembed','json','xml',
                'rest','nonce','ajax','callback','endpoint','content_type','post_meta','hook','filter','action','template','stylesheet'
            ];
            foreach ( $keywords as $keyword ) {
                if ( false !== stripos( $plain, $keyword ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function looks_like_internal_log_or_debug_text( $text ) {
        $text = trim( (string) $text );
        if ( '' === $text ) {
            return false;
        }

        if ( false !== strpos( $text, '#!trpst#trp-gettext' ) || false !== strpos( $text, 'data-trpgettextoriginal=' ) ) {
            return true;
        }

        $patterns = [
            '/\bOpenAI\s+translate_batch\s+速度版(?:开始|结束)\b/u',
            '/\bOpenAI\s+批量翻译返回\b/u',
            '/\bOpenAI\s+单条并发兜底(?:开始|返回)\b/u',
            '/\bOpenAI\s+单条兜底失败\b/u',
            '/\bOpenAI\s+单条最终兜底失败\b/u',
            '/\bOpenAI\s+单条(?:重试|回退)\b/u',
            '/\b(OpenAI|Qwen|DeepL|Hunyuan|火山方舟)\s+(?:翻译|translate_batch|批量翻译|单条并发兜底)/u',
            '/^\s*\{\s*"error"\s*:\s*\{.*\}\s*\}\s*$/us',
        ];

        foreach ( $patterns as $pattern ) {
            if ( preg_match( $pattern, $text ) ) {
                return true;
            }
        }

        return false;
    }

    protected function should_skip_text( $text ) {
        $raw_text = html_entity_decode( (string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $text     = trim( wp_strip_all_tags( $raw_text ) );
        if ( '' === $text ) {
            return true;
        }

        if ( ! preg_match( '/\p{L}/u', $text ) ) {
            return true;
        }

        if ( $this->looks_like_internal_log_or_debug_text( $raw_text ) || $this->looks_like_internal_log_or_debug_text( $text ) ) {
            return true;
        }

        if ( $this->looks_like_pure_url_or_email( $text ) ) {
            return true;
        }

        if ( $this->looks_like_technical_identifier_only( $text ) ) {
            return true;
        }

        if ( preg_match( '/^oembed\s*\((json|xml)\)$/i', $text ) ) {
            return true;
        }

        if ( $this->looks_like_code_or_config_fragment( $raw_text ) ) {
            return true;
        }

        return false;
    }

    protected function get_plain_text_length( $text ) {
        $plain = html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $plain = trim( preg_replace( '/\s+/u', ' ', $plain ) );
        if ( function_exists( 'mb_strlen' ) ) {
            return mb_strlen( $plain, 'UTF-8' );
        }
        return strlen( $plain );
    }

    protected function get_plain_word_count( $text ) {
        $plain = html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $plain = trim( preg_replace( '/\s+/u', ' ', $plain ) );
        if ( '' === $plain ) {
            return 0;
        }

        preg_match_all( '/[\p{L}\p{N}]+/u', $plain, $matches );
        return isset( $matches[0] ) ? count( $matches[0] ) : 0;
    }

    protected function is_label_like_text( $text ) {
        $plain = html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $plain = trim( preg_replace( '/\s+/u', ' ', $plain ) );
        if ( '' === $plain ) {
            return false;
        }

        $length               = $this->get_plain_text_length( $plain );
        $word_count           = $this->get_plain_word_count( $plain );
        $has_breaks           = ( false !== strpos( (string) $text, "
" ) || false !== strpos( (string) $text, "
" ) );
        $sentence_punctuation = preg_match( '/[\.!?。！？：:；;]/u', $plain );
        $breadcrumb_like      = preg_match( '#(/|>|»|›|\\|)#u', $plain );
        $ui_keywords          = preg_match( '/\b(author|authors|category|categories|tag|tags|search|menu|home|archive|archives|read more|next|previous|latest|posted|comments?)\b/i', $plain );

        if ( $has_breaks ) {
            return false;
        }

        if ( $ui_keywords ) {
            return true;
        }

        if ( $breadcrumb_like && $length <= 80 ) {
            return true;
        }

        if ( $length <= 40 && $word_count <= 6 && ! $sentence_punctuation ) {
            return true;
        }

        return false;
    }

    protected function get_string_length( $text ) {
        if ( function_exists( 'mb_strlen' ) ) {
            return mb_strlen( (string) $text, 'UTF-8' );
        }

        return strlen( (string) $text );
    }

    protected function get_string_substr( $text, $start, $length = null ) {
        if ( function_exists( 'mb_substr' ) ) {
            if ( null === $length ) {
                return mb_substr( (string) $text, (int) $start, null, 'UTF-8' );
            }

            return mb_substr( (string) $text, (int) $start, (int) $length, 'UTF-8' );
        }

        if ( null === $length ) {
            return substr( (string) $text, (int) $start );
        }

        return substr( (string) $text, (int) $start, (int) $length );
    }

    protected function split_text_preserving_separator( $text, $separator ) {
        $text      = (string) $text;
        $separator = (string) $separator;
        if ( '' === $text || '' === $separator ) {
            return [ $text ];
        }

        $tokens = preg_split( '/(' . preg_quote( $separator, '/' ) . ')/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE );
        if ( ! is_array( $tokens ) || empty( $tokens ) ) {
            return [ $text ];
        }

        $parts = [];
        $count = count( $tokens );
        for ( $i = 0; $i < $count; $i += 2 ) {
            $part = (string) $tokens[ $i ];
            if ( ( $i + 1 ) < $count ) {
                $part .= (string) $tokens[ $i + 1 ];
            }

            if ( '' !== $part ) {
                $parts[] = $part;
            }
        }

        return ! empty( $parts ) ? $parts : [ $text ];
    }

    protected function split_text_by_separators( $text, $max_chars, array $separators, $allow_hard_split = true, $separator_index = 0 ) {
        $text      = (string) $text;
        $max_chars = max( 200, (int) $max_chars );

        if ( $this->get_string_length( $text ) <= $max_chars ) {
            return [ $text ];
        }

        if ( $separator_index >= count( $separators ) ) {
            if ( ! $allow_hard_split ) {
                return [ $text ];
            }

            $chunks = [];
            $length = $this->get_string_length( $text );
            $offset = 0;

            while ( $offset < $length ) {
                $chunks[] = $this->get_string_substr( $text, $offset, $max_chars );
                $offset  += $max_chars;
            }

            return $chunks;
        }

        $pieces = $this->split_text_preserving_separator( $text, $separators[ $separator_index ] );
        if ( count( $pieces ) <= 1 ) {
            return $this->split_text_by_separators( $text, $max_chars, $separators, $allow_hard_split, $separator_index + 1 );
        }

        $chunks = [];
        $buffer = '';

        foreach ( $pieces as $piece ) {
            if ( $this->get_string_length( $piece ) > $max_chars ) {
                if ( '' !== $buffer ) {
                    $chunks[] = $buffer;
                    $buffer   = '';
                }

                $nested_chunks = $this->split_text_by_separators( $piece, $max_chars, $separators, $allow_hard_split, $separator_index + 1 );
                foreach ( $nested_chunks as $nested_chunk ) {
                    $chunks[] = $nested_chunk;
                }
                continue;
            }

            if ( '' !== $buffer && ( $this->get_string_length( $buffer ) + $this->get_string_length( $piece ) ) > $max_chars ) {
                $chunks[] = $buffer;
                $buffer   = '';
            }

            $buffer .= $piece;
        }

        if ( '' !== $buffer ) {
            $chunks[] = $buffer;
        }

        if ( count( $chunks ) <= 1 ) {
            return $this->split_text_by_separators( $text, $max_chars, $separators, $allow_hard_split, $separator_index + 1 );
        }

        return $chunks;
    }

    protected function split_large_text_into_chunks( $text ) {
        $text     = (string) $text;
        $has_html = (bool) preg_match( '/<[^>]+>/', $text );

        if ( $has_html ) {
            $chunks = $this->split_text_by_separators(
                $text,
                $this->get_compatible_int_setting( 'long_html_chunk_chars', self::COMPATIBLE_LONG_HTML_CHUNK_CHARS, 200 ),
                [ '</p>', '</li>', '</div>', '</section>', '</article>', '</h1>', '</h2>', '</h3>', '<br>', '<br/>', '<br />', "\n\n" ],
                false
            );

            return array_values( array_filter( $chunks, static function( $chunk ) {
                return '' !== trim( wp_strip_all_tags( (string) $chunk ) );
            } ) );
        }

        $chunks = $this->split_text_by_separators(
            $text,
            $this->get_compatible_int_setting( 'long_text_chunk_chars', self::COMPATIBLE_LONG_TEXT_CHUNK_CHARS, 200 ),
            [ "\n\n", "\n", '。', '！', '？', '. ', '! ', '? ', '; ', '；', ', ', '，', ' ' ],
            true
        );

        return array_values( array_filter( $chunks, static function( $chunk ) {
            return '' !== trim( (string) $chunk );
        } ) );
    }

    protected function should_chunk_large_item( array $item, $mode = 'text' ) {
        if ( ! $this->is_openai_compatible_engine() || 'label' === $mode ) {
            return false;
        }

        if ( $this->get_plain_text_length( $item['text'] ) < $this->get_compatible_int_setting( 'long_text_threshold', self::COMPATIBLE_LONG_TEXT_THRESHOLD, 400 ) ) {
            return false;
        }

        $chunks = $this->split_large_text_into_chunks( $item['text'] );
        return count( $chunks ) > 1;
    }

    protected function translate_large_item_in_chunks( array $item, $source_language, $target_language, $target_language_code ) {
        $chunks = $this->split_large_text_into_chunks( $item['text'] );
        if ( count( $chunks ) <= 1 ) {
            return [ 'success' => false, 'translated' => '' ];
        }

        $this->logger->debug( __( 'OpenAI 长文本切块翻译开始', 'langrouter-for-translatepress' ), [
            'target_lang' => $target_language_code,
            'text_len'    => $this->get_plain_text_length( $item['text'] ),
            'chunk_count' => count( $chunks ),
        ] );

        $translated_chunks = [];

        foreach ( array_values( $chunks ) as $index => $chunk ) {
            $chunk_response = $this->send_single_request(
                $source_language,
                $target_language,
                $chunk,
                'text',
                $this->get_single_request_timeout( $chunk, 'text' )
            );

            $chunk_item = [
                'id'   => $item['id'] . '_chunk_' . $index,
                'text' => $chunk,
                'keys' => $item['keys'],
            ];

            $chunk_result = $this->build_single_result_from_response( $chunk_response, $chunk_item, $target_language_code );
            if ( empty( $chunk_result['success'] ) ) {
                $this->logger->error( __( 'OpenAI 长文本切块翻译失败', 'langrouter-for-translatepress' ), [
                    'target_lang'  => $target_language_code,
                    'chunk_index'  => $index,
                    'chunk_count'  => count( $chunks ),
                    'chunk_length' => $this->get_plain_text_length( $chunk ),
                ] );

                return [ 'success' => false, 'translated' => '' ];
            }

            $translated_chunks[] = $chunk_result['translated'];
        }

        $translated_text = $this->sanitize_translation_candidate( implode( '', $translated_chunks ) );
        if ( '' === $translated_text ) {
            return [ 'success' => false, 'translated' => '' ];
        }

        return [ 'success' => true, 'translated' => $translated_text ];
    }

    protected function get_effective_single_request_concurrency( array $items ) {
        $base = $this->get_concurrency();
        if ( empty( $items ) ) {
            return max( 1, $base );
        }

        if ( ! $this->is_openai_compatible_engine() ) {
            return min( max( 2, $base ), count( $items ) );
        }

        $max_length = 0;
        foreach ( $items as $item ) {
            $max_length = max( $max_length, $this->get_plain_text_length( $item['text'] ) );
        }

        $medium_threshold = $this->get_compatible_int_setting( 'long_text_medium_threshold', 1600, 400 );
        $large_threshold  = $this->get_compatible_int_setting( 'long_text_large_threshold', 2400, 400 );
        $extreme_threshold = $this->get_compatible_int_setting( 'long_text_extreme_threshold', 3200, 400 );

        $medium_cap  = $this->get_compatible_int_setting( 'long_text_concurrency_medium', 4, 1, 32 );
        $large_cap   = $this->get_compatible_int_setting( 'long_text_concurrency_large', 3, 1, 32 );
        $extreme_cap = $this->get_compatible_int_setting( 'long_text_concurrency_extreme', 2, 1, 32 );

        if ( $extreme_threshold >= $large_threshold ) {
            if ( $max_length >= $extreme_threshold ) {
                $base = min( $base, $extreme_cap );
            } elseif ( $max_length >= $large_threshold ) {
                $base = min( $base, $large_cap );
            } elseif ( $max_length >= $medium_threshold ) {
                $base = min( $base, $medium_cap );
            }
        }

        return min( max( 1, $base ), count( $items ) );
    }

    protected function get_response_content_text_from_body( $body ) {
        $body = (string) $body;
        if ( '' === $body ) {
            return '';
        }

        $decoded = json_decode( $body, true );
        if ( ! is_array( $decoded ) ) {
            return trim( $body );
        }

        if ( isset( $decoded['choices'][0]['message']['content'] ) ) {
            $content = $decoded['choices'][0]['message']['content'];
            if ( is_array( $content ) ) {
                $parts = [];
                foreach ( $content as $part ) {
                    if ( is_array( $part ) && isset( $part['text'] ) ) {
                        $parts[] = (string) $part['text'];
                    } elseif ( is_string( $part ) ) {
                        $parts[] = $part;
                    }
                }
                return trim( implode( '', $parts ) );
            }

            return trim( (string) $content );
        }

        if ( isset( $decoded['output_text'] ) ) {
            return trim( (string) $decoded['output_text'] );
        }

        if ( isset( $decoded['output'][0]['content'][0]['text'] ) ) {
            return trim( (string) $decoded['output'][0]['content'][0]['text'] );
        }

        if ( $this->is_provider_response_id_value( isset( $decoded['id'] ) ? $decoded['id'] : '' ) ) {
            return '';
        }

        return trim( $body );
    }

    protected function get_provider_error_from_body( $body ) {
        $body = (string) $body;
        if ( '' === $body ) {
            return [];
        }

        $decoded = json_decode( $body, true );
        if ( ! is_array( $decoded ) ) {
            return [];
        }

        if ( isset( $decoded['error'] ) && is_array( $decoded['error'] ) ) {
            $error = $decoded['error'];
            return [
                'message' => isset( $error['message'] ) ? trim( (string) $error['message'] ) : '',
                'type'    => isset( $error['type'] ) ? trim( (string) $error['type'] ) : '',
                'code'    => isset( $error['code'] ) ? trim( (string) $error['code'] ) : '',
            ];
        }

        if (
            isset( $decoded['message'] ) && is_scalar( $decoded['message'] )
            && isset( $decoded['type'] ) && is_scalar( $decoded['type'] )
            && ! isset( $decoded['choices'] )
        ) {
            return [
                'message' => trim( (string) $decoded['message'] ),
                'type'    => trim( (string) $decoded['type'] ),
                'code'    => isset( $decoded['code'] ) ? trim( (string) $decoded['code'] ) : '',
            ];
        }

        return [];
    }

    protected function format_provider_error_for_log( array $provider_error ) {
        if ( empty( $provider_error ) ) {
            return '';
        }

        $parts = [];
        if ( ! empty( $provider_error['type'] ) ) {
            $parts[] = $provider_error['type'];
        }
        if ( ! empty( $provider_error['code'] ) ) {
            $parts[] = 'code=' . $provider_error['code'];
        }
        if ( ! empty( $provider_error['message'] ) ) {
            $parts[] = $provider_error['message'];
        }

        return implode( '; ', $parts );
    }

    protected function is_provider_response_id_value( $value ) {
        return is_scalar( $value ) && 1 === preg_match( '/^resp_[A-Za-z0-9]+$/', trim( (string) $value ) );
    }

    protected function sanitize_translation_candidate( $text ) {
        $text = trim( (string) $text );
        $text = preg_replace( '/^```(?:json|text)?\s*/iu', '', $text );
        $text = preg_replace( '/\s*```$/u', '', $text );
        return trim( (string) $text );
    }

    protected function looks_like_explanation_output( $text ) {
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
            '/(?:關於|关于).{0,8}(?:這個詞|这个词|此词|詞語|词语)/u',
            '/(?:意思是|意為|意为|主な使い方|使用情境|使用場景|以下にまとめました|簡単に言うと|具体来说|具體來說|例如|例：|用法示例)/u',
            '/(?:在中文里也常被使用|通常指|多用於|多用于|文脈によって意味が異なります)/u',
            '/^#{1,6}\s/u',
            '/(?:^|\n)\s*[-*•]\s+/u',
        ];

        foreach ( $patterns as $pattern ) {
            if ( preg_match( $pattern, $text ) ) {
                return true;
            }
        }

        return false;
    }

    protected function source_allows_structured_output( $source_text ) {
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

    protected function normalize_for_compare( $text ) {
        $text = strtolower( html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
        $text = preg_replace( '/\s+/u', ' ', $text );
        $text = preg_replace( '/[^\p{L}\p{N}\s]+/u', '', $text );
        return trim( (string) $text );
    }

    protected function has_unexpected_cjk_mix( $source_text, $translated_text, $target_language_code ) {
        $target_language_code = strtolower( (string) $target_language_code );
        if ( in_array( $target_language_code, [ 'zh', 'zh_cn', 'zh_tw', 'zh-hant', 'ja', 'ko', 'yue' ], true ) ) {
            return false;
        }

        $source_has_cjk     = (bool) preg_match( '/[\x{3040}-\x{30ff}\x{3400}-\x{4dbf}\x{4e00}-\x{9fff}\x{f900}-\x{faff}]/u', (string) $source_text );
        $translated_has_cjk = (bool) preg_match( '/[\x{3040}-\x{30ff}\x{3400}-\x{4dbf}\x{4e00}-\x{9fff}\x{f900}-\x{faff}]/u', (string) $translated_text );

        return $translated_has_cjk && ! $source_has_cjk;
    }

    protected function extract_significant_words( $text ) {
        $text = strtolower( html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
        preg_match_all( '/\b[\p{Latin}][\p{Latin}\-\']{3,}\b/u', $text, $matches );
        $words = isset( $matches[0] ) ? array_values( array_unique( $matches[0] ) ) : [];

        $stop_words = [
            'this','that','these','those','with','from','your','them','theme','themes','have','will','into','more','just','than','only','when','where',
            'what','which','about','into','also','make','much','very','does','should','would','could','there','their','then','than','while','because',
            'website','wordpress','using','used','user','users','content','guide','best','right','left','read','next','prev','previous','latest','tags',
        ];

        return array_values( array_diff( $words, $stop_words ) );
    }

    protected function has_excessive_source_overlap( $source_text, $translated_text, $target_language_code ) {
        $target_language_code = strtolower( (string) $target_language_code );
        if ( 'en' === $target_language_code ) {
            return false;
        }

        $source_words = $this->extract_significant_words( $source_text );
        if ( count( $source_words ) < 3 ) {
            return false;
        }

        $translated_normalized = strtolower( html_entity_decode( wp_strip_all_tags( (string) $translated_text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
        $hits                  = 0;
        foreach ( $source_words as $word ) {
            if ( false !== strpos( $translated_normalized, $word ) ) {
                $hits++;
            }
        }

        return $hits >= 3 && ( $hits / count( $source_words ) ) >= 0.70;
    }

    protected function is_suspicious_length_or_format( $source_text, $translated_text ) {
        $source_plain     = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( (string) $source_text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
        $translated_plain = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( (string) $translated_text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );

        if ( '' === $translated_plain ) {
            return true;
        }

        $source_len     = $this->get_plain_text_length( $source_plain );
        $translated_len = $this->get_plain_text_length( $translated_plain );

        if ( $this->is_label_like_text( $source_text ) ) {
            if ( $translated_len > max( 72, $source_len * 6 ) ) {
                return true;
            }
            if ( preg_match( '/[\r\n]/', $translated_plain ) ) {
                return true;
            }
            if ( preg_match( '/(?:^|\n)\s*[-*•]\s+/u', $translated_plain ) ) {
                return true;
            }
        } elseif ( $source_len > 0 && $translated_len > max( 600, $source_len * 9 ) ) {
            return true;
        }

        if ( preg_match( '/^#{1,6}\s/u', $translated_plain ) ) {
            return true;
        }

        return false;
    }

    protected function get_translation_validation_result( $source_text, $translated_text, $target_language_code ) {
        $raw_translated_text = (string) $translated_text;
        $translated_text     = $this->sanitize_translation_candidate( $translated_text );

        $result = [
            'ok'                       => true,
            'reason'                   => '',
            'raw_candidate_preview'    => $this->truncate_for_log( $raw_translated_text ),
            'candidate_preview'        => $this->truncate_for_log( $translated_text ),
            'source_preview'           => $this->truncate_for_log( $source_text ),
            'source_plain_length'      => $this->get_plain_text_length( $source_text ),
            'translated_plain_length'  => $this->get_plain_text_length( $translated_text ),
            'target_language_code'     => (string) $target_language_code,
            'normalized_source'        => '',
            'normalized_translated'    => '',
        ];

        if ( '' === $translated_text ) {
            $result['ok']     = false;
            $result['reason'] = 'empty_after_sanitize';
            return $result;
        }

        $source_allows_structured_output = $this->source_allows_structured_output( $source_text );

        if ( ! $source_allows_structured_output && $this->looks_like_explanation_output( $translated_text ) ) {
            $result['ok']     = false;
            $result['reason'] = 'looks_like_explanation_output';
            return $result;
        }

        $normalized_source                  = $this->normalize_for_compare( $source_text );
        $normalized_translated              = $this->normalize_for_compare( $translated_text );
        $result['normalized_source']        = $this->truncate_for_log( $normalized_source, 120 );
        $result['normalized_translated']    = $this->truncate_for_log( $normalized_translated, 120 );
        if ( '' !== $normalized_source && $normalized_source === $normalized_translated ) {
            $result['ok']     = false;
            $result['reason'] = 'same_as_source_after_normalize';
            return $result;
        }

        if ( $this->has_unexpected_cjk_mix( $source_text, $translated_text, $target_language_code ) ) {
            $result['ok']     = false;
            $result['reason'] = 'has_unexpected_cjk_mix';
            return $result;
        }

        if ( $this->is_suspicious_length_or_format( $source_text, $translated_text ) ) {
            $result['ok']     = false;
            $result['reason'] = 'is_suspicious_length_or_format';
            return $result;
        }

        if ( class_exists( 'TPRE_Translation_Safety_Utils' ) ) {
            if ( TPRE_Translation_Safety_Utils::has_dangerous_markup_mismatch( $source_text, $translated_text ) ) {
                $result['ok']     = false;
                $result['reason'] = 'has_dangerous_markup_mismatch';
                return $result;
            }

            if ( ! $source_allows_structured_output && TPRE_Translation_Safety_Utils::looks_like_explanation_output( $translated_text ) ) {
                $result['ok']     = false;
                $result['reason'] = 'safety_utils_looks_like_explanation_output';
                return $result;
            }

            if ( TPRE_Translation_Safety_Utils::has_excessive_label_expansion( $source_text, $translated_text ) ) {
                $result['ok']     = false;
                $result['reason'] = 'has_excessive_label_expansion';
                return $result;
            }

            if ( TPRE_Translation_Safety_Utils::has_unexpected_cjk_leak( $source_text, $translated_text, $target_language_code ) ) {
                $result['ok']     = false;
                $result['reason'] = 'has_unexpected_cjk_leak';
                return $result;
            }

            if ( TPRE_Translation_Safety_Utils::is_suspicious_translation_output( $source_text, $translated_text, $target_language_code ) ) {
                $result['ok']     = false;
                $result['reason'] = 'is_suspicious_translation_output';
                return $result;
            }
        }

        return $result;
    }

    protected function is_valid_translation( $source_text, $translated_text, $target_language_code ) {
        $validation = $this->get_translation_validation_result( $source_text, $translated_text, $target_language_code );
        return ! empty( $validation['ok'] );
    }

    protected function truncate_for_log( $text, $limit = 240 ) {
        $text = trim( wp_strip_all_tags( (string) $text ) );
        if ( function_exists( 'mb_strlen' ) ) {
            if ( mb_strlen( $text, 'UTF-8' ) > $limit ) {
                return mb_substr( $text, 0, $limit, 'UTF-8' ) . '...';
            }
            return $text;
        }
        if ( strlen( $text ) > $limit ) {
            return substr( $text, 0, $limit ) . '...';
        }
        return $text;
    }

    protected function extract_json_object_text( $text ) {
        $text = trim( $this->sanitize_translation_candidate( $text ) );
        if ( '' === $text ) {
            return '';
        }

        $decoded = json_decode( $text, true );
        if ( is_array( $decoded ) ) {
            return $text;
        }

        $length     = strlen( $text );
        $depth      = 0;
        $start      = null;
        $in_string  = false;
        $escape     = false;

        for ( $i = 0; $i < $length; $i++ ) {
            $char = $text[ $i ];
            if ( $in_string ) {
                if ( $escape ) {
                    $escape = false;
                    continue;
                }
                if ( '\\' === $char ) {
                    $escape = true;
                    continue;
                }
                if ( '"' === $char ) {
                    $in_string = false;
                }
                continue;
            }

            if ( '"' === $char ) {
                $in_string = true;
                continue;
            }

            if ( '{' === $char ) {
                if ( 0 === $depth ) {
                    $start = $i;
                }
                $depth++;
                continue;
            }

            if ( '}' === $char && $depth > 0 ) {
                $depth--;
                if ( 0 === $depth && null !== $start ) {
                    return substr( $text, $start, $i - $start + 1 );
                }
            }
        }

        return '';
    }

    protected function parse_batch_translations( $body, array $batch, $target_language_code ) {
        $provider_error = $this->get_provider_error_from_body( $body );
        if ( ! empty( $provider_error ) ) {
            return [
                'ok'             => false,
                'reason'         => 'provider_error_payload',
                'content'        => wp_json_encode( [ 'error' => $provider_error ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
                'provider_error' => $provider_error,
                'results'        => $this->build_empty_results_for_batch( $batch ),
            ];
        }

        $content    = $this->get_response_content_text_from_body( $body );
        $json_text  = $this->extract_json_object_text( $content );
        $decoded    = '' !== $json_text ? json_decode( $json_text, true ) : null;
        $item_count = count( $batch );

        if ( ! is_array( $decoded ) ) {
            return [
                'ok'      => false,
                'reason'  => 'invalid_json_object',
                'content' => $content,
                'results' => $this->build_empty_results_for_batch( $batch ),
            ];
        }

        $results = [];
        for ( $i = 0; $i < $item_count; $i++ ) {
            $key = (string) $i;
            if ( ! array_key_exists( $key, $decoded ) ) {
                return [
                    'ok'      => false,
                    'reason'  => 'missing_batch_key',
                    'failed_key' => $key,
                    'content' => $content,
                    'results' => $this->build_empty_results_for_batch( $batch ),
                ];
            }

            if ( ! is_scalar( $decoded[ $key ] ) ) {
                return [
                    'ok'      => false,
                    'reason'  => 'non_scalar_batch_value',
                    'failed_key' => $key,
                    'content' => $content,
                    'results' => $this->build_empty_results_for_batch( $batch ),
                ];
            }

            $item       = $batch[ $i ];
            $candidate  = $this->sanitize_translation_candidate( (string) $decoded[ $key ] );
            $validation = $this->get_translation_validation_result( $item['text'], $candidate, $target_language_code );
            if ( empty( $validation['ok'] ) ) {
                return [
                    'ok'         => false,
                    'reason'     => 'validation_failed',
                    'failed_key' => $key,
                    'content'    => $content,
                    'validation' => $validation,
                    'results'    => $this->build_empty_results_for_batch( $batch ),
                ];
            }

            $results[ $item['id'] ] = [
                'success'    => true,
                'translated' => $candidate,
            ];
        }

        return [
            'ok'      => true,
            'reason'  => '',
            'content' => $content,
            'results' => $results,
        ];
    }

    protected function parse_single_translation( $body, array $item, $target_language_code ) {
        $provider_error = $this->get_provider_error_from_body( $body );
        if ( ! empty( $provider_error ) ) {
            return [
                'ok'             => false,
                'reason'         => 'provider_error_payload',
                'content'        => wp_json_encode( [ 'error' => $provider_error ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
                'provider_error' => $provider_error,
                'result'         => [ 'success' => false, 'translated' => '' ],
            ];
        }

        $content   = $this->get_response_content_text_from_body( $body );
        $candidate = $this->sanitize_translation_candidate( $content );

        if ( '' !== $candidate ) {
            $json_text = $this->extract_json_object_text( $candidate );
            if ( '' !== $json_text ) {
                $decoded = json_decode( $json_text, true );
                if ( is_array( $decoded ) ) {
                    $first = reset( $decoded );
                    if ( is_scalar( $first ) ) {
                        $candidate = $this->sanitize_translation_candidate( (string) $first );
                    }
                }
            }
        }

        if ( $this->is_provider_response_id_value( $candidate ) ) {
            return [
                'ok'      => false,
                'reason'  => 'provider_response_id_value',
                'content' => $content,
                'result'  => [ 'success' => false, 'translated' => '' ],
            ];
        }

        $validation = $this->get_translation_validation_result( $item['text'], $candidate, $target_language_code );
        if ( empty( $validation['ok'] ) ) {
            return [
                'ok'         => false,
                'reason'     => 'validation_failed',
                'content'    => $content,
                'validation' => $validation,
                'result'     => [ 'success' => false, 'translated' => '' ],
            ];
        }

        return [
            'ok'      => true,
            'reason'  => '',
            'content' => $content,
            'result'  => [ 'success' => true, 'translated' => $candidate ],
        ];
    }

    protected function split_into_batches( array $items ) {
        $cfg           = $this->get_runtime_config();
        $batch_size    = max( 1, (int) $cfg['batch_size'] );
        $chars_limit   = max( 200, (int) $cfg['batch_max_chars'] );
        $batches       = [];
        $current_batch = [];
        $current_chars = 0;

        foreach ( array_values( $items ) as $item ) {
            $text_len     = function_exists( 'mb_strlen' ) ? mb_strlen( (string) $item['text'], 'UTF-8' ) : strlen( (string) $item['text'] );
            $would_exceed = ! empty( $current_batch ) && (
                count( $current_batch ) >= $batch_size ||
                ( $current_chars + $text_len ) > $chars_limit
            );

            if ( $would_exceed ) {
                $batches[]     = array_values( $current_batch );
                $current_batch = [];
                $current_chars = 0;
            }

            $current_batch[] = $item;
            $current_chars  += $text_len;
        }

        if ( ! empty( $current_batch ) ) {
            $batches[] = array_values( $current_batch );
        }

        return $batches;
    }

    protected function build_empty_results_for_batch( array $batch ) {
        $results = [];
        foreach ( $batch as $item ) {
            $results[ $item['id'] ] = [ 'success' => false, 'translated' => '' ];
        }
        return $results;
    }


    protected function split_items_by_short_text_threshold( array $items ) {
        $threshold = $this->get_short_text_merge_threshold();
        if ( $threshold <= 0 ) {
            return [ 'short' => [], 'normal' => array_values( $items ) ];
        }

        $short  = [];
        $normal = [];
        foreach ( array_values( $items ) as $item ) {
            $len = $this->get_plain_text_length( $item['text'] );
            if ( $len > 0 && $len <= $threshold ) {
                $short[] = $item;
            } else {
                $normal[] = $item;
            }
        }

        return [ 'short' => $short, 'normal' => $normal ];
    }

    protected function get_concurrency() {
        $cfg = $this->get_runtime_config();
        return max( 1, (int) $cfg['concurrency'] );
    }

    protected function should_use_parallel_http( array $batches ) {
        if ( $this->get_concurrency() <= 1 || count( $batches ) <= 1 ) {
            return false;
        }

        return function_exists( 'curl_multi_init' ) && function_exists( 'curl_multi_exec' ) && function_exists( 'curl_init' );
    }

    protected function execute_batch_requests( array $batches, $source_language, $target_language, $target_language_code ) {
        if ( empty( $batches ) ) {
            return [];
        }

        if ( $this->should_use_parallel_http( $batches ) ) {
            return $this->execute_parallel_batch_requests( $batches, $source_language, $target_language, $target_language_code );
        }

        return $this->execute_serial_batch_requests( $batches, $source_language, $target_language, $target_language_code );
    }

    protected function execute_serial_batch_requests( array $batches, $source_language, $target_language, $target_language_code ) {
        $results = [];
        foreach ( $batches as $batch ) {
            $response      = $this->send_batch_request( $source_language, $target_language, $batch );
            $batch_results = $this->build_batch_results_from_response( $response, $batch, $target_language_code );
            $results       = array_replace( $results, $batch_results );
        }
        return $results;
    }

    protected function execute_parallel_batch_requests( array $batches, $source_language, $target_language, $target_language_code ) {
        $results      = [];
        $endpoint     = $this->get_endpoint_url();
        $timeout      = $this->get_timeout();
        $concurrency  = min( $this->get_concurrency(), count( $batches ) );
        $multi_handle = curl_multi_init();
        $active       = [];
        $batch_queue  = array_values( $batches );

        $this->logger->debug( __( 'OpenAI 小批量并发翻译开始', 'langrouter-for-translatepress' ), [
            'concurrency' => $concurrency,
            'batch_count' => count( $batches ),
        ] );

        while ( count( $active ) < $concurrency && ! empty( $batch_queue ) ) {
            $batch = array_shift( $batch_queue );
            $ch    = $this->build_curl_batch_handle( $endpoint, $timeout, $source_language, $target_language, $batch );
            if ( ! $ch ) {
                $results = array_replace( $results, $this->build_empty_results_for_batch( $batch ) );
                continue;
            }

            curl_multi_add_handle( $multi_handle, $ch );
            $active[ (int) $ch ] = [
                'handle' => $ch,
                'batch'  => $batch,
            ];
        }

        do {
            do {
                $multi_exec = curl_multi_exec( $multi_handle, $running );
            } while ( $multi_exec === CURLM_CALL_MULTI_PERFORM );

            while ( $info = curl_multi_info_read( $multi_handle ) ) {
                $ch   = $info['handle'];
                $meta = $active[ (int) $ch ] ?? null;
                if ( null === $meta ) {
                    curl_multi_remove_handle( $multi_handle, $ch );
                    curl_close( $ch );
                    continue;
                }

                $batch       = $meta['batch'];
                $body        = curl_multi_getcontent( $ch );
                $curl_errno  = curl_errno( $ch );
                $curl_error  = curl_error( $ch );
                $status_code = (int) curl_getinfo( $ch, CURLINFO_RESPONSE_CODE );

                if ( 0 !== $curl_errno ) {
                    $batch_results = $this->build_empty_results_for_batch( $batch );
                    $this->logger->error( __( 'OpenAI 批量并发请求失败', 'langrouter-for-translatepress' ), [
                        'error'      => $curl_error,
                        'target_lang'=> $target_language_code,
                        'batch_size' => count( $batch ),
                    ] );
                } else {
                    $batch_results = $this->build_batch_results_from_http_payload( $status_code, $body, $batch, $target_language_code );
                }

                $results = array_replace( $results, $batch_results );

                curl_multi_remove_handle( $multi_handle, $ch );
                curl_close( $ch );
                unset( $active[ (int) $ch ] );

                while ( count( $active ) < $concurrency && ! empty( $batch_queue ) ) {
                    $next_batch = array_shift( $batch_queue );
                    $next_ch    = $this->build_curl_batch_handle( $endpoint, $timeout, $source_language, $target_language, $next_batch );
                    if ( ! $next_ch ) {
                        $results = array_replace( $results, $this->build_empty_results_for_batch( $next_batch ) );
                        continue;
                    }

                    curl_multi_add_handle( $multi_handle, $next_ch );
                    $active[ (int) $next_ch ] = [
                        'handle' => $next_ch,
                        'batch'  => $next_batch,
                    ];
                }
            }

            if ( $running && $multi_exec === CURLM_OK ) {
                curl_multi_select( $multi_handle, 0.2 );
            }
        } while ( $running || ! empty( $active ) );

        curl_multi_close( $multi_handle );
        return $results;
    }

    protected function build_curl_batch_handle( $endpoint, $timeout, $source_language, $target_language, array $batch ) {
        $payload = $this->build_request_body_json( $this->build_batch_payload( $source_language, $target_language, $batch ) );
        if ( ! is_string( $payload ) || '' === $payload ) {
            return false;
        }

        $headers      = $this->build_headers();
        $header_lines = [];
        foreach ( $headers as $header_name => $header_value ) {
            $header_lines[] = $header_name . ': ' . $header_value;
        }

        $ch = curl_init( $endpoint );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $header_lines );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, min( 5, $timeout ) );
        curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
        curl_setopt( $ch, CURLOPT_ENCODING, '' );
        if ( defined( 'CURLOPT_HTTP_VERSION' ) && defined( 'CURL_HTTP_VERSION_2TLS' ) ) {
            curl_setopt( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2TLS );
        }
        if ( defined( 'CURLOPT_TCP_KEEPALIVE' ) ) {
            curl_setopt( $ch, CURLOPT_TCP_KEEPALIVE, 1 );
        }
        if ( defined( 'CURLOPT_NOSIGNAL' ) ) {
            curl_setopt( $ch, CURLOPT_NOSIGNAL, 1 );
        }

        return $ch;
    }

    protected function build_curl_single_handle( $endpoint, $timeout, $source_language, $target_language, array $item, $mode = 'text' ) {
        $request_timeout = max( 5, (int) $timeout );
        $payload         = $this->build_request_body_json( $this->build_single_payload( $source_language, $target_language, $item['text'], $mode ) );
        if ( ! is_string( $payload ) || '' === $payload ) {
            return false;
        }

        $headers      = $this->build_headers();
        $header_lines = [];
        foreach ( $headers as $header_name => $header_value ) {
            $header_lines[] = $header_name . ': ' . $header_value;
        }

        $ch = curl_init( $endpoint );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $header_lines );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, min( 10, max( 5, (int) ceil( $request_timeout / 4 ) ) ) );
        curl_setopt( $ch, CURLOPT_TIMEOUT, $request_timeout );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
        curl_setopt( $ch, CURLOPT_ENCODING, '' );
        if ( defined( 'CURLOPT_HTTP_VERSION' ) && defined( 'CURL_HTTP_VERSION_2TLS' ) ) {
            curl_setopt( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2TLS );
        }
        if ( defined( 'CURLOPT_TCP_KEEPALIVE' ) ) {
            curl_setopt( $ch, CURLOPT_TCP_KEEPALIVE, 1 );
        }
        if ( defined( 'CURLOPT_NOSIGNAL' ) ) {
            curl_setopt( $ch, CURLOPT_NOSIGNAL, 1 );
        }

        return $ch;
    }

    protected function execute_parallel_single_requests( array $items, $source_language, $target_language, $target_language_code ) {
        $results = [];
        if ( empty( $items ) ) {
            return $results;
        }

        $parallel_items = [];
        foreach ( array_values( $items ) as $item ) {
            $mode = $this->is_label_like_text( $item['text'] ) ? 'label' : 'text';

            if ( $this->should_chunk_large_item( $item, $mode ) ) {
                $results[ $item['id'] ] = $this->translate_large_item_in_chunks( $item, $source_language, $target_language, $target_language_code );
                continue;
            }

            $parallel_items[] = $item;
        }

        if ( empty( $parallel_items ) ) {
            return $results;
        }

        if ( ! function_exists( 'curl_multi_init' ) || ! function_exists( 'curl_multi_exec' ) || ! function_exists( 'curl_init' ) || count( $parallel_items ) <= 1 ) {
            foreach ( $parallel_items as $item ) {
                $mode            = $this->is_label_like_text( $item['text'] ) ? 'label' : 'text';
                $single_response = $this->send_single_request(
                    $source_language,
                    $target_language,
                    $item['text'],
                    $mode,
                    $this->get_single_request_timeout( $item['text'], $mode )
                );
                $results[ $item['id'] ] = $this->build_single_result_from_response( $single_response, $item, $target_language_code );
            }
            return $results;
        }

        $endpoint     = $this->get_endpoint_url();
        $timeout      = $this->get_timeout();
        $concurrency  = $this->get_effective_single_request_concurrency( $parallel_items );
        $multi_handle = curl_multi_init();
        $active       = [];
        $item_queue   = array_values( $parallel_items );

        $this->logger->debug( __( 'OpenAI 单条并发兜底开始', 'langrouter-for-translatepress' ), [
            'concurrency' => $concurrency,
            'count'       => count( $parallel_items ),
        ] );

        while ( count( $active ) < $concurrency && ! empty( $item_queue ) ) {
            $item            = array_shift( $item_queue );
            $mode            = $this->is_label_like_text( $item['text'] ) ? 'label' : 'text';
            $request_timeout = $this->get_single_request_timeout( $item['text'], $mode );
            $ch              = $this->build_curl_single_handle( $endpoint, $request_timeout, $source_language, $target_language, $item, $mode );
            if ( ! $ch ) {
                $results[ $item['id'] ] = [ 'success' => false, 'translated' => '' ];
                continue;
            }
            curl_multi_add_handle( $multi_handle, $ch );
            $active[ (int) $ch ] = [
                'handle' => $ch,
                'item'   => $item,
            ];
        }

        do {
            do {
                $multi_exec = curl_multi_exec( $multi_handle, $running );
            } while ( $multi_exec === CURLM_CALL_MULTI_PERFORM );

            while ( $info = curl_multi_info_read( $multi_handle ) ) {
                $ch   = $info['handle'];
                $meta = $active[ (int) $ch ] ?? null;
                if ( null === $meta ) {
                    curl_multi_remove_handle( $multi_handle, $ch );
                    curl_close( $ch );
                    continue;
                }

                $item        = $meta['item'];
                $body        = curl_multi_getcontent( $ch );
                $curl_errno  = curl_errno( $ch );
                $curl_error  = curl_error( $ch );
                $status_code = (int) curl_getinfo( $ch, CURLINFO_RESPONSE_CODE );

                if ( 0 !== $curl_errno ) {
                    $results[ $item['id'] ] = [ 'success' => false, 'translated' => '' ];
                    $this->logger->error( __( 'OpenAI 单条并发兜底失败', 'langrouter-for-translatepress' ), [
                        'error'       => $curl_error,
                        'target_lang' => $target_language_code,
                        'text_len'    => $this->get_plain_text_length( $item['text'] ),
                    ] );
                } else {
                    $parsed = $this->parse_single_translation( $body, $item, $target_language_code );
                    $log_context = [
                        'code'        => $status_code,
                        'target_lang' => $target_language_code,
                        'ok'          => ! empty( $parsed['ok'] ),
                        'reason'      => (string) ( $parsed['reason'] ?? '' ),
                        'preview'     => $this->truncate_for_log( $parsed['content'] ?? '' ),
                    ];
                    if ( ! empty( $parsed['validation'] ) && is_array( $parsed['validation'] ) ) {
                        $log_context['validation'] = $parsed['validation'];
                    }
                    if ( ! empty( $parsed['provider_error'] ) && is_array( $parsed['provider_error'] ) ) {
                        $log_context['provider_error'] = $parsed['provider_error'];
                    }
                    if ( isset( $parsed['failed_key'] ) ) {
                        $log_context['failed_key'] = $parsed['failed_key'];
                    }
                    $this->logger->debug( __( 'OpenAI 单条并发兜底返回', 'langrouter-for-translatepress' ), $log_context );
                    $results[ $item['id'] ] = ( ( 200 === $status_code || 201 === $status_code ) && ! empty( $parsed['ok'] ) )
                        ? $parsed['result']
                        : [ 'success' => false, 'translated' => '' ];
                }

                curl_multi_remove_handle( $multi_handle, $ch );
                curl_close( $ch );
                unset( $active[ (int) $ch ] );

                while ( count( $active ) < $concurrency && ! empty( $item_queue ) ) {
                    $next_item       = array_shift( $item_queue );
                    $mode            = $this->is_label_like_text( $next_item['text'] ) ? 'label' : 'text';
                    $request_timeout = $this->get_single_request_timeout( $next_item['text'], $mode );
                    $next_ch         = $this->build_curl_single_handle( $endpoint, $request_timeout, $source_language, $target_language, $next_item, $mode );
                    if ( ! $next_ch ) {
                        $results[ $next_item['id'] ] = [ 'success' => false, 'translated' => '' ];
                        continue;
                    }
                    curl_multi_add_handle( $multi_handle, $next_ch );
                    $active[ (int) $next_ch ] = [
                        'handle' => $next_ch,
                        'item'   => $next_item,
                    ];
                }
            }

            if ( $running && $multi_exec === CURLM_OK ) {
                curl_multi_select( $multi_handle, 0.2 );
            }
        } while ( $running || ! empty( $active ) );

        curl_multi_close( $multi_handle );
        return $results;
    }

    protected function build_batch_results_from_response( $response, array $batch, $target_language_code ) {
        if ( is_wp_error( $response ) ) {
            $this->logger->error( __( 'OpenAI 批量请求失败', 'langrouter-for-translatepress' ), [
                'error'       => $response->get_error_message(),
                'target_lang' => $target_language_code,
                'batch_size'  => count( $batch ),
            ] );
            return $this->build_empty_results_for_batch( $batch );
        }

        return $this->build_batch_results_from_http_payload(
            (int) wp_remote_retrieve_response_code( $response ),
            (string) wp_remote_retrieve_body( $response ),
            $batch,
            $target_language_code
        );
    }

    protected function build_batch_results_from_http_payload( $code, $body, array $batch, $target_language_code ) {
        $parsed = $this->parse_batch_translations( $body, array_values( $batch ), $target_language_code );

        $log_context = [
            'code'        => (int) $code,
            'target_lang' => $target_language_code,
            'batch_size'  => count( $batch ),
            'ok'          => ! empty( $parsed['ok'] ),
            'reason'      => (string) ( $parsed['reason'] ?? '' ),
            'preview'     => $this->truncate_for_log( $parsed['content'] ?? '' ),
        ];
        if ( ! empty( $parsed['validation'] ) && is_array( $parsed['validation'] ) ) {
            $log_context['validation'] = $parsed['validation'];
        }
        if ( isset( $parsed['failed_key'] ) ) {
            $log_context['failed_key'] = $parsed['failed_key'];
        }

        $this->logger->debug( __( 'OpenAI 批量翻译返回', 'langrouter-for-translatepress' ), $log_context );

        if ( 200 !== (int) $code && 201 !== (int) $code ) {
            if ( ! empty( $parsed['provider_error'] ) && is_array( $parsed['provider_error'] ) ) {
                $this->logger->error( __( 'OpenAI 批量请求返回错误负载', 'langrouter-for-translatepress' ), [
                    'code'        => (int) $code,
                    'target_lang' => $target_language_code,
                    'batch_size'  => count( $batch ),
                    'error'       => $this->format_provider_error_for_log( $parsed['provider_error'] ),
                ] );
            }
            return $this->build_empty_results_for_batch( $batch );
        }

        if ( empty( $parsed['ok'] ) ) {
            return $this->build_empty_results_for_batch( $batch );
        }

        return $parsed['results'];
    }

    protected function build_single_result_from_response( $response, array $item, $target_language_code ) {
        if ( is_wp_error( $response ) ) {
            $this->logger->error( __( 'OpenAI 单条兜底失败', 'langrouter-for-translatepress' ), [
                'error'       => $response->get_error_message(),
                'target_lang' => $target_language_code,
                'text_len'    => function_exists( 'mb_strlen' ) ? mb_strlen( (string) $item['text'], 'UTF-8' ) : strlen( (string) $item['text'] ),
            ] );
            return [ 'success' => false, 'translated' => '' ];
        }

        $code   = (int) wp_remote_retrieve_response_code( $response );
        $body   = (string) wp_remote_retrieve_body( $response );
        $parsed = $this->parse_single_translation( $body, $item, $target_language_code );

        $log_context = [
            'code'        => $code,
            'target_lang' => $target_language_code,
            'ok'          => ! empty( $parsed['ok'] ),
            'reason'      => (string) ( $parsed['reason'] ?? '' ),
            'preview'     => $this->truncate_for_log( $parsed['content'] ?? '' ),
        ];
        if ( ! empty( $parsed['validation'] ) && is_array( $parsed['validation'] ) ) {
            $log_context['validation'] = $parsed['validation'];
        }
        if ( isset( $parsed['failed_key'] ) ) {
            $log_context['failed_key'] = $parsed['failed_key'];
        }

        $this->logger->debug( __( 'OpenAI 单条兜底返回', 'langrouter-for-translatepress' ), $log_context );

        if ( 200 !== $code && 201 !== $code ) {
            if ( ! empty( $parsed['provider_error'] ) && is_array( $parsed['provider_error'] ) ) {
                $this->logger->error( __( 'OpenAI 单条请求返回错误负载', 'langrouter-for-translatepress' ), [
                    'code'        => $code,
                    'target_lang' => $target_language_code,
                    'text_len'    => function_exists( 'mb_strlen' ) ? mb_strlen( (string) $item['text'], 'UTF-8' ) : strlen( (string) $item['text'] ),
                    'error'       => $this->format_provider_error_for_log( $parsed['provider_error'] ),
                ] );
            }
            return [ 'success' => false, 'translated' => '' ];
        }

        return ! empty( $parsed['ok'] ) ? $parsed['result'] : [ 'success' => false, 'translated' => '' ];
    }

    public function translate_batch( array $strings, $target_language_code, $source_language_code = null ) {
        $this->logger->debug( __( 'OpenAI translate_batch 速度版开始', 'langrouter-for-translatepress' ), [
            'target_language' => $target_language_code,
            'source_language' => $source_language_code,
            'count'           => count( $strings ),
            'model'           => $this->get_model(),
        ] );

        if ( ! $this->is_configured() ) {
            $this->logger->error( __( 'OpenAI 未配置可用 API Key', 'langrouter-for-translatepress' ), [] );
            return [];
        }

        $source_language = '';
        if ( null !== $source_language_code ) {
            $source_language = $this->get_request_language_value( $source_language_code );
        }

        $target_language = $this->get_request_language_value( $target_language_code );
        if ( '' === $target_language ) {
            $this->logger->error( __( 'OpenAI 目标语言未映射成功', 'langrouter-for-translatepress' ), [ 'target_language' => $target_language_code ] );
            return [];
        }

        $items = [];
        foreach ( $strings as $key => $value ) {
            if ( $this->should_skip_text( $value ) ) {
                continue;
            }

            $hash = md5( (string) $value );
            if ( ! isset( $items[ $hash ] ) ) {
                $items[ $hash ] = [
                    'id'   => $hash,
                    'text' => $value,
                    'keys' => [ $key ],
                ];
            } else {
                $items[ $hash ]['keys'][] = $key;
            }
        }

        if ( empty( $items ) ) {
            return [];
        }

        $items_list = array_values( $items );
        $groups     = $this->split_items_by_short_text_threshold( $items_list );
        $results    = [];

        if ( ! empty( $groups['short'] ) ) {
            $short_batches = $this->split_into_batches( $groups['short'] );
            $results       = array_replace( $results, $this->execute_batch_requests( $short_batches, $source_language, $target_language, $target_language_code ) );
        }
        if ( ! empty( $groups['normal'] ) ) {
            $results = array_replace( $results, $this->execute_parallel_single_requests( $groups['normal'], $source_language, $target_language, $target_language_code ) );
        }

        $retry_count = $this->get_retry_count();
        while ( $retry_count > 0 ) {
            $failed_items = [];
            foreach ( $items_list as $item ) {
                if ( empty( $results[ $item['id'] ]['success'] ) ) {
                    $failed_items[] = $item;
                }
            }
            if ( empty( $failed_items ) ) {
                break;
            }

            $results = array_replace( $results, $this->execute_parallel_single_requests( $failed_items, $source_language, $target_language, $target_language_code ) );
            $retry_count--;
        }

        $translated = [];
        foreach ( $items_list as $item ) {
            $item_id = $item['id'];
            if ( empty( $results[ $item_id ]['success'] ) ) {
                continue;
            }

            foreach ( $item['keys'] as $key ) {
                $translated[ $key ] = $results[ $item_id ]['translated'];
            }
        }

        $success_count = 0;
        foreach ( $results as $result_item ) {
            if ( ! empty( $result_item['success'] ) ) {
                $success_count++;
            }
        }

        $this->logger->debug( __( 'OpenAI translate_batch 速度版结束', 'langrouter-for-translatepress' ), [
            'translated_count' => count( $translated ),
            'requested_count'  => count( $strings ),
            'unique_count'     => count( $items_list ),
            'failed_count'     => count( $items_list ) - $success_count,
            'concurrency'      => $this->get_concurrency(),
            'retry_count'      => $this->get_retry_count(),
            'short_merge_threshold' => $this->get_short_text_merge_threshold(),
        ] );

        return $translated;
    }


    public function create_translator( array $tp_settings, TPRE_Logger $logger, $translator_class = 'TRP_OpenAI_Machine_Translator' ) {
        if ( function_exists( 'tpre_load_openai_translator_class' ) && ! tpre_load_openai_translator_class() ) {
            return null;
        }

        if ( ! class_exists( $translator_class ) ) {
            return null;
        }

        return new $translator_class( $tp_settings, $this, $logger );
    }

    public function test_request() {
        $this->logger->debug( __( 'OpenAI test_request 开始', 'langrouter-for-translatepress' ), [
            'model'    => $this->get_model(),
            'endpoint' => $this->get_endpoint_url(),
        ] );

        if ( ! $this->is_configured() ) {
            return [ 'response' => [ 'code' => 400 ], 'body' => 'OpenAI not configured' ];
        }

        $response = $this->send_single_request( 'English', 'Simplified Chinese', 'Where are you from?', 'text' );
        if ( is_wp_error( $response ) ) {
            $this->logger->error( __( 'OpenAI test_request 失败', 'langrouter-for-translatepress' ), [ 'error' => $response->get_error_message() ] );
            return [ 'response' => [ 'code' => 500 ], 'body' => $response->get_error_message() ];
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = (string) wp_remote_retrieve_body( $response );

        $this->logger->debug( __( 'OpenAI test_request 返回', 'langrouter-for-translatepress' ), [
            'code'    => $code,
            'preview' => $this->truncate_for_log( $this->get_response_content_text_from_body( $body ) ),
        ] );

        return [ 'response' => [ 'code' => $code ], 'body' => $body ];
    }
}
