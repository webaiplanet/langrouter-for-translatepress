<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPRE_Volc_Client {
    const SUPPORTED_LANGUAGE_CODES = [ 'zh', 'zh-hant', 'en', 'ja', 'ko', 'de', 'fr', 'es', 'it', 'pt', 'ru', 'th', 'vi', 'ar', 'cs', 'da', 'fi', 'hr', 'hu', 'id', 'ms', 'nb', 'nl', 'pl', 'ro', 'sv', 'tr', 'uk' ];

    protected $tp_settings;
    protected $router_settings;
    protected $logger;
    protected $translator = null;

    public function __construct( array $tp_settings, array $router_settings, TPRE_Logger $logger ) {
        $this->tp_settings     = $tp_settings;
        $this->router_settings = $router_settings;
        $this->logger          = $logger;
    }

    public static function create_for_admin( array $router_settings, TPRE_Logger $logger = null ) {
        if ( null === $logger ) {
            $logger = new TPRE_Logger( false );
        }

        $tp_settings = get_option( 'trp_settings', [] );
        if ( ! is_array( $tp_settings ) ) {
            $tp_settings = [];
        }

        return new self( $tp_settings, $router_settings, $logger );
    }

    public function is_available() {
        return class_exists( 'TRP_Machine_Translator' ) && class_exists( 'TRP_Volcengine_Ark_Machine_Translator' ) && $this->has_accounts();
    }

    public function has_accounts() {
        return '' !== $this->get_accounts_raw();
    }


    public function has_chat_accounts() {
        $raw = $this->get_accounts_raw();
        if ( '' === $raw ) {
            return false;
        }

        $lines = preg_split( '/\r\n|\r|\n/', trim( $raw ) );
        foreach ( (array) $lines as $line ) {
            $line = trim( (string) $line );
            if ( '' === $line ) {
                continue;
            }

            $parts = array_map( 'trim', explode( '|', $line ) );
            $part5 = isset( $parts[5] ) ? strtolower( (string) $parts[5] ) : '';
            $part6 = isset( $parts[6] ) ? strtolower( (string) $parts[6] ) : '';
            if ( 'chat' === $part5 || 'chat' === $part6 ) {
                return true;
            }
        }

        return false;
    }

    public function get_accounts_raw() {
        return trim( (string) ( $this->router_settings['models']['volc']['accounts_raw'] ?? '' ) );
    }

    public function get_account_models() {
        $raw = $this->get_accounts_raw();
        if ( '' === $raw ) {
            return [];
        }

        $lines  = preg_split( '/\r\n|\r|\n/', trim( $raw ) );
        $models = [];

        foreach ( (array) $lines as $line ) {
            $line = trim( (string) $line );
            if ( '' === $line ) {
                continue;
            }

            $parts = array_map( 'trim', explode( '|', $line ) );
            if ( empty( $parts[0] ) ) {
                continue;
            }

            $models[] = $parts[0];
        }

        return array_values( array_unique( $models ) );
    }

    public function create_translator() {
        if ( null !== $this->translator ) {
            return $this->translator;
        }

        if ( ! class_exists( 'TRP_Volcengine_Ark_Machine_Translator' ) && function_exists( 'tpre_load_volcengine_translator_class' ) ) {
            tpre_load_volcengine_translator_class();
        }

        if ( ! class_exists( 'TRP_Volcengine_Ark_Machine_Translator' ) ) {
            $this->logger->error( __( '火山方舟翻译器类不可用。', 'langrouter-for-translatepress' ), [
                'has_trp_machine_translator' => class_exists( 'TRP_Machine_Translator' ),
            ] );
            return null;
        }

        $settings = $this->tp_settings;
        if ( empty( $settings ) || ! is_array( $settings ) ) {
            $settings = [];
        }

        if ( empty( $settings['trp_machine_translation_settings'] ) || ! is_array( $settings['trp_machine_translation_settings'] ) ) {
            $settings['trp_machine_translation_settings'] = [];
        }

        $settings['trp_machine_translation_settings']['translation-engine']      = 'volcengine_ark';
        $settings['trp_machine_translation_settings']['machine-translation']      = 'yes';
        $settings['trp_machine_translation_settings']['volcengine-ark-accounts']  = $this->get_accounts_raw();
        $settings['trp_machine_translation_settings']['tpre_global_concurrency_limit'] = isset( $this->router_settings['global_concurrency_limit'] ) ? max( 0, (int) $this->router_settings['global_concurrency_limit'] ) : 0;
        $settings['trp_machine_translation_settings']['tpre_volc_concurrency'] = isset( $this->router_settings['models']['volc']['concurrency'] ) ? max( 1, (int) $this->router_settings['models']['volc']['concurrency'] ) : 24;

        $this->translator = new TRP_Volcengine_Ark_Machine_Translator( $settings );
        return $this->translator;
    }

    public function translate_batch( array $strings, $target_language_code, $source_language_code = null ) {
        if ( ! $this->is_available() ) {
            $this->logger->error( __( '火山方舟不可用：未检测到有效账号池。', 'langrouter-for-translatepress' ), [
                'target_language' => $target_language_code,
            ] );
            return [];
        }

        if ( ! $this->supports_language( $target_language_code ) ) {
            $this->logger->debug( __( '火山方舟不支持目标语言。', 'langrouter-for-translatepress' ), [
                'target_language' => $target_language_code,
            ] );
            return [];
        }

        $translator = $this->create_translator();
        if ( ! $translator ) {
            return [];
        }

        $this->logger->debug( __( '调用火山方舟真实翻译请求', 'langrouter-for-translatepress' ), [
            'target_language' => $target_language_code,
            'source_language' => $source_language_code,
            'count'           => count( $strings ),
        ] );

        $result = $translator->translate_array( $strings, $target_language_code, $source_language_code );
        return is_array( $result ) ? $result : [];
    }

    public function check_api_key_validity() {
        $translator = $this->create_translator();
        if ( ! $translator || ! method_exists( $translator, 'check_api_key_validity' ) ) {
            return [
                'message' => __( '火山方舟翻译器未就绪。', 'langrouter-for-translatepress' ),
                'error'   => true,
            ];
        }

        return $translator->check_api_key_validity();
    }

    public function get_billing_usage_summary_rows() {
        $translator = $this->create_translator();
        if ( ! $translator || ! method_exists( $translator, 'get_billing_usage_summary_rows' ) ) {
            return [];
        }

        return (array) $translator->get_billing_usage_summary_rows();
    }

    public function force_refresh_billing_usage_summary_rows() {
        $translator = $this->create_translator();
        if ( ! $translator || ! method_exists( $translator, 'force_refresh_billing_usage_summary_rows' ) ) {
            return [];
        }

        return (array) $translator->force_refresh_billing_usage_summary_rows();
    }

    public function test_request() {
        $translator = $this->create_translator();
        if ( ! $translator || ! method_exists( $translator, 'test_request' ) ) {
            return [
                'response' => [ 'code' => 500 ],
                'body'     => 'volc translator not ready',
            ];
        }

        $this->logger->debug( __( '火山方舟 test_request 开始', 'langrouter-for-translatepress' ), [] );
        $response = $translator->test_request();
        $this->logger->debug( __( '火山方舟 test_request 返回', 'langrouter-for-translatepress' ), [
            'code' => is_array( $response ) ? (int) ( $response['response']['code'] ?? 0 ) : 0,
            'body' => is_array( $response ) ? wp_strip_all_tags( (string) ( $response['body'] ?? '' ) ) : '',
        ] );
        return $response;
    }

    public function get_billing_usage_diagnostic_rows() {
        $translator = $this->create_translator();
        if ( ! $translator || ! method_exists( $translator, 'get_billing_usage_diagnostic_rows' ) ) {
            return [];
        }

        return (array) $translator->get_billing_usage_diagnostic_rows();
    }

    public function supports_language( $language_code ) {
        $candidates = $this->build_language_candidates( $language_code );
        if ( empty( $candidates ) ) {
            return false;
        }

        foreach ( $candidates as $candidate ) {
            if ( in_array( $candidate, $this->get_supported_translation_language_codes(), true ) ) {
                return true;
            }
        }

        return $this->has_chat_accounts();
    }

    public function get_supported_languages_payload() {
        return [
            'state'     => 'ok',
            'languages' => $this->get_supported_translation_language_codes(),
            'source'    => 'builtin_manual_list',
        ];
    }

    public function get_language_support_meta( $language_code ) {
        $candidates = $this->build_language_candidates( $language_code );
        $translation_supported = ! empty( array_intersect( $candidates, $this->get_supported_translation_language_codes() ) );

        return [
            'raw'        => is_string( $language_code ) ? trim( $language_code ) : '',
            'candidates' => $candidates,
            'supported'  => ( $translation_supported || $this->has_chat_accounts() ),
            'source'     => $translation_supported ? 'builtin_manual_list' : ( $this->has_chat_accounts() ? 'account_pool_chat_fallback' : 'builtin_manual_list' ),
        ];
    }


    protected function get_supported_chat_locale_map() {
        return [
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
        ];
    }

    protected function normalize_supported_language_code( $language_code ) {
        $language_code = is_string( $language_code ) ? trim( $language_code ) : '';
        if ( '' === $language_code ) {
            return '';
        }

        $normalized = strtolower( str_replace( '_', '-', $language_code ) );
        $locale_map = $this->get_supported_chat_locale_map();

        if ( isset( $locale_map[ $normalized ] ) && is_string( $locale_map[ $normalized ] ) && '' !== trim( $locale_map[ $normalized ] ) ) {
            return strtolower( str_replace( '_', '-', trim( $locale_map[ $normalized ] ) ) );
        }

        $alt_key = str_replace( '-', '_', $normalized );
        if ( isset( $locale_map[ $alt_key ] ) && is_string( $locale_map[ $alt_key ] ) && '' !== trim( $locale_map[ $alt_key ] ) ) {
            return strtolower( str_replace( '_', '-', trim( $locale_map[ $alt_key ] ) ) );
        }

        return $normalized;
    }

    protected function get_supported_translation_language_codes() {
        $languages = apply_filters( 'tpre_volc_supported_translation_language_codes', self::SUPPORTED_LANGUAGE_CODES );

        if ( ! is_array( $languages ) ) {
            $languages = self::SUPPORTED_LANGUAGE_CODES;
        }

        $normalized = [];
        foreach ( $languages as $language ) {
            if ( ! is_string( $language ) ) {
                continue;
            }

            $language = strtolower( trim( $language ) );
            if ( '' === $language ) {
                continue;
            }

            $normalized[] = $language;
        }

        return array_values( array_unique( $normalized ) );
    }

    protected function build_language_candidates( $language_code ) {
        $normalized = $this->normalize_supported_language_code( $language_code );
        if ( '' === $normalized ) {
            return [];
        }

        $raw = is_string( $language_code ) ? strtolower( trim( str_replace( '_', '-', $language_code ) ) ) : '';
        $candidates = [ $normalized ];

        if ( '' !== $raw ) {
            $candidates[] = $raw;
            $base = strtok( $raw, '-' );
            if ( is_string( $base ) && '' !== $base ) {
                $candidates[] = $base;
            }
        }

        $normalized_candidates = [];
        foreach ( $candidates as $candidate ) {
            if ( ! is_string( $candidate ) ) {
                continue;
            }

            $candidate = strtolower( trim( $candidate ) );
            if ( '' === $candidate ) {
                continue;
            }

            $normalized_candidates[] = $candidate;
        }

        return array_values( array_unique( $normalized_candidates ) );
    }

    protected function normalize_translation_language_code( $code ) {
        $code = is_string( $code ) ? trim( $code ) : '';
        if ( '' === $code ) {
            return '';
        }

        $normalized = str_replace( '_', '-', $code );
        $lower      = strtolower( $normalized );

        $exact_map = [
            'zh-cn'      => 'zh',
            'zh-sg'      => 'zh',
            'zh'         => 'zh',
            'zh-hant'    => 'zh-hant',
            'zh-hant-hk' => 'zh-hant',
            'zh-hant-tw' => 'zh-hant',
            'zh-tw'      => 'zh-hant',
            'zh-hk'      => 'zh-hant',
            'zh-mo'      => 'zh-hant',
            'en-us'      => 'en',
            'en-gb'      => 'en',
            'en'         => 'en',
            'ja-jp'      => 'ja',
            'ja'         => 'ja',
            'ko-kr'      => 'ko',
            'ko'         => 'ko',
            'de-de'      => 'de',
            'de'         => 'de',
            'fr-fr'      => 'fr',
            'fr'         => 'fr',
            'es-es'      => 'es',
            'es'         => 'es',
            'it-it'      => 'it',
            'it'         => 'it',
            'pt-br'      => 'pt',
            'pt-pt'      => 'pt',
            'pt'         => 'pt',
            'ru-ru'      => 'ru',
            'ru'         => 'ru',
            'th-th'      => 'th',
            'th'         => 'th',
            'vi-vn'      => 'vi',
            'vi'         => 'vi',
            'ar'         => 'ar',
            'cs-cz'      => 'cs',
            'cs'         => 'cs',
            'da-dk'      => 'da',
            'da'         => 'da',
            'fi-fi'      => 'fi',
            'fi'         => 'fi',
            'hr-hr'      => 'hr',
            'hr'         => 'hr',
            'hu-hu'      => 'hu',
            'hu'         => 'hu',
            'id-id'      => 'id',
            'id'         => 'id',
            'ms-my'      => 'ms',
            'ms'         => 'ms',
            'nb-no'      => 'nb',
            'nb'         => 'nb',
            'nl-nl'      => 'nl',
            'nl'         => 'nl',
            'pl-pl'      => 'pl',
            'pl'         => 'pl',
            'ro-ro'      => 'ro',
            'ro'         => 'ro',
            'sv-se'      => 'sv',
            'sv'         => 'sv',
            'tr-tr'      => 'tr',
            'tr'         => 'tr',
            'uk-ua'      => 'uk',
            'uk'         => 'uk',
        ];

        if ( isset( $exact_map[ $lower ] ) ) {
            return $exact_map[ $lower ];
        }

        $base = strtok( $lower, '-' );
        if ( 'zh' === $base ) {
            return 'zh';
        }

        foreach ( $this->get_supported_translation_language_codes() as $supported_code ) {
            if ( strtolower( $supported_code ) === $base ) {
                return $supported_code;
            }
        }

        return '';
    }
}
