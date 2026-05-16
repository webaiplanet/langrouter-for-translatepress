<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-local variables provided by the renderer.
?>
<h2 class="nav-tab-wrapper">
    <?php foreach ( $model_tabs as $slug => $tab_label ) : ?>
        <a href="<?php echo esc_url( TPRE_Admin_Settings::get_model_tab_url( $slug ) ); ?>" class="nav-tab <?php echo $slug === $current_tab ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $tab_label ); ?></a>
    <?php endforeach; ?>
</h2>
