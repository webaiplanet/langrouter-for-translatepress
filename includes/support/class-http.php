<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPRE_HTTP {
    public static function post( $url, array $args = [] ) {
        return wp_remote_post( $url, $args );
    }

    public static function get( $url, array $args = [] ) {
        return wp_remote_get( $url, $args );
    }
}
