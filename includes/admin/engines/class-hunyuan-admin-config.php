<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPRE_Hunyuan_Admin_Config extends TPRE_Admin_Engine_Config_Base {
    public static function defaults() {
        return [
            'enabled'         => 0,
            'accounts_raw'    => '',
            'endpoint'        => '',
            'model'           => 'hunyuan-translation-lite',
            'api_key'         => '',
            'secret_key'      => '',
            'region'          => '',
            'site'            => 'cn',
            'timeout'         => 30,
            'concurrency'     => 0,
            'system_prompt'   => '',
            'extra_headers'   => '',
            'extra_body_json' => '',
            'note'            => __( '腾讯官方模型走 ChatTranslations；hunyuan-mt-7b 默认走 SiliconFlow。', 'langrouter-for-translatepress' ),
        ];
    }

    public static function sanitize( array $item, array $current_item, array $context = [] ) {
        $allowed_models = [ 'hunyuan-translation-lite', 'hunyuan-translation', 'hunyuan-mt-7b', 'tencent/Hunyuan-MT-7B' ];
        $model_value    = self::sanitize_text( $item, $current_item, 'model', 'hunyuan-translation-lite' );
        if ( ! in_array( $model_value, $allowed_models, true ) ) {
            $model_value = 'hunyuan-translation-lite';
        }
        if ( 'tencent/Hunyuan-MT-7B' === $model_value ) {
            $model_value = 'hunyuan-mt-7b';
        }

        $site_value = sanitize_key( (string) self::get_value( $item, $current_item, 'site', 'cn' ) );
        if ( ! in_array( $site_value, [ 'cn', 'intl' ], true ) ) {
            $site_value = 'cn';
        }

        return [
            'enabled'         => self::get_enabled_value( $current_item, $context ),
            'endpoint'        => self::sanitize_url( $item, $current_item, 'endpoint', '' ),
            'model'           => $model_value,
            'api_key'         => self::sanitize_secret_text( $item, $current_item, 'api_key', '' ),
            'secret_key'      => self::sanitize_secret_text( $item, $current_item, 'secret_key', '' ),
            'region'          => '',
            'site'            => $site_value,
            'timeout'         => self::sanitize_absint_min( $item, $current_item, 'timeout', 5, 20 ),
            'concurrency'     => self::sanitize_absint_range( $item, $current_item, 'concurrency', 0, 32, 0 ),
            'system_prompt'   => '',
            'extra_headers'   => '',
            'extra_body_json' => self::sanitize_json_object_text( $item, $current_item, 'extra_body_json', '', [ 'engine_slug' => 'hunyuan', 'engine_label' => __( 'Hunyuan', 'langrouter-for-translatepress' ) ] ),
            'note'            => self::sanitize_textarea( $item, $current_item, 'note', '' ),
            'accounts_raw'    => '',
        ];
    }
}
