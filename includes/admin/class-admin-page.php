<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPRE_Admin_Page {
    public static function render_model_settings_page() {
        $tpre_current_tab = TPRE_Admin_Settings::get_current_model_tab();
        $tpre_model_tabs  = TPRE_Admin_Settings::get_model_tabs();

        $tpre_router_settings   = TPRE_Admin_Settings::get_settings();
        $tpre_models            = isset( $tpre_router_settings['models'] ) ? (array) $tpre_router_settings['models'] : [];
        $tpre_translation_url   = TPRE_Admin_Settings::get_translation_settings_url();
        $tpre_available_logs    = class_exists( 'TPRE_Logger' ) ? TPRE_Logger::list_log_files() : [];
        $tpre_selected_log_file = self::get_requested_log_basename();

        if ( '' === $tpre_selected_log_file && ! empty( $tpre_available_logs[0]['basename'] ) ) {
            $tpre_selected_log_file = $tpre_available_logs[0]['basename'];
        }

        $tpre_selected_log_data = '' !== $tpre_selected_log_file
            ? TPRE_Logger::read_log_file( $tpre_selected_log_file )
            : [ 'content' => '', 'truncated' => false, 'exists' => false ];

        $tpre_log_view_base_url = TPRE_Admin_Settings::get_model_tab_url( $tpre_current_tab );
        $tpre_item              = in_array( $tpre_current_tab, [ 'logs', 'help' ], true )
            ? []
            : ( $tpre_models[ $tpre_current_tab ] ?? [
                'enabled'         => 0,
                'accounts_raw'    => '',
                'endpoint'        => '',
                'model'           => '',
                'custom_model'    => '',
                'api_key'         => '',
                'secret_key'      => '',
                'region'          => '',
                'site'            => '',
                'timeout'         => 30,
                'system_prompt'   => '',
                'extra_headers'   => '',
                'extra_body_json' => '',
                'note'            => '',
            ] );
        $tpre_label             = $tpre_model_tabs[ $tpre_current_tab ] ?? $tpre_current_tab;
        $tpre_log_dir           = self::get_log_dir_fallback();
        $tpre_save_button_label = 'logs' === $tpre_current_tab
            ? __('保存日志设置', 'langrouter-for-translatepress')
            : __('保存当前模型设置', 'langrouter-for-translatepress');
        $tpre_show_save_button  = ! in_array( $tpre_current_tab, [ 'help' ], true );
        $tpre_help_link_data    = class_exists( 'TPRE_Engine_Registry' ) ? TPRE_Engine_Registry::get_help_link_data( $tpre_current_tab ) : [];
        $tpre_help_button_url   = '';

        if ( ! empty( $tpre_help_link_data['section'] ) ) {
            $tpre_help_button_url = TPRE_Admin_Settings::get_model_tab_url( 'help' );
            $tpre_help_button_url = add_query_arg( [ 'tpre_help_section' => $tpre_help_link_data['section'] ], $tpre_help_button_url );
            if ( ! empty( $tpre_help_link_data['anchor'] ) ) {
                $tpre_help_button_url .= '#' . rawurlencode( $tpre_help_link_data['anchor'] );
            }
        }

        $tpre_section_template = class_exists( 'TPRE_Engine_Registry' )
            ? TPRE_Engine_Registry::get_section_template( $tpre_current_tab )
            : 'generic-engine';
        $tpre_section_path     = TPRE_PLUGIN_DIR . 'templates/admin/sections/' . $tpre_section_template . '.php';

        // Backward-compatible aliases for included section templates.
        $current_tab        = $tpre_current_tab;
        $model_tabs         = $tpre_model_tabs;
        $router_settings    = $tpre_router_settings;
        $models             = $tpre_models;
        $translation_url    = $tpre_translation_url;
        $available_logs     = $tpre_available_logs;
        $selected_log_file  = $tpre_selected_log_file;
        $selected_log_data  = $tpre_selected_log_data;
        $log_view_base_url  = $tpre_log_view_base_url;
        $item               = $tpre_item;
        $label              = $tpre_label;
        $log_dir            = $tpre_log_dir;
        $save_button_label  = $tpre_save_button_label;
        $show_save_button   = $tpre_show_save_button;
        $help_link_data     = $tpre_help_link_data;
        $help_button_url    = $tpre_help_button_url;
        $section_template   = $tpre_section_template;
        $section_path       = $tpre_section_path;

        include TPRE_PLUGIN_DIR . 'templates/admin/page-model-settings.php';
    }

    public static function render_router_settings_block() {
        $tpre_router_settings      = TPRE_Admin_Settings::get_settings();
        $tpre_default_engine       = $tpre_router_settings['default_engine'] ?? 'volc';
        $tpre_global_concurrency_limit = isset( $tpre_router_settings['global_concurrency_limit'] ) ? (int) $tpre_router_settings['global_concurrency_limit'] : 0;
        $tpre_language_map_raw     = '';
        $tpre_fallback_map_raw     = '';
        $tpre_post_type_choices   = TPRE_Admin_Settings::get_routable_post_types();
        $tpre_post_type_rule_rows = [];
        foreach ( (array) ( $tpre_router_settings['post_type_rules'] ?? [] ) as $tpre_rule_row ) {
            if ( ! is_array( $tpre_rule_row ) || empty( $tpre_rule_row['post_types'] ) || empty( $tpre_rule_row['engine'] ) ) {
                continue;
            }

            $tpre_post_types = [];
            foreach ( (array) $tpre_rule_row['post_types'] as $tpre_post_type_slug ) {
                $tpre_post_type_slug = sanitize_key( (string) $tpre_post_type_slug );
                if ( isset( $tpre_post_type_choices[ $tpre_post_type_slug ] ) ) {
                    $tpre_post_types[] = $tpre_post_type_slug;
                }
            }

            if ( empty( $tpre_post_types ) ) {
                continue;
            }

            $tpre_fallback_mode = isset( $tpre_rule_row['fallback_mode'] ) ? sanitize_key( (string) $tpre_rule_row['fallback_mode'] ) : '';
            if ( ! in_array( $tpre_fallback_mode, [ 'none', 'default_only', 'global_chain' ], true ) ) {
                $tpre_fallback_mode = ! empty( $tpre_rule_row['use_global_chain'] ) ? 'global_chain' : 'default_only';
            }

            $tpre_post_type_rule_rows[] = [
                'post_types'       => array_values( array_unique( $tpre_post_types ) ),
                'engine'           => sanitize_key( (string) $tpre_rule_row['engine'] ),
                'fallback_mode'    => $tpre_fallback_mode,
                'use_global_chain' => 'global_chain' === $tpre_fallback_mode ? 1 : 0,
            ];
        }
        $tpre_model_settings_url   = TPRE_Admin_Settings::get_model_settings_url();
        $tpre_engine_choices       = TPRE_Admin_Settings::get_engine_choices();
        $tpre_query_engine_choices = class_exists( 'TPRE_Language_Support_Query' ) ? TPRE_Language_Support_Query::get_engine_choices() : [];
        $tpre_query_default_engine = isset( $tpre_query_engine_choices[ $tpre_default_engine ] ) ? $tpre_default_engine : 'volc';
        $tpre_query_nonce          = wp_create_nonce( 'tpre_query_language_support' );
        $tpre_traditional_chinese_mode = class_exists( 'TPRE_OpenCC_Utils' ) ? TPRE_OpenCC_Utils::get_handling_mode( $tpre_router_settings ) : 'translatepress';
        $tpre_traditional_chinese_aliases = class_exists( 'TPRE_OpenCC_Utils' ) ? TPRE_OpenCC_Utils::get_traditional_language_aliases() : [ 'zh_Hant', 'zh_TW', 'zh_HK' ];

        foreach ( (array) ( $tpre_router_settings['language_engine_map'] ?? [] ) as $tpre_lang => $tpre_engine ) {
            $tpre_language_map_raw .= $tpre_lang . ' = ' . $tpre_engine . PHP_EOL;
        }

        foreach ( (array) ( $tpre_router_settings['fallback_map'] ?? [] ) as $tpre_lang => $tpre_engine ) {
            $tpre_fallback_map_raw .= $tpre_lang . ' = ' . $tpre_engine . PHP_EOL;
        }

        $tpre_routing_help_url = add_query_arg(
            [ 'tpre_help_section' => 'routing' ],
            TPRE_Admin_Settings::get_model_tab_url( 'help' )
        );
        $tpre_opencc_help_url = add_query_arg(
            [ 'tpre_help_section' => 'engines' ],
            TPRE_Admin_Settings::get_model_tab_url( 'help' )
        ) . '#help-engine-opencc';
        
        include TPRE_PLUGIN_DIR . 'templates/router-settings.php';
    }


    public static function enqueue_admin_assets() {
        if ( isset( $_GET['page'] ) && is_scalar( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page check.
            $page = sanitize_key( wp_unslash( $_GET['page'] ) );
        } else {
            $page = '';
        }

        if ( 'tpre-model-settings' === $page ) {
            wp_enqueue_style(
                'tpre-model-settings',
                TPRE_PLUGIN_URL . 'assets/css/model-settings.css',
                [],
                TPRE_VERSION
            );
            wp_enqueue_script(
                'tpre-model-settings',
                TPRE_PLUGIN_URL . 'assets/js/model-settings.js',
                [],
                TPRE_VERSION,
                true
            );
        }

        if ( 'trp_machine_translation' === $page ) {
            $router_settings = TPRE_Admin_Settings::get_settings();
            $default_engine  = $router_settings['default_engine'] ?? 'volc';
            $query_choices   = class_exists( 'TPRE_Language_Support_Query' ) ? TPRE_Language_Support_Query::get_engine_choices() : [];
            $query_nonce     = wp_create_nonce( 'tpre_query_language_support' );
            $query_engine    = isset( $query_choices[ $default_engine ] ) ? $default_engine : 'volc';

            wp_enqueue_style(
                'tpre-router-settings',
                TPRE_PLUGIN_URL . 'assets/css/router-settings.css',
                [],
                TPRE_VERSION
            );
            wp_enqueue_script(
                'tpre-router-settings',
                TPRE_PLUGIN_URL . 'assets/js/router-settings.js',
                [],
                TPRE_VERSION,
                true
            );
            wp_enqueue_script(
                'tpre-admin-language-query',
                TPRE_PLUGIN_URL . 'assets/js/admin-language-query.js',
                [ 'wp-i18n' ],
                TPRE_VERSION,
                true
            );
            wp_set_script_translations( 'tpre-admin-language-query', 'langrouter-for-translatepress', TPRE_PLUGIN_DIR . 'languages' );
            wp_localize_script(
                'tpre-admin-language-query',
                'TPRELanguageQuery',
                [
                    'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
                    'nonce'         => $query_nonce,
                    'defaultEngine' => $query_engine,
                ]
            );
        }
    }


    protected static function get_requested_log_basename() {
        if ( ! isset( $_GET['tpre_log_file'] ) || ! is_scalar( $_GET['tpre_log_file'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only log viewer parameter.
            return '';
        }

        return TPRE_Logger::sanitize_log_basename( sanitize_file_name( wp_unslash( $_GET['tpre_log_file'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sanitized before path construction.
    }

    protected static function get_log_dir_fallback() {
        if ( class_exists( 'TPRE_Logger' ) ) {
            return TPRE_Logger::get_log_dir();
        }
        if ( ! function_exists( 'wp_upload_dir' ) ) {
            return '';
        }

        $tpre_uploads = wp_upload_dir();
        if ( ! is_array( $tpre_uploads ) || ! empty( $tpre_uploads['error'] ) || empty( $tpre_uploads['basedir'] ) ) {
            return '';
        }

        return trailingslashit( $tpre_uploads['basedir'] ) . 'langrouter-for-translatepress';
    }
}
