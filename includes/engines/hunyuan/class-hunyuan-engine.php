<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPRE_Hunyuan_Engine extends TPRE_Client_Adapter_Engine {
    public function __construct( TPRE_Hunyuan_Client $client, array $tp_settings, array $router_settings, TPRE_Logger $logger ) {
        parent::__construct( $client, $tp_settings, $router_settings, $logger, 'hunyuan', __( 'Hunyuan', 'langrouter-for-translatepress' ) );
    }

    protected function get_translator_loader_callback() {
        return 'tpre_load_hunyuan_translator_class';
    }

    protected function get_translator_class_name() {
        return 'TRP_Hunyuan_Machine_Translator';
    }
}
