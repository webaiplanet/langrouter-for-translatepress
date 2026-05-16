<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

interface TPRE_Engine_Interface {
    public function get_slug();
    public function get_label();
    public function is_available();
    public function supports_language( $language_code );
    public function translate( array $strings, $target_language_code, $source_language_code = null );
}
