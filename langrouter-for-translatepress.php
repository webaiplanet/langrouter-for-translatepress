<?php
/**
 * Plugin Name: LangRouter for TranslatePress
 * Plugin URI: https://www.webaiplanet.com/wordpress/plugins/langrouter-for-translatepress/
 * Description: Route TranslatePress automatic translations across multiple engines with language-based rules, fallback control, and flexible provider routing.
 * Version: 1.1.3
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Author: WebAIPlanet
 * Author URI: https://www.webaiplanet.com/
 * Requires Plugins: translatepress-multilingual
 * Text Domain: langrouter-for-translatepress
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( defined( 'TPRE_VERSION' ) ) {
    return;
}

define( 'TPRE_PLUGIN_FILE', __FILE__ );
define( 'TPRE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'TPRE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TPRE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TPRE_VERSION', '1.1.3' );



if ( ! function_exists( 'tpre_translate' ) ) {
    function tpre_translate( $text ) {
        return is_string( $text ) ? $text : '';
    }
}


if ( ! function_exists( 'tpre_get_configured_backend_locale' ) ) {
    function tpre_get_configured_backend_locale() {
        $locale = '';

        if ( function_exists( 'get_option' ) ) {
            $locale = get_option( 'WPLANG', '' );
        }

        if ( ! is_string( $locale ) || '' === trim( $locale ) ) {
            if ( defined( 'WPLANG' ) && is_string( WPLANG ) && '' !== WPLANG ) {
                $locale = WPLANG;
            } else {
                $locale = 'en_US';
            }
        }

        $locale = str_replace( '-', '_', trim( (string) $locale ) );

        return '' !== $locale ? $locale : 'en_US';
    }
}

if ( ! function_exists( 'tpre_get_log_locale_candidates' ) ) {
    function tpre_get_log_locale_candidates() {
        $candidates = [];

        if ( function_exists( 'tpre_get_configured_backend_locale' ) ) {
            $candidates[] = tpre_get_configured_backend_locale();
        }

        $normalized = [];
        foreach ( $candidates as $locale ) {
            if ( ! is_string( $locale ) || '' === $locale ) {
                continue;
            }
            $locale = str_replace( '-', '_', trim( $locale ) );
            if ( '' === $locale ) {
                continue;
            }
            $normalized[] = $locale;
            if ( false !== strpos( $locale, '_' ) ) {
                $normalized[] = substr( $locale, 0, strpos( $locale, '_' ) );
            }
        }

        $normalized[] = 'zh_CN';

        return array_values( array_unique( array_filter( $normalized ) ) );
    }
}

if ( ! function_exists( 'tpre_get_log_fallback_messages' ) ) {
    function tpre_get_log_fallback_messages() {
        return [
            'en_US' => [
                '实例化子引擎' => 'Instantiate sub-engine',
                '子引擎已禁用，跳过实例化' => 'Sub-engine disabled, skipping instantiation',
                '未找到子引擎工厂，跳过实例化' => 'No sub-engine factory found, skipping instantiation',
                '回退决策' => 'Fallback decision',
                'Router 路由完成' => 'Router route completed',
            ],
            'en' => [
                '实例化子引擎' => 'Instantiate sub-engine',
                '子引擎已禁用，跳过实例化' => 'Sub-engine disabled, skipping instantiation',
                '未找到子引擎工厂，跳过实例化' => 'No sub-engine factory found, skipping instantiation',
                '回退决策' => 'Fallback decision',
                'Router 路由完成' => 'Router route completed',
            ],
            'ja' => [
                '实例化子引擎' => 'サブエンジンを初期化',
                '子引擎已禁用，跳过实例化' => 'サブエンジンは無効のため初期化をスキップ',
                '未找到子引擎工厂，跳过实例化' => 'サブエンジンのファクトリが見つからないため初期化をスキップ',
                '回退决策' => 'フォールバック判定',
                'Router 路由完成' => 'Router のルーティング完了',
            ],
            'ko_KR' => [
                '实例化子引擎' => '하위 엔진 인스턴스화',
                '子引擎已禁用，跳过实例化' => '하위 엔진이 비활성화되어 인스턴스화를 건너뜁니다',
                '未找到子引擎工厂，跳过实例化' => '하위 엔진 팩토리를 찾지 못해 인스턴스화를 건너뜁니다',
                '回退决策' => '폴백 결정',
                'Router 路由完成' => 'Router 라우팅 완료',
            ],
            'ko' => [
                '实例化子引擎' => '하위 엔진 인스턴스화',
                '子引擎已禁用，跳过实例化' => '하위 엔진이 비활성화되어 인스턴스화를 건너뜁니다',
                '未找到子引擎工厂，跳过实例化' => '하위 엔진 팩토리를 찾지 못해 인스턴스화를 건너뜁니다',
                '回退决策' => '폴백 결정',
                'Router 路由完成' => 'Router 라우팅 완료',
            ],
        ];
    }
}

if ( ! function_exists( 'tpre_get_l10n_messages_for_locale' ) ) {
    function tpre_get_l10n_messages_for_locale( $locale ) {
        static $cache = [];

        $locale = is_string( $locale ) ? trim( $locale ) : '';
        if ( '' === $locale ) {
            return [];
        }

        if ( array_key_exists( $locale, $cache ) ) {
            return $cache[ $locale ];
        }

        $path = TPRE_PLUGIN_DIR . 'languages/langrouter-for-translatepress-' . $locale . '.l10n.php';
        if ( ! is_file( $path ) ) {
            $cache[ $locale ] = [];
            return $cache[ $locale ];
        }

        $data = include $path;
        $cache[ $locale ] = ( is_array( $data ) && isset( $data['messages'] ) && is_array( $data['messages'] ) ) ? $data['messages'] : [];

        return $cache[ $locale ];
    }
}



if ( ! function_exists( 'tpre_log_translate_diagnostics' ) ) {
    function tpre_log_translate_diagnostics( $text ) {
        $text = is_string( $text ) ? $text : '';
        $candidates = function_exists( 'tpre_get_log_locale_candidates' ) ? tpre_get_log_locale_candidates() : [];
        $fallback_maps = function_exists( 'tpre_get_log_fallback_messages' ) ? tpre_get_log_fallback_messages() : [];
        $result = [
            'text' => $text,
            'candidates' => $candidates,
            'determine_locale' => function_exists( 'determine_locale' ) ? determine_locale() : '',
            'user_locale' => function_exists( 'get_user_locale' ) ? get_user_locale() : '',
            'site_locale' => function_exists( 'get_locale' ) ? get_locale() : '',
            'configured_backend_locale' => function_exists( 'tpre_get_configured_backend_locale' ) ? tpre_get_configured_backend_locale() : '',
            'is_admin' => function_exists( 'is_admin' ) ? ( is_admin() ? 1 : 0 ) : 0,
            'resolved_locale' => '',
            'source' => 'original',
            'translated' => $text,
            'available_l10n_locales' => [],
        ];

        static $available_l10n_locales = null;

        if ( null === $available_l10n_locales ) {
            $available_l10n_locales = [];
            foreach ( glob( TPRE_PLUGIN_DIR . 'languages/langrouter-for-translatepress-*.l10n.php' ) ?: [] as $file ) {
                $name = basename( $file, '.l10n.php' );
                $name = str_replace( 'langrouter-for-translatepress-', '', $name );
                if ( $name !== '' ) {
                    $available_l10n_locales[] = $name;
                }
            }
        }

        $result['available_l10n_locales'] = $available_l10n_locales;

        foreach ( $candidates as $locale ) {
            $messages = function_exists( 'tpre_get_l10n_messages_for_locale' ) ? tpre_get_l10n_messages_for_locale( $locale ) : [];
            $has_l10n = isset( $messages[ $text ] ) && is_string( $messages[ $text ] ) && '' !== $messages[ $text ];
            $has_fallback = isset( $fallback_maps[ $locale ][ $text ] ) && '' !== $fallback_maps[ $locale ][ $text ];
            if ( $has_l10n ) {
                $result['resolved_locale'] = $locale;
                $result['source'] = 'l10n_php';
                $result['translated'] = $messages[ $text ];
                break;
            }
            if ( $has_fallback ) {
                $result['resolved_locale'] = $locale;
                $result['source'] = 'fallback_map';
                $result['translated'] = $fallback_maps[ $locale ][ $text ];
                break;
            }
        }

        return $result;
    }
}
if ( ! function_exists( 'tpre_log_translate' ) ) {
    function tpre_log_translate( $text ) {
        if ( ! is_string( $text ) || '' === $text ) {
            return is_string( $text ) ? $text : '';
        }

        $diagnostics = function_exists( 'tpre_log_translate_diagnostics' ) ? tpre_log_translate_diagnostics( $text ) : [];
        if ( isset( $diagnostics['translated'] ) && is_string( $diagnostics['translated'] ) && '' !== $diagnostics['translated'] ) {
            return $diagnostics['translated'];
        }

        return $text;
    }
}

if ( ! function_exists( 'tpre_log_translatef' ) ) {
    function tpre_log_translatef( $text ) {
        $args = func_get_args();
        array_shift( $args );

        // Strings used here are registered for Loco Translate in includes/i18n-log-strings.php.
        $template = function_exists( 'tpre_log_translate' ) ? tpre_log_translate( $text ) : $text;

        if ( empty( $args ) ) {
            return $template;
        }

        return vsprintf( $template, $args );
    }
}

require_once TPRE_PLUGIN_DIR . 'includes/support/class-http.php';
require_once TPRE_PLUGIN_DIR . 'includes/support/class-logger.php';
require_once TPRE_PLUGIN_DIR . 'includes/engines/interface-engine.php';
require_once TPRE_PLUGIN_DIR . 'includes/engines/class-translation-safety-utils.php';
require_once TPRE_PLUGIN_DIR . 'includes/engines/class-client-adapter-engine.php';
require_once TPRE_PLUGIN_DIR . 'includes/engines/opencc/class-opencc-utils.php';
require_once TPRE_PLUGIN_DIR . 'includes/engines/opencc/class-opencc-converter.php';
require_once TPRE_PLUGIN_DIR . 'includes/engines/volc/class-volc-client.php';
require_once TPRE_PLUGIN_DIR . 'includes/engines/volc/class-volc-engine.php';
require_once TPRE_PLUGIN_DIR . 'includes/engines/qwen/class-qwen-client.php';
require_once TPRE_PLUGIN_DIR . 'includes/engines/qwen/class-qwen-engine.php';
require_once TPRE_PLUGIN_DIR . 'includes/engines/hunyuan/class-hunyuan-client.php';
require_once TPRE_PLUGIN_DIR . 'includes/engines/hunyuan/class-hunyuan-engine.php';
require_once TPRE_PLUGIN_DIR . 'includes/engines/openai/class-openai-client.php';
require_once TPRE_PLUGIN_DIR . 'includes/engines/openai/class-openai-engine.php';
require_once TPRE_PLUGIN_DIR . 'includes/engines/deepl/class-deepl-engine.php';
require_once TPRE_PLUGIN_DIR . 'includes/engines/deepl/class-deepl-key-pool-translator.php';
require_once TPRE_PLUGIN_DIR . 'includes/class-language-normalizer.php';
require_once TPRE_PLUGIN_DIR . 'includes/class-language-support-query.php';
require_once TPRE_PLUGIN_DIR . 'includes/class-request-context-resolver.php';
require_once TPRE_PLUGIN_DIR . 'includes/class-routing-rules.php';
require_once TPRE_PLUGIN_DIR . 'includes/class-engine-manager.php';
require_once TPRE_PLUGIN_DIR . 'includes/admin/engines/class-admin-engine-config-base.php';
require_once TPRE_PLUGIN_DIR . 'includes/admin/engines/class-volc-admin-config.php';
require_once TPRE_PLUGIN_DIR . 'includes/admin/engines/class-qwen-admin-config.php';
require_once TPRE_PLUGIN_DIR . 'includes/admin/engines/class-hunyuan-admin-config.php';
require_once TPRE_PLUGIN_DIR . 'includes/admin/engines/class-openai-admin-config.php';
require_once TPRE_PLUGIN_DIR . 'includes/admin/engines/class-openai-compatible-admin-config.php';
require_once TPRE_PLUGIN_DIR . 'includes/admin/engines/class-deepl-admin-config.php';
require_once TPRE_PLUGIN_DIR . 'includes/admin/class-engine-registry.php';
require_once TPRE_PLUGIN_DIR . 'includes/admin/class-admin-page.php';
require_once TPRE_PLUGIN_DIR . 'includes/admin/class-log-actions.php';
require_once TPRE_PLUGIN_DIR . 'includes/class-admin-settings.php';
require_once TPRE_PLUGIN_DIR . 'includes/class-tp-compat-router.php';




function tpre_load_deepl_key_pool_translator_class() {
    if ( class_exists( 'TPRE_DeepL_Key_Pool_Machine_Translator' ) ) {
        return true;
    }

    if ( ! function_exists( 'tpre_deepl_define_key_pool_translator_class' ) ) {
        return false;
    }

    return tpre_deepl_define_key_pool_translator_class();
}

function tpre_load_client_adapter_translator_base_class() {
    if ( class_exists( 'TPRE_Client_Adapter_Machine_Translator_Base' ) ) {
        return true;
    }

    if ( ! class_exists( 'TRP_Machine_Translator' ) ) {
        return false;
    }

    require_once TPRE_PLUGIN_DIR . 'includes/engines/class-trp-client-adapter-machine-translator-base.php';

    return class_exists( 'TPRE_Client_Adapter_Machine_Translator_Base' );
}

function tpre_load_named_translator_class( $class_name, $relative_file ) {
    if ( class_exists( $class_name ) ) {
        return true;
    }

    if ( ! class_exists( 'TRP_Machine_Translator' ) ) {
        return false;
    }

    if ( ! tpre_load_client_adapter_translator_base_class() && false === strpos( (string) $class_name, 'Volcengine' ) ) {
        return false;
    }

    require_once TPRE_PLUGIN_DIR . ltrim( $relative_file, '/' );

    return class_exists( $class_name );
}

function tpre_load_volcengine_translator_class() {
    return tpre_load_named_translator_class( 'TRP_Volcengine_Ark_Machine_Translator', 'includes/engines/volc/class-trp-volcengine-ark-machine-translator.php' );
}

function tpre_load_qwen_translator_class() {
    return tpre_load_named_translator_class( 'TRP_Qwen_Machine_Translator', 'includes/engines/qwen/class-trp-qwen-machine-translator.php' );
}

function tpre_load_openai_translator_class() {
    return tpre_load_named_translator_class( 'TRP_OpenAI_Machine_Translator', 'includes/engines/openai/class-trp-openai-machine-translator.php' );
}

function tpre_load_hunyuan_translator_class() {
    return tpre_load_named_translator_class( 'TRP_Hunyuan_Machine_Translator', 'includes/engines/hunyuan/class-trp-hunyuan-machine-translator.php' );
}

if ( ! function_exists( 'tpre_allow_single_character_machine_translation' ) ) {
    function tpre_allow_single_character_machine_translation( $min_length ) {
        $min_length = is_numeric( $min_length ) ? (int) $min_length : 2;

        return $min_length > 1 ? 1 : $min_length;
    }
}
add_filter( 'trp_minimum_translation_length', 'tpre_allow_single_character_machine_translation', 20 );

function tpre_plugins_loaded_boot_router() {
    if ( class_exists( 'TRP_Translate_Press' ) && ! class_exists( 'TRP_Machine_Translator' ) && method_exists( 'TRP_Translate_Press', 'get_trp_instance' ) ) {
        TRP_Translate_Press::get_trp_instance();
    }

    if ( class_exists( 'TRP_Machine_Translator' ) ) {
        require_once TPRE_PLUGIN_DIR . 'includes/class-router-engine.php';
        TPRE_Router_Engine::boot();
        TPRE_TP_Compat_Router::boot();
        tpre_load_volcengine_translator_class();
        tpre_load_deepl_key_pool_translator_class();
        tpre_load_qwen_translator_class();
        tpre_load_openai_translator_class();
        tpre_load_hunyuan_translator_class();
    }

    if ( class_exists( 'TPRE_OpenCC_Converter' ) ) {
        TPRE_OpenCC_Converter::boot();
    }
}
add_action( 'plugins_loaded', 'tpre_plugins_loaded_boot_router', 1 );
function tpre_plugins_loaded_boot_admin() {
    TPRE_Admin_Settings::boot();
}
add_action( 'init', 'tpre_plugins_loaded_boot_admin', 30 );

function tpre_plugin_action_links( $links ) {
    $model_label = did_action( 'init' )
        ? esc_html__( '引擎', 'langrouter-for-translatepress' )
        : '引擎';
    $translation_label = did_action( 'init' )
        ? esc_html__( '路由', 'langrouter-for-translatepress' )
        : '路由';

    $model_link = sprintf(
        '<a href="%s">%s</a>',
        esc_url( TPRE_Admin_Settings::get_model_settings_url() ),
        esc_html( $model_label )
    );

    $translation_link = sprintf(
        '<a href="%s">%s</a>',
        esc_url( TPRE_Admin_Settings::get_translation_settings_url() ),
        esc_html( $translation_label )
    );

    array_unshift( $links, $translation_link );
    array_unshift( $links, $model_link );

    return $links;
}
add_filter( 'plugin_action_links_' . TPRE_PLUGIN_BASENAME, 'tpre_plugin_action_links' );
