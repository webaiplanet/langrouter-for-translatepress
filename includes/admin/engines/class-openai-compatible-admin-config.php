<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPRE_OpenAI_Compatible_Admin_Config extends TPRE_Admin_Engine_Config_Base {
    public static function defaults() {
        return [
            'enabled'                           => 0,
            'accounts_raw'                      => '',
            'endpoint'                          => '',
            'model'                             => '',
            'custom_model'                      => '',
            'api_key'                           => '',
            'secret_key'                        => '',
            'region'                            => '',
            'timeout'                           => 60,
            'concurrency'                       => 4,
            'max_tokens'                        => 2200,
            'retry_count'                       => 2,
            'short_text_merge_threshold'        => 36,
            'temperature'                       => 0,
            'top_p'                             => 1,
            'system_prompt'                     => '',
            'batch_size'                        => 6,
            'batch_max_chars'                   => 1200,
            'label_max_tokens'                  => 0,
            'long_text_threshold'               => 1800,
            'long_text_chunk_chars'             => 1200,
            'long_html_chunk_chars'             => 1600,
            'long_text_concurrency_medium'      => 4,
            'long_text_concurrency_large'       => 3,
            'long_text_concurrency_extreme'     => 2,
            'long_text_medium_threshold'        => 1600,
            'long_text_large_threshold'         => 2400,
            'long_text_extreme_threshold'       => 3200,
            'single_request_timeout_base'       => 45,
            'single_request_timeout_step_chars' => 700,
            'single_request_timeout_step_sec'   => 10,
            'single_request_timeout_html_bonus' => 10,
            'single_request_timeout_cap'        => 180,
            'extra_headers'                     => '',
            'extra_body_json'                   => '',
            'note'                              => __( '用于接入兼容 OpenAI 的第三方模型或网关。新手先只填 API Key、模型名称、接口地址，再套用推荐起步参数即可。', 'langrouter-for-translatepress' ),
        ];
    }

    public static function sanitize( array $item, array $current_item, array $context = [] ) {
        return [
            'enabled'                           => self::get_enabled_value( $current_item, $context ),
            'endpoint'                          => self::sanitize_url( $item, $current_item, 'endpoint', '' ),
            'model'                             => self::sanitize_text( $item, $current_item, 'model', '' ),
            'custom_model'                      => '',
            'api_key'                           => self::sanitize_secret_text( $item, $current_item, 'api_key', '' ),
            'secret_key'                        => '',
            'region'                            => '',
            'timeout'                           => self::sanitize_absint_min( $item, $current_item, 'timeout', 5, 60 ),
            'concurrency'                       => self::sanitize_absint_range( $item, $current_item, 'concurrency', 0, 32, 4 ),
            'max_tokens'                        => self::sanitize_absint_min( $item, $current_item, 'max_tokens', 128, 2200 ),
            'retry_count'                       => self::sanitize_absint_range( $item, $current_item, 'retry_count', 0, 3, 2 ),
            'short_text_merge_threshold'        => self::sanitize_absint_min( $item, $current_item, 'short_text_merge_threshold', 0, 36 ),
            'temperature'                       => self::sanitize_float_range( $item, $current_item, 'temperature', 0, 2, 0 ),
            'top_p'                             => self::sanitize_float_range( $item, $current_item, 'top_p', 0, 1, 1 ),
            'system_prompt'                     => self::sanitize_textarea( $item, $current_item, 'system_prompt', '' ),
            'batch_size'                        => self::sanitize_absint_range( $item, $current_item, 'batch_size', 1, 50, 6 ),
            'batch_max_chars'                   => self::sanitize_absint_min( $item, $current_item, 'batch_max_chars', 200, 1200 ),
            'label_max_tokens'                  => self::sanitize_absint_range( $item, $current_item, 'label_max_tokens', 0, 512, 0 ),
            'long_text_threshold'               => self::sanitize_absint_min( $item, $current_item, 'long_text_threshold', 400, 1800 ),
            'long_text_chunk_chars'             => self::sanitize_absint_min( $item, $current_item, 'long_text_chunk_chars', 200, 1200 ),
            'long_html_chunk_chars'             => self::sanitize_absint_min( $item, $current_item, 'long_html_chunk_chars', 200, 1600 ),
            'long_text_concurrency_medium'      => self::sanitize_absint_range( $item, $current_item, 'long_text_concurrency_medium', 1, 32, 4 ),
            'long_text_concurrency_large'       => self::sanitize_absint_range( $item, $current_item, 'long_text_concurrency_large', 1, 32, 3 ),
            'long_text_concurrency_extreme'     => self::sanitize_absint_range( $item, $current_item, 'long_text_concurrency_extreme', 1, 32, 2 ),
            'long_text_medium_threshold'        => self::sanitize_absint_min( $item, $current_item, 'long_text_medium_threshold', 400, 1600 ),
            'long_text_large_threshold'         => self::sanitize_absint_min( $item, $current_item, 'long_text_large_threshold', 400, 2400 ),
            'long_text_extreme_threshold'       => self::sanitize_absint_min( $item, $current_item, 'long_text_extreme_threshold', 400, 3200 ),
            'single_request_timeout_base'       => self::sanitize_absint_min( $item, $current_item, 'single_request_timeout_base', 5, 45 ),
            'single_request_timeout_step_chars' => self::sanitize_absint_min( $item, $current_item, 'single_request_timeout_step_chars', 50, 700 ),
            'single_request_timeout_step_sec'   => self::sanitize_absint_range( $item, $current_item, 'single_request_timeout_step_sec', 0, 120, 10 ),
            'single_request_timeout_html_bonus' => self::sanitize_absint_range( $item, $current_item, 'single_request_timeout_html_bonus', 0, 120, 10 ),
            'single_request_timeout_cap'        => self::sanitize_absint_min( $item, $current_item, 'single_request_timeout_cap', 5, 180 ),
            'extra_headers'                     => self::sanitize_textarea( $item, $current_item, 'extra_headers', '' ),
            'extra_body_json'                   => self::sanitize_json_object_text( $item, $current_item, 'extra_body_json', '', [ 'engine_slug' => 'openai_compatible', 'engine_label' => __( '兼容 OpenAI', 'langrouter-for-translatepress' ) ] ),
            'note'                              => self::sanitize_textarea( $item, $current_item, 'note', '' ),
            'accounts_raw'                      => '',
        ];
    }
}
