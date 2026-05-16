<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPRE_OpenCC_Converter {
    protected static function get_request_uri() {
        if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
            return '';
        }

        return sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) );
    }
    /** @var bool */
    protected static $buffer_started = false;

    /** @var int */
    protected static $buffer_level = 0;

    /** @var string */
    protected static $captured_output = '';

    /** @var bool */
    protected static $final_callback_processed = false;

    public static function boot() {
        add_action( 'init', [ __CLASS__, 'maybe_start_buffer' ], 0 );
        add_action( 'template_redirect', [ __CLASS__, 'maybe_start_buffer' ], 0 );
    }

    public static function maybe_start_buffer() {
        if ( self::$buffer_started || is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
            return;
        }

        if ( ! class_exists( 'TPRE_OpenCC_Utils' ) || ! class_exists( 'TPRE_Admin_Settings' ) ) {
            return;
        }

        $router_settings = TPRE_Admin_Settings::get_settings();
        $logger          = self::get_logger( $router_settings );

        if ( 'opencc' !== TPRE_OpenCC_Utils::get_handling_mode( $router_settings ) ) {
            $logger->debug( 'OpenCC 前台转换未启用，跳过输出缓冲', [
                'mode' => TPRE_OpenCC_Utils::get_handling_mode( $router_settings ),
            ] );
            return;
        }

        if ( ! self::is_supported_frontend_request() ) {
            $logger->debug( 'OpenCC 当前请求类型不支持前台整页转换', [
                'request_uri' => self::get_request_uri(),
            ] );
            return;
        }

        self::$buffer_started          = true;
        self::$captured_output         = '';
        self::$final_callback_processed = false;
        ob_start( [ __CLASS__, 'output_buffer_callback' ] );
        self::$buffer_level = ob_get_level();

        $logger->debug( 'OpenCC 已尽早启动全局输出缓冲，等待最外层回调在最终输出阶段统一转换', [
            'request_uri'  => self::get_request_uri(),
            'buffer_level' => self::$buffer_level,
            'handlers'     => function_exists( 'ob_list_handlers' ) ? ob_list_handlers() : [],
        ] );
    }

    public static function output_buffer_callback( $chunk, $phase ) {
        $chunk = is_string( $chunk ) ? $chunk : '';

        if ( ! self::$buffer_started ) {
            return $chunk;
        }

        self::$captured_output .= $chunk;

        if ( 0 === ( (int) $phase & PHP_OUTPUT_HANDLER_FINAL ) ) {
            return '';
        }

        if ( self::$final_callback_processed ) {
            return '';
        }

        self::$final_callback_processed = true;

        if ( ! class_exists( 'TPRE_Admin_Settings' ) ) {
            return self::$captured_output;
        }

        $router_settings = TPRE_Admin_Settings::get_settings();
        $logger          = self::get_logger( $router_settings );
        $raw_html        = self::$captured_output;
        self::$captured_output = '';

        if ( '' === $raw_html ) {
            if ( $logger ) {
                $logger->debug( 'OpenCC 最外层回调已触发，但未捕获到可转换 HTML', [
                    'phase'        => (int) $phase,
                    'buffer_level' => ob_get_level(),
                    'handlers'     => function_exists( 'ob_list_handlers' ) ? ob_list_handlers() : [],
                ] );
            }
            return $chunk;
        }

        $final_html = self::convert_fullpage_html( $raw_html );

        if ( $logger ) {
            $logger->debug( 'OpenCC 最外层回调已完成最终响应转换', [
                'phase'        => (int) $phase,
                'raw_length'   => strlen( $raw_html ),
                'final_length' => strlen( $final_html ),
                'changed'      => $raw_html !== $final_html ? 1 : 0,
                'buffer_level' => ob_get_level(),
                'handlers'     => function_exists( 'ob_list_handlers' ) ? ob_list_handlers() : [],
            ] );
        }

        return $final_html;
    }

    protected static function is_supported_frontend_request() {
        $uri  = self::get_request_uri();
        $path = wp_parse_url( $uri, PHP_URL_PATH );
        $path = is_string( $path ) ? '/' . ltrim( $path, '/' ) : '';

        if ( '' === $path ) {
            return true;
        }

        if ( preg_match( '~/(?:wp-json|wp-admin|wp-content|wp-includes)(?:/|$)~i', $path ) ) {
            return false;
        }

        if ( preg_match( '~/(?:wp-login\.php)(?:$|[/?#])~i', $path ) ) {
            return false;
        }

        if ( preg_match( '/\.(?:js|css|map|json|xml|txt|ico|png|jpg|jpeg|gif|webp|svg|woff2?|ttf|eot|pdf|zip)$/i', $path ) ) {
            return false;
        }

        return true;
    }

    public static function convert_fullpage_html( $html ) {
        if ( ! is_string( $html ) || '' === $html ) {
            return $html;
        }

        if ( ! class_exists( 'TPRE_OpenCC_Utils' ) || ! class_exists( 'TPRE_Admin_Settings' ) ) {
            return $html;
        }

        $router_settings = TPRE_Admin_Settings::get_settings();
        $logger          = self::get_logger( $router_settings );
        $language_code   = TPRE_OpenCC_Utils::get_current_request_language_code();
        $normalized_lang = TPRE_OpenCC_Utils::normalize_traditional_locale( $language_code );

        if ( ! TPRE_OpenCC_Utils::should_handle_with_opencc( $language_code, $router_settings ) ) {
            if ( $logger ) {
                $logger->debug( 'OpenCC 未执行前台转换：当前页面语言未命中繁体处理规则', [
                    'detected_language'   => is_string( $language_code ) ? $language_code : '',
                    'normalized_language' => $normalized_lang,
                    'mode'                => TPRE_OpenCC_Utils::get_handling_mode( $router_settings ),
                ] );
            }
            return $html;
        }

        $exclude_config = self::get_exclude_config();
        $restore_map    = [];
        $protected      = self::protect_excluded_blocks( $html, $exclude_config, $restore_map );
        $converted      = self::run_opencc( $protected, $language_code, $logger );

        if ( ! is_string( $converted ) || '' === $converted ) {
            $converted = $protected;
        }

        $restored = self::restore_excluded_blocks( $converted, $restore_map );

        if ( $logger ) {
            $logger->debug( 'OpenCC 已执行前台整页转换', [
                'detected_language'   => is_string( $language_code ) ? $language_code : '',
                'normalized_language' => $normalized_lang,
                'html_length'         => strlen( $html ),
                'changed'             => $restored !== $html ? 1 : 0,
            ] );

            $logger->debug( 'OpenCC 页面转换详细诊断', array_merge(
                [
                    'detected_language'   => is_string( $language_code ) ? $language_code : '',
                    'normalized_language' => $normalized_lang,
                ],
                self::build_detailed_report( $html, $restored, $exclude_config, $restore_map )
            ) );
        }

        return $restored;
    }


    protected static function get_logger( array $router_settings = [] ) {
        return class_exists( 'TPRE_Logger' ) ? new TPRE_Logger( ! empty( $router_settings['log_enabled'] ) ) : null;
    }

    protected static function get_opencc_binary_candidates() {
        $candidates = [];

        $filtered = apply_filters( 'tpre_opencc_binary_path', '/usr/bin/opencc' );
        if ( is_string( $filtered ) && '' !== trim( $filtered ) ) {
            $candidates[] = trim( $filtered );
        }

        $candidates = array_merge( $candidates, [
            '/usr/bin/opencc',
            '/usr/local/bin/opencc',
            'opencc',
        ] );

        if ( function_exists( 'shell_exec' ) ) {
            $resolved = @shell_exec( 'command -v opencc 2>/dev/null' );
            $resolved = is_string( $resolved ) ? trim( $resolved ) : '';
            if ( '' !== $resolved ) {
                $candidates[] = $resolved;
            }
        }

        return array_values( array_unique( array_filter( array_map( 'trim', $candidates ) ) ) );
    }

    protected static function get_exclude_config() {
        $config = [
            'classes' => [
                'trp-ls-language-name',
                'trp-ls-shortcode-current-language',
                'opencc-skip',
            ],
            'attrs' => [
                'data-no-opencc',
                'translate=no',
                'x-if',
                'x-text',
                'x-html',
                'v-if',
                'v-show',
                'v-text',
                'v-html',
            ],
            'tags' => [
                'template',
                'script',
                'style',
                'pre',
                'code',
                'textarea',
            ],
        ];

        $filtered = apply_filters( 'tpre_opencc_exclude_config', $config );

        return is_array( $filtered ) ? $filtered : $config;
    }

    protected static function run_opencc( $html, $language_code, $logger = null ) {
        if ( ! is_string( $html ) || '' === $html ) {
            return $html;
        }

        if ( ! function_exists( 'shell_exec' ) ) {
            if ( $logger ) {
                $logger->error( 'OpenCC 执行失败：shell_exec 不可用' );
            }
            return $html;
        }

        $binaries = self::get_opencc_binary_candidates();

        if ( empty( $binaries ) ) {
            $binaries = [ '/usr/bin/opencc', '/usr/local/bin/opencc', 'opencc' ];
        }

        $config  = TPRE_OpenCC_Utils::get_opencc_config_for_language( $language_code );
        $tmp_in  = function_exists( 'wp_tempnam' ) ? wp_tempnam( 'tpre_opencc_in' ) : tempnam( sys_get_temp_dir(), 'tpre_opencc_in' );
        $tmp_out = function_exists( 'wp_tempnam' ) ? wp_tempnam( 'tpre_opencc_out' ) : tempnam( sys_get_temp_dir(), 'tpre_opencc_out' );

        if ( ! $tmp_in || ! $tmp_out ) {
            if ( $logger ) {
                $logger->error( 'OpenCC 执行失败：临时文件创建失败', [
                    'tmp_in'  => (string) $tmp_in,
                    'tmp_out' => (string) $tmp_out,
                ] );
            }
            return $html;
        }

        file_put_contents( $tmp_in, $html );

        $attempt_logs      = [];
        $successful_binary = '';
        $converted         = false;

        foreach ( $binaries as $binary ) {
            $command = escapeshellarg( $binary )
                . ' -c ' . escapeshellarg( $config )
                . ' -i ' . escapeshellarg( $tmp_in )
                . ' -o ' . escapeshellarg( $tmp_out )
                . ' 2>&1';

            $shell_output = @shell_exec( $command );
            $candidate_out = @file_get_contents( $tmp_out );
            $attempt_logs[] = [
                'binary'       => $binary,
                'shell_output' => is_string( $shell_output ) ? trim( $shell_output ) : '',
                'produced'     => ( false !== $candidate_out && '' !== $candidate_out ) ? 1 : 0,
            ];

            if ( false !== $candidate_out && '' !== $candidate_out ) {
                $successful_binary = $binary;
                $converted         = $candidate_out;
                break;
            }

            @file_put_contents( $tmp_out, '' );
        }

        if ( is_string( $tmp_in ) && '' !== $tmp_in ) {
            wp_delete_file( $tmp_in );
        }
        if ( is_string( $tmp_out ) && '' !== $tmp_out ) {
            wp_delete_file( $tmp_out );
        }

        if ( false !== $converted && '' !== $converted ) {
            if ( $logger ) {
                $logger->debug( 'OpenCC 命令执行完成', [
                    'binary'        => $successful_binary,
                    'config'        => $config,
                    'changed'       => $converted !== $html ? 1 : 0,
                    'attempt_count' => count( $attempt_logs ),
                    'attempts'      => $attempt_logs,
                ] );
            }
            return $converted;
        }

        if ( $logger ) {
            $logger->error( 'OpenCC 命令执行失败：所有候选路径均未产生有效输出，回退原始 HTML', [
                'config'        => $config,
                'attempt_count' => count( $attempt_logs ),
                'attempts'      => $attempt_logs,
            ] );
        }

        return $html;
    }

    protected static function protect_excluded_blocks( $html, array $exclude_config, array &$restore_map ) {
        $restore_map = [];
        if ( ! is_string( $html ) || '' === $html ) {
            return $html;
        }

        $counter = 0;
        $tags    = isset( $exclude_config['tags'] ) ? (array) $exclude_config['tags'] : [];
        foreach ( $tags as $tag ) {
            $tag = strtolower( trim( (string) $tag ) );
            if ( '' === $tag ) {
                continue;
            }

            $pattern = '~<(' . preg_quote( $tag, '~' ) . ')\b[^>]*>.*?</\1>~is';
            $html    = preg_replace_callback(
                $pattern,
                static function( $matches ) use ( &$restore_map, &$counter ) {
                    $placeholder                = '<!--TPRE_OPENCC_EXCLUDE_' . $counter . '-->';
                    $restore_map[ $placeholder ] = $matches[0];
                    $counter++;
                    return $placeholder;
                },
                $html
            );
        }

        $classes = isset( $exclude_config['classes'] ) ? (array) $exclude_config['classes'] : [];
        foreach ( $classes as $class_name ) {
            $class_name = trim( (string) $class_name );
            if ( '' === $class_name ) {
                continue;
            }

            $pattern = '~<([a-zA-Z][a-zA-Z0-9:_-]*)\b([^>]*\bclass\s*=\s*(["\"])(?:(?!\3).)*\b' . preg_quote( $class_name, '~' ) . '\b(?:(?!\3).)*\3[^>]*)>(.*?)</\1>~is';
            for ( $loop = 0; $loop < 6; $loop++ ) {
                $before = $html;
                $html   = preg_replace_callback(
                    $pattern,
                    static function( $matches ) use ( &$restore_map, &$counter ) {
                        $placeholder                = '<!--TPRE_OPENCC_EXCLUDE_' . $counter . '-->';
                        $restore_map[ $placeholder ] = $matches[0];
                        $counter++;
                        return $placeholder;
                    },
                    $html
                );

                if ( $before === $html ) {
                    break;
                }
            }
        }

        $attrs = isset( $exclude_config['attrs'] ) ? (array) $exclude_config['attrs'] : [];
        foreach ( $attrs as $attr_rule ) {
            $attr_rule = trim( (string) $attr_rule );
            if ( '' === $attr_rule ) {
                continue;
            }

            if ( false !== strpos( $attr_rule, '=' ) ) {
                list( $attr_key, $attr_value ) = array_map( 'trim', explode( '=', $attr_rule, 2 ) );
                $attr_key   = preg_replace( '/[^a-zA-Z0-9_\-:]/', '', $attr_key );
                $attr_value = preg_replace( '/["\']/', '', $attr_value );
                if ( '' === $attr_key ) {
                    continue;
                }

                $pattern = '~<([a-zA-Z][a-zA-Z0-9:_-]*)\b([^>]*\b' . preg_quote( $attr_key, '~' ) . '\s*=\s*(["\"])' . preg_quote( $attr_value, '~' ) . '\3[^>]*)>(.*?)</\1>~is';
            } else {
                $attr_key = preg_replace( '/[^a-zA-Z0-9_\-:]/', '', $attr_rule );
                if ( '' === $attr_key ) {
                    continue;
                }

                $pattern = '~<([a-zA-Z][a-zA-Z0-9:_-]*)\b([^>]*\b' . preg_quote( $attr_key, '~' ) . '\b[^>]*)>(.*?)</\1>~is';
            }

            for ( $loop = 0; $loop < 6; $loop++ ) {
                $before = $html;
                $html   = preg_replace_callback(
                    $pattern,
                    static function( $matches ) use ( &$restore_map, &$counter ) {
                        $placeholder                = '<!--TPRE_OPENCC_EXCLUDE_' . $counter . '-->';
                        $restore_map[ $placeholder ] = $matches[0];
                        $counter++;
                        return $placeholder;
                    },
                    $html
                );

                if ( $before === $html ) {
                    break;
                }
            }
        }

        return $html;
    }

    protected static function restore_excluded_blocks( $html, array $restore_map ) {
        if ( ! is_string( $html ) || '' === $html || empty( $restore_map ) ) {
            return $html;
        }

        return strtr( $html, $restore_map );
    }


    protected static function build_detailed_report( $before_html, $after_html, array $exclude_config, array $restore_map ) {
        $before_sections = self::collect_section_report( $before_html );
        $after_sections  = self::collect_section_report( $after_html );
        $section_diffs   = [];

        foreach ( $before_sections as $key => $before_section ) {
            $after_section = isset( $after_sections[ $key ] ) && is_array( $after_sections[ $key ] )
                ? $after_sections[ $key ]
                : self::empty_section_report();

            $section_diffs[ $key ] = [
                'changed'                 => $before_section['text'] !== $after_section['text'] ? 1 : 0,
                'before_has_simplified'   => $before_section['has_simplified'],
                'after_has_simplified'    => $after_section['has_simplified'],
                'before_preview'          => $before_section['preview'],
                'after_preview'           => $after_section['preview'],
                'after_simplified_sample' => $after_section['simplified_sample'],
            ];
        }

        return [
            'protected_block_count'       => count( $restore_map ),
            'exclude_summary'             => self::summarize_exclude_hits( $before_html, $exclude_config ),
            'before_simplified_count'     => self::count_simplified_indicators( self::html_to_visible_text( $before_html ) ),
            'after_simplified_count'      => self::count_simplified_indicators( self::html_to_visible_text( $after_html ) ),
            'remaining_simplified_sample' => self::extract_remaining_simplified_samples( $after_html, 10 ),
            'section_diffs'               => $section_diffs,
        ];
    }

    protected static function summarize_exclude_hits( $html, array $exclude_config ) {
        $summary = [
            'tags'    => [],
            'classes' => [],
            'attrs'   => [],
        ];

        if ( ! is_string( $html ) || '' === $html ) {
            return $summary;
        }

        foreach ( (array) ( isset( $exclude_config['tags'] ) ? $exclude_config['tags'] : [] ) as $tag ) {
            $tag = strtolower( trim( (string) $tag ) );
            if ( '' === $tag ) {
                continue;
            }

            $pattern = '~<(' . preg_quote( $tag, '~' ) . ')\b[^>]*>.*?</\1>~is';
            $summary['tags'][ $tag ] = self::safe_match_count( $pattern, $html );
        }

        foreach ( (array) ( isset( $exclude_config['classes'] ) ? $exclude_config['classes'] : [] ) as $class_name ) {
            $class_name = trim( (string) $class_name );
            if ( '' === $class_name ) {
                continue;
            }

            $pattern = '~<([a-zA-Z][a-zA-Z0-9:_-]*)\b([^>]*\bclass\s*=\s*(["\'])(?:(?!\3).)*\b' . preg_quote( $class_name, '~' ) . '\b(?:(?!\3).)*\3[^>]*)>(.*?)</\1>~is';
            $summary['classes'][ $class_name ] = self::safe_match_count( $pattern, $html );
        }

        foreach ( (array) ( isset( $exclude_config['attrs'] ) ? $exclude_config['attrs'] : [] ) as $attr_rule ) {
            $attr_rule = trim( (string) $attr_rule );
            if ( '' === $attr_rule ) {
                continue;
            }

            if ( false !== strpos( $attr_rule, '=' ) ) {
                list( $attr_key, $attr_value ) = array_map( 'trim', explode( '=', $attr_rule, 2 ) );
                $attr_key   = preg_replace( '/[^a-zA-Z0-9_\-:]/', '', $attr_key );
                $attr_value = preg_replace( '/["\']/', '', $attr_value );
                if ( '' === $attr_key ) {
                    continue;
                }

                $pattern = '~<([a-zA-Z][a-zA-Z0-9:_-]*)\b([^>]*\b' . preg_quote( $attr_key, '~' ) . '\s*=\s*(["\'])' . preg_quote( $attr_value, '~' ) . '\3[^>]*)>(.*?)</\1>~is';
            } else {
                $attr_key = preg_replace( '/[^a-zA-Z0-9_\-:]/', '', $attr_rule );
                if ( '' === $attr_key ) {
                    continue;
                }

                $pattern = '~<([a-zA-Z][a-zA-Z0-9:_-]*)\b([^>]*\b' . preg_quote( $attr_key, '~' ) . '\b[^>]*)>(.*?)</\1>~is';
            }

            $summary['attrs'][ $attr_rule ] = self::safe_match_count( $pattern, $html );
        }

        return $summary;
    }

    protected static function safe_match_count( $pattern, $html ) {
        $count = @preg_match_all( $pattern, $html, $matches );
        return false === $count ? 0 : (int) $count;
    }

    protected static function collect_section_report( $html ) {
        return [
            'title_h1'    => self::extract_section_text( $html, '~<h1\b[^>]*>(.*?)</h1>~is', 1 ),
            'breadcrumbs' => self::extract_section_text( $html, '~<div\b[^>]*\bbreadcrumbs\b[^>]*>(.*?)</div>~is', 1 ),
            'entry_intro' => self::extract_entry_intro_text( $html ),
            'top_nav'     => self::extract_section_text( $html, '~<div\b[^>]*id\s*=\s*(["\'])top-menus\1[^>]*>(.*?)</div>~is', 2 ),
            'footer'      => self::extract_section_text( $html, '~<footer\b[^>]*>(.*?)</footer>~is', 1 ),
        ];
    }

    protected static function extract_entry_intro_text( $html ) {
        if ( ! is_string( $html ) || '' === $html ) {
            return self::empty_section_report();
        }

        if ( ! preg_match( '~<div\b[^>]*\bentry-content\b[^>]*>(.*?)</div>~is', $html, $matches ) ) {
            return self::empty_section_report();
        }

        $entry_html = $matches[1];

        if ( preg_match( '~<p\b[^>]*>(.*?)</p>~is', $entry_html, $paragraph ) ) {
            return self::build_section_report( $paragraph[1] );
        }

        return self::build_section_report( $entry_html );
    }

    protected static function extract_section_text( $html, $pattern, $group = 1 ) {
        if ( ! is_string( $html ) || '' === $html ) {
            return self::empty_section_report();
        }

        if ( ! preg_match( $pattern, $html, $matches ) ) {
            return self::empty_section_report();
        }

        return self::build_section_report( isset( $matches[ $group ] ) ? (string) $matches[ $group ] : '' );
    }

    protected static function build_section_report( $html_fragment ) {
        $text = self::normalize_visible_text( self::html_to_visible_text( $html_fragment ) );

        return [
            'text'              => $text,
            'preview'           => self::truncate_text( $text, 140 ),
            'has_simplified'    => self::contains_simplified_indicators( $text ) ? 1 : 0,
            'simplified_sample' => self::extract_simplified_sample_from_text( $text ),
        ];
    }

    protected static function empty_section_report() {
        return [
            'text'              => '',
            'preview'           => '',
            'has_simplified'    => 0,
            'simplified_sample' => '',
        ];
    }

    protected static function html_to_visible_text( $html ) {
        if ( ! is_string( $html ) || '' === $html ) {
            return '';
        }

        $text = html_entity_decode( wp_strip_all_tags( $html, true ), ENT_QUOTES, 'UTF-8' );
        return is_string( $text ) ? $text : '';
    }

    protected static function normalize_visible_text( $text ) {
        $text = is_string( $text ) ? $text : '';
        $text = preg_replace( '/\s+/u', ' ', $text );
        return is_string( $text ) ? trim( $text ) : '';
    }

    protected static function truncate_text( $text, $length = 140 ) {
        $text   = is_string( $text ) ? trim( $text ) : '';
        $length = max( 40, (int) $length );

        if ( '' === $text ) {
            return '';
        }

        if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
            if ( mb_strlen( $text, 'UTF-8' ) <= $length ) {
                return $text;
            }

            return mb_substr( $text, 0, $length, 'UTF-8' ) . '...';
        }

        if ( strlen( $text ) <= $length ) {
            return $text;
        }

        return substr( $text, 0, $length ) . '...';
    }

    protected static function get_simplified_indicator_pattern() {
        return '/[发后云运来过关网页记与这为会点还进选读译转务导实审应广张录怀态总惊戏执扩护报拟拥拨择挥损换据摆摇数断无旧时显术机权条构枪标样桥梦检楼欢汉沟没济浓湾湿满滤灭灵灾爷独狭现电画畅疗皱盘盗监盖着睁矫矿码砖确碍礼祷祸离积称稳穷窥竞笔签简类粮纠红约级纪纯纲纳纵纹纺纽线练组细织终结绕绘给络统经绝继续绿编缘缠罗罚罢职联聪肃肤肠肿胆胶脉脑脚脸艺节苏茎药莱莲获营萧萨蚀蚁虽补装见观规视览觉触话该详语误说诸谋谅请谐谢谣谨谱贝负贡财责贤败账货质贫贪贯贵贷贸费贺资赏赔赖赚赛赞赠赵赶趋跃践踪车转轮软轰轻载较辅辆边辽达迁迈远连迟逻遗邮邻郑鉴针钉钟钢钥钱钻铁铃铜锁锅锈锐错锡锤锦键锯镀镇镜门问间闻阅队阳阴阶际陆陈阵难雾静页顶项顺须顾顿预领颇频题颜额风飞饭饮饰饱馆馈馋驭驱验骑骗骚鲁鲜鸟鸡鸭鸿鹅鹤黄齐齿龙龟拟势吗闭开简]/u';
    }

    protected static function contains_simplified_indicators( $text ) {
        return 1 === preg_match( self::get_simplified_indicator_pattern(), (string) $text );
    }

    protected static function count_simplified_indicators( $text ) {
        $count = @preg_match_all( self::get_simplified_indicator_pattern(), (string) $text, $matches );
        return false === $count ? 0 : (int) $count;
    }

    protected static function extract_simplified_sample_from_text( $text ) {
        $text = self::normalize_visible_text( $text );
        if ( '' === $text ) {
            return '';
        }

        $pattern = '/.{0,24}[发后云运来过关网页记与这为会点还进选读译转务导实审应广张录怀态总惊戏执扩护报拟拥拨择挥损换据摆摇数断无旧时显术机权条构枪标样桥梦检楼欢汉沟没济浓湾湿满滤灭灵灾爷独狭现电画畅疗皱盘盗监盖着睁矫矿码砖确碍礼祷祸离积称稳穷窥竞笔签简类粮纠红约级纪纯纲纳纵纹纺纽线练组细织终结绕绘给络统经绝继续绿编缘缠罗罚罢职联聪肃肤肠肿胆胶脉脑脚脸艺节苏茎药莱莲获营萧萨蚀蚁虽补装见观规视览觉触话该详语误说诸谋谅请谐谢谣谨谱贝负贡财责贤败账货质贫贪贯贵贷贸费贺资赏赔赖赚赛赞赠赵赶趋跃践踪车转轮软轰轻载较辅辆边辽达迁迈远连迟逻遗邮邻郑鉴针钉钟钢钥钱钻铁铃铜锁锅锈锐错锡锤锦键锯镀镇镜门问间闻阅队阳阴阶际陆陈阵难雾静页顶项顺须顾顿预领颇频题颜额风飞饭饮饰饱馆馈馋驭驱验骑骗骚鲁鲜鸟鸡鸭鸿鹅鹤黄齐齿龙龟拟势吗闭开简].{0,24}/u';
        if ( @preg_match( $pattern, $text, $matches ) ) {
            return self::truncate_text( self::normalize_visible_text( $matches[0] ), 80 );
        }

        return '';
    }

    protected static function extract_remaining_simplified_samples( $html, $limit = 8 ) {
        $text = self::normalize_visible_text( self::html_to_visible_text( $html ) );
        if ( '' === $text ) {
            return [];
        }

        $pattern = '/.{0,24}[发后云运来过关网页记与这为会点还进选读译转务导实审应广张录怀态总惊戏执扩护报拟拥拨择挥损换据摆摇数断无旧时显术机权条构枪标样桥梦检楼欢汉沟没济浓湾湿满滤灭灵灾爷独狭现电画畅疗皱盘盗监盖着睁矫矿码砖确碍礼祷祸离积称稳穷窥竞笔签简类粮纠红约级纪纯纲纳纵纹纺纽线练组细织终结绕绘给络统经绝继续绿编缘缠罗罚罢职联聪肃肤肠肿胆胶脉脑脚脸艺节苏茎药莱莲获营萧萨蚀蚁虽补装见观规视览觉触话该详语误说诸谋谅请谐谢谣谨谱贝负贡财责贤败账货质贫贪贯贵贷贸费贺资赏赔赖赚赛赞赠赵赶趋跃践踪车转轮软轰轻载较辅辆边辽达迁迈远连迟逻遗邮邻郑鉴针钉钟钢钥钱钻铁铃铜锁锅锈锐错锡锤锦键锯镀镇镜门问间闻阅队阳阴阶际陆陈阵难雾静页顶项顺须顾顿预领颇频题颜额风飞饭饮饰饱馆馈馋驭驱验骑骗骚鲁鲜鸟鸡鸭鸿鹅鹤黄齐齿龙龟拟势吗闭开简].{0,24}/u';
        $matches = [];
        $samples = [];
        $limit   = max( 1, (int) $limit );

        if ( @preg_match_all( $pattern, $text, $matches ) ) {
            foreach ( (array) ( isset( $matches[0] ) ? $matches[0] : [] ) as $sample ) {
                $sample = self::truncate_text( self::normalize_visible_text( $sample ), 80 );
                if ( '' === $sample ) {
                    continue;
                }

                $samples[ $sample ] = $sample;
                if ( count( $samples ) >= $limit ) {
                    break;
                }
            }
        }

        return array_values( $samples );
    }

}
