<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPRE_Routing_Rules {
    protected $settings;
    protected $normalizer;

    public function __construct( array $settings, TPRE_Language_Normalizer $normalizer ) {
        $this->settings   = $settings;
        $this->normalizer = $normalizer;
    }

    public function get_default_engine_slug() {
        return ! empty( $this->settings['default_engine'] ) ? $this->settings['default_engine'] : 'volc';
    }

    protected function sanitize_post_type_rule( array $rule ) {
        $engine_slug = isset( $rule['engine'] ) ? sanitize_key( (string) $rule['engine'] ) : '';
        if ( '' === $engine_slug ) {
            return [];
        }

        $fallback_mode = isset( $rule['fallback_mode'] ) ? sanitize_key( (string) $rule['fallback_mode'] ) : '';
        if ( ! in_array( $fallback_mode, [ 'none', 'default_only', 'global_chain' ], true ) ) {
            $fallback_mode = ! empty( $rule['use_global_chain'] ) ? 'global_chain' : 'default_only';
        }

        return [
            'engine'           => $engine_slug,
            'fallback_mode'    => $fallback_mode,
            'use_global_chain' => 'global_chain' === $fallback_mode ? 1 : 0,
            'rule_source'      => isset( $rule['rule_source'] ) ? sanitize_key( (string) $rule['rule_source'] ) : 'post_type_map',
        ];
    }

    protected function find_post_type_rule( $post_type ) {
        $post_type = sanitize_key( (string) $post_type );
        if ( '' === $post_type ) {
            return [];
        }

        $post_type_map = isset( $this->settings['post_type_rule_map'] ) ? (array) $this->settings['post_type_rule_map'] : [];
        if ( isset( $post_type_map[ $post_type ] ) && is_array( $post_type_map[ $post_type ] ) ) {
            $rule = $this->sanitize_post_type_rule( $post_type_map[ $post_type ] );
            if ( ! empty( $rule ) ) {
                $rule['rule_source'] = 'post_type_map';
                return $rule;
            }
        }

        $rules = isset( $this->settings['post_type_rules'] ) ? (array) $this->settings['post_type_rules'] : [];
        foreach ( $rules as $rule_row ) {
            if ( ! is_array( $rule_row ) || empty( $rule_row['engine'] ) || empty( $rule_row['post_types'] ) || ! is_array( $rule_row['post_types'] ) ) {
                continue;
            }

            foreach ( $rule_row['post_types'] as $candidate_post_type ) {
                if ( $post_type !== sanitize_key( (string) $candidate_post_type ) ) {
                    continue;
                }

                $rule = $this->sanitize_post_type_rule( $rule_row );
                if ( ! empty( $rule ) ) {
                    $rule['rule_source'] = 'post_type_rules';
                    return $rule;
                }
            }
        }

        $legacy_map = isset( $this->settings['post_type_engine_map'] ) ? (array) $this->settings['post_type_engine_map'] : [];
        if ( isset( $legacy_map[ $post_type ] ) ) {
            $rule = $this->sanitize_post_type_rule([
                'engine'           => $legacy_map[ $post_type ],
                'fallback_mode'    => 'global_chain',
                'use_global_chain' => 1,
                'rule_source'      => 'post_type_engine_map',
            ]);
            if ( ! empty( $rule ) ) {
                $rule['rule_source'] = 'post_type_engine_map';
                return $rule;
            }
        }

        return [];
    }

    protected function build_post_type_debug_context( $post_type, $is_singular, array $post_type_rule = [] ) {
        $post_type          = sanitize_key( (string) $post_type );
        $post_type_map      = isset( $this->settings['post_type_rule_map'] ) ? (array) $this->settings['post_type_rule_map'] : [];
        $post_type_rules    = isset( $this->settings['post_type_rules'] ) && is_array( $this->settings['post_type_rules'] ) ? (array) $this->settings['post_type_rules'] : [];
        $legacy_map         = isset( $this->settings['post_type_engine_map'] ) ? (array) $this->settings['post_type_engine_map'] : [];
        $available_keys     = array_keys( $post_type_map );
        $legacy_keys        = array_keys( $legacy_map );
        $configured_sources = [];

        sort( $available_keys );
        sort( $legacy_keys );

        if ( ! empty( $post_type_map ) ) {
            $configured_sources[] = 'post_type_rule_map';
        }

        if ( ! empty( $post_type_rules ) ) {
            $configured_sources[] = 'post_type_rules';
        }

        if ( ! empty( $legacy_map ) ) {
            $configured_sources[] = 'post_type_engine_map';
        }

        if ( ! $is_singular ) {
            $lookup_reason = 'not_singular';
        } elseif ( '' === $post_type ) {
            $lookup_reason = 'empty_post_type';
        } elseif ( ! empty( $post_type_rule['engine'] ) ) {
            $lookup_reason = 'matched_' . ( isset( $post_type_rule['rule_source'] ) ? sanitize_key( (string) $post_type_rule['rule_source'] ) : 'post_type_map' );
        } elseif ( isset( $post_type_map[ $post_type ] ) ) {
            $lookup_reason = 'map_entry_invalid_or_sanitized_empty';
        } elseif ( in_array( $post_type, $legacy_keys, true ) ) {
            $lookup_reason = 'legacy_map_entry_invalid_or_sanitized_empty';
        } elseif ( ! empty( $post_type_rules ) ) {
            $lookup_reason = 'no_matching_rule_in_configured_post_type_rules';
        } else {
            $lookup_reason = 'no_post_type_rules_configured';
        }

        return [
            'post_type_rule_lookup_reason'       => $lookup_reason,
            'available_post_type_rule_keys'      => array_values( $available_keys ),
            'legacy_post_type_rule_keys'         => array_values( $legacy_keys ),
            'configured_post_type_rules_count'   => count( $post_type_rules ),
            'configured_post_type_rule_sources'  => $configured_sources,
            'configured_post_type_fallback_mode' => isset( $post_type_rule['fallback_mode'] ) ? sanitize_key( (string) $post_type_rule['fallback_mode'] ) : '',
            'post_type_rule_engine'              => isset( $post_type_rule['engine'] ) ? sanitize_key( (string) $post_type_rule['engine'] ) : '',
            'post_type_rule_source_runtime'      => isset( $post_type_rule['rule_source'] ) ? sanitize_key( (string) $post_type_rule['rule_source'] ) : 'none',
            'post_type_rule_map_has_key'         => ( '' !== $post_type && array_key_exists( $post_type, $post_type_map ) ) ? 1 : 0,
            'legacy_post_type_map_has_key'       => ( '' !== $post_type && array_key_exists( $post_type, $legacy_map ) ) ? 1 : 0,
        ];
    }

    public function resolve_engine_decision( $target_language_code, array $context = [] ) {
        $lang            = $this->normalizer->normalize( $target_language_code );
        $language_map    = isset( $this->settings['language_engine_map'] ) ? (array) $this->settings['language_engine_map'] : [];
        $fallback_map    = isset( $this->settings['fallback_map'] ) ? (array) $this->settings['fallback_map'] : [];
        $default         = $this->get_default_engine_slug();
        $post_type       = isset( $context['post_type'] ) ? sanitize_key( (string) $context['post_type'] ) : '';
        $context_type    = isset( $context['context_type'] ) ? sanitize_key( (string) $context['context_type'] ) : '';
        $object_id       = isset( $context['object_id'] ) ? (int) $context['object_id'] : 0;
        $is_singular     = ! empty( $context['is_singular'] );
        $post_type_rule  = ( $is_singular && '' !== $post_type ) ? $this->find_post_type_rule( $post_type ) : [];
        $post_type_debug = $this->build_post_type_debug_context( $post_type, $is_singular, $post_type_rule );
        $language_engine = isset( $language_map[ $lang ] ) ? sanitize_key( (string) $language_map[ $lang ] ) : '';
        $fallback_engine = isset( $fallback_map[ $lang ] ) ? sanitize_key( (string) $fallback_map[ $lang ] ) : '';

        if ( ! empty( $post_type_rule['engine'] ) ) {
            return [
                'selected_engine'                   => $post_type_rule['engine'],
                'route_source'                      => 'post_type_map',
                'matched_rule'                      => $post_type . ' = ' . $post_type_rule['engine'],
                'default_engine'                    => $default,
                'normalized_language'               => $lang,
                'matched_post_type'                 => $post_type,
                'context_type'                      => $context_type,
                'object_id'                         => $object_id,
                'is_singular'                       => $is_singular,
                'fallback_mode'                     => $post_type_rule['fallback_mode'],
                'runtime_fallback_mode'             => $post_type_rule['fallback_mode'],
                'runtime_fallback_mode_source'      => 'post_type_rule',
                'use_global_chain'                  => ! empty( $post_type_rule['use_global_chain'] ) ? 1 : 0,
                'post_type_rule_found'              => 1,
                'post_type_rule_source'             => $post_type_rule['rule_source'] ?? 'post_type_map',
                'configured_post_type_fallback_mode'=> $post_type_debug['configured_post_type_fallback_mode'],
                'post_type_rule_lookup_reason'      => $post_type_debug['post_type_rule_lookup_reason'],
                'available_post_type_rule_keys'     => $post_type_debug['available_post_type_rule_keys'],
                'legacy_post_type_rule_keys'        => $post_type_debug['legacy_post_type_rule_keys'],
                'configured_post_type_rules_count'  => $post_type_debug['configured_post_type_rules_count'],
                'configured_post_type_rule_sources' => $post_type_debug['configured_post_type_rule_sources'],
                'matched_post_type_rule_engine'     => $post_type_debug['post_type_rule_engine'],
                'post_type_rule_map_has_key'        => $post_type_debug['post_type_rule_map_has_key'],
                'legacy_post_type_map_has_key'      => $post_type_debug['legacy_post_type_map_has_key'],
                'configured_language_rule_engine'   => $language_engine,
                'configured_fallback_rule_engine'   => $fallback_engine,
            ];
        }

        if ( '' !== $language_engine ) {
            return [
                'selected_engine'                   => $language_engine,
                'route_source'                      => 'language_map',
                'matched_rule'                      => $lang . ' = ' . $language_engine,
                'default_engine'                    => $default,
                'normalized_language'               => $lang,
                'matched_post_type'                 => $post_type,
                'context_type'                      => $context_type,
                'object_id'                         => $object_id,
                'is_singular'                       => $is_singular,
                'fallback_mode'                     => 'global_chain',
                'runtime_fallback_mode'             => 'global_chain',
                'runtime_fallback_mode_source'      => 'language_map_implicit_global_chain',
                'use_global_chain'                  => 1,
                'post_type_rule_found'              => 0,
                'post_type_rule_source'             => 'none',
                'configured_post_type_fallback_mode'=> $post_type_debug['configured_post_type_fallback_mode'],
                'post_type_rule_lookup_reason'      => $post_type_debug['post_type_rule_lookup_reason'],
                'available_post_type_rule_keys'     => $post_type_debug['available_post_type_rule_keys'],
                'legacy_post_type_rule_keys'        => $post_type_debug['legacy_post_type_rule_keys'],
                'configured_post_type_rules_count'  => $post_type_debug['configured_post_type_rules_count'],
                'configured_post_type_rule_sources' => $post_type_debug['configured_post_type_rule_sources'],
                'matched_post_type_rule_engine'     => $post_type_debug['post_type_rule_engine'],
                'post_type_rule_map_has_key'        => $post_type_debug['post_type_rule_map_has_key'],
                'legacy_post_type_map_has_key'      => $post_type_debug['legacy_post_type_map_has_key'],
                'configured_language_rule_engine'   => $language_engine,
                'configured_fallback_rule_engine'   => $fallback_engine,
            ];
        }

        return [
            'selected_engine'                   => $default,
            'route_source'                      => 'default_engine',
            'matched_rule'                      => '',
            'default_engine'                    => $default,
            'normalized_language'               => $lang,
            'matched_post_type'                 => $post_type,
            'context_type'                      => $context_type,
            'object_id'                         => $object_id,
            'is_singular'                       => $is_singular,
            'fallback_mode'                     => 'global_chain',
            'runtime_fallback_mode'             => 'global_chain',
            'runtime_fallback_mode_source'      => 'default_engine_implicit_global_chain',
            'use_global_chain'                  => 1,
            'post_type_rule_found'              => 0,
            'post_type_rule_source'             => 'none',
            'configured_post_type_fallback_mode'=> $post_type_debug['configured_post_type_fallback_mode'],
            'post_type_rule_lookup_reason'      => $post_type_debug['post_type_rule_lookup_reason'],
            'available_post_type_rule_keys'     => $post_type_debug['available_post_type_rule_keys'],
            'legacy_post_type_rule_keys'        => $post_type_debug['legacy_post_type_rule_keys'],
            'configured_post_type_rules_count'  => $post_type_debug['configured_post_type_rules_count'],
            'configured_post_type_rule_sources' => $post_type_debug['configured_post_type_rule_sources'],
            'matched_post_type_rule_engine'     => $post_type_debug['post_type_rule_engine'],
            'post_type_rule_map_has_key'        => $post_type_debug['post_type_rule_map_has_key'],
            'legacy_post_type_map_has_key'      => $post_type_debug['legacy_post_type_map_has_key'],
            'configured_language_rule_engine'   => $language_engine,
            'configured_fallback_rule_engine'   => $fallback_engine,
        ];
    }

    public function resolve_engine_slug( $target_language_code, array $context = [] ) {
        $decision = $this->resolve_engine_decision( $target_language_code, $context );
        return $decision['selected_engine'];
    }

    public function build_fallback_engine_decisions( $target_language_code, $current_engine_slug, array $primary_decision = [] ) {
        $lang         = $this->normalizer->normalize( $target_language_code );
        $language_map = isset( $this->settings['language_engine_map'] ) ? (array) $this->settings['language_engine_map'] : [];
        $fallback_map = isset( $this->settings['fallback_map'] ) ? (array) $this->settings['fallback_map'] : [];
        $default      = $this->get_default_engine_slug();
        $attempts     = [];
        $seen         = [];

        $add_attempt = static function( &$attempts, &$seen, $engine_slug, $source, $matched_rule, $reason ) use ( $lang, $default, $current_engine_slug ) {
            $engine_slug = sanitize_key( (string) $engine_slug );
            if ( '' === $engine_slug || $engine_slug === $current_engine_slug || isset( $seen[ $engine_slug ] ) ) {
                return;
            }

            $seen[ $engine_slug ] = true;
            $attempts[] = [
                'fallback_engine'     => $engine_slug,
                'fallback_source'     => $source,
                'matched_rule'        => $matched_rule,
                'default_engine'      => $default,
                'current_engine'      => $current_engine_slug,
                'normalized_language' => $lang,
                'reason'              => $reason,
                'ignored_rule'        => '',
                'ignored_reason'      => '',
            ];
        };

        $fallback_mode        = isset( $primary_decision['fallback_mode'] ) ? sanitize_key( (string) $primary_decision['fallback_mode'] ) : '';
        if ( ! in_array( $fallback_mode, [ 'none', 'default_only', 'global_chain' ], true ) ) {
            $fallback_mode = ! empty( $primary_decision['use_global_chain'] ) ? 'global_chain' : 'default_only';
        }
        $continue_global_chain = 'global_chain' === $fallback_mode;
        $is_post_type_route    = isset( $primary_decision['route_source'] ) && 'post_type_map' === $primary_decision['route_source'];

        if ( $is_post_type_route && 'none' === $fallback_mode ) {
            return [];
        }

        if ( $is_post_type_route && $continue_global_chain && isset( $language_map[ $lang ] ) ) {
            $add_attempt( $attempts, $seen, $language_map[ $lang ], 'language_map', $lang . ' = ' . $language_map[ $lang ], 'post_type_rule_failed_then_language_map' );
        }

        if ( ( ! $is_post_type_route || $continue_global_chain ) && isset( $fallback_map[ $lang ] ) ) {
            $add_attempt( $attempts, $seen, $fallback_map[ $lang ], 'fallback_map', $lang . ' = ' . $fallback_map[ $lang ], 'language_or_post_type_rule_failed_then_fallback_map' );
        }

        $add_attempt( $attempts, $seen, $default, 'default_engine', '', 'fallback_to_default_engine' );

        return $attempts;
    }

    public function resolve_fallback_engine_decision( $target_language_code, $current_engine_slug, array $primary_decision = [] ) {
        $attempts = $this->build_fallback_engine_decisions( $target_language_code, $current_engine_slug, $primary_decision );
        if ( ! empty( $attempts ) ) {
            return $attempts[0];
        }

        $lang           = $this->normalizer->normalize( $target_language_code );
        $map            = isset( $this->settings['fallback_map'] ) ? (array) $this->settings['fallback_map'] : [];
        $default        = $this->get_default_engine_slug();
        $ignored_rule   = '';
        $ignored_reason = '';

        if ( isset( $map[ $lang ] ) && ! empty( $map[ $lang ] ) ) {
            $explicit = $map[ $lang ];
            $rule     = $lang . ' = ' . $explicit;

            if ( $explicit !== $current_engine_slug ) {
                return [
                    'fallback_engine'     => $explicit,
                    'fallback_source'     => 'fallback_map',
                    'matched_rule'        => $rule,
                    'default_engine'      => $default,
                    'current_engine'      => $current_engine_slug,
                    'normalized_language' => $lang,
                    'reason'              => '',
                    'ignored_rule'        => '',
                    'ignored_reason'      => '',
                ];
            }

            $ignored_rule   = $rule;
            $ignored_reason = 'fallback_rule_same_as_current_engine';
        }

        if ( $default !== $current_engine_slug ) {
            return [
                'fallback_engine'     => $default,
                'fallback_source'     => 'default_engine',
                'matched_rule'        => '',
                'default_engine'      => $default,
                'current_engine'      => $current_engine_slug,
                'normalized_language' => $lang,
                'reason'              => '',
                'ignored_rule'        => $ignored_rule,
                'ignored_reason'      => $ignored_reason,
            ];
        }

        return [
            'fallback_engine'     => null,
            'fallback_source'     => 'none',
            'matched_rule'        => '',
            'default_engine'      => $default,
            'current_engine'      => $current_engine_slug,
            'normalized_language' => $lang,
            'reason'              => $ignored_reason ? $ignored_reason : 'default_engine_same_as_current_engine',
            'ignored_rule'        => $ignored_rule,
            'ignored_reason'      => $ignored_reason,
        ];
    }

    public function resolve_fallback_engine_slug( $target_language_code, $current_engine_slug ) {
        $decision = $this->resolve_fallback_engine_decision( $target_language_code, $current_engine_slug );
        return $decision['fallback_engine'];
    }
}
