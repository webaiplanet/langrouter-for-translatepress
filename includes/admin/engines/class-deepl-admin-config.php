<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPRE_DeepL_Admin_Config extends TPRE_Admin_Engine_Config_Base {
    public static function defaults() {
        return [
            'enabled'            => 1,
            'keys_text'          => '',
            'include_single_key' => 'no',
            'throttle_seconds'   => 15,
            'error_cooldown'     => 120,
            'quota_cooldown'     => 1800,
            'forbidden_cooldown' => 600,
            'accounts_raw'       => '',
            'endpoint'           => '',
            'model'              => 'deepl',
            'api_key'            => '',
            'secret_key'         => '',
            'region'             => '',
            'timeout'            => 30,
            'system_prompt'      => '',
            'extra_headers'      => '',
            'extra_body_json'    => '',
            'note'               => __( '内置 DeepL 账号池与失败切换。', 'langrouter-for-translatepress' ),
        ];
    }

    public static function sanitize( array $item, array $current_item, array $context = [] ) {
        $keys_text = $current_item['keys_text'] ?? '';
        if ( ! empty( $item['clear_keys_pool'] ) ) {
            $keys_text = '';
        } elseif ( ! empty( $item['keys_text_unchanged'] ) ) {
            $keys_text = $current_item['keys_text'] ?? '';
        } elseif ( array_key_exists( 'keys_text', $item ) ) {
            $submitted_keys = TPRE_Admin_Settings::normalize_admin_multiline_value( $item['keys_text'] );
            $masked_current = TPRE_Admin_Settings::normalize_admin_multiline_value( TPRE_Admin_Settings::build_masked_deepl_keys_text( $current_item['keys_text'] ?? '' ) );

            if ( '' !== $submitted_keys && $submitted_keys !== $masked_current ) {
                $keys_text = TPRE_Admin_Settings::sanitize_deepl_keys_text_value( $item['keys_text'] );
            }
        }

        return [
            'enabled'            => self::get_enabled_value( $current_item, $context ),
            'keys_text'          => $keys_text,
            'include_single_key' => 'no',
            'throttle_seconds'   => self::sanitize_absint_min( $item, $current_item, 'throttle_seconds', 1, 15 ),
            'error_cooldown'     => self::sanitize_absint_min( $item, $current_item, 'error_cooldown', 1, 120 ),
            'quota_cooldown'     => self::sanitize_absint_min( $item, $current_item, 'quota_cooldown', 1, 1800 ),
            'forbidden_cooldown' => self::sanitize_absint_min( $item, $current_item, 'forbidden_cooldown', 1, 600 ),
            'note'               => self::sanitize_textarea( $item, $current_item, 'note', __( '内置 DeepL 账号池与失败切换。', 'langrouter-for-translatepress' ) ),
            'accounts_raw'       => '',
            'endpoint'           => '',
            'model'              => 'deepl',
            'api_key'            => '',
            'secret_key'         => '',
            'region'             => '',
            'timeout'            => 30,
            'system_prompt'      => '',
            'extra_headers'      => '',
            'extra_body_json'    => '',
        ];
    }
}
