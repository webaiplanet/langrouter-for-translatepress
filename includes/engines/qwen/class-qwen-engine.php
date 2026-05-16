<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPRE_Qwen_Engine extends TPRE_Client_Adapter_Engine {
    public function __construct( TPRE_Qwen_Client $client, array $tp_settings, array $router_settings, TPRE_Logger $logger ) {
        parent::__construct( $client, $tp_settings, $router_settings, $logger, 'qwen', __( 'Qwen', 'langrouter-for-translatepress' ) );
    }

    protected function get_translator_loader_callback() {
        return 'tpre_load_qwen_translator_class';
    }

    protected function get_translator_class_name() {
        return 'TRP_Qwen_Machine_Translator';
    }
}
