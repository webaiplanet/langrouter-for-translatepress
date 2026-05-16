<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class TPRE_Client_Adapter_Machine_Translator_Base extends TRP_Machine_Translator {
    protected $client;
    protected $logger;

    public function __construct( array $settings, $client, TPRE_Logger $logger ) {
        $this->client = $client;
        $this->logger = $logger;
        parent::__construct( $settings );
    }

    public function translate_array( $strings, $target_language_code, $source_language_code = null ) {
        if ( empty( $strings ) || ! is_array( $strings ) ) {
            return [];
        }

        $translated = $this->client->translate_batch( $strings, $target_language_code, $source_language_code );
        if ( empty( $translated ) || ! is_array( $translated ) || ! class_exists( 'TPRE_Translation_Safety_Utils' ) ) {
            return is_array( $translated ) ? $translated : [];
        }

        $filtered = [];
        foreach ( $translated as $key => $translated_text ) {
            if ( ! array_key_exists( $key, $strings ) ) {
                continue;
            }

            $source_text = $strings[ $key ];
            $normalized_source = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( (string) $source_text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
            $normalized_result = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( (string) $translated_text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
            $safe_passthrough  = '' !== $normalized_source && 0 === strcasecmp( $normalized_source, $normalized_result ) && TPRE_Translation_Safety_Utils::looks_like_safe_passthrough_text( $source_text );

            if ( ! $safe_passthrough && TPRE_Translation_Safety_Utils::is_suspicious_translation_output( $source_text, $translated_text, $target_language_code ) ) {
                if ( $this->logger ) {
                    $this->logger->debug( __( '客户端适配器丢弃可疑翻译结果', 'langrouter-for-translatepress' ), [
                        'target_language'    => $target_language_code,
                        'source_preview'     => $this->preview_text( $source_text ),
                        'translated_preview' => $this->preview_text( $translated_text ),
                        'runtime_fallback'   => TPRE_Translation_Safety_Utils::should_runtime_fallback_to_source( $source_text, $translated_text ) ? 'source' : 'none',
                        'persist_policy'     => 'drop_result_do_not_store',
                    ] );
                }
                continue;
            }

            $filtered[ $key ] = is_scalar( $translated_text ) ? trim( (string) $translated_text ) : $translated_text;
        }

        return $filtered;
    }

    protected function preview_text( $text, $limit = 120 ) {
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

    public function get_api_key() {
        return method_exists( $this->client, 'get_api_key' ) ? $this->client->get_api_key() : false;
    }

    public function test_request() {
        return method_exists( $this->client, 'test_request' ) ? $this->client->test_request() : [];
    }
}
