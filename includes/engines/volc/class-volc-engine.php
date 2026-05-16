<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPRE_Volc_Engine implements TPRE_Engine_Interface {
    protected $client;
    protected $tp_settings;
    protected $router_settings;
    protected $logger;
    protected $translator = null;

    public function __construct( TPRE_Volc_Client $client, array $tp_settings, array $router_settings, TPRE_Logger $logger ) {
        $this->client          = $client;
        $this->tp_settings     = $tp_settings;
        $this->router_settings = $router_settings;
        $this->logger          = $logger;
    }

    public function get_slug() { return 'volc'; }
    public function get_label() { return __( '火山方舟', 'langrouter-for-translatepress' ); }

    protected function create_translator() {
        if ( null !== $this->translator ) {
            return $this->translator;
        }

        if ( ! method_exists( $this->client, 'create_translator' ) ) {
            return null;
        }

        $this->translator = $this->client->create_translator();
        return $this->translator;
    }

    public function is_available() {
        return ! empty( $this->router_settings['models']['volc']['enabled'] ) && $this->client->is_available();
    }

    public function supports_language( $language_code ) {
        if ( ! $this->is_available() ) {
            return false;
        }

        $meta = method_exists( $this->client, 'get_language_support_meta' )
            ? $this->client->get_language_support_meta( $language_code )
            : [
                'raw'        => is_string( $language_code ) ? trim( $language_code ) : '',
                'candidates' => [],
                'supported'  => $this->client->supports_language( $language_code ),
                'source'     => 'client',
            ];

        if ( ! empty( $meta['supported'] ) ) {
            return true;
        }

        /* translators: %s: Target language code. */
        $this->logger->debug( tpre_log_translatef( '火山方舟不支持 %s 语言，直接跳过主调用。', $meta['raw'] ?? ( is_string( $language_code ) ? trim( $language_code ) : '' ) ), [
            'target_language' => $meta['raw'] ?? ( is_string( $language_code ) ? trim( $language_code ) : '' ),
            'candidates'      => $meta['candidates'] ?? [],
            'source'          => $meta['source'] ?? 'builtin_manual_list',
        ] );

        return false;
    }

    public function translate( array $strings, $target_language_code, $source_language_code = null ) {
        if ( ! $this->is_available() ) {
            return [];
        }

        if ( ! $this->supports_language( $target_language_code ) ) {
            return [];
        }

        $translator = $this->create_translator();
        if ( ! $translator || ! method_exists( $translator, 'translate_array' ) ) {
            return [];
        }

        return $translator->translate_array( $strings, $target_language_code, $source_language_code );
    }

    public function test_request() {
        return $this->client->test_request();
    }
}

