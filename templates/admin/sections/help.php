<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-local variables provided by the renderer.

$help_sections = [
    'quickstart' => __('快速开始', 'langrouter-for-translatepress'),
    'concepts'   => __('基本概念', 'langrouter-for-translatepress'),
    'engines'    => __('引擎配置', 'langrouter-for-translatepress'),
    'routing'    => __('路由设置', 'langrouter-for-translatepress'),
    'logs'       => __('日志与排错', 'langrouter-for-translatepress'),
    'faq'        => __('常见问题', 'langrouter-for-translatepress'),
    'feedback'   => __('反馈和建议', 'langrouter-for-translatepress'),
];

$tpre_help_section_raw = isset( $_GET['tpre_help_section'] ) && is_scalar( $_GET['tpre_help_section'] ) ? sanitize_key( wp_unslash( $_GET['tpre_help_section'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only help tab parameter.
$help_section          = '' !== $tpre_help_section_raw ? $tpre_help_section_raw : 'quickstart';
if ( ! isset( $help_sections[ $help_section ] ) ) {
    $help_section = 'quickstart';
}

$help_base_url             = TPRE_Admin_Settings::get_model_tab_url( 'help' );
$help_template             = TPRE_PLUGIN_DIR . 'templates/admin/help/content-' . $help_section . '.php';
$quickstart_url            = add_query_arg( [ 'tpre_help_section' => 'quickstart' ], $help_base_url );
$concepts_help_url         = add_query_arg( [ 'tpre_help_section' => 'concepts' ], $help_base_url );
$engines_help_url          = add_query_arg( [ 'tpre_help_section' => 'engines' ], $help_base_url );
$routing_help_url          = add_query_arg( [ 'tpre_help_section' => 'routing' ], $help_base_url );
$logs_help_url             = add_query_arg( [ 'tpre_help_section' => 'logs' ], $help_base_url );
$faq_help_url              = add_query_arg( [ 'tpre_help_section' => 'faq' ], $help_base_url );
$feedback_help_url         = add_query_arg( [ 'tpre_help_section' => 'feedback' ], $help_base_url );
$volc_model_url            = TPRE_Admin_Settings::get_model_tab_url( 'volc' );
$qwen_model_url            = TPRE_Admin_Settings::get_model_tab_url( 'qwen' );
$hunyuan_model_url         = TPRE_Admin_Settings::get_model_tab_url( 'hunyuan' );
$openai_model_url          = TPRE_Admin_Settings::get_model_tab_url( 'openai' );
$deepl_model_url           = TPRE_Admin_Settings::get_model_tab_url( 'deepl' );
$compatible_openai_url     = TPRE_Admin_Settings::get_model_tab_url( 'openai_compatible' );
$logs_model_url            = TPRE_Admin_Settings::get_model_tab_url( 'logs' );
$translation_url           = TPRE_Admin_Settings::get_translation_settings_url();
$uploads_log_relative_path = 'uploads/langrouter-for-translatepress/';
?>
<div class="tpre-help-section" style="display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap;">
    <div style="min-width:220px;max-width:220px;flex:0 0 220px;">
        <?php include TPRE_PLUGIN_DIR . 'templates/admin/help/nav.php'; ?>
    </div>
    <div style="min-width:320px;flex:1;background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:24px;box-sizing:border-box;">
        <p class="description" style="margin-top:0;margin-bottom:10px;">
            <?php
            echo wp_kses_post(
                __(
                    '<strong>提示</strong>：模型设置主要维护各个子引擎的模型参数、接口地址、密钥、账号池和备注。',
                    'langrouter-for-translatepress'
                )
            );
            ?>
        </p>
        <p class="description" style="padding-bottom: 20px;">
            <?php
            printf(
                wp_kses_post(
                    /* translators: %s: TranslatePress automatic translation settings URL. */
                    __(
                        '<strong>路由规则、默认引擎、语言分配和回退规则</strong>仍在 <a href="%s" target="_blank" rel="noopener noreferrer">TranslatePress → 自动翻译</a> 页面的“路由设置”区域调整。',
                        'langrouter-for-translatepress'
                    )
                ),
                esc_url( $translation_url )
            );
            ?>
        </p>
        <p class="description" style="margin-top: 0; margin-bottom: 20px; border-bottom: 1px solid #f0f0f1; padding-bottom: 20px;">
            <?php esc_html_e( '这里集中说明插件的上手顺序、路由优先级、常见配置方式和日志排错思路。建议新手先看“快速开始”和“路由设置”，再根据自己接入的引擎查看对应说明。', 'langrouter-for-translatepress' ); ?>
        </p>
        <?php if ( is_file( $help_template ) ) : ?>
            <?php include $help_template; ?>
        <?php else : ?>
            <p><?php esc_html_e( '未找到对应的帮助内容。', 'langrouter-for-translatepress' ); ?></p>
        <?php endif; ?>
    </div>
</div>
