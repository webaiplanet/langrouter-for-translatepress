<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPRE_Router_Engine extends TRP_Machine_Translator {
    /** @var TPRE_Engine_Manager */
    protected $engine_manager;

    /** @var TPRE_Routing_Rules */
    protected $routing_rules;

    /** @var TPRE_Logger */
    protected $logger;

    /** @var int */
    protected static $route_sequence = 0;

    /** @var array<string,array<string,mixed>> */
    protected $engine_decision_cache = [];

    /** @var array<string,array<int,array<string,mixed>>> */
    protected $fallback_attempts_cache = [];

    public static function boot() {
        add_filter( 'trp_machine_translation_engines', [ __CLASS__, 'register_engine' ], 20 ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- TranslatePress integration hook.
        add_filter( 'trp_automatic_translation_engines_classes', [ __CLASS__, 'register_engine_class' ], 20 ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- TranslatePress integration hook.
        add_action( 'trp_machine_translation_extra_settings_middle', [ __CLASS__, 'render_settings' ], 20 ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- TranslatePress integration hook.
        add_filter( 'trp_machine_translation_sanitize_settings', [ __CLASS__, 'sanitize_settings' ], 20, 2 ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- TranslatePress integration hook.
    }

    public static function register_engine( $engines ) {
        $engines[] = [
            'value' => 'tpre_router_engine',
            'label' => __('LangRouter 智能翻译', 'langrouter-for-translatepress'),
        ];
        return $engines;
    }

    public static function register_engine_class( $engines ) {
        $engines['tpre_router_engine'] = __CLASS__;
        return $engines;
    }

    public static function render_settings( $mt_settings ) {
        $translation_engine = isset( $mt_settings['translation-engine'] ) ? $mt_settings['translation-engine'] : '';
        if ( 'tpre_router_engine' !== $translation_engine ) {
            return;
        }

        TPRE_Admin_Page::render_router_settings_block();
    }

    public static function sanitize_settings( $settings, $raw_settings ) {
        $posted_router = self::get_verified_posted_router_settings();

        $router_source = $raw_settings;
        if ( ! empty( $posted_router ) ) {
            if ( isset( $raw_settings['trp_machine_translation_settings'] ) && is_array( $raw_settings['trp_machine_translation_settings'] ) ) {
                $router_source['trp_machine_translation_settings'] = array_replace_recursive(
                    $raw_settings['trp_machine_translation_settings'],
                    $posted_router
                );
            } elseif ( is_array( $raw_settings ) ) {
                $router_source = array_replace_recursive( $raw_settings, $posted_router );
            } else {
                $router_source = [ 'trp_machine_translation_settings' => $posted_router ];
            }
        }

        $router = TPRE_Admin_Settings::sanitize_router_settings( $router_source );
        update_option( TPRE_Admin_Settings::OPTION_KEY, $router );

        if ( ! empty( $router['log_enabled'] ) ) {
            $logger = new TPRE_Logger( true );
            $logger->debug( __( 'Router 设置已保存', 'langrouter-for-translatepress' ), 
            [
                'raw_settings_has_nested_router' => isset( $raw_settings['trp_machine_translation_settings'] ) && is_array( $raw_settings['trp_machine_translation_settings'] ) ? 1 : 0,
                'posted_router_present'          => ! empty( $posted_router ) ? 1 : 0,
                'posted_rule_rows'               => isset( $posted_router['tpre_post_type_rule_post_types'] ) && is_array( $posted_router['tpre_post_type_rule_post_types'] ) ? count( $posted_router['tpre_post_type_rule_post_types'] ) : 0,
                'posted_rules_json_length'       => isset( $posted_router['tpre_post_type_rules_json'] ) ? strlen( (string) $posted_router['tpre_post_type_rules_json'] ) : 0,
                'saved_post_type_rules_count'    => isset( $router['post_type_rules'] ) && is_array( $router['post_type_rules'] ) ? count( $router['post_type_rules'] ) : 0,
                'saved_post_type_rule_keys'      => isset( $router['post_type_rule_map'] ) && is_array( $router['post_type_rule_map'] ) ? array_keys( $router['post_type_rule_map'] ) : [],
            ] );
        }

        $settings['tpre_default_engine']          = $router['default_engine'];
        $settings['tpre_language_engine_map_raw'] = self::map_to_text( $router['language_engine_map'] );
        $settings['tpre_fallback_map_raw']        = self::map_to_text( $router['fallback_map'] );
        $settings['tpre_log_enabled']             = ! empty( $router['log_enabled'] ) ? 1 : 0;
        $settings['tpre_global_concurrency_limit'] = isset( $router['global_concurrency_limit'] ) ? (int) $router['global_concurrency_limit'] : 0;
        $settings['tpre_traditional_chinese_mode'] = isset( $router['traditional_chinese_mode'] ) ? sanitize_key( (string) $router['traditional_chinese_mode'] ) : 'translatepress';
        return $settings;
    }


    protected static function get_verified_posted_router_settings() {
        $option_page = isset( $_POST['option_page'] ) ? sanitize_key( wp_unslash( $_POST['option_page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is validated below before posted settings are used.
        $nonce       = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Sanitized before verification.

        if ( 'trp_machine_translation_settings' !== $option_page || '' === $nonce ) {
            return [];
        }

        if ( ! wp_verify_nonce( $nonce, $option_page . '-options' ) ) {
            return [];
        }

        if ( isset( $_POST['trp_machine_translation_settings'] ) && is_array( $_POST['trp_machine_translation_settings'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Settings API nonce verified just above; raw array is unslashed here and sanitized downstream.
            return wp_unslash( $_POST['trp_machine_translation_settings'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- See comment above.
        }

        return [];
    }

    protected static function map_to_text( $map ) {
        $lines = [];
        foreach ( (array) $map as $lang => $engine ) {
            $lines[] = $lang . ' = ' . $engine;
        }
        return implode( "
", $lines );
    }

    public function __construct( $settings ) {
        parent::__construct( $settings );

        $router_settings      = TPRE_Admin_Settings::get_settings();
        $normalizer           = new TPRE_Language_Normalizer();
        $this->logger         = new TPRE_Logger( ! empty( $router_settings['log_enabled'] ) );
        $this->routing_rules  = new TPRE_Routing_Rules( $router_settings, $normalizer );
        $this->engine_manager = new TPRE_Engine_Manager( $settings, $this->logger );
    }

    public function get_api_key() {
        return 'langrouter-for-translatepress';
    }

    public function test_request() {
        $router_settings = TPRE_Admin_Settings::get_settings();
        $engine_slug     = isset( $router_settings['default_engine'] ) ? $router_settings['default_engine'] : 'volc';
        $engine          = $this->engine_manager->get_engine( $engine_slug );

        $this->logger->debug( __( 'Router test_request 开始', 'langrouter-for-translatepress' ),
        [
            'default_engine' => $engine_slug,
            'engine_found'   => (bool) $engine,
        ] );

        if ( $engine && method_exists( $engine, 'test_request' ) ) {
            $response = $engine->test_request();
            if ( is_array( $response ) && isset( $response['response']['code'] ) ) {
                $code = (int) $response['response']['code'];
                $this->logger->debug( __( 'Router test_request 子引擎返回', 'langrouter-for-translatepress' ), [
                    'default_engine' => $engine_slug,
                    'code'           => $code,
                    'body'           => isset( $response['body'] ) ? wp_strip_all_tags( (string) $response['body'] ) : '',
                ] );
                if ( empty( $response['body'] ) ) {
                    $response['body'] = 'router-test engine=' . $engine_slug . ' code=' . $code;
                }
                return $response;
            }
        }

        return [
            'response' => [ 'code' => 200 ],
            /* translators: %s: Current default engine slug. */
            'body' => sprintf(
                /* translators: %s: Current default engine slug. */
                __( 'router-ok engine=%s (仅表示 Router 已接管；未执行子引擎联通性测试)', 'langrouter-for-translatepress' ),
                $engine_slug
            ),
        ];
    }

    public function check_api_key_validity() {
        return [
            'message' => '',
            'error'   => false,
        ];
    }

    public function get_supported_languages() {
        $languages = isset( $this->settings['translation-languages'] ) ? (array) $this->settings['translation-languages'] : [];
        return $this->get_engine_specific_language_codes( $languages );
    }

    public function get_engine_specific_language_codes( $languages ) {
        $codes = [];
        foreach ( (array) $languages as $language ) {
            $codes[] = $this->machine_translation_codes[ $language ] ?? $language;
        }
        return array_values( array_unique( $codes ) );
    }


    protected function get_cached_engine_decision( $target_language_code, array $route_context = [] ) {
        $cache_key = md5( wp_json_encode( [
            'target_language' => (string) $target_language_code,
            'route_context'    => $route_context,
        ] ) );

        if ( isset( $this->engine_decision_cache[ $cache_key ] ) ) {
            return $this->engine_decision_cache[ $cache_key ];
        }

        $this->engine_decision_cache[ $cache_key ] = $this->routing_rules->resolve_engine_decision( $target_language_code, $route_context );

        return $this->engine_decision_cache[ $cache_key ];
    }

    protected function get_cached_fallback_attempts( $target_language_code, $current_engine_slug, array $primary_decision = [] ) {
        $cache_key = md5( wp_json_encode( [
            'target_language'     => (string) $target_language_code,
            'current_engine_slug' => (string) $current_engine_slug,
            'primary_decision'    => $primary_decision,
        ] ) );

        if ( isset( $this->fallback_attempts_cache[ $cache_key ] ) ) {
            return $this->fallback_attempts_cache[ $cache_key ];
        }

        $this->fallback_attempts_cache[ $cache_key ] = $this->routing_rules->build_fallback_engine_decisions( $target_language_code, $current_engine_slug, $primary_decision );

        return $this->fallback_attempts_cache[ $cache_key ];
    }

    protected function next_route_id() {
        self::$route_sequence++;
        return sprintf( 'r-%s-%04d', TPRE_Logger::format_local_time( 'Ymd-His' ), self::$route_sequence );
    }

    protected function summarize_fallback_attempts( array $attempts ) {
        $chain   = [];
        $details = [];

        foreach ( $attempts as $attempt ) {
            if ( empty( $attempt['fallback_engine'] ) ) {
                continue;
            }

            $chain[] = $attempt['fallback_engine'];
            $details[] = [
                'engine'       => $attempt['fallback_engine'],
                'source'       => $attempt['fallback_source'] ?? '',
                'matched_rule' => $attempt['matched_rule'] ?? '',
                'reason'       => $attempt['reason'] ?? '',
            ];
        }

        return [
            'chain'   => $chain,
            'details' => $details,
        ];
    }

    protected function inspect_engine( $engine, $engine_slug, $target_language_code ) {
        if ( ! $engine ) {
            return [
                'ok'                => false,
                'failure_type'      => 'engine_not_found',
                'is_available'      => false,
                'supports_language' => false,
                'engine'            => $engine_slug,
            ];
        }

        $is_available = (bool) $engine->is_available();
        if ( ! $is_available ) {
            return [
                'ok'                => false,
                'failure_type'      => 'engine_unavailable',
                'is_available'      => false,
                'supports_language' => false,
                'engine'            => $engine_slug,
            ];
        }

        $supports_language = $engine->supports_language( $target_language_code );
        if ( false === $supports_language ) {
            return [
                'ok'                => false,
                'failure_type'      => 'unsupported_target_language',
                'is_available'      => true,
                'supports_language' => false,
                'engine'            => $engine_slug,
            ];
        }

        return [
            'ok'                => true,
            'failure_type'      => '',
            'is_available'      => true,
            'supports_language' => $supports_language,
            'engine'            => $engine_slug,
        ];
    }

    protected function log_route_complete( $target_language_code, $source_language_code, $request_count, $primary_engine_slug, $primary_result_count, array $fallback_meta, array $translated, array $route_context = [] ) {
        if ( ! $this->logger->is_enabled() ) {
            return;
        }

        $final_engine = ! empty( $fallback_meta['used'] ) ? ( $fallback_meta['fallback_engine'] ?? '' ) : $primary_engine_slug;
        $final_status = ! empty( $fallback_meta['used'] )
            ? ( $fallback_meta['final_status'] ?? 'fallback_unknown' )
            : ( ! empty( $fallback_meta['primary_failure_type'] )
                ? ( $fallback_meta['final_status'] ?? 'primary_failed' )
                : 'primary_success' );

        $this->logger->debug( __( 'Router 路由完成', 'langrouter-for-translatepress' ), [
            'target_language'       => $target_language_code,
            'source_language'       => $source_language_code,
            'request_count'         => $request_count,
            'primary_engine'        => $primary_engine_slug,
            'primary_result_count'  => (int) $primary_result_count,
            'primary_failure_type'  => $fallback_meta['primary_failure_type'] ?? '',
            'fallback_engine'       => $fallback_meta['fallback_engine'] ?? null,
            'fallback_source'       => $fallback_meta['fallback_source'] ?? 'none',
            'fallback_rule'         => $fallback_meta['matched_rule'] ?? '',
            'fallback_reason'       => $fallback_meta['reason'] ?? '',
            'fallback_failure_type' => $fallback_meta['fallback_failure_type'] ?? '',
            'failure_type'          => $fallback_meta['primary_failure_type'] ?? '',
            'final_engine'          => $final_engine,
            'final_status'          => $final_status,
            'final_result_count'    => count( $translated ),
            'context_type'          => $route_context['context_type'] ?? '',
            'object_id'             => $route_context['object_id'] ?? 0,
            'post_type'             => $route_context['post_type'] ?? '',
        ] );
    }

    protected function get_engine_display_name( $engine, $engine_slug ) {
        if ( $engine && method_exists( $engine, 'get_label' ) ) {
            $label = (string) $engine->get_label();
            if ( '' !== trim( $label ) ) {
                return trim( $label );
            }
        }

        return (string) $engine_slug;
    }

    protected function get_precheck_log_payload( array $check, $engine_name, $target_language_code, $source_language_code, $skip_primary_call = true ) {
        return [
            'engine'            => $engine_name,
            'target_language'   => $target_language_code,
            'source_language'   => $source_language_code,
            'failure_type'      => $check['failure_type'],
            'is_available'      => $check['is_available'],
            'supports_language' => $check['supports_language'],
            'skip_primary_call' => $skip_primary_call,
        ];
    }

    protected function log_primary_precheck_result( $engine, $engine_slug, array $check, $target_language_code, $source_language_code ) {
        $engine_name = $this->get_engine_display_name( $engine, $engine_slug );
        $payload     = $this->get_precheck_log_payload( $check, $engine_name, $target_language_code, $source_language_code, true );

        if ( 'unsupported_target_language' === $check['failure_type'] ) {
            /* translators: 1: Engine display name, 2: Target language code. */
            $this->logger->debug(
                tpre_log_translatef(
                    '%1$s不支持 %2$s 语言，跳过主调用并准备回退。',
                    $engine_name,
                    $target_language_code
                ),
                $payload
            );
            return;
        }

        if ( 'unsupported_source_language' === $check['failure_type'] ) {
            /* translators: 1: Engine display name, 2: Source language code. */
            $this->logger->debug(
                tpre_log_translatef(
                    '%1$s不支持源语言 %2$s，跳过主调用并准备回退。',
                    $engine_name,
                    $source_language_code
                ),
                $payload
            );
            return;
        }

        if ( 'engine_unavailable' === $check['failure_type'] ) {
            /* translators: %s: Engine display name. */
            $this->logger->error(
                tpre_log_translatef(
                    '%s当前不可用，跳过主调用并准备回退。',
                    $engine_name
                ),
                $payload
            );
            return;
        }

        if ( 'engine_not_found' === $check['failure_type'] ) {
            /* translators: %s: Engine display name. */
            $this->logger->error(
                tpre_log_translatef(
                    '未找到主引擎 %s，跳过主调用并准备回退。',
                    $engine_name
                ),
                $payload
            );
            return;
        }

        /* translators: %s: Engine display name. */
        $this->logger->error(
            tpre_log_translatef(
                '%s预检查未通过，跳过主调用并准备回退。',
                $engine_name
            ),
            $payload
        );
    }

    protected function log_fallback_precheck_result( $engine, $engine_slug, array $check, $target_language_code, $source_language_code, array $decision ) {
        $engine_name = $this->get_engine_display_name( $engine, $engine_slug );
        $payload     = $this->get_precheck_log_payload( $check, $engine_name, $target_language_code, $source_language_code, true );
        $payload['fallback_source'] = $decision['fallback_source'];
        $payload['matched_rule']    = $decision['matched_rule'];

        if ( 'unsupported_target_language' === $check['failure_type'] ) {
            /* translators: 1: Fallback engine display name, 2: Target language code. */
            $this->logger->debug(
                tpre_log_translatef(
                    '回退引擎 %1$s 不支持 %2$s 语言，无法继续回退。',
                    $engine_name,
                    $target_language_code
                ),
                $payload
            );
            return;
        }

        if ( 'unsupported_source_language' === $check['failure_type'] ) {
            /* translators: 1: Fallback engine display name, 2: Source language code. */
            $this->logger->debug(
                tpre_log_translatef(
                    '回退引擎 %1$s 不支持源语言 %2$s，无法继续回退。',
                    $engine_name,
                    $source_language_code
                ),
                $payload
            );
            return;
        }

        if ( 'engine_unavailable' === $check['failure_type'] ) {
            /* translators: %s: Fallback engine display name. */
            $this->logger->error(
                tpre_log_translatef(
                    '回退引擎 %s 当前不可用，无法继续回退。',
                    $engine_name
                ),
                $payload
            );
            return;
        }

        if ( 'engine_not_found' === $check['failure_type'] ) {
            /* translators: %s: Fallback engine display name. */
            $this->logger->error(
                tpre_log_translatef(
                    '未找到回退引擎 %s，无法继续回退。',
                    $engine_name
                ),
                $payload
            );
            return;
        }

        /* translators: %s: Fallback engine display name. */
        $this->logger->error(
            tpre_log_translatef(
                '回退引擎 %s 预检查未通过，无法继续回退。',
                $engine_name
            ),
            $payload
        );
    }

    protected function build_default_engine_fallback_decision( array $decision, $current_engine_slug ) {
        $default_engine = isset( $decision['default_engine'] ) ? $decision['default_engine'] : '';
        if ( empty( $default_engine ) ) {
            return null;
        }

        if ( $default_engine === $current_engine_slug ) {
            return null;
        }

        if ( ! empty( $decision['fallback_engine'] ) && $default_engine === $decision['fallback_engine'] ) {
            return null;
        }

        return [
            'fallback_engine'     => $default_engine,
            'fallback_source'     => 'default_engine',
            'matched_rule'        => '',
            'default_engine'      => $default_engine,
            'current_engine'      => $current_engine_slug,
            'normalized_language' => isset( $decision['normalized_language'] ) ? $decision['normalized_language'] : '',
            'reason'              => 'fallback_map_failed_then_default_engine',
            'ignored_rule'        => isset( $decision['matched_rule'] ) ? $decision['matched_rule'] : '',
            'ignored_reason'      => 'fallback_map_failed',
        ];
    }

    protected function try_fallback_translation( array $new_strings, $target_language_code, $source_language_code, $current_engine_slug, $trigger_failure_type, array $primary_decision = [] ) {
        $attempts = $this->get_cached_fallback_attempts( $target_language_code, $current_engine_slug, $primary_decision );
        $decision = ! empty( $attempts ) ? $attempts[0] : [
            'fallback_engine'     => null,
            'fallback_source'     => 'none',
            'matched_rule'        => '',
            'reason'              => '',
            'default_engine'      => $this->routing_rules->get_default_engine_slug(),
            'ignored_rule'        => '',
            'ignored_reason'      => '',
            'normalized_language' => $target_language_code,
        ];
        $meta     = [
            'used'                  => false,
            'fallback_engine'       => $decision['fallback_engine'],
            'fallback_source'       => $decision['fallback_source'],
            'matched_rule'          => $decision['matched_rule'],
            'reason'                => $decision['reason'],
            'primary_failure_type'  => $trigger_failure_type,
            'fallback_failure_type' => '',
            'final_status'          => 'no_fallback',
        ];

        $fallback_chain = [];
        if ( $this->logger->is_enabled() ) {
            foreach ( $attempts as $attempt_decision ) {
                if ( ! empty( $attempt_decision['fallback_engine'] ) ) {
                    $fallback_chain[] = $attempt_decision['fallback_engine'];
                }
            }
        }

        $this->logger->debug( __( '回退决策', 'langrouter-for-translatepress' ), [
            'target_language'       => $target_language_code,
            'source_language'       => $source_language_code,
            'from_engine'           => $current_engine_slug,
            'fallback_engine'       => $decision['fallback_engine'],
            'fallback_source'       => $decision['fallback_source'],
            'matched_rule'          => $decision['matched_rule'],
            'default_engine'        => $decision['default_engine'],
            'fallback_chain'        => $fallback_chain,
            'fallback_attempt_count' => count( $fallback_chain ),
            'reason'                => $decision['reason'],
            'ignored_rule'          => $decision['ignored_rule'],
            'ignored_reason'        => $decision['ignored_reason'],
            'trigger_failure'       => $trigger_failure_type,
            'count'                 => count( $new_strings ),
            'fallback_mode'         => isset( $primary_decision['fallback_mode'] ) ? $primary_decision['fallback_mode'] : ( ! empty( $primary_decision['use_global_chain'] ) ? 'global_chain' : 'default_only' ),
            'continue_global_chain' => ! empty( $primary_decision['use_global_chain'] ) ? 1 : 0,
        ] );

        if ( empty( $decision['fallback_engine'] ) ) {
            return [
                'translated' => [],
                'meta'       => $meta,
            ];
        }

        foreach ( $attempts as $index => $attempt_decision ) {
            $attempt_engine_slug = $attempt_decision['fallback_engine'];
            if ( empty( $attempt_engine_slug ) ) {
                continue;
            }

            $meta['used']            = true;
            $meta['fallback_engine'] = $attempt_engine_slug;
            $meta['fallback_source'] = $attempt_decision['fallback_source'];
            $meta['matched_rule']    = $attempt_decision['matched_rule'];
            $meta['reason']          = $attempt_decision['reason'];

            $fallback = $this->engine_manager->get_engine( $attempt_engine_slug );
            $check    = $this->inspect_engine( $fallback, $attempt_engine_slug, $target_language_code );
            if ( ! $check['ok'] ) {
                $meta['fallback_failure_type'] = $check['failure_type'];
                $meta['final_status']          = 'fallback_precheck_failed';

                $this->log_fallback_precheck_result( $fallback, $attempt_engine_slug, $check, $target_language_code, $source_language_code, $attempt_decision );

                if ( isset( $attempts[ $index + 1 ]['fallback_engine'] ) ) {
                    $next_engine_slug = $attempts[ $index + 1 ]['fallback_engine'];
                    /* translators: 1: Requested fallback engine slug, 2: Default engine slug. */
                    $this->logger->debug( tpre_log_translatef( '回退引擎 %1$s 未通过预检查，继续尝试下一个候选 %2$s。',
                    $attempt_engine_slug,
                    $next_engine_slug ),
                    [
                        'target_language'      => $target_language_code,
                        'source_language'      => $source_language_code,
                        'from_engine'          => $current_engine_slug,
                        'failed_fallback'      => $attempt_engine_slug,
                        'failed_failure_type'  => $check['failure_type'],
                        'next_fallback_engine' => $next_engine_slug,
                    ] );
                    continue;
                }

                return [
                    'translated' => [],
                    'meta'       => $meta,
                ];
            }

            $fallback_start_message = __( '尝试回退引擎', 'langrouter-for-translatepress' );

            if ( 'default_engine' === $attempt_decision['fallback_source'] ) {
                $default_engine_label = 'openai_compatible' === $attempt_engine_slug
                    ? __( '自定义 openai_compatible', 'langrouter-for-translatepress' )
                    : $attempt_engine_slug;

                /* translators: %s: Default engine label. */
                $fallback_start_message = tpre_log_translatef(
                    '回退到默认引擎 %s，开始批量翻译',
                    $default_engine_label
                );
            }

            $this->logger->debug( $fallback_start_message, [
                'target_language' => $target_language_code,
                'source_language' => $source_language_code,
                'from_engine'     => $current_engine_slug,
                'fallback_engine' => $attempt_engine_slug,
                'fallback_source' => $attempt_decision['fallback_source'],
                'matched_rule'    => $attempt_decision['matched_rule'],
                'count'           => count( $new_strings ),
            ] );

            $translated = $fallback->translate( $new_strings, $target_language_code, $source_language_code );
            $translated = is_array( $translated ) ? $translated : [];

            $this->logger->debug( __( '回退引擎执行完成', 'langrouter-for-translatepress' ),
            [
                'fallback_engine'  => $attempt_engine_slug,
                'fallback_source'  => $attempt_decision['fallback_source'],
                'matched_rule'     => $attempt_decision['matched_rule'],
                'translated_count' => count( $translated ),
            ] );

            if ( ! empty( $translated ) ) {
                $meta['fallback_failure_type'] = '';
                $meta['final_status']          = 'fallback_success';
                return [
                    'translated' => $translated,
                    'meta'       => $meta,
                ];
            }

            $meta['fallback_failure_type'] = 'empty_result';
            $meta['final_status']          = 'fallback_empty';

            if ( isset( $attempts[ $index + 1 ]['fallback_engine'] ) ) {
                $next_engine_slug = $attempts[ $index + 1 ]['fallback_engine'];
                /* translators: 1: Requested fallback engine slug, 2: Default engine slug. */
                $this->logger->debug( tpre_log_translatef( '回退引擎 %1$s 返回空结果，继续尝试下一个候选 %2$s。',
                $attempt_engine_slug, $next_engine_slug ), [
                    'target_language'      => $target_language_code,
                    'source_language'      => $source_language_code,
                    'from_engine'          => $current_engine_slug,
                    'failed_fallback'      => $attempt_engine_slug,
                    'failed_failure_type'  => 'empty_result',
                    'next_fallback_engine' => $next_engine_slug,
                ] );
                continue;
            }

            return [
                'translated' => [],
                'meta'       => $meta,
            ];
        }

        return [
            'translated' => [],
            'meta'       => $meta,
        ];
    }

    protected function should_bypass_router_translation_safety( $engine_slug ) {
        $engine_slug = is_string( $engine_slug ) ? strtolower( trim( $engine_slug ) ) : '';
        $bypass      = false;

        return (bool) apply_filters( 'tpre_router_bypass_translation_safety', $bypass, $engine_slug, $this );
    }

    protected function filter_unsafe_translations( array $source_strings, array $translated, $target_language_code, $engine_slug ) {
        if ( empty( $translated ) || ! class_exists( 'TPRE_Translation_Safety_Utils' ) ) {
            return [ 'translated' => $translated, 'invalid_count' => 0 ];
        }

        if ( $this->should_bypass_router_translation_safety( $engine_slug ) ) {
            $this->logger->debug( __( 'Router 跳过结果安全过滤', 'langrouter-for-translatepress' ), [
                'engine'          => $engine_slug,
                'target_language' => $target_language_code,
                'translated_count' => count( $translated ),
                'reason'          => 'engine_bypass',
            ] );

            return [ 'translated' => $translated, 'invalid_count' => 0 ];
        }

        $filtered      = [];
        $invalid_count = 0;

        foreach ( $translated as $key => $translated_text ) {
            if ( ! array_key_exists( $key, $source_strings ) ) {
                continue;
            }

            $source_text = $source_strings[ $key ];
            $normalized_source = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( (string) $source_text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
            $normalized_result = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( (string) $translated_text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
            $safe_passthrough  = '' !== $normalized_source && 0 === strcasecmp( $normalized_source, $normalized_result ) && TPRE_Translation_Safety_Utils::looks_like_safe_passthrough_text( $source_text );

            if ( ! $safe_passthrough && TPRE_Translation_Safety_Utils::is_suspicious_translation_output( $source_text, $translated_text, $target_language_code ) ) {
                $invalid_count++;
                $this->logger->debug( __( 'Router 丢弃可疑翻译结果', 'langrouter-for-translatepress' ), [
                    'engine'           => $engine_slug,
                    'target_language'  => $target_language_code,
                    'source_preview'   => $this->preview_string( $source_text ),
                    'result_preview'   => $this->preview_string( $translated_text ),
                    'runtime_fallback' => TPRE_Translation_Safety_Utils::should_runtime_fallback_to_source( $source_text, $translated_text ) ? 'source' : 'none',
                    'persist_policy'   => 'drop_result_do_not_store',
                ] );
                continue;
            }

            $filtered[ $key ] = $translated_text;
        }

        return [
            'translated'    => $filtered,
            'invalid_count' => $invalid_count,
        ];
    }

    protected function preview_string( $text, $limit = 160 ) {
        $text = trim( wp_strip_all_tags( (string) $text ) );
        if ( function_exists( 'mb_strlen' ) ) {
            if ( mb_strlen( $text, 'UTF-8' ) > $limit ) {
                return mb_substr( $text, 0, $limit, 'UTF-8' ) . '...';
            }
            return $text;
        }

        if ( strlen( $text ) > $limit ) {
            return substr( $text, 0, $limit ) . '...';
        }

        return $text;
    }


    public function translate_array( $new_strings, $target_language_code, $source_language_code = null ) {
        if ( $source_language_code === null ) {
            $source_language_code = $this->settings['default-language'];
        }

        $route_id = $this->next_route_id();

        return $this->logger->scoped(
            [
                'route_id' => $route_id,
            ],
            function() use ( $new_strings, $target_language_code, $source_language_code ) {
                if ( empty( $new_strings ) ) {
                    $this->logger->debug( __( 'Router 路由跳过', 'langrouter-for-translatepress' ), [
                        'target_language' => $target_language_code,
                        'source_language' => $source_language_code,
                        'route_reason'    => 'empty_input',
                    ] );
                    return [];
                }

                $request_count = count( $new_strings );

                if ( class_exists( 'TPRE_OpenCC_Utils' ) ) {
                    $router_settings = TPRE_Admin_Settings::get_settings();
                    if ( TPRE_OpenCC_Utils::should_skip_machine_translation( $target_language_code, $router_settings ) ) {
                        $this->logger->debug( __( 'Router 跳过繁体中文自动翻译，交给 OpenCC/其他方式处理', 'langrouter-for-translatepress' ), [
                            'target_language'   => $target_language_code,
                            'source_language'   => $source_language_code,
                            'normalized_lang'   => TPRE_OpenCC_Utils::normalize_traditional_locale( $target_language_code ),
                            'handling_mode'     => TPRE_OpenCC_Utils::get_handling_mode( $router_settings ),
                            'request_count'     => $request_count,
                            'skip_reason'       => 'traditional_chinese_external_conversion',
                            'persist_policy'    => 'skip_do_not_store',
                        ] );

                        return [];
                    }
                }

                $route_context = class_exists( 'TPRE_Request_Context_Resolver' ) ? TPRE_Request_Context_Resolver::resolve() : [];
                $engine_decision = $this->get_cached_engine_decision( $target_language_code, $route_context );
                $engine_slug = $engine_decision['selected_engine'];
                $engine = $this->engine_manager->get_engine( $engine_slug );

                if ( $this->logger->is_enabled() ) {
                    $planned_fallback_attempts = $this->get_cached_fallback_attempts( $target_language_code, $engine_slug, $engine_decision );
                    $planned_fallback_summary  = $this->summarize_fallback_attempts( $planned_fallback_attempts );

                    $this->logger->debug( __( 'Router 路由命中', 'langrouter-for-translatepress' ), [
                        'target_language'                    => $target_language_code,
                        'source_language'                    => $source_language_code,
                        'normalized_lang'                    => $engine_decision['normalized_language'],
                        'selected_engine'                    => $engine_slug,
                        'route_source'                       => $engine_decision['route_source'],
                        'matched_rule'                       => $engine_decision['matched_rule'],
                        'default_engine'                     => $engine_decision['default_engine'],
                        'request_count'                      => $request_count,
                        'context_type'                       => $engine_decision['context_type'] ?? '',
                        'object_id'                          => $engine_decision['object_id'] ?? 0,
                        'post_type'                          => $engine_decision['matched_post_type'] ?? '',
                        'fallback_mode'                      => isset( $engine_decision['fallback_mode'] ) ? $engine_decision['fallback_mode'] : ( ! empty( $engine_decision['use_global_chain'] ) ? 'global_chain' : 'default_only' ),
                        'runtime_fallback_mode'              => $engine_decision['runtime_fallback_mode'] ?? ( isset( $engine_decision['fallback_mode'] ) ? $engine_decision['fallback_mode'] : ( ! empty( $engine_decision['use_global_chain'] ) ? 'global_chain' : 'default_only' ) ),
                        'runtime_fallback_mode_source'       => $engine_decision['runtime_fallback_mode_source'] ?? '',
                        'configured_post_type_fallback_mode' => $engine_decision['configured_post_type_fallback_mode'] ?? '',
                        'use_global_chain'                   => ! empty( $engine_decision['use_global_chain'] ) ? 1 : 0,
                        'post_type_rule_found'               => ! empty( $engine_decision['post_type_rule_found'] ) ? 1 : 0,
                        'post_type_rule_source'              => $engine_decision['post_type_rule_source'] ?? 'none',
                        'post_type_rule_lookup_reason'       => $engine_decision['post_type_rule_lookup_reason'] ?? '',
                        'matched_post_type_rule_engine'      => $engine_decision['matched_post_type_rule_engine'] ?? '',
                        'configured_post_type_rules_count'   => isset( $engine_decision['configured_post_type_rules_count'] ) ? (int) $engine_decision['configured_post_type_rules_count'] : 0,
                        'configured_post_type_rule_sources'  => $engine_decision['configured_post_type_rule_sources'] ?? [],
                        'available_post_type_rule_keys'      => $engine_decision['available_post_type_rule_keys'] ?? [],
                        'legacy_post_type_rule_keys'         => $engine_decision['legacy_post_type_rule_keys'] ?? [],
                        'post_type_rule_map_has_key'         => isset( $engine_decision['post_type_rule_map_has_key'] ) ? (int) $engine_decision['post_type_rule_map_has_key'] : 0,
                        'legacy_post_type_map_has_key'       => isset( $engine_decision['legacy_post_type_map_has_key'] ) ? (int) $engine_decision['legacy_post_type_map_has_key'] : 0,
                        'configured_language_rule_engine'    => $engine_decision['configured_language_rule_engine'] ?? '',
                        'configured_fallback_rule_engine'    => $engine_decision['configured_fallback_rule_engine'] ?? '',
                        'planned_fallback_chain'             => $planned_fallback_summary['chain'],
                        'planned_fallback_count'             => count( $planned_fallback_summary['chain'] ),
                        'planned_fallback_details'           => $planned_fallback_summary['details'],
                    ] );
                }

                $this->logger->debug( __( 'Router 开始分发翻译请求', 'langrouter-for-translatepress' ), [
                    'target_language' => $target_language_code,
                    'source_language' => $source_language_code,
                    'engine'          => $engine_slug,
                    'count'           => $request_count,
                ] );

                $primary_check = $this->inspect_engine( $engine, $engine_slug, $target_language_code );
                if ( ! $primary_check['ok'] ) {
                    $this->log_primary_precheck_result( $engine, $engine_slug, $primary_check, $target_language_code, $source_language_code );

                    $fallback_result = $this->try_fallback_translation( $new_strings, $target_language_code, $source_language_code, $engine_slug, $primary_check['failure_type'], $engine_decision );
                    $this->log_route_complete( $target_language_code, $source_language_code, $request_count, $engine_slug, 0, $fallback_result['meta'], $fallback_result['translated'], $route_context );
                    return $fallback_result['translated'];
                }

                $translated           = $engine->translate( $new_strings, $target_language_code, $source_language_code );
                $translated           = is_array( $translated ) ? $translated : [];
                $primary_result_count = count( $translated );

                if ( $primary_result_count > 0 ) {
                    $safety_filtered = $this->filter_unsafe_translations( $new_strings, $translated, $target_language_code, $engine_slug );
                    $translated      = $safety_filtered['translated'];

                    if ( ! empty( $safety_filtered['invalid_count'] ) ) {
                        $this->logger->debug( __( 'Router 已过滤可疑翻译结果', 'langrouter-for-translatepress' ), [
                            'engine'          => $engine_slug,
                            'target_language' => $target_language_code,
                            'invalid_count'   => (int) $safety_filtered['invalid_count'],
                            'kept_count'      => count( $translated ),
                        ] );
                    }
                }

                $primary_result_count = count( $translated );

                if ( 0 === $primary_result_count ) {
                    $this->logger->error( __( '主引擎失败', 'langrouter-for-translatepress' ), [
                        'engine'            => $engine_slug,
                        'target_language'   => $target_language_code,
                        'source_language'   => $source_language_code,
                        'failure_type'      => 'empty_result',
                        'translated_count'  => 0,
                    ] );

                    $fallback_result = $this->try_fallback_translation( $new_strings, $target_language_code, $source_language_code, $engine_slug, 'empty_result', $engine_decision );
                    $this->log_route_complete( $target_language_code, $source_language_code, $request_count, $engine_slug, 0, $fallback_result['meta'], $fallback_result['translated'], $route_context );
                    return $fallback_result['translated'];
                }

                $this->logger->debug( __( '主引擎翻译完成', 'langrouter-for-translatepress' ), [
                    'engine'           => $engine_slug,
                    'translated_count' => $primary_result_count,
                ] );

                $this->log_route_complete(
                    $target_language_code,
                    $source_language_code,
                    $request_count,
                    $engine_slug,
                    $primary_result_count,
                    [
                        'used'                  => false,
                        'fallback_engine'       => null,
                        'fallback_source'       => 'none',
                        'matched_rule'          => '',
                        'reason'                => '',
                        'primary_failure_type'  => '',
                        'fallback_failure_type' => '',
                        'final_status'          => 'primary_success',
                    ],
                    $translated,
                    $route_context
                );

                return $translated;
            }
        );
    }
}
