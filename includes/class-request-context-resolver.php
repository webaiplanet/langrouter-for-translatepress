<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPRE_Request_Context_Resolver {
    /** @var array<string,mixed>|null */
    protected static $resolved_context = null;

    public static function resolve() {
        if ( null !== self::$resolved_context ) {
            return self::$resolved_context;
        }
        $context = [
            'context_type' => 'unknown',
            'object_id'    => 0,
            'post_type'    => '',
            'is_singular'  => false,
        ];

        if ( function_exists( 'is_singular' ) && is_singular() ) {
            $object_id = function_exists( 'get_queried_object_id' ) ? (int) get_queried_object_id() : 0;
            $post_type = $object_id > 0 && function_exists( 'get_post_type' ) ? (string) get_post_type( $object_id ) : '';

            if ( '' === $post_type && isset( $GLOBALS['post'] ) && $GLOBALS['post'] instanceof WP_Post ) {
                $object_id = (int) $GLOBALS['post']->ID;
                $post_type = (string) $GLOBALS['post']->post_type;
            }

            self::$resolved_context = [
                'context_type' => 'singular_post',
                'object_id'    => max( 0, $object_id ),
                'post_type'    => sanitize_key( $post_type ),
                'is_singular'  => true,
            ];

            return self::$resolved_context;
        }

        if ( isset( $GLOBALS['post'] ) && $GLOBALS['post'] instanceof WP_Post ) {
            self::$resolved_context = [
                'context_type' => 'global_post',
                'object_id'    => (int) $GLOBALS['post']->ID,
                'post_type'    => sanitize_key( (string) $GLOBALS['post']->post_type ),
                'is_singular'  => false,
            ];

            return self::$resolved_context;
        }

        self::$resolved_context = $context;

        return self::$resolved_context;
    }
}
