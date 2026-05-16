<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPRE_Volc_Admin_Config extends TPRE_Admin_Engine_Config_Base {
    public static function defaults() {
        return [
            'enabled'         => 1,
            'accounts_raw'    => '',
            'endpoint'        => '',
            'model'           => '',
            'api_key'         => '',
            'secret_key'      => '',
            'region'          => '',
            'timeout'         => 15,
            'concurrency'     => 0,
            'system_prompt'   => '',
            'extra_headers'   => '',
            'extra_body_json' => '',
            'note'            => __( '火山方舟使用账号池格式配置。', 'langrouter-for-translatepress' ),
        ];
    }

    public static function sanitize( array $item, array $current_item, array $context = [] ) {
        $accounts_raw = $current_item['accounts_raw'] ?? '';
        if ( ! empty( $item['clear_accounts_pool'] ) ) {
            $accounts_raw = '';
        } elseif ( ! empty( $item['accounts_raw_unchanged'] ) ) {
            $accounts_raw = $current_item['accounts_raw'] ?? '';
        } elseif ( array_key_exists( 'accounts_raw', $item ) ) {
            $submitted_accounts = TPRE_Admin_Settings::normalize_admin_multiline_value( $item['accounts_raw'] );
            $masked_current     = TPRE_Admin_Settings::normalize_admin_multiline_value( TPRE_Admin_Settings::build_masked_volc_accounts_text( $current_item['accounts_raw'] ?? '' ) );

            if ( '' !== $submitted_accounts && $submitted_accounts !== $masked_current ) {
                $accounts_raw = TPRE_Admin_Settings::sanitize_volc_accounts_pool_value( $item['accounts_raw'] );
            }
        }

        return [
            'enabled'         => self::get_enabled_value( $current_item, $context ),
            'accounts_raw'    => $accounts_raw,
            'endpoint'        => '',
            'model'           => '',
            'api_key'         => '',
            'secret_key'      => '',
            'region'          => '',
            'timeout'         => 15,
            'concurrency'     => self::sanitize_absint_range( $item, $current_item, 'concurrency', 0, 32, 0 ),
            'system_prompt'   => '',
            'extra_headers'   => '',
            'extra_body_json' => '',
            'note'            => __( '火山方舟页已切换为旧火山插件兼容后台。', 'langrouter-for-translatepress' ),
        ];
    }
}
