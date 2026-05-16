<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class TPRE_Client_Adapter_Engine implements TPRE_Engine_Interface {
    protected $client;
    protected $tp_settings;
    protected $router_settings;
    protected $logger;
    protected $slug;
    protected $label;
    protected $translator = null;

    public function __construct( $client, array $tp_settings, array $router_settings, TPRE_Logger $logger, $slug, $label ) {
        $this->client          = $client;
        $this->tp_settings     = $tp_settings;
        $this->router_settings = $router_settings;
        $this->logger          = $logger;
        $this->slug            = (string) $slug;
        $this->label           = (string) $label;
    }

    public function get_slug() { return $this->slug; }
    public function get_label() { return $this->label; }

    public function is_available() {
        $settings = method_exists( $this->client, 'get_model_settings' ) ? $this->client->get_model_settings() : [];
        return ! empty( $settings['enabled'] ) && method_exists( $this->client, 'is_configured' ) && $this->client->is_configured();
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
                'supported'  => method_exists( $this->client, 'supports_language' ) ? $this->client->supports_language( $language_code ) : false,
                'source'     => 'client',
                'model'      => method_exists( $this->client, 'get_model' ) ? $this->client->get_model() : '',
            ];

        if ( ! empty( $meta['supported'] ) ) {
            return true;
        }

        $this->logger->debug( tpre_log_translatef( '%1$s 不支持 %2$s 语言，直接跳过主调用。', $this->get_label(), $meta['raw'] ?? ( is_string( $language_code ) ? trim( $language_code ) : '' ) ), [
            'engine'          => $this->get_slug(),
            'target_language' => $meta['raw'] ?? ( is_string( $language_code ) ? trim( $language_code ) : '' ),
            'candidates'      => $meta['candidates'] ?? [],
            'source'          => $meta['source'] ?? 'client',
            'model'           => $meta['model'] ?? '',
        ] );

        return false;
    }

    protected function get_translator_loader_callback() {
        return null;
    }

    protected function get_translator_class_name() {
        return '';
    }

    protected function build_translator_settings() {
        $translator_settings = $this->tp_settings;
        if ( ! isset( $translator_settings['trp_machine_translation_settings'] ) || ! is_array( $translator_settings['trp_machine_translation_settings'] ) ) {
            $translator_settings['trp_machine_translation_settings'] = [];
        }

        $translator_settings['trp_machine_translation_settings']['translation-engine']  = $this->get_slug();
        $translator_settings['trp_machine_translation_settings']['machine-translation'] = 'yes';

        return $translator_settings;
    }

    protected function create_translator() {
        if ( null !== $this->translator ) {
            return $this->translator;
        }

        $loader = $this->get_translator_loader_callback();
        if ( is_callable( $loader ) && ! call_user_func( $loader ) ) {
            return null;
        }

        $translator_class = $this->get_translator_class_name();
        if ( '' === $translator_class || ! class_exists( $translator_class ) ) {
            return null;
        }

        $translator_settings = $this->build_translator_settings();

        if ( method_exists( $this->client, 'create_translator' ) ) {
            $this->translator = $this->client->create_translator( $translator_settings, $this->logger, $translator_class );
        } else {
            $this->translator = new $translator_class( $translator_settings, $this->client, $this->logger );
        }

        return $this->translator;
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
        return method_exists( $this->client, 'test_request' ) ? $this->client->test_request() : [];
    }
}
