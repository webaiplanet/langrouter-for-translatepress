<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="langrouter-settings-header-wrapper">
    <div class="langrouter-settings-header">
        <div class="langrouter-settings-logo">
            <img src="<?php echo esc_url( TPRE_PLUGIN_URL . "assets/images/langrouter-logo-with.png" ); ?>" alt="<?php esc_attr_e( "TranslatePress", "langrouter-for-translatepress" ); ?>">
            <span>for TranslatePress</span>
        </div>
        <div class="langrouter-header-items-wrapper">
            <a href="<?php echo esc_url( $tpre_translation_url ); ?>" target="_blank" rel="noopener noreferrer"><span><?php esc_html_e( '路由设置', 'langrouter-for-translatepress' ); ?></span></a>
            <span><?php esc_html_e( 'LangRouter 智能翻译 AI 引擎', 'langrouter-for-translatepress' ); ?></span>
        </div>
    </div>
</div>
<div class="wrap tpre-model-settings-wrap">
    <h1><?php //esc_html_e( '模型设置', 'langrouter-for-translatepress' ); ?></h1>

    <?php include TPRE_PLUGIN_DIR . 'templates/admin/tabs.php'; ?>

    <form method="post" class="tpre-model-settings-form">
        <?php wp_nonce_field( 'tpre_save_model_settings' ); ?>
        <input type="hidden" name="tpre_save_model_settings" value="1">
        <input type="hidden" name="tpre_current_tab" value="<?php echo esc_attr( $tpre_current_tab ); ?>">

        <h2><?php echo esc_html( $tpre_label ); ?></h2>

        <?php if ( is_file( $tpre_section_path ) ) {
            include $tpre_section_path;
        } ?>

        <div class="tpre-model-settings-actions">
            <?php if ( $tpre_show_save_button ) : ?>
                <?php submit_button( $tpre_save_button_label ); ?>
            <?php endif; ?>
            <p class="tpre-model-settings-actions-link"><a class="button button-secondary" href="<?php echo esc_url( $tpre_translation_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '前往翻译设置', 'langrouter-for-translatepress' ); ?></a></p>
            <?php if ( 'help' === $tpre_current_tab ) : ?>
                <p class="tpre-model-settings-actions-link"><a class="button button-secondary" href="<?php echo esc_url( TPRE_Admin_Settings::get_model_tab_url( 'openai' ) ); ?>"><?php esc_html_e( '返回模型设置', 'langrouter-for-translatepress' ); ?></a></p>
            <?php endif; ?>
            <?php if ( '' !== $tpre_help_button_url ) : ?>
                <p class="tpre-model-settings-actions-link tpre-model-settings-actions-link--push-right"><a class="button button-secondary" href="<?php echo esc_url( $tpre_help_button_url ); ?>"><?php esc_html_e( '本页帮助', 'langrouter-for-translatepress' ); ?></a></p>
            <?php endif; ?>
        </div>
    </form>
</div>
