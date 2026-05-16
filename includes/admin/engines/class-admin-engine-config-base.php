<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class TPRE_Admin_Engine_Config_Base {
    protected static function get_value( array $item, array $current_item, $key, $default = '' ) {
        if ( array_key_exists( $key, $item ) ) {
            return $item[ $key ];
        }

        if ( array_key_exists( $key, $current_item ) ) {
            return $current_item[ $key ];
        }

        return $default;
    }

    protected static function get_enabled_value( array $current_item, array $context ) {
        if ( array_key_exists( 'enabled_value', $context ) ) {
            return ! empty( $context['enabled_value'] ) ? 1 : 0;
        }

        return ! empty( $current_item['enabled'] ) ? 1 : 0;
    }

    protected static function sanitize_text( array $item, array $current_item, $key, $default = '' ) {
        return sanitize_text_field( (string) self::get_value( $item, $current_item, $key, $default ) );
    }

    protected static function sanitize_textarea( array $item, array $current_item, $key, $default = '' ) {
        return sanitize_textarea_field( (string) self::get_value( $item, $current_item, $key, $default ) );
    }


    protected static function sanitize_secret_text( array $item, array $current_item, $key, $default = '' ) {
        if ( ! array_key_exists( $key, $item ) ) {
            return sanitize_text_field( (string) self::get_value( $item, $current_item, $key, $default ) );
        }

        $raw = trim( (string) $item[ $key ] );
        if ( '' === $raw ) {
            $current_value = array_key_exists( $key, $current_item ) ? $current_item[ $key ] : $default;
            return sanitize_text_field( (string) $current_value );
        }

        return sanitize_text_field( $raw );
    }

    protected static function sanitize_json_object_text( array $item, array $current_item, $key, $default = '', array $context = [] ) {
        $raw = trim( (string) self::get_value( $item, $current_item, $key, $default ) );
        if ( '' === $raw ) {
            return '';
        }

        $decoded = json_decode( $raw, true );
        $is_valid_object = JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) && self::is_assoc_array( $decoded );
        if ( $is_valid_object ) {
            return $raw;
        }

        if ( array_key_exists( $key, $item ) && class_exists( 'TPRE_Admin_Settings' ) ) {
            $engine_label = isset( $context['engine_label'] ) ? (string) $context['engine_label'] : (string) ( $context['engine_slug'] ?? '' );
            if ( '' === $engine_label ) {
                $engine_label = (string) $key;
            }
            TPRE_Admin_Settings::add_validation_notice(
                'invalid_json_' . sanitize_key( (string) ( $context['engine_slug'] ?? $key ) ) . '_' . sanitize_key( (string) $key ),
                /* translators: %s: Engine label. */
                sprintf( __( '%s 的附加请求 JSON 不是合法的 JSON 对象，已保留上一次有效值。', 'langrouter-for-translatepress' ), $engine_label ),
                'warning'
            );
        }

        $current_value = array_key_exists( $key, $current_item ) ? $current_item[ $key ] : $default;
        return trim( (string) $current_value );
    }

    protected static function is_assoc_array( array $value ) {
        if ( [] === $value ) {
            return true;
        }

        return array_keys( $value ) !== range( 0, count( $value ) - 1 );
    }

    protected static function sanitize_url( array $item, array $current_item, $key, $default = '' ) {
        return esc_url_raw( trim( (string) self::get_value( $item, $current_item, $key, $default ) ) );
    }

    protected static function sanitize_absint_min( array $item, array $current_item, $key, $min, $default ) {
        $value = self::get_value( $item, $current_item, $key, $default );
        return max( (int) $min, absint( $value ) );
    }

    protected static function sanitize_absint_range( array $item, array $current_item, $key, $min, $max, $default ) {
        $value = self::get_value( $item, $current_item, $key, $default );
        return max( (int) $min, min( (int) $max, absint( $value ) ) );
    }

    protected static function sanitize_float_range( array $item, array $current_item, $key, $min, $max, $default ) {
        $value = self::get_value( $item, $current_item, $key, $default );
        return max( (float) $min, min( (float) $max, (float) $value ) );
    }
}
