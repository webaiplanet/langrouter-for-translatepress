<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPRE_Qwen_Machine_Translator extends TPRE_Client_Adapter_Machine_Translator_Base {}

if ( ! class_exists( 'TRP_Qwen_Machine_Translator', false ) ) {
    class_alias( 'TPRE_Qwen_Machine_Translator', 'TRP_Qwen_Machine_Translator' );
}
