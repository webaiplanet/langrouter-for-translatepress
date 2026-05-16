<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-local variables provided by the renderer.
?>
<div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;overflow:hidden;">
    <div style="padding:14px 16px;border-bottom:1px solid #f0f0f1;font-weight:600;">
        <?php esc_html_e( '帮助目录', 'langrouter-for-translatepress' ); ?>
    </div>
    <div>
        <?php foreach ( $help_sections as $slug => $title ) : ?>
            <?php $url = add_query_arg( [ 'tpre_help_section' => $slug ], $help_base_url ); ?>
            <a href="<?php echo esc_url( $url ); ?>" style="display:block;font-size: 14px;padding:14px 16px;text-decoration:none;border-left:4px solid <?php echo $slug === $help_section ? '#2271b1' : 'transparent'; ?>;background:<?php echo $slug === $help_section ? '#f6f7f7' : '#fff'; ?>;color:#1d2327;font-weight:<?php echo $slug === $help_section ? '600' : '400'; ?>;border-bottom:1px solid #f0f0f1;">
                <?php echo esc_html( $title ); ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<div style="margin-top:16px;background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px;">
    <div style="font-weight:600;margin-bottom:8px;"><?php esc_html_e( '建议顺序', 'langrouter-for-translatepress' ); ?></div>
    <ol style="margin:0 0 0 18px;">
        <li><?php esc_html_e( '先配一个可用引擎', 'langrouter-for-translatepress' ); ?></li>
        <li><?php esc_html_e( '再选默认引擎', 'langrouter-for-translatepress' ); ?></li>
        <li><?php esc_html_e( '先跑通单引擎', 'langrouter-for-translatepress' ); ?></li>
        <li><?php esc_html_e( '最后再加语言分配和回退', 'langrouter-for-translatepress' ); ?></li>
    </ol>
</div>
