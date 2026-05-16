<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPRE_Engine_Registry {
    const DEFAULT_TAB = 'volc';

    public static function all() {
        return [
            'volc' => [
                'label'                   => __('火山方舟', 'langrouter-for-translatepress'),
                'section_template'        => 'engine-volc',
                'show_in_model_tabs'      => true,
                'show_in_engine_choices'  => true,
                'admin_config_class'      => 'TPRE_Volc_Admin_Config',
                'engine_factory_callback' => [ __CLASS__, 'create_volc_engine' ],
                'help_section'            => 'engines',
                'help_anchor'             => 'help-engine-volc',
            ],
            'qwen' => [
                'label'                   => __('Qwen', 'langrouter-for-translatepress'),
                'section_template'        => 'engine-qwen',
                'show_in_model_tabs'      => true,
                'show_in_engine_choices'  => true,
                'admin_config_class'      => 'TPRE_Qwen_Admin_Config',
                'engine_factory_callback' => [ __CLASS__, 'create_qwen_engine' ],
                'help_section'            => 'engines',
                'help_anchor'             => 'help-engine-qwen',
            ],
            'hunyuan' => [
                'label'                   => __('Hunyuan', 'langrouter-for-translatepress'),
                'section_template'        => 'engine-hunyuan',
                'show_in_model_tabs'      => true,
                'show_in_engine_choices'  => true,
                'admin_config_class'      => 'TPRE_Hunyuan_Admin_Config',
                'engine_factory_callback' => [ __CLASS__, 'create_hunyuan_engine' ],
                'help_section'            => 'engines',
                'help_anchor'             => 'help-engine-hunyuan',
            ],
            'openai' => [
                'label'                   => __('OpenAI', 'langrouter-for-translatepress'),
                'section_template'        => 'engine-openai',
                'show_in_model_tabs'      => true,
                'show_in_engine_choices'  => true,
                'admin_config_class'      => 'TPRE_OpenAI_Admin_Config',
                'engine_factory_callback' => [ __CLASS__, 'create_openai_engine' ],
                'help_section'            => 'engines',
                'help_anchor'             => 'help-engine-openai',
            ],
            'deepl' => [
                'label'                   => __('DeepL', 'langrouter-for-translatepress'),
                'section_template'        => 'engine-deepl',
                'show_in_model_tabs'      => true,
                'show_in_engine_choices'  => true,
                'admin_config_class'      => 'TPRE_DeepL_Admin_Config',
                'engine_factory_callback' => [ __CLASS__, 'create_deepl_engine' ],
                'help_section'            => 'engines',
                'help_anchor'             => 'help-engine-deepl',
            ],
            'openai_compatible' => [
                'label'                   => __('兼容 OpenAI API', 'langrouter-for-translatepress'),
                'section_template'        => 'engine-openai-compatible',
                'show_in_model_tabs'      => true,
                'show_in_engine_choices'  => true,
                'admin_config_class'      => 'TPRE_OpenAI_Compatible_Admin_Config',
                'engine_factory_callback' => [ __CLASS__, 'create_openai_compatible_engine' ],
                'help_section'            => 'engines',
                'help_anchor'             => 'help-engine-openai-compatible',
            ],
            'logs' => [
                'label'                  => __('日志', 'langrouter-for-translatepress'),
                'section_template'       => 'logs',
                'show_in_model_tabs'     => true,
                'show_in_engine_choices' => false,
            ],
            'help' => [
                'label'                  => __('帮助', 'langrouter-for-translatepress'),
                'section_template'       => 'help',
                'show_in_model_tabs'     => true,
                'show_in_engine_choices' => false,
            ],
        ];
    }

    public static function get_model_tabs() {
        $tabs = [];
        foreach ( self::all() as $slug => $definition ) {
            if ( ! empty( $definition['show_in_model_tabs'] ) ) {
                $tabs[ $slug ] = $definition['label'];
            }
        }

        return $tabs;
    }

    public static function get_engine_choices() {
        $choices = [];
        foreach ( self::all() as $slug => $definition ) {
            if ( ! empty( $definition['show_in_engine_choices'] ) ) {
                $choices[ $slug ] = $definition['label'];
            }
        }

        return $choices;
    }

    public static function get_definition( $slug ) {
        $definitions = self::all();

        return isset( $definitions[ $slug ] ) && is_array( $definitions[ $slug ] ) ? $definitions[ $slug ] : [];
    }

    public static function get_admin_config_class( $slug ) {
        $definition = self::get_definition( $slug );

        return isset( $definition['admin_config_class'] ) ? $definition['admin_config_class'] : '';
    }

    public static function get_configurable_engine_slugs() {
        $slugs = [];
        foreach ( self::all() as $slug => $definition ) {
            if ( ! empty( $definition['admin_config_class'] ) ) {
                $slugs[] = $slug;
            }
        }

        return $slugs;
    }

    public static function get_section_template( $slug ) {
        $definition = self::get_definition( $slug );
        if ( isset( $definition['section_template'] ) && '' !== $definition['section_template'] ) {
            return $definition['section_template'];
        }

        return 'generic-engine';
    }

    public static function get_help_link_data( $slug ) {
        $definition = self::get_definition( $slug );
        if ( empty( $definition['help_section'] ) ) {
            return [];
        }

        return [
            'section' => (string) $definition['help_section'],
            'anchor'  => isset( $definition['help_anchor'] ) ? (string) $definition['help_anchor'] : '',
            'label'   => isset( $definition['label'] ) ? (string) $definition['label'] : '',
        ];
    }


    public static function create_engine( $slug, array $tp_settings, array $router_settings, TPRE_Logger $logger ) {
        $definition = self::get_definition( $slug );
        $callback   = isset( $definition['engine_factory_callback'] ) ? $definition['engine_factory_callback'] : null;

        if ( empty( $callback ) || ! is_callable( $callback ) ) {
            return null;
        }

        return call_user_func( $callback, $tp_settings, $router_settings, $logger );
    }

    public static function get_default_tab() {
        $tabs = self::get_model_tabs();
        if ( isset( $tabs[ self::DEFAULT_TAB ] ) ) {
            return self::DEFAULT_TAB;
        }

        $keys = array_keys( $tabs );
        return isset( $keys[0] ) ? $keys[0] : self::DEFAULT_TAB;
    }

    protected static function create_volc_engine( array $tp_settings, array $router_settings, TPRE_Logger $logger ) {
        $client = new TPRE_Volc_Client( $tp_settings, $router_settings, $logger );

        return new TPRE_Volc_Engine( $client, $tp_settings, $router_settings, $logger );
    }

    protected static function create_qwen_engine( array $tp_settings, array $router_settings, TPRE_Logger $logger ) {
        $client = new TPRE_Qwen_Client( $router_settings, $logger );

        return new TPRE_Qwen_Engine( $client, $tp_settings, $router_settings, $logger );
    }

    protected static function create_hunyuan_engine( array $tp_settings, array $router_settings, TPRE_Logger $logger ) {
        $client = new TPRE_Hunyuan_Client( $router_settings, $logger );

        return new TPRE_Hunyuan_Engine( $client, $tp_settings, $router_settings, $logger );
    }

    protected static function create_openai_engine( array $tp_settings, array $router_settings, TPRE_Logger $logger ) {
        $client = new TPRE_OpenAI_Client( $router_settings, $logger, 'openai' );

        return new TPRE_OpenAI_Engine( $client, $tp_settings, $router_settings, $logger, 'openai', 'OpenAI' );
    }

    protected static function create_openai_compatible_engine( array $tp_settings, array $router_settings, TPRE_Logger $logger ) {
        $client = new TPRE_OpenAI_Client( $router_settings, $logger, 'openai_compatible' );

        return new TPRE_OpenAI_Engine( $client, $tp_settings, $router_settings, $logger, 'openai_compatible', __( '兼容 OpenAI API', 'langrouter-for-translatepress' ) );
    }

    protected static function create_deepl_engine( array $tp_settings, array $router_settings, TPRE_Logger $logger ) {
        return new TPRE_DeepL_Engine( $tp_settings, $router_settings, $logger );
    }
}
