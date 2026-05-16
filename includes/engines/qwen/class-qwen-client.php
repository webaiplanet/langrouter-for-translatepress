<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// phpcs:disable WordPress.WP.AlternativeFunctions -- Intentional use of cURL/cURL multi for provider-specific parallel request handling.

class TPRE_Qwen_Client {
    const DEFAULT_TIMEOUT  = 20;
    const DEFAULT_MODEL    = 'qwen-mt-flash';
    const DEFAULT_REGION   = 'beijing';
    const DEFAULT_CONCURRENCY = 6;
    const ENDPOINT_BEIJING = 'https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions';
    const ENDPOINT_SG      = 'https://dashscope-intl.aliyuncs.com/compatible-mode/v1/chat/completions';
    const ENDPOINT_US      = 'https://dashscope-us.aliyuncs.com/compatible-mode/v1/chat/completions';

    protected $router_settings;
    protected $logger;

    protected $supported_codes_full = [
        'en','zh','zh_tw','ru','ja','ko','es','fr','pt','de','it','th','vi','id','ms','ar','hi','he','my','ta','ur','bn','pl','nl','ro','tr','km','lo','yue','cs','el','sv','hu','da','fi','uk','bg','sr','te','af','hy','as','ast','eu','be','bs','ca','ceb','hr','arz','et','gl','ka','gu','is','jv','kn','kk','lv','lt','lb','mk','mai','mt','mr','acm','ary','ars','ne','az','apc','uz','nb','nn','oc','or','pag','scn','sd','si','sk','sl','ajp','sw','tl','acq','sq','aeb','vec','war','cy','fa'
    ];

    protected $supported_codes_lite = [
        'en','zh','zh_tw','ru','ja','ko','es','fr','pt','de','it','th','vi','id','ms','ar','hi','he','ur','bn','pl','nl','tr','km','cs','sv','hu','da','fi','tl','fa'
    ];

    protected $qwen_language_names = [
        'en' => 'English', 'zh' => 'Chinese', 'zh_tw' => 'Traditional Chinese', 'ru' => 'Russian', 'ja' => 'Japanese',
        'ko' => 'Korean', 'es' => 'Spanish', 'fr' => 'French', 'pt' => 'Portuguese', 'de' => 'German', 'it' => 'Italian',
        'th' => 'Thai', 'vi' => 'Vietnamese', 'id' => 'Indonesian', 'ms' => 'Malay', 'ar' => 'Arabic', 'hi' => 'Hindi',
        'he' => 'Hebrew', 'my' => 'Burmese', 'ta' => 'Tamil', 'ur' => 'Urdu', 'bn' => 'Bengali', 'pl' => 'Polish',
        'nl' => 'Dutch', 'ro' => 'Romanian', 'tr' => 'Turkish', 'km' => 'Khmer', 'lo' => 'Lao', 'yue' => 'Cantonese',
        'cs' => 'Czech', 'el' => 'Greek', 'sv' => 'Swedish', 'hu' => 'Hungarian', 'da' => 'Danish', 'fi' => 'Finnish',
        'uk' => 'Ukrainian', 'bg' => 'Bulgarian', 'sr' => 'Serbian', 'te' => 'Telugu', 'af' => 'Afrikaans', 'hy' => 'Armenian',
        'as' => 'Assamese', 'ast' => 'Asturian', 'eu' => 'Basque', 'be' => 'Belarusian', 'bs' => 'Bosnian', 'ca' => 'Catalan',
        'ceb' => 'Cebuano', 'hr' => 'Croatian', 'arz' => 'Egyptian Arabic', 'et' => 'Estonian', 'fa' => 'Western Persian',
        'gl' => 'Galician', 'ka' => 'Georgian', 'gu' => 'Gujarati', 'is' => 'Icelandic', 'jv' => 'Javanese',
        'kn' => 'Kannada', 'kk' => 'Kazakh', 'lv' => 'Latvian', 'lt' => 'Lithuanian', 'lb' => 'Luxembourgish',
        'mk' => 'Macedonian', 'mai' => 'Maithili', 'mt' => 'Maltese', 'mr' => 'Marathi', 'acm' => 'Mesopotamian Arabic',
        'ary' => 'Moroccan Arabic', 'ars' => 'Najdi Arabic', 'ne' => 'Nepali', 'az' => 'North Azerbaijani',
        'apc' => 'North Levantine Arabic', 'uz' => 'Northern Uzbek', 'nb' => 'Norwegian Bokmål', 'nn' => 'Norwegian Nynorsk',
        'oc' => 'Occitan', 'or' => 'Odia', 'pag' => 'Pangasinan', 'scn' => 'Sicilian', 'sd' => 'Sindhi', 'si' => 'Sinhala',
        'sk' => 'Slovak', 'sl' => 'Slovenian', 'ajp' => 'South Levantine Arabic', 'sw' => 'Swahili', 'tl' => 'Tagalog',
        'acq' => 'Ta’izzi-Adeni Arabic', 'sq' => 'Tosk Albanian', 'aeb' => 'Tunisian Arabic', 'vec' => 'Venetian',
        'war' => 'Waray', 'cy' => 'Welsh',
    ];

    protected $locale_code_map = [
        'en_US' => 'en', 'en_GB' => 'en', 'en_CA' => 'en', 'en_AU' => 'en', 'en_NZ' => 'en', 'en_ZA' => 'en',
        'zh_CN' => 'zh', 'zh_SG' => 'zh', 'zh_TW' => 'zh_tw', 'zh_HK' => 'zh_tw',
        'pt_BR' => 'pt', 'pt_PT' => 'pt', 'pt_AO' => 'pt', 'pt_PT_ao90' => 'pt',
        'es_ES' => 'es', 'es_AR' => 'es', 'es_CL' => 'es', 'es_CO' => 'es', 'es_CR' => 'es', 'es_DO' => 'es',
        'es_EC' => 'es', 'es_GT' => 'es', 'es_MX' => 'es', 'es_PE' => 'es', 'es_PR' => 'es', 'es_UY' => 'es', 'es_VE' => 'es',
        'fr_FR' => 'fr', 'fr_CA' => 'fr', 'de_DE' => 'de', 'de_DE_formal' => 'de', 'it_IT' => 'it',
        'ja' => 'ja', 'ja_JP' => 'ja', 'ko_KR' => 'ko', 'ru_RU' => 'ru', 'th' => 'th', 'th_TH' => 'th',
        'vi' => 'vi', 'vi_VN' => 'vi', 'id_ID' => 'id', 'ms_MY' => 'ms', 'ar' => 'ar', 'ar_AR' => 'ar',
        'hi_IN' => 'hi', 'he_IL' => 'he', 'bn_BD' => 'bn', 'nl_NL' => 'nl', 'pl_PL' => 'pl', 'tr_TR' => 'tr',
        'cs_CZ' => 'cs', 'sv_SE' => 'sv', 'da_DK' => 'da', 'fi' => 'fi', 'fi_FI' => 'fi', 'uk' => 'uk', 'uk_UA' => 'uk',
        'bg_BG' => 'bg', 'ro_RO' => 'ro', 'nb_NO' => 'nb', 'nn_NO' => 'nn', 'el' => 'el', 'el_GR' => 'el',
        'fa_IR' => 'fa', 'tl' => 'tl', 'hu_HU' => 'hu', 'sk_SK' => 'sk', 'sl_SI' => 'sl', 'hr' => 'hr',
        'hr_HR' => 'hr', 'lt_LT' => 'lt', 'lv' => 'lv', 'lv_LV' => 'lv', 'et' => 'et', 'et_EE' => 'et',
        'ca' => 'ca', 'ca_ES' => 'ca', 'eu' => 'eu', 'gl_ES' => 'gl', 'is_IS' => 'is', 'ka_GE' => 'ka',
        'mk_MK' => 'mk', 'mn_MN' => 'mn', 'ne_NP' => 'ne', 'az' => 'az', 'az_AZ' => 'az', 'sw' => 'sw',
        'cy' => 'cy', 'sq' => 'sq', 'si_LK' => 'si', 'or' => 'or', 'sd' => 'sd', 'te' => 'te', 'ta_IN' => 'ta',
        'my_MM' => 'my', 'km_KH' => 'km', 'lo' => 'lo', 'ur' => 'ur', 'sr_RS' => 'sr', 'af' => 'af', 'hy' => 'hy',
        'as' => 'as', 'bs_BA' => 'bs', 'be_BY' => 'be', 'yue' => 'yue', 'yue_HK' => 'yue', 'uz_UZ' => 'uz',
    ];

    public function __construct( array $router_settings, TPRE_Logger $logger ) {
        $this->router_settings = $router_settings;
        $this->logger          = $logger;
    }

    public function get_model_settings() {
        $models   = isset( $this->router_settings['models'] ) && is_array( $this->router_settings['models'] ) ? $this->router_settings['models'] : [];
        $defaults = [
            'enabled'         => 0,
            'endpoint'        => '',
            'model'           => self::DEFAULT_MODEL,
            'api_key'         => '',
            'region'          => self::DEFAULT_REGION,
            'timeout'         => self::DEFAULT_TIMEOUT,
            'concurrency'     => self::DEFAULT_CONCURRENCY,
            'extra_body_json' => '',
            'note'            => '',
        ];

        $item = isset( $models['qwen'] ) && is_array( $models['qwen'] ) ? $models['qwen'] : [];
        return wp_parse_args( $item, $defaults );
    }

    public function is_configured() {
        $item = $this->get_model_settings();
        return ! empty( $item['enabled'] ) && '' !== trim( (string) $item['api_key'] );
    }

    public function get_supported_codes() {
        return $this->get_supported_translation_language_codes();
    }

    protected function is_lite_model() {
        return false !== strpos( strtolower( $this->get_model() ), 'qwen-mt-lite' );
    }

    protected function get_supported_translation_language_codes() {
        $languages = $this->is_lite_model() ? $this->supported_codes_lite : $this->supported_codes_full;
        $languages = apply_filters(
            'tpre_qwen_supported_translation_language_codes',
            $languages,
            $this->get_model(),
            $this->is_lite_model() ? 'lite' : 'full'
        );

        if ( ! is_array( $languages ) ) {
            $languages = $this->is_lite_model() ? $this->supported_codes_lite : $this->supported_codes_full;
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

    public function supports_language( $language_code ) {
        $meta = $this->get_language_support_meta( $language_code );
        return ! empty( $meta['supported'] );
    }

    public function get_language_support_meta( $language_code ) {
        $raw        = is_string( $language_code ) ? trim( $language_code ) : '';
        $qwen_code  = $this->map_trp_locale_to_qwen_code( $language_code );
        $candidates = [];

        if ( '' !== $qwen_code ) {
            $candidates[] = $qwen_code;
        }

        return [
            'raw'        => $raw,
            'candidates' => array_values( array_unique( $candidates ) ),
            'supported'  => '' !== $qwen_code && in_array( $qwen_code, $this->get_supported_translation_language_codes(), true ),
            'source'     => $this->is_lite_model() ? 'builtin_official_list_lite' : 'builtin_official_list_full',
            'model'      => $this->get_model(),
        ];
    }

    public function get_request_language_value( $trp_language_code ) {
        $qwen_code = $this->map_trp_locale_to_qwen_code( $trp_language_code );
        if ( '' === $qwen_code ) {
            return '';
        }

        return isset( $this->qwen_language_names[ $qwen_code ] ) ? $this->qwen_language_names[ $qwen_code ] : $qwen_code;
    }

    protected function map_trp_locale_to_qwen_code( $trp_language_code ) {
        if ( isset( $this->locale_code_map[ $trp_language_code ] ) ) {
            return $this->locale_code_map[ $trp_language_code ];
        }

        $trp_language_code = (string) $trp_language_code;
        $normalized        = strtolower( str_replace( '-', '_', $trp_language_code ) );
        if ( isset( $this->locale_code_map[ $normalized ] ) ) {
            return $this->locale_code_map[ $normalized ];
        }

        $iso_guess = strtolower( preg_replace( '/[_-].*/', '', $trp_language_code ) );
        if ( '' !== $iso_guess && in_array( $iso_guess, $this->get_supported_translation_language_codes(), true ) ) {
            return $iso_guess;
        }

        return '';
    }

    protected function normalize_endpoint_url( $endpoint ) {
        $endpoint = trim( (string) $endpoint );
        if ( '' === $endpoint ) {
            return '';
        }

        $endpoint = preg_replace( '#/+$#', '', $endpoint );
        if ( preg_match( '#/chat/completions$#i', $endpoint ) ) {
            return $endpoint;
        }

        if ( preg_match( '#/compatible-mode/v1$#i', $endpoint ) ) {
            return $endpoint . '/chat/completions';
        }

        return $endpoint;
    }

    public function get_endpoint_url() {
        $item = $this->get_model_settings();
        if ( ! empty( $item['endpoint'] ) ) {
            return $this->normalize_endpoint_url( $item['endpoint'] );
        }

        $region = strtolower( trim( (string) ( $item['region'] ?? self::DEFAULT_REGION ) ) );
        switch ( $region ) {
            case 'singapore':
            case 'intl':
            case 'sg':
                return $this->normalize_endpoint_url( self::ENDPOINT_SG );
            case 'us':
            case 'virginia':
            case 'us-va':
                return $this->normalize_endpoint_url( self::ENDPOINT_US );
            case 'beijing':
            default:
                return $this->normalize_endpoint_url( self::ENDPOINT_BEIJING );
        }
    }

    public function get_timeout() {
        $item = $this->get_model_settings();
        return max( 5, (int) ( $item['timeout'] ?? self::DEFAULT_TIMEOUT ) );
    }

    public function get_api_key() {
        $item = $this->get_model_settings();
        return trim( (string) ( $item['api_key'] ?? '' ) );
    }

    public function get_model() {
        $item  = $this->get_model_settings();
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

    protected function build_payload( $source_language, $target_language, $text ) {
        $payload = [
            'model' => $this->get_model(),
            'messages' => [
                [
                    'role'    => 'system',
                    'content' => 'You are a website localization translation engine. Return translation only. For very short labels or single CJK characters, prefer concise website wording that fits tables and UI labels. Do not turn them into language names, country names, route names, or explanatory phrases unless the source explicitly means that.',
                ],
                [
                    'role'    => 'user',
                    'content' => (string) $text,
                ],
            ],
            'translation_options' => [
                'source_lang' => '' !== (string) $source_language ? $source_language : 'auto',
                'target_lang' => $target_language,
            ],
            'temperature' => 0,
            'seed'        => 7,
        ];

        $extra = $this->get_extra_body();
        if ( ! empty( $extra ) ) {
            $payload = array_replace_recursive( $payload, $extra );
        }

        return $payload;
    }

    protected function build_headers() {
        return [
            'Authorization' => 'Bearer ' . $this->get_api_key(),
            'Content-Type'  => 'application/json',
        ];
    }

    protected function build_request_body_json( $source_language, $target_language, $text ) {
        return wp_json_encode( $this->build_payload( $source_language, $target_language, $text ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    }

    protected function send_request( $source_language, $target_language, $text ) {
        $args = [
            'timeout' => $this->get_timeout(),
            'headers' => $this->build_headers(),
            'body'    => $this->build_request_body_json( $source_language, $target_language, $text ),
        ];

        return wp_remote_post( $this->get_endpoint_url(), $args );
    }

    protected function get_global_concurrency_limit() {
        return isset( $this->router_settings['global_concurrency_limit'] ) ? max( 0, (int) $this->router_settings['global_concurrency_limit'] ) : 0;
    }

    protected function get_concurrency() {
        $item               = $this->get_model_settings();
        $global_limit       = $this->get_global_concurrency_limit();
        $engine_concurrency = isset( $item['concurrency'] ) ? (int) $item['concurrency'] : 0;

        if ( $engine_concurrency > 0 ) {
            return max( 1, $engine_concurrency );
        }

        if ( $global_limit > 0 ) {
            return max( 1, $global_limit );
        }

        return self::DEFAULT_CONCURRENCY;
    }

    protected function looks_like_internal_log_or_debug_text( $text ) {
        $text = trim( (string) $text );
        if ( '' === $text ) {
            return false;
        }

        if ( false !== strpos( $text, '#!trpst#trp-gettext' ) || false !== strpos( $text, 'data-trpgettextoriginal=' ) ) {
            return true;
        }

        return (bool) preg_match(
            '/\b(OpenAI|Qwen|DeepL|Hunyuan|火山方舟)\s+(?:translate_batch|批量翻译|单条并发兜底|单条兜底|翻译)/u',
            $text
        );
    }

    protected function should_skip_text( $text ) {
        $raw  = html_entity_decode( (string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $text = trim( wp_strip_all_tags( $raw ) );
        if ( '' === $text ) {
            return true;
        }

        if ( ! preg_match( '/\p{L}/u', $text ) ) {
            return true;
        }

        if ( $this->looks_like_internal_log_or_debug_text( $raw ) || $this->looks_like_internal_log_or_debug_text( $text ) ) {
            return true;
        }

        if ( preg_match( '/^oembed\s*\((json|xml)\)$/i', $text ) ) {
            return true;
        }

        return false;
    }

    protected function extract_translation_text( $response ) {
        if ( is_wp_error( $response ) ) {
            return '';
        }

        $body = wp_remote_retrieve_body( $response );
        if ( '' === $body ) {
            return '';
        }

        $decoded = json_decode( $body, true );
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

        if ( isset( $decoded['output']['text'] ) ) {
            return trim( (string) $decoded['output']['text'] );
        }

        return '';
    }

    public function translate_batch( array $strings, $target_language_code, $source_language_code = null ) {
        $this->logger->debug( __( 'Qwen translate_batch 开始', 'langrouter-for-translatepress' ), [
            'target_language' => $target_language_code,
            'source_language' => $source_language_code,
            'count'           => count( $strings ),
        ] );

        if ( ! $this->is_configured() ) {
            $this->logger->error( __( 'Qwen 未配置可用 API Key', 'langrouter-for-translatepress' ), [] );
            return [];
        }

        $source_language = '';
        if ( null !== $source_language_code ) {
            $source_language = $this->get_request_language_value( $source_language_code );
        }
        if ( '' === $source_language ) {
            $source_language = 'auto';
        }

        $target_language = $this->get_request_language_value( $target_language_code );
        if ( '' === $target_language ) {
            $this->logger->error( __( 'Qwen 目标语言未映射成功', 'langrouter-for-translatepress' ), [ 'target_language' => $target_language_code ] );
            return [];
        }

        $items = [];
        foreach ( $strings as $key => $value ) {
            if ( $this->should_skip_text( $value ) ) {
                continue;
            }
            $hash = md5( (string) $value );
            if ( ! isset( $items[ $hash ] ) ) {
                $items[ $hash ] = [ 'id' => $hash, 'text' => $value, 'keys' => [ $key ] ];
            } else {
                $items[ $hash ]['keys'][] = $key;
            }
        }

        if ( empty( $items ) ) {
            return [];
        }

        $translated = [];
        $items_list  = array_values( $items );
        $results     = $this->execute_requests( $items_list, $source_language, $target_language, $target_language_code );

        foreach ( $items_list as $item ) {
            $item_id = $item['id'];
            if ( empty( $results[ $item_id ]['success'] ) ) {
                continue;
            }

            foreach ( $item['keys'] as $key ) {
                $translated[ $key ] = $results[ $item_id ]['translated'];
            }
        }

        $this->logger->debug( __( 'Qwen translate_batch 结束', 'langrouter-for-translatepress' ), [
            'translated_count' => count( $translated ),
            'requested_count'  => count( $strings ),
        ] );

        return $translated;
    }


    protected function execute_requests( array $items, $source_language, $target_language, $target_language_code ) {
        if ( $this->should_use_parallel_http( $items ) ) {
            return $this->execute_parallel_requests( $items, $source_language, $target_language, $target_language_code );
        }

        return $this->execute_serial_requests( $items, $source_language, $target_language, $target_language_code );
    }

    protected function should_use_parallel_http( array $items ) {
        if ( $this->get_concurrency() <= 1 || count( $items ) <= 1 ) {
            return false;
        }

        return function_exists( 'curl_multi_init' ) && function_exists( 'curl_multi_exec' ) && function_exists( 'curl_init' );
    }

    protected function execute_serial_requests( array $items, $source_language, $target_language, $target_language_code ) {
        $results = [];
        foreach ( $items as $item ) {
            $response = $this->send_request( $source_language, $target_language, $item['text'] );
            $results[ $item['id'] ] = $this->build_result_from_response( $response, $item, $target_language_code );
        }
        return $results;
    }

    protected function execute_parallel_requests( array $items, $source_language, $target_language, $target_language_code ) {
        $results      = [];
        $endpoint     = $this->get_endpoint_url();
        $timeout      = $this->get_timeout();
        $concurrency  = min( $this->get_concurrency(), count( $items ) );
        $multi_handle = curl_multi_init();
        $active       = [];
        $item_queue   = array_values( $items );

        $this->logger->debug( __( 'Qwen 并发翻译开始', 'langrouter-for-translatepress' ), [
            'concurrency'  => $concurrency,
            'unique_count' => count( $items ),
        ] );

        while ( count( $active ) < $concurrency && ! empty( $item_queue ) ) {
            $item = array_shift( $item_queue );
            $ch   = $this->build_curl_handle( $endpoint, $timeout, $source_language, $target_language, $item['text'] );
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
                    $results[ $item['id'] ] = $this->build_result_from_transport_error( $curl_error, $item, $target_language_code );
                } else {
                    $results[ $item['id'] ] = $this->build_result_from_http_payload( $status_code, $body, $item, $target_language_code );
                }

                curl_multi_remove_handle( $multi_handle, $ch );
                curl_close( $ch );
                unset( $active[ (int) $ch ] );

                while ( count( $active ) < $concurrency && ! empty( $item_queue ) ) {
                    $next_item = array_shift( $item_queue );
                    $next_ch   = $this->build_curl_handle( $endpoint, $timeout, $source_language, $target_language, $next_item['text'] );
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
                curl_multi_select( $multi_handle, 1.0 );
            }
        } while ( $running || ! empty( $active ) );

        curl_multi_close( $multi_handle );

        return $results;
    }

    protected function build_curl_handle( $endpoint, $timeout, $source_language, $target_language, $text ) {
        $payload = $this->build_request_body_json( $source_language, $target_language, $text );
        if ( ! is_string( $payload ) ) {
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
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, min( 10, $timeout ) );
        curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
        curl_setopt( $ch, CURLOPT_ENCODING, '' );

        return $ch;
    }

    protected function build_result_from_response( $response, array $item, $target_language_code ) {
        if ( is_wp_error( $response ) ) {
            return $this->build_result_from_transport_error( $response->get_error_message(), $item, $target_language_code );
        }

        return $this->build_result_from_http_payload(
            (int) wp_remote_retrieve_response_code( $response ),
            (string) wp_remote_retrieve_body( $response ),
            $item,
            $target_language_code
        );
    }

    protected function build_result_from_transport_error( $message, array $item, $target_language_code ) {
        $this->logger->error( __( 'Qwen 请求失败', 'langrouter-for-translatepress' ), [
            'error'       => (string) $message,
            'target_lang' => $target_language_code,
            'text_len'    => function_exists( 'mb_strlen' ) ? mb_strlen( (string) $item['text'], 'UTF-8' ) : strlen( (string) $item['text'] ),
        ] );

        return [ 'success' => false, 'translated' => '' ];
    }

    protected function build_result_from_http_payload( $code, $body, array $item, $target_language_code ) {
        $text = $this->extract_translation_text( [
            'response' => [ 'code' => (int) $code ],
            'body'     => (string) $body,
        ] );

        $this->logger->debug( __( 'Qwen 单条翻译返回', 'langrouter-for-translatepress' ), [
            'code'        => (int) $code,
            'target_lang' => $target_language_code,
            'text_len'    => function_exists( 'mb_strlen' ) ? mb_strlen( (string) $item['text'], 'UTF-8' ) : strlen( (string) $item['text'] ),
            'ok'          => '' !== $text,
        ] );

        if ( '' === $text ) {
            $this->logger->error( __( 'Qwen 返回无法解析的响应', 'langrouter-for-translatepress' ), [
                'code' => (int) $code,
                'body' => wp_strip_all_tags( (string) $body ),
            ] );
            return [ 'success' => false, 'translated' => '' ];
        }

        return [ 'success' => true, 'translated' => $text ];
    }

    public function create_translator( array $tp_settings, TPRE_Logger $logger, $translator_class = 'TRP_Qwen_Machine_Translator' ) {
        if ( function_exists( 'tpre_load_qwen_translator_class' ) && ! tpre_load_qwen_translator_class() ) {
            return null;
        }

        if ( ! class_exists( $translator_class ) ) {
            return null;
        }

        return new $translator_class( $tp_settings, $this, $logger );
    }

    public function test_request() {
        $this->logger->debug( __( 'Qwen test_request 开始', 'langrouter-for-translatepress' ), [
            'configured' => $this->is_configured(),
            'endpoint'   => $this->get_endpoint_url(),
            'normalized' => true,
            'model'      => $this->get_model(),
        ] );

        if ( ! $this->is_configured() ) {
            return [
                'response' => [ 'code' => 400 ],
                'body' => __( 'Qwen API Key 未配置', 'langrouter-for-translatepress' ),
            ];
        }

        $response = $this->send_request( 'Chinese', 'English', '我看到这个视频后没有笑' );
        $code     = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
        $body     = is_wp_error( $response ) ? $response->get_error_message() : (string) wp_remote_retrieve_body( $response );
        $this->logger->debug( __( 'Qwen test_request 返回', 'langrouter-for-translatepress' ), [
            'code' => $code,
            'body' => wp_strip_all_tags( $body ),
        ] );

        return is_wp_error( $response )
            ? [ 'response' => [ 'code' => 0 ], 'body' => $response->get_error_message() ]
            : $response;
    }
}
