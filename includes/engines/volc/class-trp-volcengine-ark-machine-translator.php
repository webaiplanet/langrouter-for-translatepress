<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/trait-volc-chat-rules.php';
// phpcs:disable WordPress.WP.AlternativeFunctions -- Intentional use of cURL/cURL multi for provider-specific parallel request handling.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- TranslatePress/Volcengine compatibility filters.

class TPRE_Volcengine_Ark_Machine_Translator extends TRP_Machine_Translator {
    use TPRE_Volc_Chat_Rules_Trait;

    protected $correct_api_key = null;
    protected $last_request_mode = '';
    protected $last_chat_validation = array();
    protected $last_response_status = '';
    protected $last_response_error_message = '';
    protected static $request_local_result_cache = array();
    protected $accounts_pool_cache = null;
    protected $accounts_pool_validation_error = '';


    const BILLING_SYNC_OK = 'ok';
    const BILLING_SYNC_OK_ZERO = 'ok_zero';
    const BILLING_SYNC_ERROR = 'error';
    const BILLING_SYNC_RATE_LIMITED = 'rate_limited';

    const STATUS_OK = 'ok';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_BLOCKED = 'blocked';
    const STATUS_RETRY = 'retry';
    const STATUS_LIMIT_REACHED = 'limit_reached';

    protected function save_debug( $data ) {
        return;
    }

    protected function log_debug( $message, array $context = array() ) {
        if ( class_exists( 'TPRE_Logger' ) && method_exists( 'TPRE_Logger', 'quick_debug' ) ) {
            TPRE_Logger::quick_debug( $message, $context );
        }
    }

    protected function log_error( $message, array $context = array() ) {
        if ( class_exists( 'TPRE_Logger' ) && method_exists( 'TPRE_Logger', 'quick_error' ) ) {
            TPRE_Logger::quick_error( $message, $context );
        }
    }

    public function verify_request_parameters( $target_language_code, $source_language_code = null ) {
        $target_language_code = is_string( $target_language_code ) ? trim( $target_language_code ) : '';
        $source_language_code = is_string( $source_language_code ) ? trim( $source_language_code ) : '';

        if ( '' === $target_language_code || '' === $source_language_code ) {
            return false;
        }

        $mapped_source = isset( $this->machine_translation_codes[ $source_language_code ] ) ? $this->machine_translation_codes[ $source_language_code ] : $source_language_code;
        $mapped_target = isset( $this->machine_translation_codes[ $target_language_code ] ) ? $this->machine_translation_codes[ $target_language_code ] : $target_language_code;

        $normalized_source = $this->normalize_translation_language_code( $mapped_source );
        if ( '' === $normalized_source ) {
            $normalized_source = $this->normalize_translation_language_code( $source_language_code );
        }

        $normalized_target = $this->normalize_translation_language_code( $mapped_target );
        if ( '' === $normalized_target ) {
            $normalized_target = $this->normalize_translation_language_code( $target_language_code );
        }

        if ( '' !== $normalized_source && '' !== $normalized_target ) {
            return true;
        }

        foreach ( $this->get_accounts_pool() as $account ) {
            if ( isset( $account['mode'] ) && 'chat' === (string) $account['mode'] ) {
                return '' !== trim( (string) $mapped_source ) && '' !== trim( (string) $mapped_target );
            }
        }

        return false;
    }

    protected function get_account_log_context( $account ) {
        if ( false === $account || ! is_array( $account ) ) {
            return array( 'account_available' => false );
        }

        $runtime_usage = isset( $account['index'] ) ? $this->get_runtime_usage_today( (int) $account['index'] ) : array();

        return array(
            'account_available'      => true,
            'account_index'          => isset( $account['index'] ) ? (int) $account['index'] : -1,
            'endpoint_id'            => isset( $account['endpoint_id'] ) ? (string) $account['endpoint_id'] : '',
            'mode'                   => isset( $account['mode'] ) ? (string) $account['mode'] : 'translation',
            'chat_model'             => isset( $account['chat_model'] ) ? (string) $account['chat_model'] : '',
            'billing_id'             => isset( $account['billing_id'] ) ? (string) $account['billing_id'] : '',
            'runtime_id'             => isset( $account['runtime_id'] ) ? (string) $account['runtime_id'] : '',
            'safety_threshold'       => isset( $account['safety_threshold'] ) ? (int) $account['safety_threshold'] : 0,
            'runtime_total_tokens'   => isset( $runtime_usage['total_tokens'] ) ? (int) $runtime_usage['total_tokens'] : 0,
            'runtime_request_count'  => isset( $runtime_usage['requests'] ) ? (int) $runtime_usage['requests'] : 0,
            'runtime_error_message'  => isset( $runtime_usage['error_message'] ) ? (string) $runtime_usage['error_message'] : '',
        );
    }

    protected function summarize_response_shape( $response ) {
        if ( is_wp_error( $response ) ) {
            return array(
                'is_wp_error'  => true,
                'error_code'   => $response->get_error_code(),
                'error_message'=> $response->get_error_message(),
            );
        }

        $body_text = (string) wp_remote_retrieve_body( $response );
        $decoded   = json_decode( $body_text, true );
        $texts     = array();

        if ( is_array( $decoded ) ) {
            $this->collect_output_text_candidates( $decoded, $texts );
        }

        return array(
            'is_wp_error'        => false,
            'http_code'          => (int) wp_remote_retrieve_response_code( $response ),
            'body_len'           => strlen( $body_text ),
            'decoded_type'       => is_array( $decoded ) ? 'array' : gettype( $decoded ),
            'top_level_keys'     => is_array( $decoded ) ? array_slice( array_keys( $decoded ), 0, 12 ) : array(),
            'output_text_count'  => count( $texts ),
            'usage_present'      => is_array( $decoded ) && isset( $decoded['usage'] ) && is_array( $decoded['usage'] ),
            'api_error_code'     => is_array( $decoded ) && isset( $decoded['error']['code'] ) ? (string) $decoded['error']['code'] : '',
            'api_error_message'  => is_array( $decoded ) && isset( $decoded['error']['message'] ) ? (string) $decoded['error']['message'] : '',
        );
    }



    protected function set_last_request_mode( $mode ) {
        $this->last_request_mode = is_string( $mode ) ? $mode : '';
    }

    protected function get_last_request_mode() {
        return is_string( $this->last_request_mode ) ? $this->last_request_mode : '';
    }

    protected function get_last_response_status() {
        return is_string( $this->last_response_status ) ? $this->last_response_status : '';
    }

    protected function get_last_response_error_message() {
        return is_string( $this->last_response_error_message ) ? $this->last_response_error_message : '';
    }

    protected function reset_last_response_meta() {
        $this->last_response_status = '';
        $this->last_response_error_message = '';
    }


    protected function build_request_local_cache_key( $source_language, $target_language, $string ) {
        return md5( strtolower( trim( (string) $source_language ) ) . '|' . strtolower( trim( (string) $target_language ) ) . '|' . (string) $string );
    }

    protected function get_request_local_result( $source_language, $target_language, $string ) {
        $cache_key = $this->build_request_local_cache_key( $source_language, $target_language, $string );
        return array_key_exists( $cache_key, self::$request_local_result_cache ) ? self::$request_local_result_cache[ $cache_key ] : false;
    }

    protected function set_request_local_result( $source_language, $target_language, $string, $translated_text ) {
        $cache_key = $this->build_request_local_cache_key( $source_language, $target_language, $string );
        self::$request_local_result_cache[ $cache_key ] = $translated_text;
    }

    protected function has_explicit_translation_accounts() {
        foreach ( $this->get_accounts_pool() as $pool_account ) {
            if ( isset( $pool_account['mode'] ) && 'translation' === (string) $pool_account['mode'] ) {
                return true;
            }
        }

        return false;
    }

    protected function has_multiple_pool_accounts() {
        return count( $this->get_accounts_pool() ) > 1;
    }

    protected function is_translation_endpoint_candidate( $account, $source_language = '', $target_language = '' ) {
        if ( ! is_array( $account ) ) {
            return false;
        }

        $mode = isset( $account['mode'] ) ? (string) $account['mode'] : 'translation';

        if ( $this->has_multiple_pool_accounts() ) {
            return 'translation' === $mode;
        }

        if ( 'translation' === $mode ) {
            return true;
        }

        return false;
    }

    protected function is_chat_endpoint_candidate( $account ) {
        if ( ! is_array( $account ) ) {
            return false;
        }

        $mode = isset( $account['mode'] ) ? (string) $account['mode'] : 'translation';

        if ( $this->has_multiple_pool_accounts() ) {
            return 'chat' === $mode;
        }

        if ( 'translation' === $mode ) {
            return false;
        }

        return true;
    }

    protected function has_mixed_endpoint_types( $source_language = '', $target_language = '' ) {
        $has_translation = false;
        $has_chat        = false;

        foreach ( $this->get_accounts_pool() as $pool_account ) {
            if ( $this->is_translation_endpoint_candidate( $pool_account, $source_language, $target_language ) ) {
                $has_translation = true;
            }
            if ( $this->is_chat_endpoint_candidate( $pool_account ) ) {
                $has_chat = true;
            }
            if ( $has_translation && $has_chat ) {
                return true;
            }
        }

        return false;
    }

    protected function find_matching_account_index( $start_index, callable $matcher, $exclude_index = null ) {
        $pool = $this->get_accounts_pool();
        if ( empty( $pool ) ) {
            return false;
        }

        $count = count( $pool );
        for ( $offset = 0; $offset < $count; $offset++ ) {
            $index = ( $start_index + $offset ) % $count;
            if ( null !== $exclude_index && (int) $exclude_index === $index ) {
                continue;
            }
            if ( ! $this->is_account_available( $index ) ) {
                continue;
            }
            if ( $matcher( $pool[ $index ] ) ) {
                return $index;
            }
        }

        return false;
    }

    protected function get_preferred_account_index_for_request( $source_language, $target_language, $prefer_fallback = false, $exclude_index = null ) {
        $pool = $this->get_accounts_pool();
        if ( empty( $pool ) ) {
            return false;
        }

        $supports_translation = $this->are_languages_supported( $source_language, $target_language );
        $has_mixed            = $this->has_mixed_endpoint_types( $source_language, $target_language );
        $start_index          = max( 0, (int) $this->get_current_account_index() );

        if ( $has_mixed ) {
            if ( $supports_translation ) {
                $primary_matcher = function( $account ) use ( $source_language, $target_language ) {
                    return $this->is_translation_endpoint_candidate( $account, $source_language, $target_language );
                };
                $fallback_matcher = function( $account ) {
                    return $this->is_chat_endpoint_candidate( $account );
                };
            } else {
                $primary_matcher = function( $account ) {
                    return $this->is_chat_endpoint_candidate( $account );
                };
                $fallback_matcher = function( $account ) use ( $source_language, $target_language ) {
                    return $this->is_translation_endpoint_candidate( $account, $source_language, $target_language );
                };
            }

            $matcher = $prefer_fallback ? $fallback_matcher : $primary_matcher;
            $index   = $this->find_matching_account_index( $start_index, $matcher, $exclude_index );
            if ( false !== $index ) {
                return $index;
            }

            if ( ! $prefer_fallback ) {
                $primary_exists = false;
                foreach ( $pool as $pool_account ) {
                    if ( $primary_matcher( $pool_account ) ) {
                        $primary_exists = true;
                        break;
                    }
                }
                if ( ! $primary_exists ) {
                    $index = $this->find_matching_account_index( $start_index, $fallback_matcher, $exclude_index );
                    if ( false !== $index ) {
                        return $index;
                    }
                }
            }

            return false;
        }

        return $this->find_next_available_account_index( $start_index );
    }

    protected function prime_active_account_for_request( $source_language, $target_language ) {
        $index = $this->get_preferred_account_index_for_request( $source_language, $target_language, false, null );
        if ( false === $index ) {
            return false;
        }

        $this->set_current_account_index( $index );
        return $this->get_active_account( $index );
    }

    protected function get_retry_account_for_request( $source_language, $target_language, $current_account ) {
        if ( $this->is_volc_chat_fragile_language_pair( $source_language, $target_language ) ) {
            return false;
        }

        if ( ! is_array( $current_account ) || ! isset( $current_account['index'] ) ) {
            return false;
        }

        $exclude_index = (int) $current_account['index'];
        $start_index   = $exclude_index + 1;

        if ( $this->has_mixed_endpoint_types( $source_language, $target_language ) ) {
            $current_is_translation = $this->is_translation_endpoint_candidate( $current_account, $source_language, $target_language );
            $current_is_chat        = $this->is_chat_endpoint_candidate( $current_account );
            $supports_translation   = $this->are_languages_supported( $source_language, $target_language );

            if ( $current_is_translation ) {
                /*
                 * 优先 translation，但当 translation 接入点不可用/失败时，允许回退到 chat。
                 * 顺序：先继续找其它 translation 账号；找不到时，再落到 chat 账号。
                 */
                if ( $supports_translation ) {
                    $index = $this->find_matching_account_index( $start_index, function( $account ) use ( $source_language, $target_language ) {
                        return $this->is_translation_endpoint_candidate( $account, $source_language, $target_language );
                    }, $exclude_index );
                    if ( false !== $index ) {
                        $this->set_current_account_index( $index );
                        return $this->get_active_account( $index );
                    }
                }

                $index = $this->find_matching_account_index( $start_index, function( $account ) {
                    return $this->is_chat_endpoint_candidate( $account );
                }, $exclude_index );
                if ( false !== $index ) {
                    $this->set_current_account_index( $index );
                    return $this->get_active_account( $index );
                }
            }

            if ( $current_is_chat && $supports_translation ) {
                $index = $this->find_matching_account_index( $start_index, function( $account ) use ( $source_language, $target_language ) {
                    return $this->is_translation_endpoint_candidate( $account, $source_language, $target_language );
                }, $exclude_index );
                if ( false !== $index ) {
                    $this->set_current_account_index( $index );
                    return $this->get_active_account( $index );
                }
            }

            if ( ! $supports_translation || $current_is_chat ) {
                $index = $this->find_matching_account_index( $start_index, function( $account ) {
                    return $this->is_chat_endpoint_candidate( $account );
                }, $exclude_index );
                if ( false !== $index ) {
                    $this->set_current_account_index( $index );
                    return $this->get_active_account( $index );
                }
            }

            return false;
        }

        if ( $this->switch_to_next_account( $exclude_index ) ) {
            return $this->get_active_account();
        }

        return false;
    }

    protected function is_chat_only_request( $source_language, $target_language, $account = null ) {
        if ( null === $account ) {
            $account = $this->get_active_account();
        }

        if ( false === $account || ! is_array( $account ) ) {
            return false;
        }

        $request_modes = $this->get_account_request_modes( $account, $source_language, $target_language );
        return is_array( $request_modes ) && 1 === count( $request_modes ) && 'chat' === reset( $request_modes );
    }

    protected function normalize_language_for_chat_speed( $language_code ) {
        $language_code = strtolower( str_replace( '-', '_', (string) $language_code ) );
        if ( '' === $language_code ) {
            return '';
        }

        $parts = explode( '_', $language_code );
        return (string) reset( $parts );
    }

    protected function get_chat_speed_tier( $source_language, $target_language ) {
        $source = $this->normalize_language_for_chat_speed( $source_language );
        $target = $this->normalize_language_for_chat_speed( $target_language );

        $balanced_targets = array( 'ca', 'cs', 'cy', 'et', 'eu', 'ga', 'gl', 'hr', 'hu', 'is', 'lt', 'lv', 'mt', 'ro', 'sk', 'sl', 'sq' );
        $cautious_targets = array( 'am', 'az', 'be', 'bg', 'bs', 'hy', 'ka', 'kk', 'mk', 'sr', 'uk' );
        $zh_fast_targets  = array( 'de', 'en', 'es', 'fr', 'it', 'ja', 'ko', 'nl', 'pl', 'pt', 'ru', 'tr' );

        $tier = 'fast';
        if ( in_array( $target, $cautious_targets, true ) ) {
            $tier = 'cautious';
        } elseif ( in_array( $target, $balanced_targets, true ) ) {
            $tier = 'balanced';
        }

        if ( 'zh' === $source && ! in_array( $target, $zh_fast_targets, true ) ) {
            $tier = 'cautious';
        }

        return (string) apply_filters( 'tpre_volcengine_ark_chat_speed_tier', $tier, $source_language, $target_language, $this->settings );
    }

    protected function get_default_chat_chunk_size( $source_language, $target_language ) {
        if ( $this->is_volc_chat_fragile_language_pair( $source_language, $target_language ) ) {
            return 1;
        }

        $tier = $this->get_chat_speed_tier( $source_language, $target_language );
        if ( 'cautious' === $tier ) {
            $default = 4;
        } elseif ( 'balanced' === $tier ) {
            $default = 4;
        } else {
            $default = 6;
        }
        return max( 1, (int) apply_filters( 'tpre_volcengine_ark_chat_chunk_size', $default, $source_language, $target_language, $this->settings ) );
    }

    protected function get_default_chat_parallel_limit( $source_language, $target_language ) {
        if ( $this->is_volc_chat_fragile_language_pair( $source_language, $target_language ) ) {
            return 1;
        }

        $default = 1;
        return max( 1, (int) apply_filters( 'tpre_volcengine_ark_chat_parallel_limit', $default, $source_language, $target_language, $this->settings ) );
    }

    protected function get_chat_only_request_timeout( $source_language, $target_language, $quality_retry = false, $chunk_size = 0 ) {
        $chunk_size = max( 1, (int) $chunk_size );

        if ( $this->is_volc_chat_fragile_language_pair( $source_language, $target_language ) ) {
            $default = $quality_retry ? 40 : ( $chunk_size > 1 ? 36 : 30 );
            return max( 30, (int) apply_filters( 'tpre_volcengine_ark_chat_request_timeout', $default, $source_language, $target_language, (bool) $quality_retry, $chunk_size, $this->settings ) );
        }
        $tier       = $this->get_chat_speed_tier( $source_language, $target_language );
        if ( $quality_retry ) {
            $default = 20;
        } elseif ( 'cautious' === $tier ) {
            $default = $chunk_size >= 4 ? 24 : 20;
        } elseif ( 'balanced' === $tier ) {
            $default = $chunk_size >= 4 ? 22 : 18;
        } else {
            $default = $chunk_size >= 4 ? 20 : 18;
        }
        return max( 18, (int) apply_filters( 'tpre_volcengine_ark_chat_request_timeout', $default, $source_language, $target_language, (bool) $quality_retry, $chunk_size, $this->settings ) );
    }

    protected function get_effective_request_timeout( $source_language, $target_language, $account = null, $quality_retry = false, $chunk_size = 0 ) {
        if ( $this->is_chat_only_request( $source_language, $target_language, $account ) ) {
            return $this->get_chat_only_request_timeout( $source_language, $target_language, $quality_retry, $chunk_size );
        }

        return $this->get_request_timeout();
    }

    protected function should_skip_chat_quality_retry() {
        $status = $this->get_last_response_status();
        $error  = strtolower( $this->get_last_response_error_message() );

        if ( self::STATUS_RETRY === $status ) {
            return true;
        }

        foreach ( array( 'curl error 28', 'timed out', 'timeout', 'operation timed out', 'http_request_failed', 'serveroverloaded' ) as $needle ) {
            if ( false !== strpos( $error, $needle ) ) {
                return true;
            }
        }

        return false;
    }


    protected function get_mt_settings() {
        return isset( $this->settings['trp_machine_translation_settings'] ) && is_array( $this->settings['trp_machine_translation_settings'] )
            ? $this->settings['trp_machine_translation_settings']
            : array();
    }

    protected function get_accounts_pool() {
        if ( null !== $this->accounts_pool_cache ) {
            return is_array( $this->accounts_pool_cache ) ? $this->accounts_pool_cache : array();
        }

        $this->set_accounts_pool_validation_error( '' );

        $settings = $this->get_mt_settings();
        $raw      = isset( $settings['volcengine-ark-accounts'] ) ? (string) $settings['volcengine-ark-accounts'] : '';
        if ( '' === trim( $raw ) ) {
            $this->accounts_pool_cache = array();
            return array();
        }

        $lines                = preg_split( '/
|
|
/', trim( $raw ) );
        $pool                = array();
        $valid_line_count    = 0;
        $implicit_mode_count = 0;
        $invalid_mode_count  = 0;

        foreach ( $lines as $line ) {
            $line = trim( (string) $line );
            if ( '' === $line ) {
                continue;
            }

            $parts = array_map( 'trim', explode( '|', $line ) );
            if ( count( $parts ) < 2 || '' === $parts[0] || '' === $parts[1] ) {
                continue;
            }

            $valid_line_count++;

            $endpoint_id    = $parts[0];
            $api_key        = $parts[1];
            $ak             = isset( $parts[2] ) ? $parts[2] : '';
            $sk             = isset( $parts[3] ) ? $parts[3] : '';
            $threshold      = isset( $parts[4] ) ? max( 0, (int) $parts[4] ) : ( isset( $parts[2] ) && count( $parts ) === 3 ? max( 0, (int) $parts[2] ) : 0 );
            $mode           = 'translation';
            $chat_model     = '';
            $mode_explicit  = false;

            if ( isset( $parts[5] ) ) {
                $part5 = strtolower( trim( (string) $parts[5] ) );
                if ( in_array( $part5, array( 'auto', 'translation', 'chat' ), true ) ) {
                    $mode          = $part5;
                    $mode_explicit = true;
                    if ( isset( $parts[6] ) ) {
                        $chat_model = trim( (string) $parts[6] );
                    }
                } else {
                    $chat_model = trim( (string) $parts[5] );
                    if ( isset( $parts[6] ) ) {
                        $part6 = strtolower( trim( (string) $parts[6] ) );
                        if ( in_array( $part6, array( 'auto', 'translation', 'chat' ), true ) ) {
                            $mode          = $part6;
                            $mode_explicit = true;
                        }
                    }
                }
            }

            $runtime_id         = sha1( $endpoint_id . '|' . $api_key );
            $billing_id         = ( '' !== $ak && '' !== $sk ) ? sha1( $ak . '|' . $sk ) : '';
            $official_usage_key = ( '' !== $ak && '' !== $sk ) ? sha1( $ak . '|' . $sk . '|' . $endpoint_id ) : sha1( 'endpoint|' . $endpoint_id );

            $pool[] = array(
                'index'              => 0,
                'endpoint_id'        => $endpoint_id,
                'model'              => $endpoint_id,
                'chat_model'         => $chat_model,
                'api_key'            => $api_key,
                'access_key'         => $ak,
                'secret_key'         => $sk,
                'safety_threshold'   => $threshold,
                'mode'               => $mode,
                'mode_explicit'      => $mode_explicit,
                'runtime_id'         => $runtime_id,
                'billing_id'         => $billing_id,
                'official_usage_key' => $official_usage_key,
                'has_billing_access' => ( '' !== $billing_id ),
            );
        }
        $pool = array_values( $pool );
        foreach ( $pool as $i => $account ) {
            $pool[ $i ]['index'] = $i;
        }

        $this->accounts_pool_cache = $pool;
        return $pool;
    }


    protected function set_accounts_pool_validation_error( $message ) {
        $this->accounts_pool_validation_error = is_string( $message ) ? $message : '';
    }

    protected function get_accounts_pool_validation_error() {
        return is_string( $this->accounts_pool_validation_error ) ? $this->accounts_pool_validation_error : '';
    }

    protected function get_account_safety_threshold( $account_index ) {
        $pool = $this->get_accounts_pool();
        if ( isset( $pool[ $account_index ]['safety_threshold'] ) ) {
            return max( 0, (int) $pool[ $account_index ]['safety_threshold'] );
        }

        return 0;
    }

    protected function get_usage_date_key() {
        return gmdate( 'Y-m-d', current_time( 'timestamp' ) );
    }

    protected function get_runtime_stats() {
        $stats = get_option( 'tpre_volcengine_ark_runtime_usage_stats', array() );
        return is_array( $stats ) ? $stats : array();
    }

    protected function update_runtime_stats( $stats ) {
        update_option( 'tpre_volcengine_ark_runtime_usage_stats', $stats, false );
    }

    protected function get_official_usage_cache() {
        $cache = get_option( 'tpre_volcengine_ark_official_usage_cache', array() );
        return is_array( $cache ) ? $cache : array();
    }

    protected function update_official_usage_cache( $cache ) {
        update_option( 'tpre_volcengine_ark_official_usage_cache', $cache, false );
    }

    protected function get_persistent_account_statuses() {
        $statuses = get_option( 'tpre_volcengine_ark_account_status_cache', array() );
        return is_array( $statuses ) ? $statuses : array();
    }

    protected function update_persistent_account_statuses( $statuses ) {
        update_option( 'tpre_volcengine_ark_account_status_cache', $statuses, false );
    }

    protected function get_persistent_account_status( $account_index ) {
        $account = $this->get_account_by_index( $account_index );
        if ( false === $account || empty( $account['runtime_id'] ) ) {
            return array();
        }

        $runtime_id = (string) $account['runtime_id'];
        $statuses   = $this->get_persistent_account_statuses();
        if ( ! isset( $statuses[ $runtime_id ] ) || ! is_array( $statuses[ $runtime_id ] ) ) {
            return array();
        }

        return $statuses[ $runtime_id ];
    }

    protected function persist_account_status( $account_index, $status, $error_message = '', $touched_at = '' ) {
        $account = $this->get_account_by_index( $account_index );
        if ( false === $account || empty( $account['runtime_id'] ) ) {
            return;
        }

        $runtime_id = (string) $account['runtime_id'];
        $statuses   = $this->get_persistent_account_statuses();
        $current    = isset( $statuses[ $runtime_id ] ) && is_array( $statuses[ $runtime_id ] ) ? $statuses[ $runtime_id ] : array();

        $current['status'] = (string) $status;
        if ( '' !== $error_message ) {
            $current['last_error'] = (string) $error_message;
        } elseif ( self::STATUS_OK === (string) $status ) {
            $current['last_error'] = '';
        }
        $current['updated_at'] = '' !== $touched_at ? (string) $touched_at : current_time( 'mysql' );

        $statuses[ $runtime_id ] = $current;
        $this->update_persistent_account_statuses( $statuses );
    }

    protected function get_recent_runtime_status_snapshot( $account_index ) {
        $account = $this->get_account_by_index( $account_index );
        if ( false === $account || empty( $account['runtime_id'] ) ) {
            return array();
        }

        $runtime_id = (string) $account['runtime_id'];
        $stats      = $this->get_runtime_stats();
        if ( empty( $stats ) || ! is_array( $stats ) ) {
            return array();
        }

        $dates = array_keys( $stats );
        rsort( $dates, SORT_STRING );
        foreach ( $dates as $date ) {
            if ( empty( $stats[ $date ][ $runtime_id ] ) || ! is_array( $stats[ $date ][ $runtime_id ] ) ) {
                continue;
            }

            $snapshot = $stats[ $date ][ $runtime_id ];
            if ( empty( $snapshot['status'] ) && empty( $snapshot['last_error'] ) && empty( $snapshot['last_used'] ) ) {
                continue;
            }

            return $snapshot;
        }

        return array();
    }

    protected function get_account_by_index( $account_index ) {
        $pool = $this->get_accounts_pool();
        return isset( $pool[ $account_index ] ) ? $pool[ $account_index ] : false;
    }

    protected function get_runtime_usage_today( $account_index ) {
        $account = $this->get_account_by_index( $account_index );
        $stats   = $this->get_runtime_stats();
        $date    = $this->get_usage_date_key();
        $default = array(
            'requests'          => 0,
            'prompt_tokens'     => 0,
            'completion_tokens' => 0,
            'total_tokens'      => 0,
            'last_used'         => '',
            'last_error'        => '',
            'status'            => self::STATUS_OK,
        );

        if ( false === $account ) {
            return $default;
        }

        $persisted = $this->get_persistent_account_status( $account_index );
        if ( empty( $persisted ) ) {
            $persisted = $this->get_recent_runtime_status_snapshot( $account_index );
        }
        if ( ! empty( $persisted ) ) {
            if ( ! empty( $persisted['status'] ) ) {
                $default['status'] = (string) $persisted['status'];
            }
            if ( ! empty( $persisted['last_error'] ) ) {
                $default['last_error'] = (string) $persisted['last_error'];
            }
            if ( ! empty( $persisted['updated_at'] ) ) {
                $default['last_used'] = (string) $persisted['updated_at'];
            } elseif ( ! empty( $persisted['last_used'] ) ) {
                $default['last_used'] = (string) $persisted['last_used'];
            }
        }

        $runtime_id = $account['runtime_id'];
        if ( ! isset( $stats[ $date ][ $runtime_id ] ) || ! is_array( $stats[ $date ][ $runtime_id ] ) ) {
            return $default;
        }

        return wp_parse_args( $stats[ $date ][ $runtime_id ], $default );
    }

    protected function infer_status_from_error( $error_message ) {
        $error_message = (string) $error_message;
        if ( '' === $error_message ) {
            return self::STATUS_OK;
        }

        if ( false !== stripos( $error_message, 'tpre_volcengine_ark_unsupported_language' ) || false !== mb_stripos( $error_message, '不在火山翻译模型支持范围内', 0, 'UTF-8' ) ) {
            return self::STATUS_OK;
        }

        if ( $this->message_indicates_overdue( $error_message ) ) {
            return self::STATUS_OVERDUE;
        }

        if ( $this->message_indicates_blocked( $error_message ) ) {
            return self::STATUS_BLOCKED;
        }

        $retryable_keywords = array( 'cURL error', 'Could not resolve host', 'Connection refused', 'timed out', 'timeout', 'SSL', 'certificate', 'network', 'rate limit', 'quota', '限流', '配额', '额度', '用量', 'ServerOverloaded', 'overload', 'overloaded', '429', 'Too Many Requests', 'please retry later', 'temporarily unavailable' );
        foreach ( $retryable_keywords as $keyword ) {
            if ( false !== stripos( $error_message, $keyword ) || false !== mb_stripos( $error_message, $keyword, 0, 'UTF-8' ) ) {
                return self::STATUS_RETRY;
            }
        }

        return self::STATUS_OK;
    }

    protected function message_indicates_overdue( $message ) {
        $message = (string) $message;
        if ( '' === $message ) {
            return false;
        }

        return false !== stripos( $message, 'AccountOverdueError' )
            || false !== stripos( $message, 'overdue' )
            || false !== mb_stripos( $message, '欠费', 0, 'UTF-8' );
    }

    protected function message_indicates_blocked( $message ) {
        $message = (string) $message;
        if ( '' === $message ) {
            return false;
        }

        $blocking_keywords = array( 'AuthenticationError', 'Unauthorized', 'invalid api key', 'invalid_api_key', 'invalid api', 'api key', 'Forbidden', 'invalid signature', 'signature mismatch' );
        foreach ( $blocking_keywords as $keyword ) {
            if ( false !== stripos( $message, $keyword ) ) {
                return true;
            }
        }

        return false;
    }

    protected function official_usage_indicates_overdue( $usage ) {
        return is_array( $usage ) && $this->message_indicates_overdue( isset( $usage['error_message'] ) ? (string) $usage['error_message'] : '' );
    }

    protected function official_usage_indicates_blocked( $usage ) {
        return is_array( $usage ) && $this->message_indicates_blocked( isset( $usage['error_message'] ) ? (string) $usage['error_message'] : '' );
    }

    protected function get_status_label( $status ) {
        switch ( (string) $status ) {
            case self::STATUS_OVERDUE:
                return __( '欠费', 'langrouter-for-translatepress' );
            case self::STATUS_BLOCKED:
                return __( '异常', 'langrouter-for-translatepress' );
            case self::STATUS_RETRY:
                return __( '待重试', 'langrouter-for-translatepress' );
            case self::STATUS_LIMIT_REACHED:
                return __( '本地阈值已达', 'langrouter-for-translatepress' );
            case self::STATUS_OK:
            default:
                return __( '正常', 'langrouter-for-translatepress' );
        }
    }

    protected function record_account_usage( $account_index, $usage = array(), $error = '' ) {
        $account = $this->get_account_by_index( $account_index );
        if ( false === $account ) {
            return;
        }

        $stats      = $this->get_runtime_stats();
        $date       = $this->get_usage_date_key();
        $runtime_id = $account['runtime_id'];

        if ( ! isset( $stats[ $date ] ) || ! is_array( $stats[ $date ] ) ) {
            $stats[ $date ] = array();
        }

        $current = $this->get_runtime_usage_today( $account_index );
        $current['requests']          = (int) $current['requests'] + 1;
        $current['prompt_tokens']     = (int) $current['prompt_tokens'] + (int) ( isset( $usage['prompt_tokens'] ) ? $usage['prompt_tokens'] : 0 );
        $current['completion_tokens'] = (int) $current['completion_tokens'] + (int) ( isset( $usage['completion_tokens'] ) ? $usage['completion_tokens'] : 0 );
        $current['total_tokens']      = (int) $current['total_tokens'] + (int) ( isset( $usage['total_tokens'] ) ? $usage['total_tokens'] : 0 );
        $current['last_used']         = current_time( 'mysql' );

        if ( '' !== $error ) {
            $current['last_error'] = (string) $error;
            $current['status']     = $this->infer_status_from_error( $error );
        } else {
            $limit = $this->get_account_safety_threshold( $account_index );
            if ( $limit > 0 && (int) $current['total_tokens'] >= $limit ) {
                $current['status'] = self::STATUS_LIMIT_REACHED;
            } elseif ( self::STATUS_OVERDUE !== $current['status'] && self::STATUS_BLOCKED !== $current['status'] ) {
                $current['status'] = self::STATUS_OK;
            }
        }

        $stats[ $date ][ $runtime_id ] = $current;
        $this->update_runtime_stats( $stats );
    }

    protected function set_account_status( $account_index, $status, $error_message = '' ) {
        $account = $this->get_account_by_index( $account_index );
        if ( false === $account ) {
            return;
        }

        $stats      = $this->get_runtime_stats();
        $date       = $this->get_usage_date_key();
        $runtime_id = $account['runtime_id'];

        if ( ! isset( $stats[ $date ] ) || ! is_array( $stats[ $date ] ) ) {
            $stats[ $date ] = array();
        }

        $current = $this->get_runtime_usage_today( $account_index );
        $current['status'] = (string) $status;
        if ( '' !== $error_message ) {
            $current['last_error'] = (string) $error_message;
        } elseif ( self::STATUS_OK === (string) $status ) {
            $current['last_error'] = '';
        }
        if ( '' === $current['last_used'] ) {
            $current['last_used'] = current_time( 'mysql' );
        }
        $stats[ $date ][ $runtime_id ] = $current;
        $this->update_runtime_stats( $stats );
        $this->persist_account_status( $account_index, $status, isset( $current['last_error'] ) ? (string) $current['last_error'] : '', (string) $current['last_used'] );
    }

    protected function get_usage_cache_ttl() {
        return (int) apply_filters( 'tpre_volcengine_ark_usage_cache_ttl', 180 );
    }

    protected function get_zero_usage_cache_ttl() {
        return (int) apply_filters( 'tpre_volcengine_ark_zero_usage_cache_ttl', 300 );
    }

    protected function get_rate_limit_backoff() {
        return (int) apply_filters( 'tpre_volcengine_ark_rate_limit_backoff', 300 );
    }

    protected function get_error_backoff() {
        return (int) apply_filters( 'tpre_volcengine_ark_error_backoff', 180 );
    }

    protected function get_usage_force_refresh_window() {
        return (int) apply_filters( 'tpre_volcengine_ark_usage_force_refresh_window', 100000 );
    }

    protected function get_status_probe_cache() {
        $cache = get_option( 'tpre_volcengine_ark_status_probe_cache', array() );
        return is_array( $cache ) ? $cache : array();
    }

    protected function update_status_probe_cache( $cache ) {
        update_option( 'tpre_volcengine_ark_status_probe_cache', $cache, false );
    }

    protected function get_status_probe_ttl() {
        return (int) apply_filters( 'tpre_volcengine_ark_status_probe_ttl', 300 );
    }

    protected function probe_account_runtime_status( $account, $force_refresh = false ) {
        if ( ! is_array( $account ) || empty( $account['runtime_id'] ) || empty( $account['api_key'] ) ) {
            return false;
        }

        $runtime_id = (string) $account['runtime_id'];
        $date       = $this->get_usage_date_key();
        $cache      = $this->get_status_probe_cache();

        if ( ! $force_refresh && isset( $cache[ $date ][ $runtime_id ]['checked_at_ts'] ) ) {
            $cached = $cache[ $date ][ $runtime_id ];
            $age    = time() - (int) $cached['checked_at_ts'];
            if ( $age >= 0 && $age < $this->get_status_probe_ttl() ) {
                return $cached;
            }
        }

        $request_modes = $this->get_account_request_modes( $account, 'zh', 'en' );
        $last_error    = '';
        $last_status   = self::STATUS_OK;
        $result        = false;

        foreach ( $request_modes as $mode ) {
            $body = $this->build_request_body( 'zh', 'en', array( '你好' ), false, $mode );
            if ( false === $body ) {
                continue;
            }

            $body['model'] = $this->get_request_model_for_mode( $account, $mode );
            $request_url   = ( 'chat' === $mode ) ? $this->get_chat_completions_url() : $this->get_base_url() . '/responses';
            $response      = wp_remote_post(
                $request_url,
                array(
                    'timeout' => 12,
                    'headers' => array(
                        'Content-Type'  => 'application/json',
                        'Authorization' => 'Bearer ' . $account['api_key'],
                    ),
                    'body'    => wp_json_encode( $body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
                )
            );

            if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
                $result = array(
                    'status'        => self::STATUS_OK,
                    'error_message' => '',
                    'checked_at'    => current_time( 'mysql' ),
                    'checked_at_ts' => time(),
                );
                break;
            }

            $last_error  = $this->get_response_error_message( $response );
            $last_status = $this->infer_status_from_error( $last_error );
            $mode_index  = array_search( $mode, $request_modes, true );
            $has_next    = ( false !== $mode_index && $mode_index < ( count( $request_modes ) - 1 ) );

            if ( $has_next && ! in_array( $last_status, array( self::STATUS_OVERDUE, self::STATUS_BLOCKED ), true ) ) {
                continue;
            }

            $result = array(
                'status'        => $last_status,
                'error_message' => $last_error,
                'checked_at'    => current_time( 'mysql' ),
                'checked_at_ts' => time(),
            );
            break;
        }

        if ( false === $result ) {
            $result = array(
                'status'        => $last_status,
                'error_message' => $last_error,
                'checked_at'    => current_time( 'mysql' ),
                'checked_at_ts' => time(),
            );
        }

        if ( ! isset( $cache[ $date ] ) || ! is_array( $cache[ $date ] ) ) {
            $cache[ $date ] = array();
        }
        $cache[ $date ][ $runtime_id ] = $result;
        $this->update_status_probe_cache( $cache );

        if ( isset( $account['index'] ) ) {
            $account_index  = (int) $account['index'];
            $runtime_usage  = $this->get_runtime_usage_today( $account_index );
            $runtime_status = isset( $runtime_usage['status'] ) ? (string) $runtime_usage['status'] : self::STATUS_OK;

            if ( in_array( (string) $result['status'], array( self::STATUS_OVERDUE, self::STATUS_BLOCKED ), true ) ) {
                $this->set_account_status( $account_index, (string) $result['status'], (string) $result['error_message'] );
            } elseif ( self::STATUS_OK === (string) $result['status'] && in_array( $runtime_status, array( self::STATUS_OVERDUE, self::STATUS_BLOCKED ), true ) ) {
                $this->set_account_status( $account_index, self::STATUS_OK, '' );
            }
        }

        return $result;
    }

    protected function official_usage_is_usable( $usage ) {
        return is_array( $usage ) && in_array( (string) ( isset( $usage['sync_status'] ) ? $usage['sync_status'] : '' ), array( self::BILLING_SYNC_OK, self::BILLING_SYNC_OK_ZERO, self::BILLING_SYNC_RATE_LIMITED ), true );
    }

    protected function get_local_billing_group_total_tokens( $billing_id ) {
        if ( '' === (string) $billing_id ) {
            return 0;
        }

        $total = 0;
        foreach ( $this->get_accounts_pool() as $account ) {
            if ( $billing_id !== $account['billing_id'] ) {
                continue;
            }
            $usage  = $this->get_runtime_usage_today( (int) $account['index'] );
            $total += (int) $usage['total_tokens'];
        }

        return $total;
    }

    protected function get_billing_group_runtime_status( $billing_id ) {
        if ( '' === (string) $billing_id ) {
            return self::STATUS_OK;
        }

        $group_status = self::STATUS_OK;
        foreach ( $this->get_accounts_pool() as $account ) {
            if ( $billing_id !== $account['billing_id'] ) {
                continue;
            }

            $usage  = $this->get_runtime_usage_today( (int) $account['index'] );
            $status = isset( $usage['status'] ) ? (string) $usage['status'] : self::STATUS_OK;

            if ( self::STATUS_OVERDUE === $status ) {
                return self::STATUS_OVERDUE;
            }

            if ( self::STATUS_BLOCKED === $status ) {
                $group_status = self::STATUS_BLOCKED;
            }
        }

        return $group_status;
    }

    protected function get_runtime_status_label( $account_index, $account, $usage ) {
        $status = isset( $usage['status'] ) ? (string) $usage['status'] : self::STATUS_OK;

        if ( self::STATUS_OVERDUE === $status ) {
            return __( '欠费', 'langrouter-for-translatepress' );
        }
        if ( self::STATUS_BLOCKED === $status ) {
            return __( '异常', 'langrouter-for-translatepress' );
        }

        if ( ! empty( $account['official_usage_key'] ) ) {
            $official_usage = $this->get_billing_usage_today_by_account( $account );
            if ( is_array( $official_usage ) ) {
                if ( $this->official_usage_indicates_overdue( $official_usage ) ) {
                    return __( '欠费', 'langrouter-for-translatepress' );
                }
                if ( $this->official_usage_indicates_blocked( $official_usage ) ) {
                    return __( '异常', 'langrouter-for-translatepress' );
                }

                $sync_status = (string) $official_usage['sync_status'];
                if ( self::BILLING_SYNC_ERROR === $sync_status ) {
                    return __( '官方同步失败，按本地控量', 'langrouter-for-translatepress' );
                }
                if ( self::BILLING_SYNC_RATE_LIMITED === $sync_status ) {
                    return __( '官方限流，按本地控量', 'langrouter-for-translatepress' );
                }

                if ( $this->official_usage_is_usable( $official_usage ) && $this->is_account_limit_reached( $account_index ) ) {
                    return __( '已达安全阈值', 'langrouter-for-translatepress' );
                }

                if ( self::BILLING_SYNC_OK_ZERO === $sync_status ) {
                    $local_total = (int) $usage['total_tokens'];
                    return $local_total > 0
                        ? __( '等待官方同步', 'langrouter-for-translatepress' )
                        : __( '正常（今日官方为0）', 'langrouter-for-translatepress' );
                }

                return __( '正常（按官方）', 'langrouter-for-translatepress' );
            }
        }

        if ( $this->is_account_limit_reached( $account_index ) ) {
            return __( '已达安全阈值', 'langrouter-for-translatepress' );
        }

        return $this->get_status_label( $status );
    }

    protected function get_control_plane_host() {
        return 'ark.cn-beijing.volcengineapi.com';
    }

    protected function get_control_plane_region() {
        return 'cn-beijing';
    }

    protected function build_signature_key( $secret_key, $date_stamp, $region_name, $service_name ) {
        $k_date    = hash_hmac( 'sha256', $date_stamp, $secret_key, true );
        $k_region  = hash_hmac( 'sha256', $region_name, $k_date, true );
        $k_service = hash_hmac( 'sha256', $service_name, $k_region, true );
        return hash_hmac( 'sha256', 'request', $k_service, true );
    }

    protected function build_control_plane_signed_request( $action, $body, $access_key, $secret_key ) {
        $host            = $this->get_control_plane_host();
        $region          = $this->get_control_plane_region();
        $service         = 'ark';
        $method          = 'POST';
        $canonical_uri   = '/';
        $query           = array( 'Action' => $action, 'Version' => '2024-01-01' );
        ksort( $query );
        $body_json       = wp_json_encode( $body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
        $amz_date        = gmdate( 'Ymd\THis\Z' );
        $short_date      = gmdate( 'Ymd' );
        $payload_hash    = hash( 'sha256', $body_json );
        $canonical_query = http_build_query( $query, '', '&', PHP_QUERY_RFC3986 );
        $canonical_headers = array(
            'host'             => $host,
            'x-content-sha256' => $payload_hash,
            'x-date'           => $amz_date,
        );
        ksort( $canonical_headers );
        $signed_headers = implode( ';', array_keys( $canonical_headers ) );
        $canonical_headers_string = '';
        foreach ( $canonical_headers as $name => $value ) {
            $canonical_headers_string .= $name . ':' . trim( (string) $value ) . "
";
        }
        $canonical_request = implode( "
", array( $method, $canonical_uri, $canonical_query, $canonical_headers_string, $signed_headers, $payload_hash ) );
        $credential_scope  = $short_date . '/' . $region . '/' . $service . '/request';
        $string_to_sign    = implode( "
", array( 'HMAC-SHA256', $amz_date, $credential_scope, hash( 'sha256', $canonical_request ) ) );
        $signing_key       = $this->build_signature_key( $secret_key, $short_date, $region, $service );
        $signature         = hash_hmac( 'sha256', $string_to_sign, $signing_key );
        $authorization     = sprintf( 'HMAC-SHA256 Credential=%s/%s, SignedHeaders=%s, Signature=%s', $access_key, $credential_scope, $signed_headers, $signature );
        return array(
            'url'     => 'https://' . $host . '/?' . $canonical_query,
            'headers' => array(
                'Host'             => $host,
                'Content-Type'     => 'application/json; charset=UTF-8',
                'X-Date'           => $amz_date,
                'X-Content-Sha256' => $payload_hash,
                'Authorization'    => $authorization,
            ),
            'body'    => $body_json,
        );
    }

    protected function parse_usage_result_row( $decoded, $target_date ) {
        if ( ! is_array( $decoded ) || empty( $decoded['Result']['Fields'] ) || ! isset( $decoded['Result']['Data'] ) ) {
            return false;
        }
        $fields = array();
        foreach ( $decoded['Result']['Fields'] as $field ) {
            if ( isset( $field['Name'] ) ) {
                $fields[] = (string) $field['Name'];
            }
        }
        if ( empty( $fields ) ) {
            return false;
        }
        foreach ( $decoded['Result']['Data'] as $row ) {
            if ( ! is_array( $row ) ) {
                continue;
            }
            $assoc = array();
            foreach ( $fields as $i => $name ) {
                $assoc[ $name ] = isset( $row[ $i ] ) ? $row[ $i ] : null;
            }
            if ( isset( $assoc['Day'] ) && (string) $assoc['Day'] === (string) $target_date ) {
                return array(
                    'stat_day'       => (string) $assoc['Day'],
                    'input_tokens'   => isset( $assoc['InputTokens'] ) ? (int) $assoc['InputTokens'] : 0,
                    'output_tokens'  => isset( $assoc['OutputTokens'] ) ? (int) $assoc['OutputTokens'] : 0,
                    'total_tokens'   => isset( $assoc['TotalTokens'] ) ? (int) $assoc['TotalTokens'] : 0,
                    'req_count'      => isset( $assoc['ReqCnt'] ) ? (int) $assoc['ReqCnt'] : 0,
                    'model_endpoint' => isset( $assoc['ModelEndpoint'] ) ? (string) $assoc['ModelEndpoint'] : '',
                    'endpoint_name'  => isset( $assoc['EndpointName'] ) ? (string) $assoc['EndpointName'] : '',
                );
            }
        }
        return false;
    }

    protected function is_empty_usage_response( $decoded ) {
        if ( ! is_array( $decoded ) ) {
            return false;
        }

        if ( ! empty( $decoded['ResponseMetadata']['Error'] ) ) {
            return false;
        }

        if ( ! isset( $decoded['Result'] ) || ! is_array( $decoded['Result'] ) ) {
            return false;
        }

        if ( isset( $decoded['Result']['DataCount'] ) && 0 === (int) $decoded['Result']['DataCount'] ) {
            return true;
        }

        if ( isset( $decoded['Result']['Data'] ) && is_array( $decoded['Result']['Data'] ) && empty( $decoded['Result']['Data'] ) ) {
            return true;
        }

        return false;
    }

    protected function is_rate_limited_usage_response( $decoded ) {
        if ( ! is_array( $decoded ) || empty( $decoded['ResponseMetadata']['Error'] ) || ! is_array( $decoded['ResponseMetadata']['Error'] ) ) {
            return false;
        }

        $error = $decoded['ResponseMetadata']['Error'];
        $code  = isset( $error['Code'] ) ? (string) $error['Code'] : '';
        $coden = isset( $error['CodeN'] ) ? (int) $error['CodeN'] : 0;
        return ( 'AccountFlowLimitExceeded' === $code || 100027 === $coden );
    }

    protected function get_usage_sync_status_ttl( $sync_status ) {
        switch ( (string) $sync_status ) {
            case self::BILLING_SYNC_OK_ZERO:
                return $this->get_zero_usage_cache_ttl();
            case self::BILLING_SYNC_RATE_LIMITED:
                return $this->get_rate_limit_backoff();
            case self::BILLING_SYNC_ERROR:
                return $this->get_error_backoff();
            case self::BILLING_SYNC_OK:
            default:
                return $this->get_usage_cache_ttl();
        }
    }

    protected function format_usage_sync_message( $decoded, $fallback = '' ) {
        if ( is_array( $decoded ) && ! empty( $decoded['ResponseMetadata']['Error'] ) ) {
            $error   = $decoded['ResponseMetadata']['Error'];
            $parts   = array();
            $code    = isset( $error['Code'] ) ? (string) $error['Code'] : '';
            $message = isset( $error['Message'] ) ? (string) $error['Message'] : '';
            if ( '' !== $code ) {
                $parts[] = $code;
            }
            if ( '' !== $message ) {
                $parts[] = $message;
            }
            if ( ! empty( $parts ) ) {
                return implode( '：', $parts );
            }
        }
        return '' !== $fallback ? $fallback : '官方用量同步失败。';
    }

    protected function fetch_official_usage_for_account( $account ) {
        if ( empty( $account['access_key'] ) || empty( $account['secret_key'] ) || empty( $account['endpoint_id'] ) ) {
            return false;
        }
        $today   = $this->get_usage_date_key();
        $request = $this->build_control_plane_signed_request(
            'GetInferenceUsage',
            array(
                'QueryInterval'    => 'Day',
                'StartTime'        => $today,
                'EndTime'          => $today,
                'ShowWindowDetail' => true,
                'Conditions'       => array(
                    array(
                        'Key'    => 'ModelEndpoint',
                        'Values' => array( (string) $account['endpoint_id'] ),
                    ),
                ),
            ),
            $account['access_key'],
            $account['secret_key']
        );
        $response = wp_remote_post( $request['url'], array( 'timeout' => max( 15, $this->get_request_timeout() ), 'headers' => $request['headers'], 'body' => $request['body'] ) );
        if ( is_wp_error( $response ) ) {
            return array( 'billing_id' => $account['billing_id'], 'endpoint_id' => $account['endpoint_id'], 'official_usage_key' => $account['official_usage_key'], 'stat_day' => $today, 'input_tokens' => 0, 'output_tokens' => 0, 'total_tokens' => 0, 'req_count' => 0, 'synced_at' => current_time( 'mysql' ), 'sync_status' => self::BILLING_SYNC_ERROR, 'error_message' => $response->get_error_message() );
        }
        $decoded = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
            $sync_status = $this->is_rate_limited_usage_response( $decoded ) ? self::BILLING_SYNC_RATE_LIMITED : self::BILLING_SYNC_ERROR;
            return array( 'billing_id' => $account['billing_id'], 'endpoint_id' => $account['endpoint_id'], 'official_usage_key' => $account['official_usage_key'], 'stat_day' => $today, 'input_tokens' => 0, 'output_tokens' => 0, 'total_tokens' => 0, 'req_count' => 0, 'synced_at' => current_time( 'mysql' ), 'sync_status' => $sync_status, 'error_message' => $this->format_usage_sync_message( $decoded, is_array( $decoded ) ? wp_json_encode( $decoded ) : wp_remote_retrieve_body( $response ) ) );
        }
        if ( is_array( $decoded ) && ! empty( $decoded['ResponseMetadata']['Error'] ) ) {
            $sync_status = $this->is_rate_limited_usage_response( $decoded ) ? self::BILLING_SYNC_RATE_LIMITED : self::BILLING_SYNC_ERROR;
            return array( 'billing_id' => $account['billing_id'], 'endpoint_id' => $account['endpoint_id'], 'official_usage_key' => $account['official_usage_key'], 'stat_day' => $today, 'input_tokens' => 0, 'output_tokens' => 0, 'total_tokens' => 0, 'req_count' => 0, 'synced_at' => current_time( 'mysql' ), 'sync_status' => $sync_status, 'error_message' => $this->format_usage_sync_message( $decoded ) );
        }
        $parsed = $this->parse_usage_result_row( $decoded, $today );
        if ( false === $parsed ) {
            if ( $this->is_empty_usage_response( $decoded ) ) {
                return array(
                    'billing_id'    => $account['billing_id'],
                    'endpoint_id'   => $account['endpoint_id'],
                    'official_usage_key' => $account['official_usage_key'],
                    'stat_day'      => $today,
                    'input_tokens'  => 0,
                    'output_tokens' => 0,
                    'total_tokens'  => 0,
                    'req_count'     => 0,
                    'synced_at'     => current_time( 'mysql' ),
                    'sync_status'   => self::BILLING_SYNC_OK_ZERO,
                    'error_message' => __( '今日暂无官方用量数据，已按0处理。', 'langrouter-for-translatepress' ),
                );
            }

            return array( 'billing_id' => $account['billing_id'], 'endpoint_id' => $account['endpoint_id'], 'official_usage_key' => $account['official_usage_key'], 'stat_day' => $today, 'input_tokens' => 0, 'output_tokens' => 0, 'total_tokens' => 0, 'req_count' => 0, 'synced_at' => current_time( 'mysql' ), 'sync_status' => self::BILLING_SYNC_ERROR, 'error_message' => __( '官方用量返回结构无法解析。', 'langrouter-for-translatepress' ),);
        }
        return array_merge( $parsed, array( 'billing_id' => $account['billing_id'], 'endpoint_id' => $account['endpoint_id'], 'official_usage_key' => $account['official_usage_key'], 'synced_at' => current_time( 'mysql' ), 'sync_status' => self::BILLING_SYNC_OK, 'error_message' => '' ) );
    }

    protected function get_billing_usage_today_by_account( $account, $force_refresh = false ) {
        $usage_key = isset( $account['official_usage_key'] ) ? (string) $account['official_usage_key'] : '';
        if ( '' === $usage_key ) {
            return false;
        }
        $cache = $this->get_official_usage_cache();
        $date  = $this->get_usage_date_key();

        if ( ! $force_refresh && isset( $cache[ $date ][ $usage_key ]['synced_at_ts'] ) ) {
            $cached = $cache[ $date ][ $usage_key ];
            $age    = time() - (int) $cached['synced_at_ts'];
            $ttl    = $this->get_usage_sync_status_ttl( isset( $cached['sync_status'] ) ? $cached['sync_status'] : self::BILLING_SYNC_OK );
            if ( $age >= 0 && $age < $ttl ) {
                return $cached;
            }
        }
        $fresh = $this->fetch_official_usage_for_account( $account );
        if ( false === $fresh ) {
            return false;
        }
        $fresh['synced_at_ts'] = time();
        if ( ! isset( $cache[ $date ] ) || ! is_array( $cache[ $date ] ) ) {
            $cache[ $date ] = array();
        }
        $cache[ $date ][ $usage_key ] = $fresh;
        $this->update_official_usage_cache( $cache );
        return $fresh;
    }

    protected function get_billing_threshold_for_id( $billing_id ) {
        $thresholds = array();
        foreach ( $this->get_accounts_pool() as $account ) {
            if ( $billing_id === $account['billing_id'] && ! empty( $account['safety_threshold'] ) ) {
                $thresholds[] = (int) $account['safety_threshold'];
            }
        }
        return empty( $thresholds ) ? 0 : min( $thresholds );
    }

    protected function get_billing_group_runtime_count( $billing_id ) {
        $count = 0;
        foreach ( $this->get_accounts_pool() as $account ) {
            if ( $billing_id === $account['billing_id'] ) {
                $count++;
            }
        }
        return $count;
    }

    protected function get_short_hash( $value ) {
        return '' === (string) $value ? '—' : substr( (string) $value, 0, 10 );
    }

    public function get_runtime_usage_summary_rows() {
        return array();
    }

    public function get_billing_usage_summary_rows() {
        $rows = array();
        $current_account = $this->get_account_by_index( $this->get_current_account_index() );
        $current_runtime_id = ( is_array( $current_account ) && ! empty( $current_account['runtime_id'] ) ) ? (string) $current_account['runtime_id'] : '';

        foreach ( $this->get_accounts_pool() as $account ) {
            $usage         = $this->get_billing_usage_today_by_account( $account );
            $runtime_usage = $this->get_runtime_usage_today( (int) $account['index'] );
            $threshold     = ! empty( $account['safety_threshold'] ) ? (int) $account['safety_threshold'] : 0;
            $local_total   = (int) $runtime_usage['total_tokens'];
            $total         = is_array( $usage ) ? (int) $usage['total_tokens'] : 0;
            $status        = __( '未同步', 'langrouter-for-translatepress' );
            $synced_at     = '—';
            $error_detail  = is_array( $usage ) && ! empty( $usage['error_message'] ) ? (string) $usage['error_message'] : '';

            if ( self::STATUS_OVERDUE === (string) ( $runtime_usage['status'] ?? self::STATUS_OK ) ) {
                $status = __( '欠费', 'langrouter-for-translatepress' );
            } elseif ( self::STATUS_BLOCKED === (string) ( $runtime_usage['status'] ?? self::STATUS_OK ) ) {
                $status = __( '异常', 'langrouter-for-translatepress' );
            }

            if ( is_array( $usage ) ) {
                $synced_at   = ! empty( $usage['synced_at'] ) ? $usage['synced_at'] : '—';
                $sync_status = (string) $usage['sync_status'];
                if ( $this->official_usage_indicates_overdue( $usage ) ) {
                    $status = __( '欠费', 'langrouter-for-translatepress' );
                } elseif ( $this->official_usage_indicates_blocked( $usage ) ) {
                    $status = __( '异常', 'langrouter-for-translatepress' );
                } elseif ( self::BILLING_SYNC_ERROR === $sync_status ) {
                    $status = $threshold > 0 && $local_total >= $threshold
                        ? __( '达到本地阈值（官方同步失败）', 'langrouter-for-translatepress' )
                        : __( '官方同步失败，按本地控量', 'langrouter-for-translatepress' );
                } elseif ( $threshold > 0 && max( $total, $local_total ) >= $threshold ) {
                    $status = __( '达到安全阈值', 'langrouter-for-translatepress' );
                } elseif ( self::BILLING_SYNC_RATE_LIMITED === $sync_status ) {
                    $status = __( '官方限流，稍后重试', 'langrouter-for-translatepress' );
                } elseif ( self::BILLING_SYNC_OK_ZERO === $sync_status ) {
                    if ( $local_total > 0 ) {
                        $status = __( '等待官方同步', 'langrouter-for-translatepress' );
                    } else {
                        $probe = $this->probe_account_runtime_status( $account );
                        if ( is_array( $probe ) && self::STATUS_OVERDUE === (string) ( $probe['status'] ?? self::STATUS_OK ) ) {
                            $status = __( '欠费', 'langrouter-for-translatepress' );
                            if ( ! empty( $probe['error_message'] ) ) {
                                $error_detail = (string) $probe['error_message'];
                            }
                        } elseif ( is_array( $probe ) && self::STATUS_BLOCKED === (string) ( $probe['status'] ?? self::STATUS_OK ) ) {
                            $status = __( '异常', 'langrouter-for-translatepress' );
                            if ( ! empty( $probe['error_message'] ) ) {
                                $error_detail = (string) $probe['error_message'];
                            }
                        } else {
                            $status = __( '今日无官方用量', 'langrouter-for-translatepress' );
                        }
                    }
                } else {
                    $status = __( '正常', 'langrouter-for-translatepress' );
                }
            }

            $rows[] = array(
                'endpoint_id'      => (string) $account['endpoint_id'],
                'endpoint_short'   => $this->get_short_hash( $account['endpoint_id'] ),
                'endpoint_name'    => is_array( $usage ) && ! empty( $usage['endpoint_name'] ) ? (string) $usage['endpoint_name'] : '',
                'mode'             => ! empty( $account['mode'] ) ? (string) $account['mode'] : 'translation',
                'is_current'       => ( '' !== $current_runtime_id && $current_runtime_id === (string) $account['runtime_id'] ),
                'stat_day'         => is_array( $usage ) && ! empty( $usage['stat_day'] ) ? $usage['stat_day'] : $this->get_usage_date_key(),
                'input_tokens'     => is_array( $usage ) ? (int) $usage['input_tokens'] : 0,
                'output_tokens'    => is_array( $usage ) ? (int) $usage['output_tokens'] : 0,
                'total_tokens'     => $total,
                'req_count'        => is_array( $usage ) ? (int) $usage['req_count'] : 0,
                'local_total'      => $local_total,
                'safety_threshold' => $threshold,
                'status'           => $status,
                'error_detail'     => $error_detail,
                'synced_at'        => $synced_at,
            );
        }
        return $rows;
    }

    public function get_billing_usage_diagnostic_rows() {
        $rows = array();
        foreach ( $this->get_billing_usage_summary_rows() as $row ) {
            if ( empty( $row['error_detail'] ) ) {
                continue;
            }
            $rows[] = array(
                'billing_id_short' => ! empty( $row['endpoint_short'] ) ? $row['endpoint_short'] : '—',
                'message'          => $row['error_detail'],
            );
        }
        return $rows;
    }

    public function force_refresh_billing_usage_summary_rows() {
        foreach ( $this->get_accounts_pool() as $account ) {
            $usage         = $this->get_billing_usage_today_by_account( $account, true );
            $runtime_usage = $this->get_runtime_usage_today( (int) $account['index'] );
            $local_total   = (int) $runtime_usage['total_tokens'];

            if ( is_array( $usage ) && self::BILLING_SYNC_OK_ZERO === (string) $usage['sync_status'] && 0 === $local_total ) {
                $this->probe_account_runtime_status( $account, true );
            }
        }

        return $this->get_billing_usage_summary_rows();
    }

    protected function get_current_account_index() {
        return max( 0, (int) get_option( 'tpre_volcengine_ark_account_index', 0 ) );
    }

    protected function set_current_account_index( $index ) {
        update_option( 'tpre_volcengine_ark_account_index', max( 0, (int) $index ), false );
    }


    protected function is_account_limit_reached( $account_index ) {
        $account = $this->get_account_by_index( $account_index );
        if ( false === $account ) {
            return false;
        }

        $limit = $this->get_account_safety_threshold( $account_index );
        if ( $limit <= 0 ) {
            return false;
        }

        $official_usage = $this->get_billing_usage_today_by_account( $account );
        $local_usage    = $this->get_runtime_usage_today( $account_index );
        $local_total    = (int) $local_usage['total_tokens'];

        if ( is_array( $official_usage ) ) {
            $sync_status     = (string) $official_usage['sync_status'];
            $official_total  = (int) $official_usage['total_tokens'];
            $effective_total = max( $official_total, $local_total );

            if ( self::BILLING_SYNC_ERROR === $sync_status ) {
                return $local_total >= $limit;
            }

            if ( in_array( $sync_status, array( self::BILLING_SYNC_OK, self::BILLING_SYNC_OK_ZERO, self::BILLING_SYNC_RATE_LIMITED ), true ) ) {
                $refresh_window = $this->get_usage_force_refresh_window();
                if ( $limit > 0 && ( $limit - $effective_total ) <= $refresh_window ) {
                    $fresh_usage = $this->get_billing_usage_today_by_account( $account, true );
                    if ( is_array( $fresh_usage ) ) {
                        if ( self::BILLING_SYNC_ERROR === (string) $fresh_usage['sync_status'] ) {
                            return $local_total >= $limit;
                        }
                        $official_total  = (int) $fresh_usage['total_tokens'];
                        $effective_total = max( $official_total, $local_total );
                    }
                }

                return $effective_total >= $limit;
            }
        }

        return $local_total >= $limit;
    }

    protected function is_account_blocked( $account_index ) {
        $usage    = $this->get_runtime_usage_today( $account_index );
        $status   = isset( $usage['status'] ) ? (string) $usage['status'] : self::STATUS_OK;
        $account  = $this->get_account_by_index( $account_index );

        if ( false !== $account && ! empty( $account['billing_id'] ) ) {
            $official_usage = $this->get_billing_usage_today_by_account( $account );
            if ( is_array( $official_usage ) ) {
                $this->reconcile_runtime_status_with_official_usage( $account_index, $official_usage );
                if ( $this->official_usage_indicates_overdue( $official_usage ) ) {
                    return true;
                }
                if ( $this->official_usage_is_usable( $official_usage ) ) {
                    $usage  = $this->get_runtime_usage_today( $account_index );
                    $status = isset( $usage['status'] ) ? (string) $usage['status'] : self::STATUS_OK;
                }
            }
        }

        return in_array( $status, array( self::STATUS_OVERDUE, self::STATUS_BLOCKED ), true );
    }

    protected function get_status_reprobe_backoff( $status ) {
        switch ( (string) $status ) {
            case self::STATUS_OVERDUE:
                return (int) apply_filters( 'tpre_volcengine_ark_overdue_reprobe_backoff', 900 );
            case self::STATUS_BLOCKED:
                return (int) apply_filters( 'tpre_volcengine_ark_blocked_reprobe_backoff', 300 );
            case self::STATUS_RETRY:
                return (int) apply_filters( 'tpre_volcengine_ark_retry_reprobe_backoff', $this->get_error_backoff() );
            case self::STATUS_LIMIT_REACHED:
                return PHP_INT_MAX;
            case self::STATUS_OK:
            default:
                return 0;
        }
    }

    protected function get_usage_last_touched_timestamp( $usage ) {
        if ( ! is_array( $usage ) || empty( $usage['last_used'] ) ) {
            return 0;
        }

        $timestamp = strtotime( (string) $usage['last_used'] );
        return false === $timestamp ? 0 : (int) $timestamp;
    }

    protected function reconcile_runtime_status_with_official_usage( $account_index, $official_usage ) {
        $account = $this->get_account_by_index( $account_index );
        if ( false === $account || empty( $account['billing_id'] ) || ! is_array( $official_usage ) ) {
            return;
        }

        if ( $this->official_usage_indicates_overdue( $official_usage ) ) {
            $this->set_account_status( $account_index, self::STATUS_OVERDUE, isset( $official_usage['error_message'] ) ? (string) $official_usage['error_message'] : '' );
            return;
        }

        if ( $this->official_usage_indicates_blocked( $official_usage ) ) {
            $this->set_account_status( $account_index, self::STATUS_BLOCKED, isset( $official_usage['error_message'] ) ? (string) $official_usage['error_message'] : '' );
            return;
        }

        if ( ! $this->official_usage_is_usable( $official_usage ) ) {
            return;
        }

        $usage  = $this->get_runtime_usage_today( $account_index );
        $status = isset( $usage['status'] ) ? (string) $usage['status'] : self::STATUS_OK;
        if ( in_array( $status, array( self::STATUS_OVERDUE, self::STATUS_RETRY, self::STATUS_OK ), true ) ) {
            $next_status = $this->is_account_limit_reached( $account_index ) ? self::STATUS_LIMIT_REACHED : self::STATUS_OK;
            $this->set_account_status( $account_index, $next_status );
        }
    }

    protected function can_reprobe_unavailable_account( $account_index ) {
        if ( $this->is_account_limit_reached( $account_index ) ) {
            return false;
        }

        $usage      = $this->get_runtime_usage_today( $account_index );
        $status     = isset( $usage['status'] ) ? (string) $usage['status'] : self::STATUS_OK;
        $backoff    = $this->get_status_reprobe_backoff( $status );
        $last_touch = $this->get_usage_last_touched_timestamp( $usage );

        if ( $backoff <= 0 ) {
            return true;
        }

        if ( $last_touch <= 0 ) {
            return true;
        }

        return ( time() - $last_touch ) >= $backoff;
    }

    protected function is_account_strictly_available( $account_index ) {
        if ( $this->is_account_limit_reached( $account_index ) ) {
            return false;
        }

        return ! $this->is_account_blocked( $account_index );
    }

    protected function is_account_available( $account_index ) {
        return $this->is_account_strictly_available( $account_index ) || $this->can_reprobe_unavailable_account( $account_index );
    }

    protected function find_next_available_account_index( $start_index = 0 ) {
        $pool = $this->get_accounts_pool();
        if ( empty( $pool ) ) {
            return false;
        }

        $count = count( $pool );
        for ( $offset = 0; $offset < $count; $offset++ ) {
            $index = ( $start_index + $offset ) % $count;
            if ( $this->is_account_strictly_available( $index ) ) {
                return $index;
            }
        }

        for ( $offset = 0; $offset < $count; $offset++ ) {
            $index = ( $start_index + $offset ) % $count;
            if ( $this->can_reprobe_unavailable_account( $index ) ) {
                return $index;
            }
        }

        return false;
    }

    protected function get_active_account( $force_index = null ) {
        $pool = $this->get_accounts_pool();
        if ( empty( $pool ) ) {
            return false;
        }

        $index = null === $force_index ? $this->get_current_account_index() : (int) $force_index;
        $index = $this->find_next_available_account_index( $index );
        if ( false === $index || ! isset( $pool[ $index ] ) ) {
            return false;
        }

        $this->set_current_account_index( $index );
        $account = $pool[ $index ];
        return $account;
    }

    protected function switch_to_next_account( $current_index = null ) {
        $pool = $this->get_accounts_pool();
        if ( count( $pool ) <= 1 ) {
            return false;
        }

        $base_index = null === $current_index ? $this->get_current_account_index() : (int) $current_index;
        $next_index = $this->find_next_available_account_index( $base_index + 1 );
        if ( false === $next_index ) {
            return false;
        }
        $this->set_current_account_index( $next_index );
        return true;
    }

    public function get_api_key() {
        $account = $this->get_active_account();
        return ( false !== $account && ! empty( $account['api_key'] ) ) ? $account['api_key'] : false;
    }

    protected function get_base_url() {
        return 'https://ark.cn-beijing.volces.com/api/v3';
    }

    protected function get_chat_completions_url() {
        return $this->get_base_url() . '/chat/completions';
    }

    protected function get_request_model_for_mode( $account, $mode = 'translation' ) {
        if ( false === $account || ! is_array( $account ) ) {
            return '';
        }

        $mode = is_string( $mode ) && '' !== $mode ? $mode : 'translation';
        if ( 'chat' === $mode && ! empty( $account['chat_model'] ) ) {
            return trim( (string) $account['chat_model'] );
        }

        return ! empty( $account['endpoint_id'] ) ? trim( (string) $account['endpoint_id'] ) : '';
    }

    protected function get_model( $mode = 'translation' ) {
        return $this->get_request_model_for_mode( $this->get_active_account(), $mode );
    }

    protected function get_request_timeout() {
        return (int) apply_filters( 'tpre_volcengine_ark_request_timeout', 15 );
    }

    protected function get_parallel_requests_limit() {
        $settings           = $this->get_mt_settings();
        $global_limit       = isset( $settings['tpre_global_concurrency_limit'] ) ? max( 0, (int) $settings['tpre_global_concurrency_limit'] ) : 0;
        $engine_concurrency = isset( $settings['tpre_volc_concurrency'] ) ? (int) $settings['tpre_volc_concurrency'] : 0;

        if ( $engine_concurrency > 0 ) {
            return max( 1, $engine_concurrency );
        }

        if ( $global_limit > 0 ) {
            return max( 1, $global_limit );
        }

        return max( 1, (int) apply_filters( 'tpre_volcengine_ark_parallel_requests', 24 ) );
    }

    protected function can_use_curl_multi() {
        return function_exists( 'curl_multi_init' ) && function_exists( 'curl_init' );
    }

    protected function sanitize_utf8_text( $value ) {
        $value = (string) $value;
        if ( '' === $value ) {
            return '';
        }

        if ( function_exists( 'wp_check_invalid_utf8' ) ) {
            $value = wp_check_invalid_utf8( $value, true );
        }

        $value = preg_replace( '/^\xEF\xBB\xBF/u', '', $value );
        $value = str_replace( chr( 0 ), '', $value );
        $value = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value );

        return is_string( $value ) ? $value : '';
    }

    protected function get_cache_ttl() {
        return (int) apply_filters( 'tpre_volcengine_ark_cache_ttl', DAY_IN_SECONDS * 30 );
    }

    protected function get_supported_translation_language_codes() {
        return array( 'zh', 'en', 'ja', 'ko', 'de', 'fr', 'es', 'it', 'pt', 'ru', 'ar', 'cs', 'da', 'fi', 'hr', 'hu', 'id', 'ms', 'nl', 'pl', 'ro', 'sv', 'tr', 'uk' );
    }

    protected function is_traditional_chinese_language_code( $code ) {
        $code = is_string( $code ) ? trim( $code ) : '';
        if ( '' === $code ) {
            return false;
        }

        $normalized = strtolower( str_replace( '_', '-', $code ) );

        return in_array( $normalized, array( 'zh-hant', 'zh-tw', 'zh-hk', 'zh-mo' ), true );
    }

    protected function normalize_translation_language_code( $code ) {
        $code = is_string( $code ) ? trim( $code ) : '';
        if ( '' === $code ) {
            return '';
        }

        $normalized = str_replace( '_', '-', $code );
        $lower      = strtolower( $normalized );

        $exact_map = array(
            'zh_CN' => 'zh',
            'en_US' => 'en',
            'ja' => 'ja',
            'ko_KR' => 'ko',
            'de_DE' => 'de',
            'fr_FR' => 'fr',
            'es_ES' => 'es',
            'it_IT' => 'it',
            'pt_BR' => 'pt',
            'ru_RU' => 'ru',
            'ar' => 'ar',
            'cs_CZ' => 'cs',
            'da_DK' => 'da',
            'fi' => 'fi',
            'hr' => 'hr',
            'hu_HU' => 'hu',
            'id_ID' => 'id',
            'ms_MY' => 'ms',
            'nl_NL' => 'nl',
            'pl_PL' => 'pl',
            'ro_RO' => 'ro',
            'sv_SE' => 'sv',
            'tr_TR' => 'tr',
            'uk' => 'uk',
        );

        if ( isset( $exact_map[ $lower ] ) ) {
            return $exact_map[ $lower ];
        }

        $base = strtok( $lower, '-' );
        if ( 'zh' === $base ) {
            return 'zh';
        }

        foreach ( $this->get_supported_translation_language_codes() as $supported_code ) {
            if ( strtolower( $supported_code ) === $base ) {
                return $supported_code;
            }
        }

        return '';
    }


    protected function get_account_request_modes( $account, $source_language, $target_language ) {
        $account_mode = is_array( $account ) && ! empty( $account['mode'] ) ? (string) $account['mode'] : 'translation';

        if ( 'chat' === $account_mode ) {
            return array( 'chat' );
        }

        if ( 'auto' === $account_mode ) {
            return $this->are_languages_supported( $source_language, $target_language )
                ? array( 'translation' )
                : array( 'chat' );
        }

        return array( 'translation' );
    }

    protected function build_translation_request_body( $source_language, $target_language, $strings_array, $quality_retry = false ) {
        $source_language = $this->normalize_translation_language_code( $source_language );
        $target_language = $this->normalize_translation_language_code( $target_language );

        if ( '' === $source_language || '' === $target_language ) {
            return false;
        }

        if ( ! in_array( $source_language, $this->get_supported_translation_language_codes(), true ) || ! in_array( $target_language, $this->get_supported_translation_language_codes(), true ) ) {
            return false;
        }

        $content = array();
        foreach ( array_values( $strings_array ) as $value ) {
            $content[] = array(
                'type'                => 'input_text',
                'text'                => $this->sanitize_utf8_text( $value ),
                'translation_options' => array(
                    'source_language' => $source_language,
                    'target_language' => $target_language,
                ),
            );
        }

        $body = array(
            'model' => $this->get_model(),
            'input' => array(
                array(
                    'role'    => 'user',
                    'content' => $content,
                ),
            ),
        );

        if ( $quality_retry ) {
            $body['instructions'] = $this->get_quality_retry_instructions( $source_language, $target_language );
        }

        return $body;
    }


    protected function build_request_body( $source_language, $target_language, $strings_array, $quality_retry = false, $mode = 'translation' ) {
        if ( 'chat' === $mode ) {
            return $this->build_chat_request_body( $source_language, $target_language, $strings_array, $quality_retry );
        }

        return $this->build_translation_request_body( $source_language, $target_language, $strings_array, $quality_retry );
    }

    protected function get_quality_retry_instructions( $source_language, $target_language ) {
        return sprintf(
            'You are a machine translation engine. Translate the entire input from %1$s into %2$s completely. Return only the translated text in %2$s for each input item. Do not keep source-language sentences or clauses untranslated. Do not mix the source language with the target language except for proper nouns, brand names, product names, model names, code, URLs, emails, and HTML entities. Do not explain, summarize, add notes, or add quotation marks.',
            strtoupper( (string) $source_language ),
            strtoupper( (string) $target_language )
        );
    }

    protected function cleanup_translation_literal_newline_artifacts( $source_text, $translated_text, $request_mode = '' ) {
        $request_mode = is_string( $request_mode ) && '' !== $request_mode ? $request_mode : $this->get_last_request_mode();
        if ( 'translation' !== $request_mode ) {
            return is_string( $translated_text ) ? $translated_text : '';
        }

        $translated_text = is_string( $translated_text ) ? $translated_text : '';
        if ( '' === $translated_text || false === strpos( $translated_text, '\\' ) ) {
            return $translated_text;
        }

        $source_text = is_string( $source_text ) ? $source_text : '';
        if ( false !== strpos( $source_text, '\\n' ) || false !== strpos( $source_text, '\\r' ) ) {
            return $translated_text;
        }

        $safe_token_pattern = '(?:[A-Za-z0-9_.-]+\.[A-Za-z0-9]{1,8}\b|[A-Za-z_][A-Za-z0-9_]*\s*\(|(?:[A-Za-z0-9_.-]+/)+[A-Za-z0-9_.-]+|[A-Za-z]:\\[^\s]+|wp-content/[A-Za-z0-9_./-]+)';
        $cleaned = preg_replace( '/(?:(?:\\\\r\\\\n|\\\\n|\\\\r)+|(?:\\r\\n|\\n|\\r)+)\s*(?=' . $safe_token_pattern . ')/u', '', $translated_text );

        return is_string( $cleaned ) ? $cleaned : $translated_text;
    }

    protected function looks_like_pure_url_or_email( $text ) {
        $text = trim( (string) $text );
        if ( '' === $text ) {
            return false;
        }

        if ( preg_match( '#^https?://[^\s]+$#i', $text ) ) {
            return true;
        }

        if ( preg_match( '/^[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}$/i', $text ) ) {
            return true;
        }

        return false;
    }

    protected function looks_like_technical_identifier_only( $text ) {
        $text = trim( (string) $text );
        if ( '' === $text ) {
            return false;
        }

        $plain = preg_replace( '/\s+/u', ' ', $text );
        $len   = function_exists( 'mb_strlen' ) ? mb_strlen( $plain, 'UTF-8' ) : strlen( $plain );
        if ( $len > 80 ) {
            return false;
        }

        if ( preg_match( '/^[A-Za-z0-9_.:\-\/]+(?:\s*\([A-Za-z0-9_.:\-\/]+\))?$/', $plain ) ) {
            return true;
        }

        if ( preg_match( '/^[A-Za-z_][A-Za-z0-9_]*(?:\(\))?$/', $plain ) ) {
            return true;
        }

        return false;
    }

    protected function should_translate_inline_markup_mixed_text( $raw_value, $plain_value ) {
        $raw_value   = (string) $raw_value;
        $plain_value = trim( (string) $plain_value );

        if ( '' === trim( $raw_value ) || '' === $plain_value ) {
            return false;
        }

        $has_markup = false;
        if ( class_exists( 'TPRE_Translation_Safety_Utils' ) ) {
            $has_markup = TPRE_Translation_Safety_Utils::contains_raw_html_tags( $raw_value )
                || TPRE_Translation_Safety_Utils::contains_encoded_tag_entities( $raw_value );
        }

        if ( ! $has_markup ) {
            $has_markup = (bool) preg_match( '/<\/?[A-Za-z][^>]*>|&lt;\/?[A-Za-z][^&]{0,200}&gt;/i', $raw_value );
        }

        if ( ! $has_markup || ! preg_match( '/\p{L}/u', $plain_value ) ) {
            return false;
        }

        $plain_without_tokens = preg_replace( '/[A-Za-z0-9_:\/.\-]+(?:\([A-Za-z0-9_,:\/.\-\s]*\))?/u', ' ', $plain_value );
        $plain_without_tokens = trim( preg_replace( '/\s+/u', ' ', (string) $plain_without_tokens ) );

        if ( '' === $plain_without_tokens ) {
            return false;
        }

        if ( preg_match( '/[\x{3040}-\x{30FF}\x{3400}-\x{4DBF}\x{4E00}-\x{9FFF}\x{AC00}-\x{D7AF}]/u', $plain_without_tokens ) ) {
            return true;
        }

        if ( preg_match_all( '/\b[\p{L}]{3,}\b/u', $plain_without_tokens, $matches ) && ! empty( $matches[0] ) && count( $matches[0] ) >= 2 ) {
            return true;
        }

        $plain_len = function_exists( 'mb_strlen' ) ? mb_strlen( $plain_without_tokens, 'UTF-8' ) : strlen( $plain_without_tokens );
        return $plain_len >= 12;
    }

    protected function looks_like_internal_log_or_debug_text( $text ) {
        $text = trim( (string) $text );
        if ( '' === $text ) {
            return false;
        }

        if ( false !== strpos( $text, '#!trpst#trp-gettext' ) || false !== strpos( $text, 'data-trpgettextoriginal=' ) ) {
            return true;
        }

        return (bool) preg_match(
            '/\b(OpenAI|Qwen|DeepL|Hunyuan|火山方舟|混元)\s+(?:translate_batch|批量翻译|单条并发兜底|单条兜底|翻译)/u',
            $text
        );
    }

    protected function should_skip_remote_translation( $value ) {
        $raw_value = html_entity_decode( (string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $value     = trim( wp_strip_all_tags( $raw_value ) );
        if ( '' === $value ) {
            return true;
        }

        if ( $this->looks_like_pure_url_or_email( $value ) ) {
            return true;
        }

        if ( ! preg_match( '/\p{L}/u', $value ) ) {
            return true;
        }

        if ( $this->looks_like_technical_identifier_only( $value ) ) {
            return true;
        }

        if ( preg_match( '/^[\p{N}\p{P}\p{S}\s]+$/u', $value ) ) {
            return true;
        }

        if ( class_exists( 'TPRE_Translation_Safety_Utils' ) && TPRE_Translation_Safety_Utils::looks_like_code_or_template_fragment( $raw_value ) ) {
            if ( ! $this->should_translate_inline_markup_mixed_text( $raw_value, $value ) ) {
                return true;
            }
        }

        if ( preg_match( '/^(?:\.?\.?\/)?[A-Za-z0-9_\-.\/]+\.[A-Za-z0-9]{1,12}$/', $value ) ) {
            return true;
        }

        if ( class_exists( 'TPRE_Translation_Safety_Utils' ) && TPRE_Translation_Safety_Utils::looks_like_safe_passthrough_text( $value ) ) {
            return true;
        }

        if ( $this->looks_like_internal_log_or_debug_text( $raw_value ) || $this->looks_like_internal_log_or_debug_text( $value ) ) {
            return true;
        }

        if ( preg_match( '/^oembed\s*\((json|xml)\)$/i', $value ) ) {
            return true;
        }

        return false;
    }

    protected function maybe_get_string_cache_key( $source_language, $target_language, $string ) {
        return 'trp_veark_str_v6_' . md5( $this->get_model() . '|' . $source_language . '|' . $target_language . '|' . wp_json_encode( $this->get_cjk_passthrough_target_prefixes() ) . '|' . (string) $string );
    }

    protected function get_cached_string_result( $source_language, $target_language, $string ) {
        $cache_key = $this->maybe_get_string_cache_key( $source_language, $target_language, $string );
        $cached    = get_transient( $cache_key );
        if ( ! is_string( $cached ) || '' === $cached ) {
            return false;
        }

        if ( $this->is_suspect_incomplete_translation( $source_language, $target_language, $string, $cached ) ) {
            delete_transient( $cache_key );
            return false;
        }

        return $cached;
    }

    protected function set_cached_string_result( $source_language, $target_language, $string, $translated ) {
        if ( ! is_string( $translated ) || '' === trim( $translated ) ) {
            return;
        }
        $cache_key = $this->maybe_get_string_cache_key( $source_language, $target_language, $string );
        set_transient( $cache_key, $translated, $this->get_cache_ttl() );
    }

    protected function contains_cjk_text( $text ) {
        $text = (string) $text;
        return '' !== $text && 1 === preg_match( '/[\x{3400}-\x{4DBF}\x{4E00}-\x{9FFF}\x{F900}-\x{FAFF}]/u', $text );
    }

    protected function count_cjk_chars( $text ) {
        $text = (string) $text;
        if ( '' === $text ) {
            return 0;
        }

        if ( preg_match_all( '/[\x{3400}-\x{4DBF}\x{4E00}-\x{9FFF}\x{F900}-\x{FAFF}]/u', $text, $matches ) ) {
            return count( $matches[0] );
        }

        return 0;
    }

    protected function get_cjk_passthrough_target_prefixes() {
        $defaults = array( 'zh' );

        $targets = apply_filters( 'tpre_volc_cjk_passthrough_target_prefixes', $defaults, $this->settings );
        if ( ! is_array( $targets ) ) {
            $targets = $defaults;
        }

        $normalized = array();
        foreach ( $targets as $target ) {
            if ( ! is_string( $target ) ) {
                continue;
            }

            $target = strtolower( trim( $target ) );
            if ( '' === $target ) {
                continue;
            }

            $normalized[] = $target;
        }

        if ( empty( $normalized ) ) {
            $normalized = $defaults;
        }

        return array_values( array_unique( $normalized ) );
    }

    protected function target_language_allows_cjk_passthrough( $target_language ) {
        $target_language = strtolower( trim( (string) $target_language ) );
        if ( '' === $target_language ) {
            return false;
        }

        foreach ( $this->get_cjk_passthrough_target_prefixes() as $prefix ) {
            if ( 0 === strpos( $target_language, $prefix ) ) {
                return true;
            }
        }

        return false;
    }

    protected function is_suspect_incomplete_translation( $source_language, $target_language, $source_text, $translated_text ) {
        $source_text     = trim( $this->sanitize_utf8_text( (string) $source_text ) );
        $translated_text = trim( $this->sanitize_utf8_text( (string) $translated_text ) );
        $source_language = strtolower( (string) $source_language );
        $target_language = strtolower( (string) $target_language );

        if ( '' === $source_text || '' === $translated_text ) {
            return true;
        }

        if ( $translated_text === $source_text ) {
            return true;
        }

        $source_has_cjk        = $this->contains_cjk_text( $source_text );
        $translated_has_cjk    = $this->contains_cjk_text( $translated_text );
        $target_allows_cjk_out = $this->target_language_allows_cjk_passthrough( $target_language );

        if ( $source_has_cjk && ! $target_allows_cjk_out && $translated_has_cjk ) {
            $translated_cjk_count = $this->count_cjk_chars( $translated_text );
            $source_cjk_count     = max( 1, $this->count_cjk_chars( $source_text ) );
            if ( $translated_cjk_count >= 6 ) {
                return true;
            }
            if ( $translated_cjk_count >= max( 3, (int) ceil( $source_cjk_count * 0.2 ) ) ) {
                return true;
            }
            if ( preg_match( '/[\x{3400}-\x{4DBF}\x{4E00}-\x{9FFF}\x{F900}-\x{FAFF}]{4,}/u', $translated_text ) ) {
                return true;
            }
        }

        if ( mb_strlen( $source_text, 'UTF-8' ) >= 12 && false !== mb_stripos( $translated_text, $source_text, 0, 'UTF-8' ) ) {
            return true;
        }

        return false;
    }

    protected function build_chunks( $new_strings, $source_language = '', $target_language = '' ) {
        $chunks = array();

        $active_account = $this->prime_active_account_for_request( $source_language, $target_language );
        if ( false === $active_account ) {
            $active_account = $this->get_active_account();
        }
        $request_modes  = false !== $active_account ? $this->get_account_request_modes( $active_account, $source_language, $target_language ) : array();
        $chat_only      = is_array( $request_modes ) && 1 === count( $request_modes ) && 'chat' === reset( $request_modes );

        $default_chat_chunk_size = $this->get_default_chat_chunk_size( $source_language, $target_language );

        $chunk_size = $chat_only
            ? $default_chat_chunk_size
            : 1;

        $chunk_size = max( 1, $chunk_size );
        if ( $chunk_size <= 1 ) {
            foreach ( $new_strings as $key => $value ) {
                $chunks[] = array( $key => $value );
            }
            return $chunks;
        }

        $current = array();
        foreach ( $new_strings as $key => $value ) {
            $current[ $key ] = $value;
            if ( count( $current ) >= $chunk_size ) {
                $chunks[] = $current;
                $current  = array();
            }
        }

        if ( ! empty( $current ) ) {
            $chunks[] = $current;
        }

        return $chunks;
    }

    protected function extract_usage_from_body_text( $body_text ) {
        $data = json_decode( $body_text, true );
        if ( ! is_array( $data ) || ! isset( $data['usage'] ) || ! is_array( $data['usage'] ) ) {
            return array();
        }

        return array(
            'prompt_tokens'     => isset( $data['usage']['prompt_tokens'] ) ? (int) $data['usage']['prompt_tokens'] : 0,
            'completion_tokens' => isset( $data['usage']['completion_tokens'] ) ? (int) $data['usage']['completion_tokens'] : 0,
            'total_tokens'      => isset( $data['usage']['total_tokens'] ) ? (int) $data['usage']['total_tokens'] : 0,
        );
    }

    protected function should_switch_account( $response ) {
        if ( is_wp_error( $response ) ) {
            if ( 'tpre_volcengine_ark_unsupported_language' === $response->get_error_code() ) {
                return false;
            }
            return true;
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = (string) wp_remote_retrieve_body( $response );

        if ( in_array( $code, array( 401, 403, 429, 500, 502, 503, 504 ), true ) ) {
            return true;
        }

        $keywords = array( 'quota', 'rate limit', 'insufficient', 'exceeded', 'token', '余额', '限流', '配额', '额度', '用量', 'overdue' );
        foreach ( $keywords as $keyword ) {
            if ( false !== stripos( $body, $keyword ) ) {
                return true;
            }
        }

        return false;
    }

    protected function get_response_error_message( $response ) {
        if ( is_wp_error( $response ) ) {
            return $response->get_error_message();
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['error']['message'] ) ) {
            $prefix = isset( $body['error']['code'] ) ? $body['error']['code'] . ' | ' : '';
            return (string) $prefix . $body['error']['message'];
        }

        return __( '火山翻译请求失败。', 'langrouter-for-translatepress' );
    }

    protected function are_languages_supported( $source_language, $target_language ) {
        $source_language = $this->normalize_translation_language_code( $source_language );
        $target_language = $this->normalize_translation_language_code( $target_language );
        if ( '' === $source_language || '' === $target_language ) {
            return false;
        }

        return in_array( $source_language, $this->get_supported_translation_language_codes(), true ) && in_array( $target_language, $this->get_supported_translation_language_codes(), true );
    }

    protected function send_responses_request( $source_language, $target_language, $strings_array, $timeout = null, $account = null, $quality_retry = false ) {
        if ( null === $account ) {
            $account = $this->get_active_account();
        }

        if ( false === $account ) {
            $this->log_error( __( '火山请求终止：无可用接入点', 'langrouter-for-translatepress' ), array(
                'source_language' => $source_language,
                'target_language' => $target_language,
                'string_count'    => is_array( $strings_array ) ? count( $strings_array ) : 0,
                'quality_retry'   => (bool) $quality_retry,
            ) );
            return new WP_Error( 'tpre_volcengine_ark_no_available_account', __( '账号池今日已用完或均不可用，已停止自动翻译。', 'langrouter-for-translatepress' ) );
        }

        if ( $this->is_account_limit_reached( (int) $account['index'] ) ) {
            $this->set_account_status( (int) $account['index'], self::STATUS_LIMIT_REACHED );
            $this->log_error( __( '火山请求终止：当前接入点达到安全阈值', 'langrouter-for-translatepress' ), array_merge(
                $this->get_account_log_context( $account ),
                array(
                    'source_language' => $source_language,
                    'target_language' => $target_language,
                    'string_count'    => is_array( $strings_array ) ? count( $strings_array ) : 0,
                    'quality_retry'   => (bool) $quality_retry,
                )
            ) );
            return new WP_Error( 'tpre_volcengine_ark_account_limit_reached', __( '当前接入点已达到安全阈值，已停止继续消耗。', 'langrouter-for-translatepress' ) );
        }

        $request_modes = $this->get_account_request_modes( $account, $source_language, $target_language );
        $this->set_last_request_mode( '' );
        $this->reset_last_response_meta();
        $last_response = null;
        $last_error    = '';

        $this->log_debug( __( '火山请求准备发送', 'langrouter-for-translatepress' ), array_merge(
            $this->get_account_log_context( $account ),
            array(
                'source_language'       => $source_language,
                'target_language'       => $target_language,
                'string_count'          => is_array( $strings_array ) ? count( $strings_array ) : 0,
                'request_modes'         => $request_modes,
                'quality_retry'         => (bool) $quality_retry,
                'translation_supported' => $this->are_languages_supported( $source_language, $target_language ),
                'timeout'               => null === $timeout ? $this->get_effective_request_timeout( $source_language, $target_language, $account, $quality_retry, is_array( $strings_array ) ? count( $strings_array ) : 0 ) : (int) $timeout,
            )
        ) );

        foreach ( $request_modes as $mode ) {
            $body = $this->build_request_body( $source_language, $target_language, $strings_array, $quality_retry, $mode );
            if ( false === $body ) {
                $this->log_debug( __( '火山请求模式跳过：请求体构建失败', 'langrouter-for-translatepress' ), array_merge(
                    $this->get_account_log_context( $account ),
                    array(
                        'mode'            => $mode,
                        'source_language' => $source_language,
                        'target_language' => $target_language,
                        'string_count'    => is_array( $strings_array ) ? count( $strings_array ) : 0,
                        'quality_retry'   => (bool) $quality_retry,
                    )
                ) );
                continue;
            }

            $body['model'] = $this->get_request_model_for_mode( $account, $mode );

            $request_url     = ( 'chat' === $mode ) ? $this->get_chat_completions_url() : $this->get_base_url() . '/responses';

            $this->log_debug( __( '火山请求发送中', 'langrouter-for-translatepress' ), array_merge(
                $this->get_account_log_context( $account ),
                array(
                    'request_mode'      => $mode,
                    'resolved_model'    => $body['model'],
                    'source_language'   => $source_language,
                    'target_language'   => $target_language,
                    'string_count'      => is_array( $strings_array ) ? count( $strings_array ) : 0,
                    'quality_retry'     => (bool) $quality_retry,
                    'body_top_level'    => array_keys( $body ),
                    'input_item_count'  => isset( $body['input'][0]['content'] ) && is_array( $body['input'][0]['content'] ) ? count( $body['input'][0]['content'] ) : ( isset( $body['messages'] ) && is_array( $body['messages'] ) ? count( $body['messages'] ) : 0 ),
                    'instructions_len'  => isset( $body['instructions'] ) ? strlen( (string) $body['instructions'] ) : ( isset( $body['messages'][0]['content'] ) ? strlen( (string) $body['messages'][0]['content'] ) : 0 ),
                    'request_url'       => $request_url,
                )
            ) );

            $request_timeout = null === $timeout ? $this->get_effective_request_timeout( $source_language, $target_language, $account, $quality_retry, is_array( $strings_array ) ? count( $strings_array ) : 0 ) : (int) $timeout;
            $response        = wp_remote_post(
                $request_url,
                array(
                    'timeout' => $request_timeout,
                    'headers' => array(
                        'Content-Type'  => 'application/json',
                        'Authorization' => 'Bearer ' . $account['api_key'],
                    ),
                    'body'    => wp_json_encode( $body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
                )
            );

            $response_shape = $this->summarize_response_shape( $response );

            if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
                $usage = $this->extract_usage_from_body_text( wp_remote_retrieve_body( $response ) );
                $this->record_account_usage( (int) $account['index'], $usage );
                $this->set_last_request_mode( $mode );
                $this->last_response_status = self::STATUS_OK;
                $this->last_response_error_message = '';
                $this->log_debug( __( '火山请求返回成功', 'langrouter-for-translatepress' ), array_merge(
                    $this->get_account_log_context( $account ),
                    array(
                        'request_mode' => $mode,
                        'resolved_model'=> $body['model'],
                        'quality_retry'=> (bool) $quality_retry,
                        'usage'        => $usage,
                    ),
                    $response_shape
                ) );
                return $response;
            }

            $last_response = $response;
            $last_error    = $this->get_response_error_message( $response );
            $status        = $this->infer_status_from_error( $last_error );
            $this->last_response_status = $status;
            $this->last_response_error_message = $last_error;
            $mode_index    = array_search( $mode, $request_modes, true );
            $has_next_mode = ( false !== $mode_index && $mode_index < ( count( $request_modes ) - 1 ) );

            $this->log_error( __( '火山请求返回失败', 'langrouter-for-translatepress' ), array_merge(
                $this->get_account_log_context( $account ),
                array(
                    'request_mode'   => $mode,
                    'resolved_model' => $body['model'],
                    'quality_retry'  => (bool) $quality_retry,
                    'error_message'  => $last_error,
                    'derived_status' => $status,
                    'has_next_mode'  => (bool) $has_next_mode,
                ),
                $response_shape
            ) );

            if ( $has_next_mode && ! in_array( $status, array( self::STATUS_OVERDUE, self::STATUS_BLOCKED ), true ) ) {
                $this->log_debug( __( '火山请求切换到下一个模式', 'langrouter-for-translatepress' ), array_merge(
                    $this->get_account_log_context( $account ),
                    array(
                        'failed_mode'    => $mode,
                        'next_mode'      => $request_modes[ $mode_index + 1 ],
                        'quality_retry'  => (bool) $quality_retry,
                        'error_message'  => $last_error,
                        'derived_status' => $status,
                    )
                ) );
                continue;
            }

            $this->record_account_usage( (int) $account['index'], array(), $last_error );
            return $response;
        }

        if ( '' !== $last_error ) {
            $this->record_account_usage( (int) $account['index'], array(), $last_error );
            return $last_response;
        }

        $this->log_error( __( '火山请求结束：当前语言在全部模式下均未成功', 'langrouter-for-translatepress' ), array_merge(
            $this->get_account_log_context( $account ),
            array(
                'source_language' => $source_language,
                'target_language' => $target_language,
                'string_count'    => is_array( $strings_array ) ? count( $strings_array ) : 0,
                'quality_retry'   => (bool) $quality_retry,
                'request_modes'   => $request_modes,
            )
        ) );

        return new WP_Error( 'tpre_volcengine_ark_unsupported_language', __( '当前语言不在火山翻译模型支持范围内，且聊天模型兜底也未成功。', 'langrouter-for-translatepress' ) );
    }

    protected function collect_output_text_candidates( $node, &$texts ) {
        if ( ! is_array( $node ) ) {
            return;
        }

        if ( isset( $node['type'] ) && 'output_text' === $node['type'] && isset( $node['text'] ) && is_string( $node['text'] ) ) {
            $texts[] = $node['text'];
        }

        if ( isset( $node['message']['content'] ) ) {
            if ( is_string( $node['message']['content'] ) ) {
                $texts[] = $node['message']['content'];
            } elseif ( is_array( $node['message']['content'] ) ) {
                foreach ( $node['message']['content'] as $content_item ) {
                    if ( is_string( $content_item ) ) {
                        $texts[] = $content_item;
                    } elseif ( is_array( $content_item ) ) {
                        if ( isset( $content_item['text'] ) && is_string( $content_item['text'] ) ) {
                            $texts[] = $content_item['text'];
                        } elseif ( isset( $content_item['type'], $content_item['content'] ) && 'text' === $content_item['type'] && is_string( $content_item['content'] ) ) {
                            $texts[] = $content_item['content'];
                        }
                    }
                }
            }
        }

        if ( isset( $node['content'] ) && is_array( $node['content'] ) ) {
            foreach ( $node['content'] as $item ) {
                if ( is_array( $item ) && isset( $item['type'] ) && 'output_text' === $item['type'] && isset( $item['text'] ) && is_string( $item['text'] ) ) {
                    $texts[] = $item['text'];
                }
            }
        }

        foreach ( $node as $value ) {
            if ( is_array( $value ) ) {
                $this->collect_output_text_candidates( $value, $texts );
            }
        }
    }

    protected function maybe_extract_first_scalar_from_json_text( $text ) {
        $text = trim( $this->sanitize_utf8_text( $text ) );
        if ( '' === $text ) {
            return false;
        }

        $decoded = json_decode( $text, true );
        if ( is_string( $decoded ) ) {
            $decoded = json_decode( $decoded, true );
        }

        if ( is_array( $decoded ) ) {
            foreach ( $decoded as $item ) {
                if ( is_scalar( $item ) ) {
                    $item = trim( $this->sanitize_utf8_text( (string) $item ) );
                    if ( '' !== $item ) {
                        if ( class_exists( 'TPRE_Translation_Safety_Utils' ) && TPRE_Translation_Safety_Utils::is_provider_response_id_value( $item ) ) {
                            continue;
                        }
                        return $item;
                    }
                }
            }
        }

        return false;
    }

    protected function maybe_parse_json_output_text_items( $text, $expected_count ) {
        $text = trim( $this->sanitize_utf8_text( $text ) );
        if ( '' === $text ) {
            return false;
        }

        $candidates = array( $text );
        if ( preg_match( '/```(?:json)?\s*(\[[\s\S]*\])\s*```/u', $text, $match ) && ! empty( $match[1] ) ) {
            $candidates[] = trim( $match[1] );
        }
        if ( preg_match( '/(\[[\s\S]*\])/u', $text, $match ) && ! empty( $match[1] ) ) {
            $candidates[] = trim( $match[1] );
        }

        $expanded_candidates = array();
        foreach ( array_values( array_unique( $candidates ) ) as $candidate ) {
            $expanded_candidates[] = $candidate;
            $decoded_scalar = json_decode( $candidate, true );
            if ( is_string( $decoded_scalar ) ) {
                $decoded_scalar = trim( $this->sanitize_utf8_text( $decoded_scalar ) );
                if ( '' !== $decoded_scalar ) {
                    $expanded_candidates[] = $decoded_scalar;
                }
            }
        }

        foreach ( array_values( array_unique( $expanded_candidates ) ) as $candidate ) {
            $decoded = json_decode( $candidate, true );
            if ( ! is_array( $decoded ) || count( $decoded ) !== (int) $expected_count ) {
                continue;
            }

            $items = array();
            foreach ( $decoded as $item ) {
                if ( ! is_scalar( $item ) ) {
                    $items = array();
                    break;
                }
                $item = trim( $this->sanitize_utf8_text( (string) $item ) );
                if ( class_exists( 'TPRE_Translation_Safety_Utils' ) && TPRE_Translation_Safety_Utils::is_provider_response_id_value( $item ) ) {
                    $items = array();
                    break;
                }
                $items[] = $item;
            }

            if ( count( $items ) === (int) $expected_count ) {
                return $items;
            }
        }

        $line_candidates = preg_split( '/\R/u', trim( preg_replace( '/```(?:json)?|```/u', '', $text ) ) );
        if ( is_array( $line_candidates ) ) {
            $line_items = array();
            foreach ( $line_candidates as $line ) {
                $line = trim( preg_replace( '/^(?:[-*•]|\d+[.)])\s*/u', '', (string) $line ) );
                if ( '' !== $line ) {
                    $line_items[] = trim( $this->sanitize_utf8_text( $line ) );
                }
            }
            if ( count( $line_items ) === (int) $expected_count ) {
                return $line_items;
            }
        }

        return false;
    }

    protected function parse_response_text_items( $response_body, $expected_count ) {
        $data = json_decode( $response_body, true );
        if ( ! is_array( $data ) ) {
            return false;
        }

        $texts = array();
        $this->collect_output_text_candidates( $data, $texts );
        $texts = array_values(
            array_filter(
                array_map(
                    function( $text ) {
                        return trim( $this->sanitize_utf8_text( $text ) );
                    },
                    $texts
                ),
                function( $text ) {
                    if ( '' === $text ) {
                        return false;
                    }

                    if ( class_exists( 'TPRE_Translation_Safety_Utils' ) && TPRE_Translation_Safety_Utils::is_provider_response_id_value( $text ) ) {
                        return false;
                    }

                    return true;
                }
            )
        );

        foreach ( array_values( array_unique( $texts ) ) as $text ) {
            $json_items = $this->maybe_parse_json_output_text_items( $text, $expected_count );
            if ( false !== $json_items ) {
                return $json_items;
            }
        }

        if ( count( $texts ) === (int) $expected_count ) {
            return $texts;
        }

        if ( 1 === (int) $expected_count && ! empty( $texts ) ) {
            $first_scalar = $this->maybe_extract_first_scalar_from_json_text( $texts[0] );
            if ( false !== $first_scalar ) {
                return array( $first_scalar );
            }
            return array( $texts[0] );
        }

        return false;
    }

    protected function normalize_response_items( $response, $chunk ) {
        if ( is_wp_error( $response ) ) {
            return false;
        }

        if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
            return false;
        }

        $items = $this->parse_response_text_items( wp_remote_retrieve_body( $response ), count( $chunk ) );
        if ( is_array( $items ) && 'translation' === $this->get_last_request_mode() && count( $items ) === count( $chunk ) ) {
            $normalized_items = array();
            $index            = 0;
            foreach ( array_values( $chunk ) as $source_text ) {
                $normalized_items[] = $this->cleanup_translation_literal_newline_artifacts( $source_text, $items[ $index ], 'translation' );
                $index++;
            }
            $items = $normalized_items;
        }

        if ( false === $items ) {
            $this->log_error( __( '火山响应解析失败', 'langrouter-for-translatepress' ), array_merge(
                array(
                    'expected_count' => count( $chunk ),
                    'chunk_keys'     => array_keys( $chunk ),
                ),
                $this->summarize_response_shape( $response )
            ) );
        } else {
            $this->log_debug( __( '火山响应解析成功', 'langrouter-for-translatepress' ), array(
                'expected_count' => count( $chunk ),
                'parsed_count'   => count( $items ),
                'chunk_keys'     => array_keys( $chunk ),
            ) );
        }

        return $items;
    }

    protected function are_chunk_items_acceptable( $source_language, $target_language, $chunk, $items, $request_mode = '' ) {
        $this->last_chat_validation = array();

        if ( ! is_array( $items ) || count( $items ) !== count( $chunk ) ) {
            return false;
        }

        $request_mode = is_string( $request_mode ) && '' !== $request_mode ? $request_mode : $this->get_last_request_mode();
        $has_acceptable_item = false;

        $i = 0;
        foreach ( $chunk as $old_string ) {
            if ( ! isset( $items[ $i ] ) || ! is_string( $items[ $i ] ) ) {
                return false;
            }

            $translated_text = trim( $this->sanitize_utf8_text( $items[ $i ] ) );
            $translated_text = trim( $this->cleanup_translation_literal_newline_artifacts( $old_string, $translated_text ) );
            if ( method_exists( $this, 'restore_volc_chat_text_after_response' ) ) {
                $translated_text = trim( $this->restore_volc_chat_text_after_response( $old_string, $translated_text ) );
            }

            if ( 'chat' === $request_mode ) {
                $validation = $this->get_volc_chat_validation_result( $source_language, $target_language, $old_string, $translated_text );
                if ( empty( $validation['ok'] ) ) {
                    if ( empty( $this->last_chat_validation ) ) {
                        $this->last_chat_validation = $validation;
                    }
                    $this->log_debug( __( '火山 Chat 结果校验未通过', 'langrouter-for-translatepress' ), $validation );
                    $i++;
                    continue;
                }
                $has_acceptable_item = true;
            } else {
                if ( '' === $translated_text || $translated_text === trim( (string) $old_string ) ) {
                    $i++;
                    continue;
                }
                if ( $this->is_suspect_incomplete_translation( $source_language, $target_language, $old_string, $translated_text ) ) {
                    $i++;
                    continue;
                }
                $has_acceptable_item = true;
            }

            $i++;
        }

        return $has_acceptable_item;
    }

    protected function maybe_mark_account_unavailable_from_response( $account, $response ) {
        if ( false === $account || ! is_array( $account ) || ! isset( $account['index'] ) ) {
            return;
        }

        if ( ! $this->should_switch_account( $response ) ) {
            return;
        }

        $error  = $this->get_response_error_message( $response );
        $status = $this->infer_status_from_error( $error );
        $this->log_debug( __( '火山接入点状态评估', 'langrouter-for-translatepress' ), array_merge(
            $this->get_account_log_context( $account ),
            array(
                'error_message'  => $error,
                'derived_status' => $status,
            )
        ) );
        if ( in_array( $status, array( self::STATUS_OVERDUE, self::STATUS_BLOCKED ), true ) ) {
            $this->set_account_status( (int) $account['index'], $status, $error );
            $this->log_error( __( '火山接入点已标记不可用', 'langrouter-for-translatepress' ), array_merge(
                $this->get_account_log_context( $account ),
                array(
                    'error_message'  => $error,
                    'derived_status' => $status,
                )
            ) );
        }
    }

    protected function translate_chunk_quality_retry( $source_language, $target_language, $chunk, $account ) {
        if ( false === $account ) {
            return false;
        }

        $response = $this->send_responses_request( $source_language, $target_language, $chunk, null, $account, true );
        $items    = $this->normalize_response_items( $response, $chunk );
        if ( false !== $items && $this->are_chunk_items_acceptable( $source_language, $target_language, $chunk, $items, $this->get_last_request_mode() ) ) {
            return $items;
        }

        $this->maybe_mark_account_unavailable_from_response( $account, $response );
        return false;
    }


    protected function send_parallel_chunk_requests( $source_language, $target_language, $chunks, $timeout = null ) {
        $account = $this->get_active_account();
        if ( $this->is_chat_only_request( $source_language, $target_language, $account ) ) {
            return false;
        }

        if ( ! $this->can_use_curl_multi() || count( $chunks ) <= 1 ) {
            return false;
        }

        if ( false === $account || empty( $account['api_key'] ) ) {
            return false;
        }

        $timeout        = null === $timeout ? $this->get_effective_request_timeout( $source_language, $target_language, $account, false, isset( $chunks[ array_key_first( $chunks ) ] ) && is_array( $chunks[ array_key_first( $chunks ) ] ) ? count( $chunks[ array_key_first( $chunks ) ] ) : 0 ) : (int) $timeout;
        $chat_only_fast = $this->is_chat_only_request( $source_language, $target_language, $account );
        $multi_handle   = curl_multi_init();
        $handles        = array();
        $results        = array();

        foreach ( $chunks as $index => $chunk ) {
            $request_modes = $this->get_account_request_modes( $account, $source_language, $target_language );
            $request_mode  = ! empty( $request_modes ) ? (string) reset( $request_modes ) : 'translation';
            $body = $this->build_request_body( $source_language, $target_language, $chunk, false, $request_mode );
            if ( false === $body ) {
                $results[ $index ] = false;
                continue;
            }

            $body['model'] = $this->get_request_model_for_mode( $account, $request_mode );
            $url = $this->get_base_url() . ( 'chat' === $request_mode ? '/chat/completions' : '/responses' );
            $headers = array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $account['api_key'],
            );

            $ch = curl_init( $url );
            curl_setopt( $ch, CURLOPT_POST, true );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, wp_json_encode( $body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
            curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
            curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $chat_only_fast ? 4 : 6 );
            curl_setopt( $ch, CURLOPT_ENCODING, '' );
            if ( defined( 'CURL_HTTP_VERSION_2TLS' ) ) {
                curl_setopt( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2TLS );
            } else {
                curl_setopt( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
            }
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
            curl_setopt( $ch, CURLOPT_FORBID_REUSE, false );
            curl_setopt( $ch, CURLOPT_FRESH_CONNECT, false );

            curl_multi_add_handle( $multi_handle, $ch );
            $handles[ $index ] = array(
                'handle' => $ch,
                'chunk'  => $chunk,
            );
        }

        if ( empty( $handles ) ) {
            curl_multi_close( $multi_handle );
            return false;
        }

        $running = null;
        do {
            $multi_exec = curl_multi_exec( $multi_handle, $running );
            if ( CURLM_OK !== $multi_exec ) {
                break;
            }
            if ( $running > 0 ) {
                curl_multi_select( $multi_handle, 1.0 );
            }
        } while ( $running > 0 );

        foreach ( $handles as $index => $item ) {
            $ch        = $item['handle'];
            $chunk     = $item['chunk'];
            $body_text = curl_multi_getcontent( $ch );
            $http_code = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
            $errno     = curl_errno( $ch );
            $error     = '';

            if ( 0 === $errno && 200 === $http_code ) {
                $results[ $index ] = $this->parse_response_text_items( $body_text, count( $chunk ) );
                $usage = $this->extract_usage_from_body_text( $body_text );
                $this->record_account_usage( (int) $account['index'], $usage );
            } else {
                if ( 0 !== $errno ) {
                    $error = 'cURL error ' . $errno . ': ' . curl_error( $ch );
                } else {
                    $decoded = json_decode( $body_text, true );
                    if ( isset( $decoded['error']['message'] ) ) {
                        $prefix = isset( $decoded['error']['code'] ) ? $decoded['error']['code'] . ' | ' : '';
                        $error  = (string) $prefix . $decoded['error']['message'];
                    } else {
                        $error = 'HTTP ' . $http_code;
                    }
                }
                $results[ $index ] = false;
                $this->record_account_usage( (int) $account['index'], array(), $error );
            }

            curl_multi_remove_handle( $multi_handle, $ch );
            curl_close( $ch );
        }

        curl_multi_close( $multi_handle );
        return $results;
    }

    protected function translate_chunk_with_retry( $source_language, $target_language, $chunk, $quality_retry_only = false ) {
        $account = $this->get_active_account();
        if ( false === $account ) {
            $this->log_error( __( '火山分块翻译失败：无可用接入点', 'langrouter-for-translatepress' ), array(
                'source_language'   => $source_language,
                'target_language'   => $target_language,
                'chunk_size'        => is_array( $chunk ) ? count( $chunk ) : 0,
                'quality_retry_only'=> (bool) $quality_retry_only,
            ) );
            return false;
        }

        $is_chat_only = $this->is_chat_only_request( $source_language, $target_language, $account );
        $is_fragile_chat_pair = $is_chat_only && $this->is_volc_chat_fragile_language_pair( $source_language, $target_language );

        $this->log_debug( __( '火山分块翻译开始', 'langrouter-for-translatepress' ), array_merge(
            $this->get_account_log_context( $account ),
            array(
                'source_language'    => $source_language,
                'target_language'    => $target_language,
                'chunk_size'         => is_array( $chunk ) ? count( $chunk ) : 0,
                'chunk_keys'         => array_keys( (array) $chunk ),
                'quality_retry_only' => (bool) $quality_retry_only,
            )
        ) );

        if ( ! $quality_retry_only ) {
            $response = $this->send_responses_request( $source_language, $target_language, $chunk, null, $account, false );
            $items    = $this->normalize_response_items( $response, $chunk );
            if ( false !== $items && $this->are_chunk_items_acceptable( $source_language, $target_language, $chunk, $items, $this->get_last_request_mode() ) ) {
                $this->log_debug( __( '火山分块翻译主请求成功', 'langrouter-for-translatepress' ), array_merge(
                    $this->get_account_log_context( $account ),
                    array(
                        'chunk_size'   => count( $chunk ),
                        'parsed_count' => count( $items ),
                        'validation'   => $this->last_chat_validation,
                    )
                ) );
                return $items;
            }

            if ( false === $items ) {
                $this->maybe_mark_account_unavailable_from_response( $account, $response );
            } else {
                $this->log_error( __( '火山分块翻译主请求结果未通过校验', 'langrouter-for-translatepress' ), array_merge(
                    $this->get_account_log_context( $account ),
                    array(
                        'chunk_size'   => count( $chunk ),
                        'parsed_count' => count( $items ),
                        'validation'   => $this->last_chat_validation,
                    )
                ) );
                if ( $is_chat_only ) {
                    $single_source_text = '';
                    if ( 1 === count( $chunk ) ) {
                        $single_source_text = (string) reset( $chunk );
                    }
                    $should_force_retry = ! $quality_retry_only
                        && 1 === count( $chunk )
                        && is_string( $single_source_text )
                        && preg_match( '/[\x{4E00}-\x{9FFF}]/u', $single_source_text )
                        && ! ( class_exists( 'TPRE_Translation_Safety_Utils' ) && TPRE_Translation_Safety_Utils::looks_like_safe_passthrough_text( $single_source_text ) )
                        && ! empty( $this->last_chat_validation['reason'] )
                        && in_array( $this->last_chat_validation['reason'], array( 'same_as_source_after_normalize', 'has_source_language_leak', 'is_suspicious_translation_output' ), true );

                    if ( $should_force_retry ) {
                        $this->log_debug( __( '火山聊天单条触发强制质量重试', 'langrouter-for-translatepress' ), array_merge(
                            $this->get_account_log_context( $account ),
                            array(
                                'chunk_size'  => count( $chunk ),
                                'validation'  => $this->last_chat_validation,
                                'source_preview' => function_exists( 'mb_substr' ) ? mb_substr( trim( wp_strip_all_tags( $single_source_text ) ), 0, 120, 'UTF-8' ) : substr( trim( wp_strip_all_tags( $single_source_text ) ), 0, 120 ),
                            )
                        ) );

                        $retry_response = $this->send_responses_request( $source_language, $target_language, $chunk, null, $account, true );
                        $retry_items    = $this->normalize_response_items( $retry_response, $chunk );
                        if ( false !== $retry_items && $this->are_chunk_items_acceptable( $source_language, $target_language, $chunk, $retry_items, $this->get_last_request_mode() ) ) {
                            $this->log_debug( __( '火山聊天单条强制质量重试成功', 'langrouter-for-translatepress' ), array_merge(
                                $this->get_account_log_context( $account ),
                                array(
                                    'chunk_size'   => count( $chunk ),
                                    'parsed_count' => count( $retry_items ),
                                )
                            ) );
                            return $retry_items;
                        }
                    }

                    $this->log_debug( __( '火山聊天分块保留主请求结果，跳过整块质量重试', 'langrouter-for-translatepress' ), array_merge(
                        $this->get_account_log_context( $account ),
                        array(
                            'chunk_size'   => count( $chunk ),
                            'parsed_count' => count( $items ),
                        )
                    ) );
                    return $items;
                }
            }
        }

        $supports_translation = $this->are_languages_supported( $source_language, $target_language );
        $single_retry_policy = $this->has_mixed_endpoint_types( $source_language, $target_language ) && ! $supports_translation;

        if ( ! $is_chat_only && ! $single_retry_policy ) {
            $items = $this->translate_chunk_quality_retry( $source_language, $target_language, $chunk, $account );
            if ( false !== $items ) {
                $this->log_debug( __( '火山分块翻译质量重试成功', 'langrouter-for-translatepress' ), array_merge(
                    $this->get_account_log_context( $account ),
                    array(
                        'chunk_size'   => count( $chunk ),
                        'parsed_count' => count( $items ),
                    )
                ) );
                return $items;
            }
        } elseif ( $is_chat_only && ( $quality_retry_only || ! $this->should_skip_chat_quality_retry() ) ) {
            return false;
        }

        $retry_account = $this->get_retry_account_for_request( $source_language, $target_language, $account );
        if ( false !== $retry_account ) {
            $this->log_debug( __( '火山分块翻译切换到下一个接入点', 'langrouter-for-translatepress' ), array(
                'previous_endpoint_id' => isset( $account['endpoint_id'] ) ? (string) $account['endpoint_id'] : '',
                'previous_index'       => isset( $account['index'] ) ? (int) $account['index'] : -1,
                'next_endpoint_id'     => isset( $retry_account['endpoint_id'] ) ? (string) $retry_account['endpoint_id'] : '',
                'next_index'           => isset( $retry_account['index'] ) ? (int) $retry_account['index'] : -1,
                'chunk_size'           => count( $chunk ),
                'single_retry_policy'  => $single_retry_policy,
            ) );

            $response = $this->send_responses_request( $source_language, $target_language, $chunk, null, $retry_account, false );
            $items    = $this->normalize_response_items( $response, $chunk );
            if ( false !== $items ) {
                if ( $this->are_chunk_items_acceptable( $source_language, $target_language, $chunk, $items, $this->get_last_request_mode() ) ) {
                    $this->log_debug( __( '火山分块翻译跨接入点重试成功', 'langrouter-for-translatepress' ), array_merge(
                        $this->get_account_log_context( $retry_account ),
                        array(
                            'chunk_size'   => count( $chunk ),
                            'parsed_count' => count( $items ),
                        )
                    ) );
                    return $items;
                }

                if ( $is_chat_only || $single_retry_policy ) {
                    return $items;
                }
            }
        }

        if ( $is_chat_only && ! $quality_retry_only && count( $chunk ) > 1 ) {
            $split_items = $this->recover_chat_chunk_by_splitting( $source_language, $target_language, $chunk );
            if ( false !== $split_items ) {
                return $split_items;
            }
        }

        $this->log_error( __( '火山分块翻译失败：全部重试已结束', 'langrouter-for-translatepress' ), array(
            'source_language'    => $source_language,
            'target_language'    => $target_language,
            'chunk_size'         => count( $chunk ),
            'quality_retry_only' => (bool) $quality_retry_only,
            'chunk_keys'         => array_keys( (array) $chunk ),
        ) );

        return false;
    }


    protected function split_associative_chunk_in_half( $chunk ) {
        $chunk = is_array( $chunk ) ? $chunk : array();
        $count = count( $chunk );
        if ( $count <= 1 ) {
            return array( $chunk );
        }

        $offset = (int) ceil( $count / 2 );
        return array(
            array_slice( $chunk, 0, $offset, true ),
            array_slice( $chunk, $offset, null, true ),
        );
    }

    protected function recover_chat_chunk_by_splitting( $source_language, $target_language, $chunk ) {
        $chunk = is_array( $chunk ) ? $chunk : array();
        if ( count( $chunk ) <= 1 ) {
            return false;
        }

        $this->log_debug( __( '火山聊天分块失败后进入拆分补救', 'langrouter-for-translatepress' ), array(
            'source_language' => $source_language,
            'target_language' => $target_language,
            'chunk_size'      => count( $chunk ),
            'chunk_keys'      => array_keys( $chunk ),
        ) );

        $merged = array();
        $offset = 0;
        foreach ( $this->split_associative_chunk_in_half( $chunk ) as $sub_chunk ) {
            if ( empty( $sub_chunk ) ) {
                continue;
            }

            $sub_items = $this->translate_chunk_with_retry( $source_language, $target_language, $sub_chunk, false );
            $sub_count = count( $sub_chunk );
            for ( $i = 0; $i < $sub_count; $i++ ) {
                $merged[ $offset + $i ] = ( is_array( $sub_items ) && isset( $sub_items[ $i ] ) && is_string( $sub_items[ $i ] ) ) ? $sub_items[ $i ] : null;
            }
            $offset += $sub_count;
        }

        if ( empty( $merged ) ) {
            return false;
        }

        ksort( $merged );
        return $merged;
    }

    protected function dedupe_uncached_strings( $new_strings, $source_language, $target_language ) {
        $resolved    = array();
        $unique      = array();
        $occurrences = array();

        foreach ( $new_strings as $key => $value ) {
            $string = (string) $value;
            if ( $this->should_skip_remote_translation( $string ) ) {
                $resolved[ $key ] = $string;
                $this->set_request_local_result( $source_language, $target_language, $string, $string );
                continue;
            }

            $cached = $this->get_cached_string_result( $source_language, $target_language, $string );
            if ( false !== $cached ) {
                $resolved[ $key ] = $cached;
                $this->set_request_local_result( $source_language, $target_language, $string, $cached );
                continue;
            }

            $request_local_cached = $this->get_request_local_result( $source_language, $target_language, $string );
            if ( false !== $request_local_cached ) {
                $resolved[ $key ] = $request_local_cached;
                continue;
            }

            $fingerprint = md5( $string );
            if ( ! isset( $occurrences[ $fingerprint ] ) ) {
                $occurrences[ $fingerprint ] = array(
                    'text' => $string,
                    'keys' => array(),
                );
                $unique[ $fingerprint ] = $string;
            }
            $occurrences[ $fingerprint ]['keys'][] = $key;
        }

        return array( $resolved, $unique, $occurrences );
    }

    protected function spread_translations_to_keys( $translated_unique, $occurrences, &$translated_strings ) {
        foreach ( $translated_unique as $fingerprint => $translated_text ) {
            if ( ! isset( $occurrences[ $fingerprint ]['keys'] ) ) {
                continue;
            }
            foreach ( $occurrences[ $fingerprint ]['keys'] as $key ) {
                $translated_strings[ $key ] = $translated_text;
            }
        }
    }

    public function send_request( $source_language, $target_language, $strings_array ) {
        return $this->send_responses_request( $source_language, $target_language, $strings_array );
    }

    public function translate_array( $new_strings, $target_language_code, $source_language_code = null ) {
        if ( null === $source_language_code ) {
            $source_language_code = $this->settings['default-language'];
        }

        if ( empty( $new_strings ) || ! $this->verify_request_parameters( $target_language_code, $source_language_code ) ) {
            $this->log_debug( __( '火山 translate_array 直接返回：入参无效', 'langrouter-for-translatepress' ), array(
                'requested_count'      => is_array( $new_strings ) ? count( $new_strings ) : 0,
                'target_language_code' => $target_language_code,
                'source_language_code' => $source_language_code,
            ) );
            return array();
        }

        $source_language = isset( $this->machine_translation_codes[ $source_language_code ] ) ? $this->machine_translation_codes[ $source_language_code ] : $source_language_code;
        $target_language = isset( $this->machine_translation_codes[ $target_language_code ] ) ? $this->machine_translation_codes[ $target_language_code ] : $target_language_code;

        $normalized_source = $this->normalize_translation_language_code( $source_language );
        $normalized_target = $this->normalize_translation_language_code( $target_language );
        if ( '' === $normalized_source ) {
            $normalized_source = $this->normalize_translation_language_code( $source_language_code );
        }
        if ( '' === $normalized_target ) {
            $normalized_target = $this->normalize_translation_language_code( $target_language_code );
        }
        if ( '' === trim( (string) $source_language ) || '' === trim( (string) $target_language ) ) {
            $this->log_error( __( '火山 translate_array 直接返回：语言映射为空', 'langrouter-for-translatepress' ), array(
                'requested_count'      => count( $new_strings ),
                'target_language_code' => $target_language_code,
                'source_language_code' => $source_language_code,
                'mapped_source'        => $source_language,
                'mapped_target'        => $target_language,
            ) );
            return array();
        }
        if ( '' === $normalized_source ) {
            $normalized_source = $source_language;
        }
        if ( '' === $normalized_target ) {
            $normalized_target = $target_language;
        }

        $active_account = $this->prime_active_account_for_request( $source_language, $target_language );
        if ( false === $active_account ) {
            $active_account = $this->get_active_account();
        }
        if ( false === $active_account ) {
            $pool_error = $this->get_accounts_pool_validation_error();
            $context    = array(
                'requested_count'      => count( $new_strings ),
                'target_language_code' => $target_language_code,
                'source_language_code' => $source_language_code,
            );
            if ( '' !== $pool_error ) {
                $context['pool_validation_error'] = $pool_error;
            }
            $this->log_error( __( '火山 translate_array 直接返回：无可用接入点', 'langrouter-for-translatepress' ), $context );
            return array();
        }

        $this->log_debug( __( '火山 translate_array 开始', 'langrouter-for-translatepress' ), array_merge(
            $this->get_account_log_context( $active_account ),
            array(
                'requested_count'       => count( $new_strings ),
                'target_language_code'  => $target_language_code,
                'source_language_code'  => $source_language_code,
                'mapped_source'         => $source_language,
                'mapped_target'         => $target_language,
                'normalized_source'     => $normalized_source,
                'normalized_target'     => $normalized_target,
            )
        ) );

        $translated_strings = array();
        list( $translated_strings, $unique_strings, $occurrences ) = $this->dedupe_uncached_strings( $new_strings, $source_language, $target_language );

        $this->log_debug( __( '火山 translate_array 预处理结果', 'langrouter-for-translatepress' ), array(
            'requested_count'     => count( $new_strings ),
            'pre_resolved_count'  => count( $translated_strings ),
            'unique_count'        => count( $unique_strings ),
            'occurrence_groups'   => count( $occurrences ),
        ) );

        if ( empty( $unique_strings ) ) {
            return $translated_strings;
        }

        $chunks             = $this->build_chunks( $unique_strings, $source_language, $target_language );
        $parallel_limit     = $this->get_parallel_requests_limit();
        $request_modes      = $this->get_account_request_modes( $active_account, $source_language, $target_language );
        $is_chat_only_batch = is_array( $request_modes ) && 1 === count( $request_modes ) && 'chat' === reset( $request_modes );
        if ( $is_chat_only_batch ) {
            $chat_parallel_cap = $this->get_default_chat_parallel_limit( $source_language, $target_language );
            $parallel_limit     = max( 1, $chat_parallel_cap );
        }
        $translated_unique  = array();

        $chunk_group_index = 0;
        for ( $chunk_offset = 0; $chunk_offset < count( $chunks ); ) {
            $chunk_group = array_slice( $chunks, $chunk_offset, $parallel_limit, true );

            $this->log_debug( __( '火山并发分组开始', 'langrouter-for-translatepress' ), array(
                'group_index'     => $chunk_group_index,
                'group_size'      => count( $chunk_group ),
                'parallel_limit'  => $parallel_limit,
                'chunk_sizes'     => array_map( 'count', $chunk_group ),
            ) );

            $parallel_timeout = $this->get_effective_request_timeout( $source_language, $target_language, $active_account, false, isset( $chunk_group[ array_key_first( $chunk_group ) ] ) && is_array( $chunk_group[ array_key_first( $chunk_group ) ] ) ? count( $chunk_group[ array_key_first( $chunk_group ) ] ) : 0 );
            $group_results = $this->send_parallel_chunk_requests( $source_language, $target_language, $chunk_group, $parallel_timeout );
            $group_had_retry_pressure = false;

            foreach ( $chunk_group as $group_index => $chunk ) {
                $items = is_array( $group_results ) && array_key_exists( $group_index, $group_results ) ? $group_results[ $group_index ] : false;
                if ( false === $items ) {
                    $group_had_retry_pressure = true;
                    $this->log_debug( __( '火山分块并发结果缺失，进入串行重试', 'langrouter-for-translatepress' ), array(
                        'group_index' => $chunk_group_index,
                        'chunk_index' => $group_index,
                        'chunk_size'  => count( $chunk ),
                    ) );
                    $items = $this->translate_chunk_with_retry( $source_language, $target_language, $chunk );
                } elseif ( ! $this->are_chunk_items_acceptable( $source_language, $target_language, $chunk, $items ) ) {
                    if ( $is_chat_only_batch ) {
                        $this->log_debug( __( '火山聊天并发结果未通过整块校验，跳过质量重试并按单条验收', 'langrouter-for-translatepress' ), array(
                            'group_index'  => $chunk_group_index,
                            'chunk_index'  => $group_index,
                            'chunk_size'   => count( $chunk ),
                            'parsed_count' => count( $items ),
                        ) );
                    } else {
                        $this->log_debug( __( '火山分块并发结果未通过校验，进入质量重试', 'langrouter-for-translatepress' ), array(
                            'group_index'  => $chunk_group_index,
                            'chunk_index'  => $group_index,
                            'chunk_size'   => count( $chunk ),
                            'parsed_count' => count( $items ),
                        ) );
                        $items = $this->translate_chunk_with_retry( $source_language, $target_language, $chunk, true );
                    }
                }

                if ( false === $items ) {
                    $group_had_retry_pressure = true;
                    $this->log_error( __( '火山分块最终无结果', 'langrouter-for-translatepress' ), array(
                        'group_index' => $chunk_group_index,
                        'chunk_index' => $group_index,
                        'chunk_size'  => count( $chunk ),
                        'chunk_keys'  => array_keys( $chunk ),
                    ) );
                    continue;
                }

                $accepted_in_chunk = 0;
                $i = 0;
                foreach ( $chunk as $fingerprint => $old_string ) {
                    if ( isset( $items[ $i ] ) && is_string( $items[ $i ] ) ) {
                        $translated_text = trim( $this->sanitize_utf8_text( $items[ $i ] ) );
                        $translated_text = trim( $this->cleanup_translation_literal_newline_artifacts( $old_string, $translated_text ) );
                        if ( method_exists( $this, 'restore_volc_chat_text_after_response' ) ) {
                            $translated_text = trim( $this->restore_volc_chat_text_after_response( $old_string, $translated_text ) );
                        }
                        $request_mode    = $this->get_last_request_mode();
                        $validation      = array();
                        if ( 'chat' === $request_mode ) {
                            $validation = $this->get_volc_chat_validation_result( $source_language, $target_language, $old_string, $translated_text );
                            $acceptable = ! empty( $validation['ok'] );
                        } else {
                            $acceptable = ! $this->is_suspect_incomplete_translation( $source_language, $target_language, $old_string, $translated_text );
                        }

                        $same_source_allowed = false;
                        if ( 'chat' === $request_mode ) {
                            $same_source_allowed = ! empty( $validation['ok'] )
                                && isset( $validation['reason'] )
                                && in_array( $validation['reason'], array( 'accepted_same_as_source_short_token', 'accepted_same_as_source_passthrough' ), true );
                            if ( ! $same_source_allowed && class_exists( 'TPRE_Translation_Safety_Utils' ) ) {
                                $same_source_allowed = TPRE_Translation_Safety_Utils::looks_like_safe_passthrough_text( $old_string )
                                    && 0 === strcasecmp( trim( (string) $translated_text ), trim( (string) $old_string ) );
                            }
                        }

                        if ( '' !== $translated_text && $acceptable && ( $translated_text !== trim( $old_string ) || $same_source_allowed ) ) {
                            $translated_unique[ $fingerprint ] = $translated_text;
                            $this->set_cached_string_result( $source_language, $target_language, $old_string, $translated_text );
                            $this->set_request_local_result( $source_language, $target_language, $old_string, $translated_text );
                            $accepted_in_chunk++;
                        }
                    }
                    $i++;
                }

                if ( false && $is_chat_only_batch && 0 === $accepted_in_chunk && count( $chunk ) > 1 ) {
                    $this->log_debug( __( '火山聊天分块零验收，进入单条补救重试', 'langrouter-for-translatepress' ), array(
                        'group_index'  => $chunk_group_index,
                        'chunk_index'  => $group_index,
                        'chunk_size'   => count( $chunk ),
                        'parsed_count' => count( $items ),
                    ) );

                    foreach ( $chunk as $fingerprint_retry => $old_string_retry ) {
                        if ( isset( $translated_unique[ $fingerprint_retry ] ) ) {
                            continue;
                        }

                        if ( class_exists( 'TPRE_Translation_Safety_Utils' ) && TPRE_Translation_Safety_Utils::looks_like_safe_passthrough_text( $old_string_retry ) ) {
                            $passthrough_text = trim( $old_string_retry );
                            $translated_unique[ $fingerprint_retry ] = $passthrough_text;
                            $this->set_cached_string_result( $source_language, $target_language, $old_string_retry, $passthrough_text );
                            $this->set_request_local_result( $source_language, $target_language, $old_string_retry, $passthrough_text );
                            $accepted_in_chunk++;
                            continue;
                        }

                        $single_items = $this->translate_chunk_with_retry( $source_language, $target_language, array( $fingerprint_retry => $old_string_retry ) );
                        if ( false === $single_items || ! isset( $single_items[0] ) || ! is_string( $single_items[0] ) ) {
                            continue;
                        }

                        $translated_text = trim( $this->sanitize_utf8_text( $single_items[0] ) );
                        $translated_text = trim( $this->cleanup_translation_literal_newline_artifacts( $old_string_retry, $translated_text ) );
                        if ( method_exists( $this, 'restore_volc_chat_text_after_response' ) ) {
                            $translated_text = trim( $this->restore_volc_chat_text_after_response( $old_string_retry, $translated_text ) );
                        }
                        $request_mode    = $this->get_last_request_mode();
                        $validation      = array();
                        if ( 'chat' === $request_mode ) {
                            $validation = $this->get_volc_chat_validation_result( $source_language, $target_language, $old_string_retry, $translated_text );
                            $acceptable = ! empty( $validation['ok'] );
                        } else {
                            $acceptable = ! $this->is_suspect_incomplete_translation( $source_language, $target_language, $old_string_retry, $translated_text );
                        }

                        $same_source_allowed = false;
                        if ( 'chat' === $request_mode ) {
                            $same_source_allowed = ! empty( $validation['ok'] )
                                && isset( $validation['reason'] )
                                && in_array( $validation['reason'], array( 'accepted_same_as_source_short_token', 'accepted_same_as_source_passthrough' ), true );
                            if ( ! $same_source_allowed && class_exists( 'TPRE_Translation_Safety_Utils' ) ) {
                                $same_source_allowed = TPRE_Translation_Safety_Utils::looks_like_safe_passthrough_text( $old_string )
                                    && 0 === strcasecmp( trim( (string) $translated_text ), trim( (string) $old_string ) );
                            }
                        }

                        if ( '' !== $translated_text && $acceptable && ( $translated_text !== trim( $old_string_retry ) || $same_source_allowed ) ) {
                            $translated_unique[ $fingerprint_retry ] = $translated_text;
                            $this->set_cached_string_result( $source_language, $target_language, $old_string_retry, $translated_text );
                            $this->set_request_local_result( $source_language, $target_language, $old_string_retry, $translated_text );
                            $accepted_in_chunk++;
                        }
                    }
                }

                $this->log_debug( __( '火山分块结果写入完成', 'langrouter-for-translatepress' ), array(
                    'group_index'        => $chunk_group_index,
                    'chunk_index'        => $group_index,
                    'chunk_size'         => count( $chunk ),
                    'parsed_count'       => count( $items ),
                    'accepted_in_chunk'  => $accepted_in_chunk,
                    'translated_unique'  => count( $translated_unique ),
                ) );

                $this->machine_translator_logger->count_towards_quota( $chunk );
                if ( $this->machine_translator_logger->quota_exceeded() ) {
                    $this->log_error( __( '火山配额记录达到上限，提前结束批量翻译', 'langrouter-for-translatepress' ), array(
                        'group_index'       => $chunk_group_index,
                        'chunk_index'       => $group_index,
                        'translated_unique' => count( $translated_unique ),
                    ) );
                    break 2;
                }
            }

            if ( $is_chat_only_batch && $group_had_retry_pressure && $parallel_limit > 1 ) {
                $parallel_limit = max( 1, $parallel_limit - 1 );
                $this->log_debug( __( '火山聊天批次检测到重试压力，后续并发自动降档', 'langrouter-for-translatepress' ), array(
                    'group_index'        => $chunk_group_index,
                    'next_parallel_limit'=> $parallel_limit,
                    'target_language'    => $target_language,
                ) );
            }

            $chunk_offset += count( $chunk_group );
            $chunk_group_index++;
        }

        $this->spread_translations_to_keys( $translated_unique, $occurrences, $translated_strings );
        $this->log_debug( __( '火山 translate_array 结束', 'langrouter-for-translatepress' ), array(
            'requested_count'       => count( $new_strings ),
            'translated_unique'     => count( $translated_unique ),
            'final_translated_count'=> count( $translated_strings ),
            'occurrence_groups'     => count( $occurrences ),
        ) );
        return $translated_strings;
    }

    public function test_request() {
        return $this->send_request( 'zh', 'en', array( '你好，世界' ) );
    }

    public function check_api_key_validity() {
        if ( isset( $this->correct_api_key ) && null !== $this->correct_api_key ) {
            return $this->correct_api_key;
        }

        $is_error = false;
        $message  = '';
        $settings = $this->get_mt_settings();
        $pool     = $this->get_accounts_pool();

        if ( isset( $settings['translation-engine'], $settings['machine-translation'] ) && 'volcengine_ark' === $settings['translation-engine'] && 'yes' === $settings['machine-translation'] ) {
            if ( empty( $pool ) ) {
                $is_error = true;
                $message  = $this->get_accounts_pool_validation_error();
                if ( '' === $message ) {
                    $message = __( '请填写多账号池。', 'langrouter-for-translatepress' );
                }
            } else {
                $response = $this->test_request();
                if ( is_wp_error( $response ) ) {
                    $is_error = true;
                    $message  = $response->get_error_message();
                } else {
                    $code = wp_remote_retrieve_response_code( $response );
                    if ( 200 !== (int) $code ) {
                        $message  = $this->get_response_error_message( $response );
                        $is_error = true;
                    } elseif ( false === $this->normalize_response_items( $response, array( '你好，世界' ) ) ) {
                        $is_error = true;
                        $message  = __( '火山翻译已返回结果，但插件暂时无法正确解析。', 'langrouter-for-translatepress' );
                    }
                }
            }
        }

        $this->correct_api_key = array(
            'message' => $message,
            'error'   => $is_error,
        );

        return $this->correct_api_key;
    }

    protected function get_supported_chat_language_codes() {
        if ( method_exists( $this, 'get_volc_chat_supported_locale_map' ) ) {
            $map = $this->get_volc_chat_supported_locale_map();
            if ( is_array( $map ) && ! empty( $map ) ) {
                $values = array();
                foreach ( $map as $value ) {
                    if ( ! is_string( $value ) ) {
                        continue;
                    }
                    $value = trim( $value );
                    if ( '' === $value ) {
                        continue;
                    }
                    $values[] = str_replace( '_', '-', strtolower( $value ) );
                }
                if ( ! empty( $values ) ) {
                    return array_values( array_unique( $values ) );
                }
            }
        }

        return $this->get_supported_translation_language_codes();
    }

    protected function normalize_volc_supported_language_code( $code ) {
        $code = is_string( $code ) ? trim( $code ) : '';
        if ( '' === $code ) {
            return '';
        }

        $normalized = strtolower( str_replace( '_', '-', $code ) );

        if ( method_exists( $this, 'get_volc_chat_supported_locale_map' ) ) {
            $locale_map = $this->get_volc_chat_supported_locale_map();
            if ( is_array( $locale_map ) ) {
                if ( isset( $locale_map[ $normalized ] ) && is_string( $locale_map[ $normalized ] ) && '' !== trim( $locale_map[ $normalized ] ) ) {
                    return str_replace( '_', '-', strtolower( trim( $locale_map[ $normalized ] ) ) );
                }
                $alt_key = str_replace( '-', '_', $normalized );
                if ( isset( $locale_map[ $alt_key ] ) && is_string( $locale_map[ $alt_key ] ) && '' !== trim( $locale_map[ $alt_key ] ) ) {
                    return str_replace( '_', '-', strtolower( trim( $locale_map[ $alt_key ] ) ) );
                }
            }
        }

        $translation_normalized = $this->normalize_translation_language_code( $code );
        if ( '' !== $translation_normalized ) {
            return str_replace( '_', '-', strtolower( $translation_normalized ) );
        }

        return $normalized;
    }

    public function get_supported_languages() {
        return $this->get_engine_specific_language_codes( $this->settings['translation-languages'] );
    }

    public function get_engine_specific_language_codes( $languages ) {
        $codes     = $this->trp_languages->get_iso_codes( $languages );
        $supported = $this->get_supported_chat_language_codes();

        foreach ( $codes as $lang_key => $iso_code ) {
            $normalized = $this->normalize_volc_supported_language_code( $iso_code );
            if ( '' === $normalized || ! in_array( $normalized, $supported, true ) ) {
                unset( $codes[ $lang_key ] );
            } else {
                $codes[ $lang_key ] = $iso_code;
            }
        }

        return $codes;
    }
}

if ( ! class_exists( 'TRP_Volcengine_Ark_Machine_Translator', false ) ) {
    class_alias( 'TPRE_Volcengine_Ark_Machine_Translator', 'TRP_Volcengine_Ark_Machine_Translator' );
}
