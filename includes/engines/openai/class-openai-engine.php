<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPRE_OpenAI_Engine extends TPRE_Client_Adapter_Engine {
    public function __construct( TPRE_OpenAI_Client $client, array $tp_settings, array $router_settings, TPRE_Logger $logger, $slug = 'openai', $label = 'OpenAI' ) {
        parent::__construct( $client, $tp_settings, $router_settings, $logger, $slug, $label );
    }

    protected function get_translator_loader_callback() {
        return 'tpre_load_openai_translator_class';
    }

    protected function get_translator_class_name() {
        return 'TRP_OpenAI_Machine_Translator';
    }
}
