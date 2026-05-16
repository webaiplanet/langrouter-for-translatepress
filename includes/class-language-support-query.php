<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPRE_Language_Support_Query {
    protected $router_settings;
    protected $tp_settings;
    protected $logger;

    public function __construct( array $router_settings = [], array $tp_settings = [] ) {
        $this->router_settings = ! empty( $router_settings ) ? $router_settings : TPRE_Admin_Settings::get_settings();
        $this->tp_settings     = ! empty( $tp_settings ) ? $tp_settings : get_option( 'trp_settings', [] );
        $this->logger          = new TPRE_Logger( false );
    }

    public static function get_engine_choices() {
        return [
            'volc'     => __( '火山方舟', 'langrouter-for-translatepress' ),
            'qwen'     => __( 'Qwen', 'langrouter-for-translatepress' ),
            'hunyuan'  => __( 'Hunyuan', 'langrouter-for-translatepress' ),
            'deepl'    => __( 'DeepL', 'langrouter-for-translatepress' ),
        ];
    }

    public function query( $input_language, $selected_engine ) {
        $raw_input       = is_string( $input_language ) ? trim( $input_language ) : '';
        $selected_engine = sanitize_key( (string) $selected_engine );
        $engine_choices  = self::get_engine_choices();

        if ( '' === $raw_input ) {
            return [
                'ok'      => false,
                'message' => __( '请输入语言后再查询。', 'langrouter-for-translatepress' ),
            ];
        }

        if ( ! isset( $engine_choices[ $selected_engine ] ) ) {
            return [
                'ok'      => false,
                'message' => __( '请选择要检查的模型。', 'langrouter-for-translatepress' ),
            ];
        }

        $normalized_input   = $this->normalize_query_language( $raw_input );
        $provider_results   = [];
        $all_model_results  = [];
        $supported_by       = [];

        foreach ( array_keys( $engine_choices ) as $engine_slug ) {
            $rows                            = $this->get_engine_model_results( $engine_slug, $normalized_input );
            $provider_results[ $engine_slug ] = $this->build_provider_summary( $engine_slug, $rows );
            $all_model_results               = array_merge( $all_model_results, $rows );
            foreach ( $rows as $row ) {
                if ( ! empty( $row['supported'] ) ) {
                    $supported_by[] = $row;
                }
            }
        }

        return [
            'ok'               => true,
            'raw_input'        => $raw_input,
            'normalized_input' => $normalized_input,
            'selected'         => $provider_results[ $selected_engine ] ?? [],
            'provider_results' => $provider_results,
            'supported_by'     => $supported_by,
            'all_model_results'=> $all_model_results,
        ];
    }

    public function normalize_query_language( $language ) {
        $language = trim( (string) $language );
        if ( '' === $language ) {
            return '';
        }

        $compact = strtolower( str_replace( [ '-', ' ' ], '_', $language ) );
        $aliases = $this->get_language_aliases();
        if ( isset( $aliases[ $compact ] ) ) {
            return $aliases[ $compact ];
        }

        if ( preg_match( '/^[a-z]{2,8}(?:[_-][a-z0-9]{2,8})*$/i', $language ) ) {
            return $this->normalize_locale_like_code( $language );
        }

        return $language;
    }

    protected function get_language_aliases() {
        return [
            '中文'                => 'zh',
            '汉语'                => 'zh',
            '簡體中文'            => 'zh',
            '简体中文'            => 'zh',
            'chinese'             => 'zh',
            'simplified_chinese'  => 'zh',
            'simplifiedchinese'   => 'zh',
            '繁体中文'            => 'zh_Hant',
            '繁體中文'            => 'zh_Hant',
            '繁中'                => 'zh_Hant',
            'traditional_chinese' => 'zh_Hant',
            'traditionalchinese'  => 'zh_Hant',
            'cantonese'           => 'yue',
            '粤语'                => 'yue',
            '廣東話'              => 'yue',
            '广东话'              => 'yue',
            '英文'                => 'en',
            '英语'                => 'en',
            'english'             => 'en',
            '日语'                => 'ja',
            '日文'                => 'ja',
            'japanese'            => 'ja',
            '韩语'                => 'ko',
            '韓語'                => 'ko',
            'korean'              => 'ko',
            '法语'                => 'fr',
            'french'              => 'fr',
            '德语'                => 'de',
            'german'              => 'de',
            '西班牙语'            => 'es',
            'spanish'             => 'es',
            '葡萄牙语'            => 'pt',
            'portuguese'          => 'pt',
            '俄语'                => 'ru',
            'russian'             => 'ru',
            '阿拉伯语'            => 'ar',
            'arabic'              => 'ar',
            '泰语'                => 'th',
            'thai'                => 'th',
            '越南语'              => 'vi',
            'vietnamese'          => 'vi',
            '挪威博克马尔语'      => 'nb',
            'norwegian_bokmal'    => 'nb',
            'norwegianbokmal'     => 'nb',
            'bokmal'              => 'nb',
        ];
    }

    protected function normalize_locale_like_code( $language ) {
        $language = str_replace( '-', '_', trim( (string) $language ) );
        if ( '' === $language ) {
            return '';
        }

        $parts = explode( '_', $language );
        if ( empty( $parts ) ) {
            return $language;
        }

        $parts[0] = strtolower( (string) $parts[0] );
        foreach ( $parts as $index => $part ) {
            if ( 0 === $index ) {
                continue;
            }

            $part = (string) $part;
            if ( '' === $part ) {
                continue;
            }

            if ( 2 === strlen( $part ) && ctype_alpha( $part ) ) {
                $parts[ $index ] = strtoupper( $part );
                continue;
            }

            if ( ctype_alpha( $part ) ) {
                $parts[ $index ] = ucfirst( strtolower( $part ) );
                continue;
            }

            $parts[ $index ] = strtoupper( $part );
        }

        return implode( '_', $parts );
    }

    protected function get_engine_enabled( $engine_slug ) {
        return ! empty( $this->router_settings['models'][ $engine_slug ]['enabled'] );
    }

    protected function get_current_engine_model( $engine_slug ) {
        $model = isset( $this->router_settings['models'][ $engine_slug ]['model'] ) ? trim( (string) $this->router_settings['models'][ $engine_slug ]['model'] ) : '';
        if ( '' !== $model ) {
            return $model;
        }

        if ( 'deepl' === $engine_slug ) {
            return 'deepl';
        }

        if ( 'volc' === $engine_slug ) {
            return 'volcengine-ark';
        }

        return $engine_slug;
    }

    protected function build_provider_summary( $engine_slug, array $rows ) {
        $choices      = self::get_engine_choices();
        $current_row  = null;
        $supported    = [];

        foreach ( $rows as $row ) {
            if ( ! empty( $row['is_current'] ) && null === $current_row ) {
                $current_row = $row;
            }
            if ( ! empty( $row['supported'] ) ) {
                $supported[] = $row;
            }
        }

        if ( null === $current_row && ! empty( $rows[0] ) ) {
            $current_row = $rows[0];
        }

        $message = '';
        if ( null !== $current_row && ! empty( $current_row['message'] ) ) {
            $message = $current_row['message'];
        }

        return [
            'engine'           => $engine_slug,
            'label'            => $choices[ $engine_slug ] ?? $engine_slug,
            'enabled'          => $this->get_engine_enabled( $engine_slug ),
            'current_model'    => $current_row['model'] ?? $this->get_current_engine_model( $engine_slug ),
            'current_row'      => $current_row,
            'supported_models' => $supported,
            'rows'             => $rows,
            'message'          => $message,
        ];
    }

    protected function get_engine_model_results( $engine_slug, $language_code ) {
        switch ( $engine_slug ) {
            case 'qwen':
                return $this->get_qwen_model_results( $language_code );
            case 'hunyuan':
                return $this->get_hunyuan_model_results( $language_code );
            case 'volc':
                return $this->get_volc_model_results( $language_code );
            case 'deepl':
                return $this->get_deepl_model_results( $language_code );
        }

        return [];
    }

    protected function get_qwen_model_results( $language_code ) {
        $current_model = $this->get_current_engine_model( 'qwen' );
        $models        = [ 'qwen-mt-plus', 'qwen-mt-flash', 'qwen-mt-turbo', 'qwen-mt-lite' ];
        $rows          = [];

        foreach ( $models as $model ) {
            $temp_settings                            = $this->router_settings;
            $temp_settings['models']['qwen']['model'] = $model;
            $client                                   = new TPRE_Qwen_Client( $temp_settings, $this->logger );
            $meta                                     = $client->get_language_support_meta( $language_code );

            $rows[] = [
                'engine'     => 'qwen',
                'label'      => self::get_engine_choices()['qwen'],
                'model'      => $model,
                'enabled'    => $this->get_engine_enabled( 'qwen' ),
                'is_current' => $model === $current_model,
                'supported'  => ! empty( $meta['supported'] ),
                'status'     => ! empty( $meta['supported'] ) ? 'supported' : 'unsupported',
                'source'     => $meta['source'] ?? 'builtin_static',
                'candidates' => isset( $meta['candidates'] ) && is_array( $meta['candidates'] ) ? $meta['candidates'] : [],
                'message'    => '',
            ];
        }

        return $rows;
    }

    protected function get_hunyuan_model_results( $language_code ) {
        $base_client   = new TPRE_Hunyuan_Client( $this->router_settings, $this->logger );
        $current_model = $base_client->get_model();
        $models        = [ 'hunyuan-translation-lite', 'hunyuan-translation', TPRE_Hunyuan_Client::MODEL_MT7B ];
        $rows          = [];

        foreach ( $models as $model ) {
            $temp_settings                               = $this->router_settings;
            $temp_settings['models']['hunyuan']['model'] = $model;
            $client                                      = new TPRE_Hunyuan_Client( $temp_settings, $this->logger );
            if ( method_exists( $client, 'get_language_support_meta' ) ) {
                $meta = $client->get_language_support_meta( $language_code );
            } else {
                $mapped = $client->map_trp_locale_to_hunyuan_code( $language_code );
                $meta   = [
                    'supported'  => '' !== $mapped,
                    'candidates' => '' !== $mapped ? [ $mapped ] : [],
                    'source'     => 'builtin_static',
                    'model'      => $client->get_model(),
                ];
            }

            $rows[] = [
                'engine'     => 'hunyuan',
                'label'      => self::get_engine_choices()['hunyuan'],
                'model'      => $meta['model'] ?? $model,
                'enabled'    => $this->get_engine_enabled( 'hunyuan' ),
                'is_current' => ( $meta['model'] ?? $model ) === $current_model,
                'supported'  => ! empty( $meta['supported'] ),
                'status'     => ! empty( $meta['supported'] ) ? 'supported' : 'unsupported',
                'source'     => $meta['source'] ?? 'builtin_static',
                'candidates' => isset( $meta['candidates'] ) && is_array( $meta['candidates'] ) ? $meta['candidates'] : [],
                'message'    => '',
            ];
        }

        return $rows;
    }

    protected function get_volc_model_results( $language_code ) {
        $client = new TPRE_Volc_Client( $this->tp_settings, $this->router_settings, $this->logger );
        $meta   = $client->get_language_support_meta( $language_code );

        return [
            [
                'engine'     => 'volc',
                'label'      => self::get_engine_choices()['volc'],
                'model'      => 'doubao-seed-translation-250915',
                'enabled'    => $this->get_engine_enabled( 'volc' ),
                'is_current' => true,
                'supported'  => ! empty( $meta['supported'] ),
                'status'     => ! empty( $meta['supported'] ) ? 'supported' : 'unsupported',
                'source'     => $meta['source'] ?? 'builtin_manual_list',
                'candidates' => isset( $meta['candidates'] ) && is_array( $meta['candidates'] ) ? $meta['candidates'] : [],
                'message'    => '',
            ],
        ];
    }

    protected function get_deepl_model_results( $language_code ) {
        $engine   = new TPRE_DeepL_Engine( $this->tp_settings, $this->router_settings, $this->logger );
        $meta     = method_exists( $engine, 'get_language_support_meta' ) ? $engine->get_language_support_meta( $language_code ) : [
            'supported'  => $engine->supports_language( $language_code ),
            'status'     => 'unknown',
            'source'     => 'api_cached',
            'candidates' => $this->build_deepl_candidates( $language_code ),
            'message'    => '',
        ];
        $model_id = __( 'TranslatePress 内置 DeepL', 'langrouter-for-translatepress' );

        return [
            [
                'engine'     => 'deepl',
                'label'      => self::get_engine_choices()['deepl'],
                'model'      => $model_id,
                'enabled'    => $this->get_engine_enabled( 'deepl' ),
                'is_current' => true,
                'supported'  => ! empty( $meta['supported'] ) && 'supported' === ( $meta['status'] ?? '' ),
                'status'     => $meta['status'] ?? ( ! empty( $meta['supported'] ) ? 'supported' : 'unsupported' ),
                'source'     => $meta['source'] ?? 'api_cached',
                'candidates' => isset( $meta['candidates'] ) && is_array( $meta['candidates'] ) ? $meta['candidates'] : $this->build_deepl_candidates( $language_code ),
                'message'    => $meta['message'] ?? '',
            ],
        ];
    }

    protected function build_deepl_candidates( $language_code ) {
        $raw = is_string( $language_code ) ? trim( $language_code ) : '';
        if ( '' === $raw ) {
            return [];
        }

        $candidates = [
            $raw,
            strtolower( $raw ),
            str_replace( '_', '-', strtolower( $raw ) ),
            preg_replace( '/[_-].*$/', '', strtolower( $raw ) ),
        ];

        $normalized = [];
        foreach ( $candidates as $candidate ) {
            if ( ! is_string( $candidate ) ) {
                continue;
            }

            $candidate = trim( $candidate );
            if ( '' === $candidate ) {
                continue;
            }

            $normalized[] = $candidate;
        }

        return array_values( array_unique( $normalized ) );
    }
}
