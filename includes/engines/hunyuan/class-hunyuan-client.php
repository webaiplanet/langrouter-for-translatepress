<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// phpcs:disable WordPress.WP.AlternativeFunctions -- Intentional use of cURL/cURL multi for provider-specific parallel request handling.

class TPRE_Hunyuan_Client {
    const DEFAULT_TIMEOUT = 20;
    const DEFAULT_MODEL = 'hunyuan-translation-lite';
    const DEFAULT_CONCURRENCY = 5;
    const ENDPOINT_TENCENT = 'https://hunyuan.tencentcloudapi.com/';
    const ENDPOINT_TENCENT_INTL = 'https://hunyuan.ai.intl.tencentcloudapi.com/';
    const ENDPOINT_SILICONFLOW = 'https://api.siliconflow.cn/v1/chat/completions';
    const TENCENT_VERSION = '2023-09-01';
    const TENCENT_ACTION = 'ChatTranslations';
    const MODEL_MT7B = 'hunyuan-mt-7b';
    const MODEL_MT7B_VENDOR = 'tencent/Hunyuan-MT-7B';

    protected $router_settings;
    protected $logger;

    protected $common_supported_codes = [
        'zh', 'zh-TR', 'yue', 'en', 'fr', 'pt', 'es', 'ja', 'tr', 'ru', 'ar', 'ko', 'th', 'it', 'de', 'vi', 'ms', 'id',
    ];

    protected $full_supported_codes = [
        'zh', 'zh-TR', 'yue', 'en', 'fr', 'pt', 'es', 'ja', 'tr', 'ru', 'ar', 'ko', 'th', 'it', 'de', 'vi', 'ms', 'id',
        'fil', 'hi', 'pl', 'cs', 'nl', 'km', 'my', 'fa', 'gu', 'ur', 'te', 'mr', 'he', 'bn', 'ta', 'uk', 'bo', 'kk', 'mn', 'ug',
    ];

    protected $mt7b_supported_codes = [
        'zh', 'en', 'ja', 'ko', 'de', 'fr', 'es', 'it', 'pt', 'ru', 'ar', 'cs', 'id', 'ms', 'nl', 'pl', 'tr', 'uk', 'vi',
    ];

    protected $language_names = [
        'zh'    => 'Simplified Chinese',
        'zh-TR' => 'Traditional Chinese',
        'yue'   => 'Cantonese',
        'en'    => 'English',
        'fr'    => 'French',
        'pt'    => 'Portuguese',
        'es'    => 'Spanish',
        'ja'    => 'Japanese',
        'tr'    => 'Turkish',
        'ru'    => 'Russian',
        'ar'    => 'Arabic',
        'ko'    => 'Korean',
        'th'    => 'Thai',
        'it'    => 'Italian',
        'de'    => 'German',
        'vi'    => 'Vietnamese',
        'ms'    => 'Malay',
        'id'    => 'Indonesian',
        'fil'   => 'Filipino',
        'hi'    => 'Hindi',
        'pl'    => 'Polish',
        'cs'    => 'Czech',
        'nl'    => 'Dutch',
        'km'    => 'Khmer',
        'my'    => 'Burmese',
        'fa'    => 'Persian',
        'gu'    => 'Gujarati',
        'ur'    => 'Urdu',
        'te'    => 'Telugu',
        'mr'    => 'Marathi',
        'he'    => 'Hebrew',
        'bn'    => 'Bengali',
        'ta'    => 'Tamil',
        'uk'    => 'Ukrainian',
        'bo'    => 'Tibetan',
        'kk'    => 'Kazakh',
        'mn'    => 'Mongolian',
        'ug'    => 'Uyghur',
    ];

    protected $locale_code_map = [
        'zh_CN' => 'zh', 'zh_SG' => 'zh', 'zh' => 'zh',
        'zh_TW' => 'zh-TR', 'zh_HK' => 'zh-TR', 'zh_MO' => 'zh-TR', 'zh_Hant' => 'zh-TR', 'zh_Hant_TW' => 'zh-TR', 'zh_Hant_HK' => 'zh-TR',
        'yue' => 'yue', 'yue_HK' => 'yue',
        'en' => 'en', 'en_US' => 'en', 'en_GB' => 'en', 'en_CA' => 'en', 'en_AU' => 'en', 'en_NZ' => 'en', 'en_ZA' => 'en',
        'fr' => 'fr', 'fr_FR' => 'fr', 'fr_CA' => 'fr',
        'pt' => 'pt', 'pt_BR' => 'pt', 'pt_PT' => 'pt', 'pt_AO' => 'pt', 'pt_PT_ao90' => 'pt',
        'es' => 'es', 'es_ES' => 'es', 'es_AR' => 'es', 'es_CL' => 'es', 'es_CO' => 'es', 'es_CR' => 'es', 'es_DO' => 'es', 'es_EC' => 'es', 'es_GT' => 'es', 'es_MX' => 'es', 'es_PE' => 'es', 'es_PR' => 'es', 'es_UY' => 'es', 'es_VE' => 'es',
        'ja' => 'ja', 'ja_JP' => 'ja',
        'tr' => 'tr', 'tr_TR' => 'tr',
        'ru' => 'ru', 'ru_RU' => 'ru',
        'ar' => 'ar', 'ar_AR' => 'ar', 'ar_SA' => 'ar',
        'ko' => 'ko', 'ko_KR' => 'ko',
        'th' => 'th', 'th_TH' => 'th',
        'it' => 'it', 'it_IT' => 'it',
        'de' => 'de', 'de_DE' => 'de', 'de_DE_formal' => 'de',
        'vi' => 'vi', 'vi_VN' => 'vi',
        'ms' => 'ms', 'ms_MY' => 'ms',
        'id' => 'id', 'id_ID' => 'id',
        'fil' => 'fil',
        'hi' => 'hi', 'hi_IN' => 'hi',
        'pl' => 'pl', 'pl_PL' => 'pl',
        'cs' => 'cs', 'cs_CZ' => 'cs',
        'nl' => 'nl', 'nl_NL' => 'nl',
        'km' => 'km', 'km_KH' => 'km',
        'my' => 'my', 'my_MM' => 'my',
        'fa' => 'fa', 'fa_IR' => 'fa',
        'gu' => 'gu',
        'ur' => 'ur',
        'te' => 'te',
        'mr' => 'mr',
        'he' => 'he', 'he_IL' => 'he',
        'bn' => 'bn', 'bn_BD' => 'bn',
        'ta' => 'ta', 'ta_IN' => 'ta',
        'uk' => 'uk', 'uk_UA' => 'uk',
        'bo' => 'bo',
        'kk' => 'kk',
        'mn' => 'mn', 'mn_MN' => 'mn',
        'ug' => 'ug',
    ];

    public function __construct( array $router_settings, TPRE_Logger $logger ) {
        $this->router_settings = $router_settings;
        $this->logger = $logger;
    }

    public function get_model_settings() {
        $models = isset( $this->router_settings['models'] ) && is_array( $this->router_settings['models'] ) ? $this->router_settings['models'] : [];
        $defaults = [
            'enabled' => 0,
            'endpoint' => '',
            'model' => self::DEFAULT_MODEL,
            'api_key' => '',
            'secret_key' => '',
            'region' => '',
            'site' => 'cn',
            'timeout' => self::DEFAULT_TIMEOUT,
            'concurrency' => self::DEFAULT_CONCURRENCY,
            'extra_body_json' => '',
            'note' => '',
        ];
        $item = isset( $models['hunyuan'] ) && is_array( $models['hunyuan'] ) ? $models['hunyuan'] : [];
        return wp_parse_args( $item, $defaults );
    }

    public function normalize_model_slug( $model ) {
        $model = trim( (string) $model );
        $lower = strtolower( $model );
        if ( in_array( $lower, [ 'hunyuan-translation-lite', 'hunyuan-translation' ], true ) ) {
            return $lower;
        }
        if ( in_array( $lower, [ 'hunyuan-mt-7b', 'tencent/hunyuan-mt-7b', 'hunyuan_mt_7b' ], true ) ) {
            return self::MODEL_MT7B;
        }
        return self::DEFAULT_MODEL;
    }

    public function get_model() {
        $item = $this->get_model_settings();
        return $this->normalize_model_slug( $item['model'] ?? self::DEFAULT_MODEL );
    }

    public function is_official_model() {
        return in_array( $this->get_model(), [ 'hunyuan-translation-lite', 'hunyuan-translation' ], true );
    }

    public function is_openai_compatible_model() {
        return self::MODEL_MT7B === $this->get_model();
    }

    public function get_provider_mode() {
        return $this->is_official_model() ? 'tencent' : 'openai';
    }

    public function get_supported_codes() {
        $model = $this->get_model();
        if ( 'hunyuan-translation' === $model ) {
            return $this->full_supported_codes;
        }
        if ( self::MODEL_MT7B === $model ) {
            return $this->mt7b_supported_codes;
        }
        return $this->common_supported_codes;
    }

    public function supports_language( $language_code ) {
        return '' !== $this->map_trp_locale_to_hunyuan_code( $language_code );
    }

    public function get_language_support_meta( $language_code ) {
        $mapped = $this->map_trp_locale_to_hunyuan_code( $language_code );
        $model  = $this->get_model();
        $source = 'builtin_official_list_lite';

        if ( 'hunyuan-translation' === $model ) {
            $source = 'builtin_official_list_full';
        } elseif ( self::MODEL_MT7B === $model ) {
            $source = 'builtin_plugin_mt7b_subset';
        }

        return [
            'raw'        => is_string( $language_code ) ? trim( $language_code ) : '',
            'candidates' => '' !== $mapped ? [ $mapped ] : [],
            'supported'  => '' !== $mapped,
            'source'     => $source,
            'model'      => $model,
        ];
    }

    public function get_request_language_name( $trp_language_code ) {
        $code = $this->map_trp_locale_to_hunyuan_code( $trp_language_code );
        if ( '' === $code ) {
            return '';
        }
        return isset( $this->language_names[ $code ] ) ? $this->language_names[ $code ] : $code;
    }

    public function map_trp_locale_to_hunyuan_code( $trp_language_code ) {
        if ( isset( $this->locale_code_map[ $trp_language_code ] ) ) {
            $mapped = $this->locale_code_map[ $trp_language_code ];
            return in_array( $mapped, $this->get_supported_codes(), true ) ? $mapped : '';
        }

        $normalized = str_replace( '-', '_', (string) $trp_language_code );
        if ( isset( $this->locale_code_map[ $normalized ] ) ) {
            $mapped = $this->locale_code_map[ $normalized ];
            return in_array( $mapped, $this->get_supported_codes(), true ) ? $mapped : '';
        }

        $lowered = strtolower( $normalized );
        if ( isset( $this->locale_code_map[ $lowered ] ) ) {
            $mapped = $this->locale_code_map[ $lowered ];
            return in_array( $mapped, $this->get_supported_codes(), true ) ? $mapped : '';
        }

        $iso_guess = preg_replace( '/[_-].*/', '', strtolower( (string) $trp_language_code ) );
        return in_array( $iso_guess, $this->get_supported_codes(), true ) ? $iso_guess : '';
    }

    public function is_configured() {
        $item = $this->get_model_settings();
        if ( empty( $item['enabled'] ) ) {
            return false;
        }
        if ( $this->is_official_model() ) {
            return '' !== trim( (string) ( $item['api_key'] ?? '' ) ) && '' !== trim( (string) ( $item['secret_key'] ?? '' ) );
        }
        return '' !== trim( (string) ( $item['api_key'] ?? '' ) );
    }

    public function get_timeout() {
        $item = $this->get_model_settings();
        return max( 5, (int) ( $item['timeout'] ?? self::DEFAULT_TIMEOUT ) );
    }

    public function get_api_key() {
        $item = $this->get_model_settings();
        return trim( (string) ( $item['api_key'] ?? '' ) );
    }

    public function get_secret_key() {
        $item = $this->get_model_settings();
        return trim( (string) ( $item['secret_key'] ?? '' ) );
    }

    public function get_region() {
        $item = $this->get_model_settings();
        $region = trim( (string) ( $item['region'] ?? '' ) );
        return in_array( strtolower( $region ), [ 'cn', 'intl' ], true ) ? '' : $region;
    }

    public function get_site() {
        $item = $this->get_model_settings();
        $site = strtolower( trim( (string) ( $item['site'] ?? 'cn' ) ) );
        return 'intl' === $site ? 'intl' : 'cn';
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

    protected function normalize_endpoint_url( $endpoint ) {
        $endpoint = trim( (string) $endpoint );
        if ( '' === $endpoint ) {
            return '';
        }
        $endpoint = preg_replace( '#/+$#', '', $endpoint );

        if ( $this->is_official_model() ) {
            $lower = strtolower( $endpoint );
            if ( false !== strpos( $lower, 'api.hunyuan.cloud.tencent.com' ) || false !== strpos( $lower, '/v1/chat/completions' ) || false !== strpos( $lower, '/compatible-mode/' ) ) {
                return self::ENDPOINT_TENCENT;
            }
            if ( false === strpos( $lower, 'hunyuan.tencentcloudapi.com' ) ) {
                return $endpoint . '/';
            }
            return preg_replace( '#^https?://hunyuan\.tencentcloudapi\.com.*$#i', self::ENDPOINT_TENCENT, $endpoint );
        }

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
        if ( ! empty( $item['endpoint'] ) ) {
            $normalized = $this->normalize_endpoint_url( $item['endpoint'] );
            if ( '' !== $normalized ) {
                return $normalized;
            }
        }
        if ( $this->is_official_model() ) {
            return 'intl' === $this->get_site() ? self::ENDPOINT_TENCENT_INTL : self::ENDPOINT_TENCENT;
        }
        return self::ENDPOINT_SILICONFLOW;
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
            '/\b(OpenAI|Qwen|DeepL|Hunyuan|火山方舟|Hunyuan)\s+(?:translate_batch|批量翻译|单条并发兜底|单条兜底|翻译)/u',
            $text
        );
    }

    protected function get_skip_reason( $text ) {
        $raw = html_entity_decode( (string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $stripped = trim( wp_strip_all_tags( $raw ) );
        if ( '' === $stripped ) {
            return 'empty_after_strip';
        }
        if ( ! preg_match( '/\p{L}/u', $stripped ) ) {
            return 'no_letter_after_strip';
        }
        if ( $this->looks_like_internal_log_or_debug_text( $raw ) || $this->looks_like_internal_log_or_debug_text( $stripped ) ) {
            return 'internal_log_or_debug_text';
        }
        if ( preg_match( '/^oembed\s*\((json|xml)\)$/i', $stripped ) ) {
            return 'oembed_marker';
        }
        if ( class_exists( 'TPRE_Translation_Safety_Utils' ) && TPRE_Translation_Safety_Utils::looks_like_code_or_template_fragment( $raw ) ) {
            return 'code_or_template_fragment';
        }
        return '';
    }

    protected function should_skip_text( $text ) {
        return '' !== $this->get_skip_reason( $text );
    }

    protected function sanitize_translation_candidate( $text ) {
        $text = trim( (string) $text );
        $text = preg_replace( '/^```(?:json|text|html)?\s*/iu', '', $text );
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
            '/\bHere(?:\sis)?\s+the translation\b/i',
            '/^(translation|translated text|note|explanation)\s*[:：-]/i',
            '/(?:^|\n)\s*[-*•]\s+/u',
        ];

        foreach ( $patterns as $pattern ) {
            if ( preg_match( $pattern, $text ) ) {
                return true;
            }
        }

        return false;
    }

    protected function is_valid_translation_result( $source_text, $translated_text, $target_language_code ) {
        $translated_text = $this->sanitize_translation_candidate( $translated_text );
        if ( '' === $translated_text ) {
            return false;
        }

        if ( $this->looks_like_explanation_output( $translated_text ) ) {
            return false;
        }

        $normalized_source = trim( preg_replace( '/\s+/u', ' ', strtolower( html_entity_decode( wp_strip_all_tags( (string) $source_text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) ) );
        $normalized_translated = trim( preg_replace( '/\s+/u', ' ', strtolower( html_entity_decode( wp_strip_all_tags( (string) $translated_text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) ) );
        if ( '' !== $normalized_source && $normalized_source === $normalized_translated ) {
            return false;
        }

        $target_language_code = strtolower( (string) $target_language_code );
        if ( ! in_array( $target_language_code, [ 'zh', 'zh_cn', 'zh_tw', 'zh-hant', 'ja', 'ko', 'yue' ], true ) ) {
            $source_has_cjk     = (bool) preg_match( '/[\x{3040}-\x{30ff}\x{3400}-\x{4dbf}\x{4e00}-\x{9fff}\x{f900}-\x{faff}]/u', (string) $source_text );
            $translated_has_cjk = (bool) preg_match( '/[\x{3040}-\x{30ff}\x{3400}-\x{4dbf}\x{4e00}-\x{9fff}\x{f900}-\x{faff}]/u', (string) $translated_text );
            if ( $translated_has_cjk && ! $source_has_cjk ) {
                return false;
            }
        }

        if ( class_exists( 'TPRE_Translation_Safety_Utils' ) && TPRE_Translation_Safety_Utils::has_dangerous_markup_mismatch( $source_text, $translated_text ) ) {
            return false;
        }

        return true;
    }

    protected function summarize_text_for_log( $text, $max_length = 120 ) {
        $preview = html_entity_decode( (string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $preview = preg_replace( '/\s+/u', ' ', trim( wp_strip_all_tags( $preview ) ) );
        if ( '' === $preview ) {
            $preview = preg_replace( '/\s+/u', ' ', trim( (string) $text ) );
        }
        if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
            if ( mb_strlen( $preview, 'UTF-8' ) > $max_length ) {
                $preview = mb_substr( $preview, 0, $max_length, 'UTF-8' ) . '…';
            }
        } elseif ( strlen( $preview ) > $max_length ) {
            $preview = substr( $preview, 0, $max_length ) . '...';
        }
        return $preview;
    }

    protected function extract_api_error_from_body( $body ) {
        if ( '' === (string) $body ) {
            return [];
        }
        $decoded = json_decode( (string) $body, true );
        if ( ! is_array( $decoded ) ) {
            return [];
        }
        if ( isset( $decoded['Response']['Error'] ) && is_array( $decoded['Response']['Error'] ) ) {
            return [
                'code' => (string) ( $decoded['Response']['Error']['Code'] ?? '' ),
                'message' => (string) ( $decoded['Response']['Error']['Message'] ?? '' ),
            ];
        }
        if ( isset( $decoded['error'] ) && is_array( $decoded['error'] ) ) {
            return [
                'code' => (string) ( $decoded['error']['code'] ?? '' ),
                'message' => (string) ( $decoded['error']['message'] ?? '' ),
            ];
        }
        return [];
    }

    protected function estimate_max_tokens( $text ) {
        $length = function_exists( 'mb_strlen' ) ? mb_strlen( (string) $text, 'UTF-8' ) : strlen( (string) $text );
        return max( 128, min( 4096, (int) ceil( $length * 2.2 ) ) );
    }

    protected function get_openai_compatible_model_name() {
        return self::MODEL_MT7B_VENDOR;
    }

    protected function build_openai_payload( $source_language_name, $target_language_name, $text ) {
        $system_prompt = 'You are a professional translation engine. Translate the user text from '
            . ( '' !== $source_language_name ? $source_language_name : 'the source language' )
            . ' to ' . $target_language_name
            . '. Preserve HTML tags, placeholders (%s, %1$s, {{var}}, :name), URLs, numbers, punctuation and line breaks. Output translation only. Do not add explanations, quotes, or notes. For very short labels or single CJK characters, prefer concise website wording that fits tables and UI labels. Do not turn them into language names, country names, route names, or explanatory phrases unless the source explicitly means that.';

        $payload = [
            'model' => $this->get_openai_compatible_model_name(),
            'messages' => [
                [ 'role' => 'system', 'content' => $system_prompt ],
                [ 'role' => 'user', 'content' => (string) $text ],
            ],
            'temperature' => 0,
            'top_p' => 0.2,
            'stream' => false,
            'max_tokens' => $this->estimate_max_tokens( $text ),
        ];

        $extra = $this->get_extra_body();
        if ( ! empty( $extra ) ) {
            $payload = array_replace_recursive( $payload, $extra );
        }
        return $payload;
    }

    protected function build_tencent_payload( $source_code, $target_code, $text ) {
        $payload = [
            'Model' => $this->get_model(),
            'Stream' => false,
            'Text' => (string) $text,
            'Source' => (string) $source_code,
            'Target' => (string) $target_code,
        ];

        $extra = $this->get_extra_body();
        if ( ! empty( $extra ) ) {
            $payload = array_replace_recursive( $payload, $extra );
        }
        return $payload;
    }

    protected function build_headers_for_openai() {
        return [
            'Authorization' => 'Bearer ' . $this->get_api_key(),
            'Content-Type' => 'application/json',
        ];
    }

    protected function get_tencent_signing_headers( $payload_json ) {
        $host = wp_parse_url( $this->get_endpoint_url(), PHP_URL_HOST );
        if ( ! $host ) {
            $host = 'hunyuan.tencentcloudapi.com';
        }
        $action = self::TENCENT_ACTION;
        $timestamp = time();
        $date = gmdate( 'Y-m-d', $timestamp );
        $service = 'hunyuan';
        $algorithm = 'TC3-HMAC-SHA256';
        $canonical_uri = '/';
        $canonical_querystring = '';
        $content_type = 'application/json; charset=utf-8';
        $canonical_headers = "content-type:{$content_type}
        host:{$host}
        x-tc-action:" . strtolower( $action ) . "
        ";
        $signed_headers = 'content-type;host;x-tc-action';
        $hashed_request_payload = hash( 'sha256', (string) $payload_json );
        $canonical_request = "POST
        {$canonical_uri}
        {$canonical_querystring}
        {$canonical_headers}
        {$signed_headers}
        {$hashed_request_payload}";
        $credential_scope = $date . '/' . $service . '/tc3_request';
        $string_to_sign = $algorithm . "
        " . $timestamp . "
        " . $credential_scope . "
        " . hash( 'sha256', $canonical_request );
        $secret_date = hash_hmac( 'sha256', $date, 'TC3' . $this->get_secret_key(), true );
        $secret_service = hash_hmac( 'sha256', $service, $secret_date, true );
        $secret_signing = hash_hmac( 'sha256', 'tc3_request', $secret_service, true );
        $signature = hash_hmac( 'sha256', $string_to_sign, $secret_signing );
        $authorization = $algorithm
            . ' Credential=' . $this->get_api_key() . '/' . $credential_scope
            . ', SignedHeaders=' . $signed_headers
            . ', Signature=' . $signature;

        $headers = [
            'Authorization' => $authorization,
            'Content-Type' => $content_type,
            'Host' => $host,
            'X-TC-Action' => $action,
            'X-TC-Version' => self::TENCENT_VERSION,
            'X-TC-Timestamp' => (string) $timestamp,
        ];
        if ( '' !== $this->get_region() ) {
            $headers['X-TC-Region'] = $this->get_region();
        }
        return $headers;
    }

    protected function build_request_context( $source_language_code, $target_language_code, $text ) {
        if ( $this->is_official_model() ) {
            $payload = $this->build_tencent_payload( $source_language_code, $target_language_code, $text );
            $payload_json = wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
            return [
                'endpoint' => $this->get_endpoint_url(),
                'headers' => $this->get_tencent_signing_headers( $payload_json ),
                'body' => $payload_json,
                'mode' => 'tencent',
            ];
        }

        $source_language_name = '' !== $source_language_code ? $this->get_request_language_name( $source_language_code ) : '';
        $target_language_name = $this->get_request_language_name( $target_language_code );
        $payload = $this->build_openai_payload( $source_language_name, $target_language_name, $text );
        return [
            'endpoint' => $this->get_endpoint_url(),
            'headers' => $this->build_headers_for_openai(),
            'body' => wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
            'mode' => 'openai',
        ];
    }

    protected function send_request( $source_language_code, $target_language_code, $text ) {
        $ctx = $this->build_request_context( $source_language_code, $target_language_code, $text );
        return wp_remote_post(
            $ctx['endpoint'],
            [
                'timeout' => $this->get_timeout(),
                'headers' => $ctx['headers'],
                'body' => $ctx['body'],
            ]
        );
    }

    protected function get_global_concurrency_limit() {
        return isset( $this->router_settings['global_concurrency_limit'] ) ? max( 0, (int) $this->router_settings['global_concurrency_limit'] ) : 0;
    }

    protected function get_concurrency() {
        if ( $this->is_official_model() ) {
            return 1;
        }

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

    protected function should_use_parallel_http( array $items ) {
        if ( $this->is_official_model() ) {
            return false;
        }
        if ( $this->get_concurrency() <= 1 || count( $items ) <= 1 ) {
            return false;
        }
        return function_exists( 'curl_multi_init' ) && function_exists( 'curl_multi_exec' ) && function_exists( 'curl_init' );
    }

    protected function build_curl_handle( $source_language_code, $target_language_code, $text ) {
        $ctx = $this->build_request_context( $source_language_code, $target_language_code, $text );
        $header_lines = [];
        foreach ( $ctx['headers'] as $header_name => $header_value ) {
            $header_lines[] = $header_name . ': ' . $header_value;
        }
        $ch = curl_init( $ctx['endpoint'] );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $header_lines );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $ctx['body'] );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, min( 10, $this->get_timeout() ) );
        curl_setopt( $ch, CURLOPT_TIMEOUT, $this->get_timeout() );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
        curl_setopt( $ch, CURLOPT_ENCODING, '' );
        return $ch;
    }

    protected function extract_translation_text( $response ) {
        if ( is_wp_error( $response ) ) {
            return '';
        }
        $body = wp_remote_retrieve_body( $response );
        return $this->extract_translation_text_from_body( $body );
    }

    protected function extract_translation_text_from_body( $body ) {
        if ( '' === (string) $body ) {
            return '';
        }
        $decoded = json_decode( (string) $body, true );
        if ( ! is_array( $decoded ) ) {
            return '';
        }

        if ( isset( $decoded['Response']['Choices'][0]['Message']['Content'] ) ) {
            return trim( (string) $decoded['Response']['Choices'][0]['Message']['Content'] );
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
        return '';
    }

    protected function build_result_from_transport_error( $message, array $item, $target_language_code ) {
        $this->logger->error( __( 'Hunyuan请求失败', 'langrouter-for-translatepress' ), [
            'error' => (string) $message,
            'target_lang' => $target_language_code,
            'text_len' => function_exists( 'mb_strlen' ) ? mb_strlen( (string) $item['text'], 'UTF-8' ) : strlen( (string) $item['text'] ),
            'provider' => $this->get_provider_mode(),
            'model' => $this->get_model(),
        ] );
        return [ 'success' => false, 'translated' => '' ];
    }

    protected function build_result_from_http_payload( $code, $body, array $item, $target_language_code ) {
        $text = $this->extract_translation_text_from_body( (string) $body );
        $api_error = $this->extract_api_error_from_body( (string) $body );
        $this->logger->debug( __( 'Hunyuan单条翻译返回', 'langrouter-for-translatepress' ), [
            'code' => (int) $code,
            'target_lang' => $target_language_code,
            'text_len' => function_exists( 'mb_strlen' ) ? mb_strlen( (string) $item['text'], 'UTF-8' ) : strlen( (string) $item['text'] ),
            'ok' => '' !== $text,
            'api_error_code' => (string) ( $api_error['code'] ?? '' ),
            'provider' => $this->get_provider_mode(),
            'model' => $this->get_model(),
        ] );
        $text = $this->sanitize_translation_candidate( $text );
        if ( '' === $text ) {
            $this->logger->error( __( 'Hunyuan返回无法解析的响应', 'langrouter-for-translatepress' ), [
                'code' => (int) $code,
                'api_error_code' => (string) ( $api_error['code'] ?? '' ),
                'api_error_message' => (string) ( $api_error['message'] ?? '' ),
                'body' => wp_strip_all_tags( (string) $body ),
                'text_preview' => $this->summarize_text_for_log( $item['text'] ),
                'provider' => $this->get_provider_mode(),
                'model' => $this->get_model(),
            ] );
            return [ 'success' => false, 'translated' => '' ];
        }
        if ( ! $this->is_valid_translation_result( $item['text'], $text, $target_language_code ) ) {
            $this->logger->debug( __( 'Hunyuan返回结果未通过安全校验，回退原文', 'langrouter-for-translatepress' ), [
                'code' => (int) $code,
                'target_lang' => $target_language_code,
                'source_preview' => $this->summarize_text_for_log( $item['text'] ),
                'translated_preview' => $this->summarize_text_for_log( $text ),
                'provider' => $this->get_provider_mode(),
                'model' => $this->get_model(),
            ] );
            return [ 'success' => false, 'translated' => '' ];
        }
        return [ 'success' => true, 'translated' => $text ];
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

    protected function execute_serial_requests( array $items, $source_language_code, $target_language_code ) {
        $results = [];
        foreach ( $items as $item ) {
            $response = $this->send_request( $source_language_code, $target_language_code, $item['text'] );
            $results[ $item['id'] ] = $this->build_result_from_response( $response, $item, $target_language_code );
        }
        return $results;
    }

    protected function execute_parallel_requests( array $items, $source_language_code, $target_language_code ) {
        $results = [];
        $concurrency = min( $this->get_concurrency(), count( $items ) );
        $multi_handle = curl_multi_init();
        $active = [];
        $item_queue = array_values( $items );

        $this->logger->debug( __( 'Hunyuan并发翻译开始', 'langrouter-for-translatepress' ), [ 'concurrency' => $concurrency, 'unique_count' => count( $items ), 'provider' => $this->get_provider_mode(), 'model' => $this->get_model() ] );

        while ( count( $active ) < $concurrency && ! empty( $item_queue ) ) {
            $item = array_shift( $item_queue );
            $ch = $this->build_curl_handle( $source_language_code, $target_language_code, $item['text'] );
            curl_multi_add_handle( $multi_handle, $ch );
            $active[ (int) $ch ] = [ 'handle' => $ch, 'item' => $item ];
        }

        do {
            do {
                $multi_exec = curl_multi_exec( $multi_handle, $running );
            } while ( $multi_exec === CURLM_CALL_MULTI_PERFORM );

            while ( $info = curl_multi_info_read( $multi_handle ) ) {
                $ch = $info['handle'];
                $meta = $active[ (int) $ch ] ?? null;
                if ( null === $meta ) {
                    curl_multi_remove_handle( $multi_handle, $ch );
                    curl_close( $ch );
                    continue;
                }

                $item = $meta['item'];
                $body = curl_multi_getcontent( $ch );
                $curl_errno = curl_errno( $ch );
                $curl_error = curl_error( $ch );
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
                    $next_ch = $this->build_curl_handle( $source_language_code, $target_language_code, $next_item['text'] );
                    curl_multi_add_handle( $multi_handle, $next_ch );
                    $active[ (int) $next_ch ] = [ 'handle' => $next_ch, 'item' => $next_item ];
                }
            }

            if ( $running && $multi_exec === CURLM_OK ) {
                curl_multi_select( $multi_handle, 1.0 );
            }
        } while ( $running || ! empty( $active ) );

        curl_multi_close( $multi_handle );
        return $results;
    }

    protected function execute_requests( array $items, $source_language_code, $target_language_code ) {
        if ( $this->should_use_parallel_http( $items ) ) {
            return $this->execute_parallel_requests( $items, $source_language_code, $target_language_code );
        }
        return $this->execute_serial_requests( $items, $source_language_code, $target_language_code );
    }

    public function translate_batch( array $strings, $target_language_code, $source_language_code = null ) {
        $this->logger->debug( __( 'Hunyuan translate_batch 开始', 'langrouter-for-translatepress' ), [
            'target_language' => $target_language_code,
            'source_language' => $source_language_code,
            'count' => count( $strings ),
            'model' => $this->get_model(),
            'provider' => $this->get_provider_mode(),
        ] );

        if ( ! $this->is_configured() ) {
            $this->logger->error( __( 'Hunyuan未配置可用凭据', 'langrouter-for-translatepress' ), [ 'provider' => $this->get_provider_mode(), 'model' => $this->get_model() ] );
            return [];
        }

        if ( null === $source_language_code ) {
            $source_language_code = '';
        }
        $source_mapped = '' !== (string) $source_language_code ? $this->map_trp_locale_to_hunyuan_code( $source_language_code ) : '';
        $target_mapped = $this->map_trp_locale_to_hunyuan_code( $target_language_code );
        if ( '' === $target_mapped ) {
            $this->logger->error( __( 'Hunyuan目标语言未映射成功', 'langrouter-for-translatepress' ), [ 'target_language' => $target_language_code, 'model' => $this->get_model(), 'provider' => $this->get_provider_mode() ] );
            return [];
        }
        if ( '' === $source_mapped ) {
            $source_mapped = 'auto';
        }

        $items = [];
        $skip_reason_counts = [];
        $skipped_samples = [];
        $duplicate_hits = 0;
        foreach ( $strings as $key => $value ) {
            $skip_reason = $this->get_skip_reason( $value );
            if ( '' !== $skip_reason ) {
                if ( ! isset( $skip_reason_counts[ $skip_reason ] ) ) {
                    $skip_reason_counts[ $skip_reason ] = 0;
                }
                $skip_reason_counts[ $skip_reason ]++;
                if ( count( $skipped_samples ) < 8 ) {
                    $skipped_samples[] = [
                        'key' => $key,
                        'reason' => $skip_reason,
                        'preview' => $this->summarize_text_for_log( $value ),
                    ];
                }
                continue;
            }
            $hash = md5( (string) $value );
            if ( ! isset( $items[ $hash ] ) ) {
                $items[ $hash ] = [ 'id' => $hash, 'text' => $value, 'keys' => [ $key ] ];
            } else {
                $items[ $hash ]['keys'][] = $key;
                $duplicate_hits++;
            }
        }

        $this->logger->debug( __( 'Hunyuan批次预处理结果', 'langrouter-for-translatepress' ), [
            'requested_count' => count( $strings ),
            'unique_count' => count( $items ),
            'skipped_count' => array_sum( $skip_reason_counts ),
            'duplicate_hits' => $duplicate_hits,
            'skip_reason_counts' => $skip_reason_counts,
            'skipped_samples' => $skipped_samples,
            'source_mapped' => $source_mapped,
            'target_mapped' => $target_mapped,
            'provider' => $this->get_provider_mode(),
            'model' => $this->get_model(),
        ] );

        if ( empty( $items ) ) {
            $this->logger->error( __( 'Hunyuan批次无可发送文本', 'langrouter-for-translatepress' ), [
                'requested_count' => count( $strings ),
                'skip_reason_counts' => $skip_reason_counts,
                'skipped_samples' => $skipped_samples,
                'provider' => $this->get_provider_mode(),
                'model' => $this->get_model(),
            ] );
            return [];
        }

        $translated = [];
        $items_list = array_values( $items );
        $results = $this->execute_requests( $items_list, $source_mapped, $target_mapped );
        $failed_samples = [];
        foreach ( $items_list as $item ) {
            $item_id = $item['id'];
            if ( empty( $results[ $item_id ]['success'] ) ) {
                if ( count( $failed_samples ) < 8 ) {
                    $failed_samples[] = [
                        'preview' => $this->summarize_text_for_log( $item['text'] ),
                        'key_count' => count( $item['keys'] ),
                    ];
                }
                continue;
            }
            foreach ( $item['keys'] as $key ) {
                $translated[ $key ] = $results[ $item_id ]['translated'];
            }
        }

        $successful_unique_count = 0;
        foreach ( $items_list as $item ) {
            $item_id = $item['id'];
            if ( ! empty( $results[ $item_id ]['success'] ) ) {
                $successful_unique_count++;
            }
        }
        $failed_unique_count = max( 0, count( $items_list ) - $successful_unique_count );

        if ( empty( $translated ) ) {
            $this->logger->error( __( 'Hunyuan批次请求已发送但无成功结果', 'langrouter-for-translatepress' ), [
                'requested_count' => count( $strings ),
                'unique_count' => count( $items_list ),
                'failed_unique_count' => $failed_unique_count,
                'failed_samples' => $failed_samples,
                'provider' => $this->get_provider_mode(),
                'model' => $this->get_model(),
            ] );
        }

        $this->logger->debug( __( 'Hunyuan translate_batch 结束', 'langrouter-for-translatepress' ), [
            'translated_count' => count( $translated ),
            'requested_count' => count( $strings ),
            'unique_count' => count( $items_list ),
            'failed_unique_count' => $failed_unique_count,
            'model' => $this->get_model(),
            'provider' => $this->get_provider_mode(),
        ] );
        return $translated;
    }


    public function create_translator( array $tp_settings, TPRE_Logger $logger, $translator_class = 'TRP_Hunyuan_Machine_Translator' ) {
        if ( function_exists( 'tpre_load_hunyuan_translator_class' ) && ! tpre_load_hunyuan_translator_class() ) {
            return null;
        }

        if ( ! class_exists( $translator_class ) ) {
            return null;
        }

        return new $translator_class( $tp_settings, $this, $logger );
    }

    public function test_request() {
        $this->logger->debug( __( 'Hunyuan test_request 开始', 'langrouter-for-translatepress' ), [
            'configured' => $this->is_configured(),
            'endpoint' => $this->get_endpoint_url(),
            'model' => $this->get_model(),
            'provider' => $this->get_provider_mode(),
        ] );

        if ( ! $this->is_configured() ) {
            return [ 'response' => [ 'code' => 400 ], 'body' => $this->is_official_model() ? '腾讯官方模型需填写 SecretId 与 SecretKey' : 'Hunyuan API Key 未配置' ];
        }

        $response = $this->send_request( 'zh', 'en', '今天天气很好。' );
        $code = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
        $body = is_wp_error( $response ) ? $response->get_error_message() : (string) wp_remote_retrieve_body( $response );
        $this->logger->debug( __( 'Hunyuan test_request 返回', 'langrouter-for-translatepress' ), [ 'code' => $code, 'body' => wp_strip_all_tags( $body ), 'provider' => $this->get_provider_mode(), 'model' => $this->get_model() ] );
        return is_wp_error( $response ) ? [ 'response' => [ 'code' => 0 ], 'body' => $response->get_error_message() ] : $response;
    }
}
