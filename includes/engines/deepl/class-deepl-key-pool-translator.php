<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'tpre_deepl_get_base_translator_class_name' ) ) {
    function tpre_deepl_get_base_translator_class_name() {
        if ( class_exists( 'TRP_IN_Deepl_Machine_Translator' ) ) {
            return 'TRP_IN_Deepl_Machine_Translator';
        }
        if ( class_exists( 'TRP_Deepl_Machine_Translator' ) ) {
            return 'TRP_Deepl_Machine_Translator';
        }
        return '';
    }
}

if ( ! function_exists( 'tpre_deepl_get_model_settings' ) ) {
    function tpre_deepl_get_model_settings() {
        $router_settings = class_exists( 'TPRE_Admin_Settings' ) ? TPRE_Admin_Settings::get_settings() : [];
        $models          = isset( $router_settings['models'] ) && is_array( $router_settings['models'] ) ? $router_settings['models'] : [];
        $defaults        = [
            'enabled'            => 1,
            'keys_text'          => '',
            'include_single_key' => 'no',
            'throttle_seconds'   => 15,
            'error_cooldown'     => 120,
            'quota_cooldown'     => 1800,
            'forbidden_cooldown' => 600,
            'note'               => __( 'DeepL 已接入内置账号池。', 'langrouter-for-translatepress' ),
        ];

        $settings = isset( $models['deepl'] ) && is_array( $models['deepl'] ) ? $models['deepl'] : [];
        return wp_parse_args( $settings, $defaults );
    }
}

if ( ! function_exists( 'tpre_deepl_get_runtime_option_name' ) ) {
    function tpre_deepl_get_runtime_option_name() {
        return 'tpre_deepl_runtime';
    }
}

if ( ! function_exists( 'tpre_deepl_infer_key_type' ) ) {
    function tpre_deepl_infer_key_type( $key, $default_type = 'pro' ) {
        $key          = trim( (string) $key );
        $default_type = 'free' === $default_type ? 'free' : 'pro';

        if ( '' === $key ) {
            return $default_type;
        }

        if ( preg_match( '/:fx$/i', $key ) ) {
            return 'free';
        }

        return $default_type;
    }
}

if ( ! function_exists( 'tpre_deepl_mask_key' ) ) {
    function tpre_deepl_mask_key( $key ) {
        $key = trim( (string) $key );
        $len = strlen( $key );
        if ( $len <= 8 ) {
            return str_repeat( '*', max( 0, $len - 2 ) ) . substr( $key, -2 );
        }

        return substr( $key, 0, 4 ) . str_repeat( '*', max( 0, $len - 8 ) ) . substr( $key, -4 );
    }
}


if ( ! function_exists( 'tpre_deepl_router_log' ) ) {
    function tpre_deepl_router_log( $level, $message, array $context = [] ) {
        if ( ! class_exists( 'TPRE_Logger' ) ) {
            return;
        }

        if ( 'error' === strtolower( (string) $level ) ) {
            TPRE_Logger::quick_error( $message, $context );
            return;
        }

        TPRE_Logger::quick_debug( $message, $context );
    }
}

if ( ! function_exists( 'tpre_deepl_status_label' ) ) {
    function tpre_deepl_status_label( $status ) {
        $map = array(
            'idle'                   => __('空闲', 'langrouter-for-translatepress'),
            'ok'                     => __('正常', 'langrouter-for-translatepress'),
            'fallback_ok'            => __('兜底正常', 'langrouter-for-translatepress'),
            'wp_error'               => __('网络错误', 'langrouter-for-translatepress'),
            'fallback_wp_error'      => __('兜底网络错误', 'langrouter-for-translatepress'),
            '429_throttled'          => __('请求过快，已冷却', 'langrouter-for-translatepress'),
            'fallback_429_throttled' => __('兜底请求过快，已冷却', 'langrouter-for-translatepress'),
            '456_quota'              => __('额度用尽，已冷却', 'langrouter-for-translatepress'),
            'fallback_456_quota'     => __('兜底额度用尽，已冷却', 'langrouter-for-translatepress'),
            '403_forbidden'          => __('权限被拒绝', 'langrouter-for-translatepress'),
            'fallback_403_forbidden' => __('兜底权限被拒绝', 'langrouter-for-translatepress'),
            'server_error'           => __('服务器错误', 'langrouter-for-translatepress'),
            'fallback_server_error'  => __('兜底服务器错误', 'langrouter-for-translatepress'),
            'request_failed'         => __('请求失败', 'langrouter-for-translatepress'),
            'fallback_failed'        => __('兜底请求失败', 'langrouter-for-translatepress'),
        );

        return isset( $map[ $status ] ) ? $map[ $status ] : ( '' !== (string) $status ? (string) $status : '-' );
    }
}

if ( ! function_exists( 'tpre_deepl_get_default_api_type_for_admin' ) ) {
    function tpre_deepl_get_default_api_type_for_admin() {
        return 'free';
    }
}

if ( ! function_exists( 'tpre_deepl_parse_pool_entries_for_admin' ) ) {
    function tpre_deepl_parse_pool_entries_for_admin( $keys_text ) {
        $default_type = tpre_deepl_get_default_api_type_for_admin();
        $lines        = preg_split( '/\r\n|\r|\n/', (string) $keys_text );
        $entries      = [];
        $seen         = [];

        foreach ( $lines as $line_index => $line ) {
            $line = trim( (string) $line );
            if ( '' === $line ) {
                continue;
            }

            $type = $default_type;
            $key  = $line;

            if ( preg_match( '/^(free|pro)\s*:\s*(.+)$/i', $line, $matches ) ) {
                $type = strtolower( $matches[1] );
                $key  = trim( $matches[2] );
            } else {
                $type = tpre_deepl_infer_key_type( $key, $default_type );
            }

            if ( '' === $key ) {
                continue;
            }

            $hash = sha1( $type . '|' . $key );
            if ( isset( $seen[ $hash ] ) ) {
                continue;
            }

            $seen[ $hash ] = true;
            $entries[]     = [
                'line_no'    => (int) $line_index + 1,
                'hash'       => $hash,
                'type'       => $type,
                'masked_key' => tpre_deepl_mask_key( $key ),
            ];
        }

        return $entries;
    }
}


if ( ! function_exists( 'tpre_deepl_sync_runtime_with_keys_text' ) ) {
    function tpre_deepl_sync_runtime_with_keys_text( $keys_text ) {
        $runtime = get_option( tpre_deepl_get_runtime_option_name(), [] );
        if ( ! is_array( $runtime ) ) {
            $runtime = [];
        }

        $parsed_entries = tpre_deepl_parse_pool_entries_for_admin( $keys_text );
        $allowed_hashes = [];
        foreach ( $parsed_entries as $entry ) {
            if ( empty( $entry['hash'] ) ) {
                continue;
            }
            $allowed_hashes[ $entry['hash'] ] = [
                'masked_key' => isset( $entry['masked_key'] ) ? (string) $entry['masked_key'] : '',
                'type'       => isset( $entry['type'] ) ? (string) $entry['type'] : '',
                'line_no'    => isset( $entry['line_no'] ) ? (int) $entry['line_no'] : 0,
            ];
        }

        $existing_entries = ( ! empty( $runtime['entries'] ) && is_array( $runtime['entries'] ) ) ? $runtime['entries'] : [];
        $synced_entries   = [];

        foreach ( $allowed_hashes as $hash => $meta ) {
            $current = isset( $existing_entries[ $hash ] ) && is_array( $existing_entries[ $hash ] ) ? $existing_entries[ $hash ] : [];
            $synced_entries[ $hash ] = array_merge(
                [
                    'masked_key'      => $meta['masked_key'],
                    'type'            => $meta['type'],
                    'is_fallback'     => 'no',
                    'status'          => 'idle',
                    'last_code'       => '',
                    'cooldown_until'  => 0,
                    'last_success_at' => 0,
                    'line_no'         => $meta['line_no'],
                ],
                $current,
                [
                    'masked_key'  => $meta['masked_key'],
                    'type'        => $meta['type'],
                    'line_no'     => $meta['line_no'],
                    'is_fallback' => 'no',
                ]
            );
        }

        $new_runtime = $runtime;
        $new_runtime['entries'] = $synced_entries;
        $pool_count = count( $parsed_entries );
        $pointer    = isset( $runtime['pointer'] ) ? (int) $runtime['pointer'] : 0;
        $new_runtime['pointer'] = $pool_count > 0 ? min( max( 0, $pointer ), $pool_count - 1 ) : 0;

        if ( $new_runtime !== $runtime ) {
            update_option( tpre_deepl_get_runtime_option_name(), $new_runtime, false );
        }

        return $new_runtime;
    }
}

if ( ! function_exists( 'tpre_deepl_define_key_pool_translator_class' ) ) {
    function tpre_deepl_define_key_pool_translator_class() {
        if ( class_exists( 'TPRE_DeepL_Key_Pool_Machine_Translator', false ) ) {
            return true;
        }

        $base_class = tpre_deepl_get_base_translator_class_name();
        if ( '' === $base_class ) {
            return false;
        }

        if ( ! class_exists( 'TPRE_DeepL_Base_Translator', false ) ) {
            class_alias( $base_class, 'TPRE_DeepL_Base_Translator' );
        }

        if ( class_exists( 'TPRE_DeepL_Base_Translator', false ) && ! class_exists( 'TPRE_DeepL_Key_Pool_Machine_Translator', false ) ) {
            class TPRE_DeepL_Key_Pool_Machine_Translator extends TPRE_DeepL_Base_Translator {
                protected $tpre_active_entry = null;
                protected $tpre_pool_cache   = null;
                protected $tpre_fallback_key = null;

                protected function tpre_default_type() {
                    return 'free';
                }

                protected function tpre_build_language_code_candidates( $language_code ) {
                    $raw = is_string( $language_code ) ? trim( $language_code ) : '';
                    if ( '' === $raw ) {
                        return [];
                    }

                    $normalized = str_replace( '-', '_', $raw );
                    $lower      = strtolower( $normalized );
                    $base       = preg_replace( '/[_-].*$/', '', $lower );

                    $candidates = [
                        $raw,
                        $normalized,
                        strtolower( $raw ),
                        $lower,
                    ];

                    if ( is_string( $base ) && '' !== $base ) {
                        $candidates[] = $base;
                    }

                    $result = [];
                    foreach ( $candidates as $candidate ) {
                        if ( ! is_string( $candidate ) ) {
                            continue;
                        }

                        $candidate = trim( $candidate );
                        if ( '' === $candidate ) {
                            continue;
                        }

                        $result[] = $candidate;
                    }

                    return array_values( array_unique( $result ) );
                }

                protected function tpre_resolve_machine_translation_code( $language_code ) {
                    if ( ! is_array( $this->machine_translation_codes ) ) {
                        return is_string( $language_code ) ? trim( $language_code ) : '';
                    }

                    $lookup = [];
                    foreach ( $this->machine_translation_codes as $mapped_key => $mapped_value ) {
                        if ( ! is_string( $mapped_key ) || '' === trim( $mapped_key ) || ! is_string( $mapped_value ) || '' === trim( $mapped_value ) ) {
                            continue;
                        }

                        $normalized_key            = strtolower( str_replace( '-', '_', trim( $mapped_key ) ) );
                        $lookup[ $normalized_key ] = trim( $mapped_value );
                    }

                    foreach ( $this->tpre_build_language_code_candidates( $language_code ) as $candidate ) {
                        $normalized_candidate = strtolower( str_replace( '-', '_', $candidate ) );
                        if ( isset( $lookup[ $normalized_candidate ] ) ) {
                            return $lookup[ $normalized_candidate ];
                        }
                    }

                    return is_string( $language_code ) ? trim( $language_code ) : '';
                }

                protected function tpre_prepare_request_languages( $target_language_code, $source_language_code ) {
                    $source_language = $this->tpre_resolve_machine_translation_code( $source_language_code );
                    $target_language = $this->tpre_resolve_machine_translation_code( $target_language_code );

                    $source_language = apply_filters( 'trp_deepl_source_language', $source_language, $source_language_code, $target_language_code ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- TranslatePress compatibility filter.
                    $target_language = apply_filters( 'trp_deepl_target_language', $target_language, $source_language_code, $target_language_code ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- TranslatePress compatibility filter.

                    return [
                        'source_language' => is_string( $source_language ) ? trim( $source_language ) : '',
                        'target_language' => is_string( $target_language ) ? trim( $target_language ) : '',
                    ];
                }

                protected function tpre_get_model_settings() {
                    return tpre_deepl_get_model_settings();
                }

                protected function tpre_parse_entry( $raw_line, $default_type, $is_fallback = false, $line_no = 0 ) {
                    $line = trim( (string) $raw_line );
                    if ( '' === $line ) {
                        return null;
                    }

                    $type = $default_type;
                    $key  = $line;

                    if ( preg_match( '/^(free|pro)\s*:\s*(.+)$/i', $line, $matches ) ) {
                        $type = strtolower( $matches[1] );
                        $key  = trim( $matches[2] );
                    } else {
                        $type = tpre_deepl_infer_key_type( $key, $default_type );
                    }

                    if ( '' === $key ) {
                        return null;
                    }

                    return [
                        'key'         => $key,
                        'type'        => $type,
                        'hash'        => sha1( $type . '|' . $key ),
                        'masked_key'  => tpre_deepl_mask_key( $key ),
                        'line_no'     => (int) $line_no,
                        'is_fallback' => ! empty( $is_fallback ),
                    ];
                }

                protected function tpre_get_pool_entries() {
                    if ( null !== $this->tpre_pool_cache ) {
                        return $this->tpre_pool_cache;
                    }

                    $settings     = $this->tpre_get_model_settings();
                    $entries      = [];
                    $seen         = [];
                    $default_type = $this->tpre_default_type();
                    $keys_text    = isset( $settings['keys_text'] ) ? (string) $settings['keys_text'] : '';
                    $lines        = preg_split( '/\r\n|\r|\n/', $keys_text );

                    foreach ( $lines as $line_index => $line ) {
                        $entry = $this->tpre_parse_entry( $line, $default_type, false, (int) $line_index + 1 );
                        if ( empty( $entry['key'] ) ) {
                            continue;
                        }
                        if ( isset( $seen[ $entry['hash'] ] ) ) {
                            continue;
                        }
                        $seen[ $entry['hash'] ] = true;
                        $entries[]              = $entry;
                    }

                    $this->tpre_pool_cache = $entries;
                    return $entries;
                }

                protected function tpre_get_fallback_entry() {
                    if ( null !== $this->tpre_fallback_key ) {
                        return $this->tpre_fallback_key;
                    }

                    $settings = $this->tpre_get_model_settings();
                    $this->tpre_fallback_key = false;
                    return false;
                }

                protected function tpre_get_pointer() {
                    $runtime = get_option( tpre_deepl_get_runtime_option_name(), [] );
                    return isset( $runtime['pointer'] ) ? (int) $runtime['pointer'] : 0;
                }

                protected function tpre_set_pointer( $value ) {
                    $runtime            = get_option( tpre_deepl_get_runtime_option_name(), [] );
                    $runtime['pointer'] = max( 0, (int) $value );
                    update_option( tpre_deepl_get_runtime_option_name(), $runtime, false );
                }

                protected function tpre_get_runtime() {
                    $runtime = get_option( tpre_deepl_get_runtime_option_name(), [] );
                    return is_array( $runtime ) ? $runtime : [];
                }

                protected function tpre_update_runtime_entry( $entry, $data ) {
                    if ( empty( $entry['hash'] ) ) {
                        return;
                    }

                    $runtime = $this->tpre_get_runtime();
                    if ( empty( $runtime['entries'] ) || ! is_array( $runtime['entries'] ) ) {
                        $runtime['entries'] = [];
                    }
                    if ( empty( $runtime['entries'][ $entry['hash'] ] ) || ! is_array( $runtime['entries'][ $entry['hash'] ] ) ) {
                        $runtime['entries'][ $entry['hash'] ] = [
                            'masked_key'     => $entry['masked_key'] ?? '',
                            'type'           => $entry['type'] ?? '',
                            'is_fallback'    => ! empty( $entry['is_fallback'] ) ? 'yes' : 'no',
                            'status'         => 'idle',
                            'last_code'      => '',
                            'cooldown_until' => 0,
                            'last_success_at'=> 0,
                            'line_no'        => isset( $entry['line_no'] ) ? (int) $entry['line_no'] : 0,
                        ];
                    }

                    $runtime['entries'][ $entry['hash'] ] = array_merge( $runtime['entries'][ $entry['hash'] ], $data );
                    update_option( tpre_deepl_get_runtime_option_name(), $runtime, false );
                }

                protected function tpre_is_on_cooldown( $entry ) {
                    if ( empty( $entry['hash'] ) ) {
                        return false;
                    }
                    $runtime = $this->tpre_get_runtime();
                    $until   = isset( $runtime['entries'][ $entry['hash'] ]['cooldown_until'] ) ? (int) $runtime['entries'][ $entry['hash'] ]['cooldown_until'] : 0;
                    return $until > time();
                }

                protected function tpre_set_cooldown( $entry, $seconds, $status, $code ) {
                    $this->tpre_update_runtime_entry(
                        $entry,
                        [
                            'last_code'      => is_scalar( $code ) ? (string) $code : '',
                            'status'         => $status,
                            'cooldown_until' => time() + max( 1, (int) $seconds ),
                        ]
                    );
                }

                protected function tpre_mark_success( $entry, $code = 200 ) {
                    $this->tpre_update_runtime_entry(
                        $entry,
                        [
                            'last_code'       => (string) $code,
                            'status'          => ! empty( $entry['is_fallback'] ) ? 'fallback_ok' : 'ok',
                            'cooldown_until'  => 0,
                            'last_success_at' => time(),
                        ]
                    );
                }

                protected function tpre_with_entry( $entry, $callback ) {
                    $previous                = $this->tpre_active_entry;
                    $this->tpre_active_entry = $entry;
                    $result                  = call_user_func( $callback );
                    $this->tpre_active_entry = $previous;
                    return $result;
                }

                protected function tpre_get_candidate_entries() {
                    $pool_entries = $this->tpre_get_pool_entries();
                    $count        = count( $pool_entries );
                    $ordered      = [];
                    $cooling      = [];

                    if ( $count > 0 ) {
                        $pointer = $this->tpre_get_pointer();
                        for ( $i = 0; $i < $count; $i++ ) {
                            $index = ( $pointer + $i ) % $count;
                            $entry = $pool_entries[ $index ];
                            if ( $this->tpre_is_on_cooldown( $entry ) ) {
                                $cooling[] = $entry;
                            } else {
                                $ordered[] = $entry;
                            }
                        }

                        if ( ! empty( $ordered ) ) {
                            return $ordered;
                        }

                        $fallback = $this->tpre_get_fallback_entry();
                        if ( ! empty( $fallback['key'] ) && ! $this->tpre_is_on_cooldown( $fallback ) ) {
                            $cooling[] = $fallback;
                        }

                        return ! empty( $cooling ) ? $cooling : [];
                    }

                    $fallback = $this->tpre_get_fallback_entry();
                    if ( ! empty( $fallback['key'] ) ) {
                        return [ $fallback ];
                    }

                    return [];
                }

                protected function tpre_advance_pointer_from_entry( $entry ) {
                    if ( ! empty( $entry['is_fallback'] ) ) {
                        return;
                    }

                    $entries = $this->tpre_get_pool_entries();
                    foreach ( $entries as $index => $candidate ) {
                        if ( $candidate['hash'] === $entry['hash'] ) {
                            $this->tpre_set_pointer( $index + 1 );
                            return;
                        }
                    }
                }

                protected function tpre_mark_failure_from_response( $entry, $response ) {
                    tpre_deepl_router_log( 'error', __( 'DeepL 账号池条目请求失败', 'langrouter-for-translatepress' ),
                    [
                        'masked_key'  => $entry['masked_key'] ?? '',
                        'type'        => $entry['type'] ?? '',
                        'is_fallback' => ! empty( $entry['is_fallback'] ) ? 'yes' : 'no',
                        'code'        => is_wp_error( $response ) ? 'wp_error' : (int) wp_remote_retrieve_response_code( $response ),
                    ] );
                    $settings = $this->tpre_get_model_settings();

                    if ( is_wp_error( $response ) ) {
                        $this->tpre_set_cooldown( $entry, (int) $settings['error_cooldown'], ! empty( $entry['is_fallback'] ) ? 'fallback_wp_error' : 'wp_error', 'wp_error' );
                        return;
                    }

                    $code = (int) wp_remote_retrieve_response_code( $response );
                    if ( 429 === $code ) {
                        $this->tpre_set_cooldown( $entry, (int) $settings['throttle_seconds'], ! empty( $entry['is_fallback'] ) ? 'fallback_429_throttled' : '429_throttled', $code );
                        return;
                    }
                    if ( 456 === $code ) {
                        $this->tpre_set_cooldown( $entry, (int) $settings['quota_cooldown'], ! empty( $entry['is_fallback'] ) ? 'fallback_456_quota' : '456_quota', $code );
                        return;
                    }
                    if ( 403 === $code ) {
                        $this->tpre_set_cooldown( $entry, (int) $settings['forbidden_cooldown'], ! empty( $entry['is_fallback'] ) ? 'fallback_403_forbidden' : '403_forbidden', $code );
                        return;
                    }
                    if ( $code >= 500 || 0 === $code ) {
                        $this->tpre_set_cooldown( $entry, (int) $settings['error_cooldown'], ! empty( $entry['is_fallback'] ) ? 'fallback_server_error' : 'server_error', $code );
                        return;
                    }

                    $this->tpre_update_runtime_entry(
                        $entry,
                        [
                            'last_code'      => (string) $code,
                            'status'         => ! empty( $entry['is_fallback'] ) ? 'fallback_failed' : 'request_failed',
                            'cooldown_until' => 0,
                        ]
                    );
                }

                protected function tpre_try_with_pool( $callback ) {
                    tpre_deepl_router_log(
                        'debug',
                        __( 'DeepL 账号池开始选择候选 key', 'langrouter-for-translatepress' ),
                        []
                    );

                    $entries = $this->tpre_get_candidate_entries();

                    if ( empty( $entries ) ) {
                        tpre_deepl_router_log(
                            'error',
                            __( 'DeepL 账号池无可用 key', 'langrouter-for-translatepress' ),
                            []
                        );
                        return null;
                    }

                    $last_response = null;
                    foreach ( $entries as $entry ) {
                        tpre_deepl_router_log( 'debug', __( 'DeepL 尝试账号池条目', 'langrouter-for-translatepress' ),
                        [
                            'masked_key'  => $entry['masked_key'] ?? '',
                            'type'        => $entry['type'] ?? '',
                            'is_fallback' => ! empty( $entry['is_fallback'] ) ? 'yes' : 'no',
                        ] );
                        $response      = $this->tpre_with_entry( $entry, $callback );
                        $last_response = $response;
                        $code          = (int) wp_remote_retrieve_response_code( $response );

                        if ( 200 === $code ) {
                            tpre_deepl_router_log( 'debug', __( 'DeepL 账号池条目请求成功', 'langrouter-for-translatepress' ), 
                            [
                                'masked_key'  => $entry['masked_key'] ?? '',
                                'type'        => $entry['type'] ?? '',
                                'is_fallback' => ! empty( $entry['is_fallback'] ) ? 'yes' : 'no',
                                'code'        => $code,
                            ] );
                            $this->tpre_mark_success( $entry, $code );
                            $this->tpre_advance_pointer_from_entry( $entry );
                            return $response;
                        }

                        $this->tpre_mark_failure_from_response( $entry, $response );
                    }

                    return $last_response;
                }

                public function get_api_key() {
                    if ( ! empty( $this->tpre_active_entry['key'] ) ) {
                        return $this->tpre_active_entry['key'];
                    }

                    $entries = $this->tpre_get_pool_entries();
                    if ( ! empty( $entries[0]['key'] ) ) {
                        return $entries[0]['key'];
                    }

                    $fallback = $this->tpre_get_fallback_entry();
                    if ( ! empty( $fallback['key'] ) ) {
                        return $fallback['key'];
                    }

                    return parent::get_api_key();
                }

                public function get_api_url() {
                    $type = null;
                    if ( ! empty( $this->tpre_active_entry['type'] ) ) {
                        $type = $this->tpre_active_entry['type'];
                    } else {
                        $entries = $this->tpre_get_pool_entries();
                        if ( ! empty( $entries[0]['type'] ) ) {
                            $type = $entries[0]['type'];
                        } else {
                            $fallback = $this->tpre_get_fallback_entry();
                            if ( ! empty( $fallback['type'] ) ) {
                                $type = $fallback['type'];
                            }
                        }
                    }

                    if ( 'free' === $type ) {
                        return 'https://api-free.deepl.com/v2';
                    }
                    if ( 'pro' === $type ) {
                        return 'https://api.deepl.com/v2';
                    }

                    return parent::get_api_url();
                }


                protected function tpre_is_single_character_context_candidate( $text ) {
                    $plain = trim( html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
                    if ( '' === $plain ) {
                        return false;
                    }

                    if ( function_exists( 'mb_strlen' ) ) {
                        if ( 1 !== mb_strlen( $plain, 'UTF-8' ) ) {
                            return false;
                        }
                    } elseif ( 1 !== strlen( $plain ) ) {
                        return false;
                    }

                    if ( 1 === preg_match( '/^[[:punct:][:space:][:digit:]]+$/u', $plain ) ) {
                        return false;
                    }

                    if ( 1 === preg_match( '/^[A-Za-z0-9]$/', $plain ) ) {
                        return false;
                    }

                    return 1 === preg_match( '/\p{L}/u', $plain );
                }

                protected function tpre_build_single_character_fallback_context( $source_language_code = '' ) {
                    $source_language_code = strtolower( str_replace( '-', '_', (string) $source_language_code ) );

                    if ( 0 === strpos( $source_language_code, 'zh' ) ) {
                        return '以下文本是网站表格或界面里的极短标签，通常表示速度、等级、灵活度、难度、方案类型或状态。请仅翻译成目标语言中的简短界面标签，不要解释，不要翻成语言名、国家名、路线名、品牌名。';
                    }

                    if ( 0 === strpos( $source_language_code, 'ja' ) ) {
                        return '以下のテキストは、ウェブサイトの表やUIに出る非常に短いラベルです。速度、レベル、柔軟性、難易度、プラン種別、状態などを表します。説明は付けず、対象言語の短いUIラベルだけを返してください。言語名、国名、路線名、ブランド名にしないでください。';
                    }

                    if ( 0 === strpos( $source_language_code, 'ko' ) ) {
                        return '다음 텍스트는 웹사이트 표나 UI에 쓰이는 매우 짧은 라벨입니다. 속도, 수준, 유연성, 난이도, 유형, 상태 등을 뜻합니다. 설명하지 말고 대상 언어의 짧은 UI 라벨만 번역하세요. 언어명, 국가명, 노선명, 브랜드명으로 번역하지 마세요.';
                    }

                    return 'The following texts are ultra-short labels from a website comparison table or UI. They usually indicate speed, level, flexibility, difficulty, option type, or status. Translate each item as a short UI label only. Do not output explanations, language names, country names, route names, or brand names.';
                }

                protected function tpre_build_source_side_context( array $regular_strings, $source_language_code = '' ) {
                    $parts = [];

                    foreach ( $regular_strings as $value ) {
                        $plain = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( (string) $value ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
                        if ( '' === $plain ) {
                            continue;
                        }

                        $parts[] = $plain;

                        if ( count( $parts ) >= 12 ) {
                            break;
                        }
                    }

                    if ( empty( $parts ) ) {
                        return $this->tpre_build_single_character_fallback_context( $source_language_code );
                    }

                    return implode( "
", $parts );
                }

                protected function tpre_split_chunk_for_context( array $chunk, $source_language_code = '' ) {
                    $regular_strings = [];
                    $context_strings = [];

                    foreach ( $chunk as $key => $value ) {
                        if ( $this->tpre_is_single_character_context_candidate( $value ) ) {
                            $context_strings[ $key ] = $value;
                        } else {
                            $regular_strings[ $key ] = $value;
                        }
                    }

                    $groups = [];

                    if ( ! empty( $regular_strings ) ) {
                        $groups[] = [
                            'chunk'   => $regular_strings,
                            'context' => '',
                            'mode'    => 'default',
                        ];
                    }

                    if ( ! empty( $context_strings ) ) {
                        $groups[] = [
                            'chunk'   => $context_strings,
                            'context' => $this->tpre_build_source_side_context( $regular_strings, $source_language_code ),
                            'mode'    => 'single_character_context',
                        ];
                    }

                    return $groups;
                }

                protected function tpre_send_request_with_optional_context( $source_language, $language_code, $strings_array, $formality = 'default', $context = '' ) {
                    if ( '' === trim( (string) $context ) ) {
                        return parent::send_request( $source_language, $language_code, $strings_array, $formality );
                    }

                    $glossary_language_pairs_user_input = apply_filters( 'trp_add_deepl_glossaries_ids', [] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- TranslatePress integration hook.
                    $glossary_id                        = null;

                    if ( ! empty( $glossary_language_pairs_user_input ) && method_exists( $this, 'sort_the_user_input_data_for_glossary' ) ) {
                        $glossary_id = $this->sort_the_user_input_data_for_glossary( $glossary_language_pairs_user_input, $source_language, $language_code );
                    }

                    $params = [
                        'source_lang'           => $source_language,
                        'target_lang'           => $language_code,
                        'split_sentences'       => '1',
                        'enable_beta_languages' => '1',
                        'context'               => $context,
                    ];

                    if ( 'default' !== $formality ) {
                        $params['formality'] = $formality;
                    }

                    if ( $glossary_id ) {
                        $params['glossary_id'] = $glossary_id;
                    }

                    $params = apply_filters( 'trp_deepl_request_params', $params ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- TranslatePress integration hook.

                    $translation_request_parts = [];
                    foreach ( $params as $key => $value ) {
                        if ( is_array( $value ) ) {
                            continue;
                        }

                        if ( is_bool( $value ) ) {
                            $value = $value ? 'true' : 'false';
                        }

                        $translation_request_parts[] = rawurlencode( (string) $key ) . '=' . rawurlencode( (string) $value );
                    }

                    foreach ( $strings_array as $new_string ) {
                        $translation_request_parts[] = 'text=' . rawurlencode( html_entity_decode( $new_string, ENT_QUOTES ) );
                    }

                    $translation_request = implode( '&', $translation_request_parts );

                    $request_headers = [
                        'Referer'       => $this->get_referer(),
                        'Authorization' => 'DeepL-Auth-Key ' . $this->get_api_key(),
                    ];

                    return wp_remote_post(
                        $this->get_api_url() . '/translate',
                        [
                            'method'  => 'POST',
                            'timeout' => 45,
                            'headers' => $request_headers,
                            'body'    => $translation_request,
                        ]
                    );
                }

                protected function tpre_merge_translated_chunk( $response, array $chunk, array &$translated_strings, $source_language, $target_language ) {
                    $this->machine_translator_logger->log(
                        [
                            'strings'     => serialize( $chunk ),
                            'response'    => serialize( $response ),
                            'lang_source' => $source_language,
                            'lang_target' => $target_language,
                        ]
                    );

                    if ( ! is_array( $response ) || is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
                        return false;
                    }

                    $this->machine_translator_logger->count_towards_quota( $chunk );
                    $translation_response = json_decode( wp_remote_retrieve_body( $response ) );
                    $translations         = empty( $translation_response->translations ) ? [] : $translation_response->translations;
                    $i                    = 0;

                    foreach ( $chunk as $key => $old_string ) {
                        if ( isset( $translations[ $i ] ) && ! empty( $translations[ $i ]->text ) ) {
                            $translated_strings[ $key ] = $translations[ $i ]->text;
                        } else {
                            $translated_strings[ $key ] = $old_string;
                        }
                        $i++;
                    }

                    return true;
                }

                public function translate_array( $new_strings, $target_language_code, $source_language_code = null ) {

                    tpre_deepl_router_log( 'debug', __( 'DeepL translate_array 开始', 'langrouter-for-translatepress' ), 
                    [
                        'target_language' => $target_language_code,
                        'source_language' => $source_language_code,
                        'count'           => is_array( $new_strings ) ? count( $new_strings ) : 0,
                    ] );
                    if ( null === $source_language_code ) {
                        $source_language_code = $this->settings['default-language'];
                    }

                    if ( empty( $this->tpre_get_pool_entries() ) && ! $this->tpre_get_fallback_entry() ) {
                        return parent::translate_array( $new_strings, $target_language_code, $source_language_code );
                    }

                    if ( empty( $new_strings ) ) {
                        return [];
                    }

                    $languages       = $this->tpre_prepare_request_languages( $target_language_code, $source_language_code );
                    $source_language = $languages['source_language'];
                    $target_language = $languages['target_language'];

                    if ( '' === $source_language || '' === $target_language ) {
                        tpre_deepl_router_log( 'error', __( 'DeepL 语言代码解析失败，跳过本次请求。', 'langrouter-for-translatepress' ), [
                            'target_language'        => $target_language_code,
                            'source_language'        => $source_language_code,
                            'resolved_target_lang'   => $target_language,
                            'resolved_source_lang'   => $source_language,
                        ] );
                        return [];
                    }

                    $translated_strings = [];
                    $formality          = $this->get_request_formality_for_language( $target_language_code );
                    $chunks             = array_chunk( $new_strings, 50, true );

                    foreach ( $chunks as $chunk ) {
                        $request_groups = $this->tpre_split_chunk_for_context( $chunk, $source_language_code );

                        foreach ( $request_groups as $group ) {
                            $group_chunk   = isset( $group['chunk'] ) && is_array( $group['chunk'] ) ? $group['chunk'] : [];
                            $group_context = isset( $group['context'] ) ? (string) $group['context'] : '';
                            $group_mode    = isset( $group['mode'] ) ? (string) $group['mode'] : 'default';

                            if ( empty( $group_chunk ) ) {
                                continue;
                            }

                            if ( 'single_character_context' === $group_mode ) {
                                tpre_deepl_router_log( 'debug', __( 'DeepL 短文本上下文增强已启用', 'langrouter-for-translatepress' ), [
                                    'target_language' => $target_language_code,
                                    'source_language' => $source_language_code,
                                    'count'           => count( $group_chunk ),
                                    'sample'          => array_slice( array_values( $group_chunk ), 0, 5 ),
                                    'context_preview' => '' !== $group_context ? mb_substr( $group_context, 0, 120, 'UTF-8' ) : '',
                                ] );
                            }

                            $response = $this->tpre_try_with_pool(
                                function() use ( $source_language, $target_language, $group_chunk, $formality, $group_context ) {
                                    return $this->tpre_send_request_with_optional_context( $source_language, $target_language, $group_chunk, $formality, $group_context );
                                }
                            );

                            $this->tpre_merge_translated_chunk( $response, $group_chunk, $translated_strings, $source_language, $target_language );

                            if ( $this->machine_translator_logger->quota_exceeded() ) {
                                tpre_deepl_router_log( 'error', __( 'DeepL quota_exceeded 提前停止后续 chunk', 'langrouter-for-translatepress' ), [
                                    'target_language' => $target_language_code,
                                ] );
                                break 2;
                            }
                        }
                    }

                    tpre_deepl_router_log( 'debug', __( 'DeepL translate_array 结束', 'langrouter-for-translatepress' ), [
                        'target_language'   => $target_language_code,
                        'translated_count'  => is_array( $translated_strings ) ? count( $translated_strings ) : 0,
                    ] );
                    return $translated_strings;
                }

                public function test_request() {
                    tpre_deepl_router_log( 'debug', __( 'DeepL test_request 开始', 'langrouter-for-translatepress' ), [] );
                    if ( empty( $this->tpre_get_pool_entries() ) && ! $this->tpre_get_fallback_entry() ) {
                        return parent::test_request();
                    }

                    $response = $this->tpre_try_with_pool(
                        function() {
                            return parent::send_request( 'en', 'es', [ 'Where are you from ?' ], 'less' );
                        }
                    );

                    $final_response = $response ? $response : parent::test_request();
                    tpre_deepl_router_log( 'debug', __( 'DeepL test_request 返回', 'langrouter-for-translatepress' ), [
                        'code' => is_array( $final_response ) ? (int) ( $final_response['response']['code'] ?? 0 ) : 0,
                        'body' => is_array( $final_response ) ? wp_strip_all_tags( (string) ( $final_response['body'] ?? '' ) ) : '',
                    ] );
                    return $final_response;
                }

                public function get_supported_languages() {
                    if ( empty( $this->tpre_get_pool_entries() ) && ! $this->tpre_get_fallback_entry() ) {
                        return parent::get_supported_languages();
                    }

                    $response = $this->tpre_try_with_pool(
                        function() {
                            return wp_remote_post(
                                $this->get_api_url() . '/languages',
                                [
                                    'method'  => 'POST',
                                    'body'    => [ 'type' => 'target' ],
                                    'timeout' => 45,
                                    'headers' => [
                                        'Referer'       => $this->get_referer(),
                                        'Authorization' => 'DeepL-Auth-Key ' . $this->get_api_key(),
                                    ],
                                ]
                            );
                        }
                    );

                    if ( is_array( $response ) && ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
                        $data                = json_decode( wp_remote_retrieve_body( $response ) );
                        $supported_languages = [];
                        if ( is_array( $data ) ) {
                            foreach ( $data as $entry ) {
                                if ( empty( $entry->language ) ) {
                                    continue;
                                }

                                $code = strtolower( trim( str_replace( '_', '-', (string) $entry->language ) ) );
                                if ( '' === $code ) {
                                    continue;
                                }

                                $supported_languages[] = $code;
                                if ( false !== strpos( $code, '-' ) ) {
                                    $base = strtok( $code, '-' );
                                    if ( is_string( $base ) && '' !== $base ) {
                                        $supported_languages[] = $base;
                                    }
                                }
                            }
                        }
                        $supported_languages = array_values( array_unique( $supported_languages ) );
                        return apply_filters( 'trp_deepl_supported_languages', $supported_languages ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- TranslatePress compatibility filter.
                    }

                    return [];
                }

                public function check_formality() {
                    if ( empty( $this->tpre_get_pool_entries() ) && ! $this->tpre_get_fallback_entry() ) {
                        return parent::check_formality();
                    }

                    $response = $this->tpre_try_with_pool(
                        function() {
                            return wp_remote_post(
                                $this->get_api_url() . '/languages',
                                [
                                    'method'  => 'POST',
                                    'body'    => 'type=target',
                                    'timeout' => 45,
                                    'headers' => [
                                        'Referer'       => $this->get_referer(),
                                        'Authorization' => 'DeepL-Auth-Key ' . $this->get_api_key(),
                                    ],
                                ]
                            );
                        }
                    );

                    if ( ! is_array( $response ) || is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
                        return parent::check_formality();
                    }

                    $all_languages         = $this->trp_languages->get_wp_languages();
                    $supported_languages   = json_decode( wp_remote_retrieve_body( $response ) );
                    $portuguese_variations = [ (object) [ 'language' => 'pt', 'name' => 'Portuguese', 'supports_formality' => true ] ];
                    $supported_languages   = array_merge( is_array( $supported_languages ) ? $supported_languages : [], $portuguese_variations );
                    $language_iso_codes    = [];
                    $formality_supported_languages = [];

                    foreach ( $all_languages as $language ) {
                        $language_iso_codes[ $language['language'] ] = reset( $language['iso'] );
                    }

                    $exceptions_map = [ 'EN-GB' => 'en_GB', 'EN-US' => 'en_US' ];
                    foreach ( $supported_languages as $supported_language ) {
                        if ( array_key_exists( $supported_language->language, $exceptions_map ) ) {
                            $formality_supported_languages[ $exceptions_map[ $supported_language->language ] ] = $supported_language->supports_formality ? 'true' : 'false';
                            continue;
                        }

                        $matched_languages = array_keys( $language_iso_codes, strtolower( $supported_language->language ) );
                        if ( $matched_languages ) {
                            foreach ( $matched_languages as $matched_language ) {
                                $formality_supported_languages[ $matched_language ] = $supported_language->supports_formality ? 'true' : 'false';
                            }
                        }
                    }

                    return apply_filters( 'trp_deepl_formality_languages', $formality_supported_languages ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- TranslatePress compatibility filter.
                }
            }
        }

        return class_exists( 'TPRE_DeepL_Key_Pool_Machine_Translator', false );
    }
}
