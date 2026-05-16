<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-local variables provided by the renderer.
?>

<?php
$volc_client          = TPRE_Volc_Client::create_for_admin( $router_settings, new TPRE_Logger( false ) );
$masked_accounts_text = class_exists( 'TPRE_Admin_Settings' ) ? TPRE_Admin_Settings::build_masked_volc_accounts_text( $item['accounts_raw'] ?? '' ) : '';
$api_check            = $volc_client->has_accounts() ? $volc_client->check_api_key_validity() : [ 'error' => false, 'message' => '' ];
$billing_rows         = $volc_client->has_accounts() ? $volc_client->get_billing_usage_summary_rows() : [];
$diagnostics          = $volc_client->has_accounts() ? $volc_client->get_billing_usage_diagnostic_rows() : [];

$current_endpoint_row = null;
foreach ( $billing_rows as $billing_row ) {
    if ( ! empty( $billing_row['is_current'] ) ) {
        $current_endpoint_row = $billing_row;
        break;
    }
}
$volc_refresh_url     = wp_nonce_url(
    add_query_arg(
        [
            'page'                      => 'tpre-model-settings',
            'tab'                       => 'volc',
            'tpre_volc_refresh_billing' => 1,
        ],
        admin_url( 'options-general.php' )
    ),
    'tpre_volc_refresh_billing',
    'tpre_volc_refresh_nonce'
);
?>

<?php if ( ! empty( $api_check['error'] ) ) : ?>
    <div class="notice notice-error inline"><p><?php echo esc_html( $api_check['message'] ); ?></p></div>
<?php endif; ?>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row"><?php esc_html_e( '启用', 'langrouter-for-translatepress' ); ?></th>
        <td>
            <label><input type="checkbox" name="tpre_settings[models][volc][enabled]" value="1" <?php checked( ! empty( $item['enabled'] ) ); ?>> <?php esc_html_e( '启用火山方舟子引擎', 'langrouter-for-translatepress' ); ?></label>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="tpre-volc-concurrency"><?php esc_html_e( '并发数', 'langrouter-for-translatepress' ); ?></label></th>
        <td>
            <input id="tpre-volc-concurrency" type="number" min="0" max="32" name="tpre_settings[models][volc][concurrency]" value="<?php echo esc_attr( (string) ( $item['concurrency'] ?? 0 ) ); ?>" />
            <p class="description"><?php esc_html_e( '同时发起的火山请求数量。填 0 表示继承路由设置里的全局默认并发；如果全局也为 0，则继续使用火山引擎的内置默认值。低配服务器建议先试 1–3。', 'langrouter-for-translatepress' ); ?></p>
        </td>
    </tr>
</table>

<span class="tpre-volc-accounts-heading"><?php esc_html_e( '账号池', 'langrouter-for-translatepress' ); ?></span>

<textarea id="tpre-volc-accounts-raw" class="large-text code tpre-volc-accounts-text" name="tpre_settings[models][volc][accounts_raw]" rows="6" spellcheck="false" wrap="off" data-masked-value="<?php echo esc_attr( $masked_accounts_text ); ?>" data-unchanged-flag-name="tpre_settings[models][volc][accounts_raw_unchanged]"><?php echo esc_textarea( $masked_accounts_text ); ?></textarea>
<p class="description"><?php esc_html_e( '保存后会隐藏 APIKey / AK / SK ，无法复制，修改必须重新输入完整信息。', 'langrouter-for-translatepress' ); ?></p>
<p>
    <label><input type="checkbox" name="tpre_settings[models][volc][clear_accounts_pool]" value="1" /> <?php esc_html_e( '保存时清空当前账号池', 'langrouter-for-translatepress' ); ?></label>
</p>

<p class="description">
    <?php echo wp_kses_post( __( '翻译模型：<code>接入点ID|APIKey|Access Key|Secret Access Key|安全阈值Tokens</code>。', 'langrouter-for-translatepress' ) ); ?>
</p>
<p class="description tpre-volc-description-top">
    <?php
    echo wp_kses(
        __(
            '聊天模型：<code>接入点ID|APIKey|Access Key|Secret Access Key|安全阈值Tokens|chat</code>。<a href="https://console.volcengine.com/iam/keymanage" target="_blank" rel="noopener noreferrer">创建火山引擎 KEY</a>。',
            'langrouter-for-translatepress'
        ),
        array(
            'code' => array(),
            'a'    => array(
                'href'   => array(),
                'target' => array(),
                'rel'    => array(),
            ),
        )
    );
    ?>
</p>
<p class="description">
    <?php
    echo wp_kses_post(
        __(
            '兼容旧格式：<code>接入点ID|APIKey|每日上限Tokens</code>。旧格式无法查询官方用量，只能继续依赖本地限额。',
            'langrouter-for-translatepress'
        )
    );
    ?>
</p>
<p class="description"><?php esc_html_e( 'Access Key/Secret Access Key 用于查询 GetInferenceUsage；APIKey 仍用于实际翻译请求。', 'langrouter-for-translatepress' ); ?></p>

<?php if ( ! empty( $billing_rows ) ) : ?>
    <div class="notice notice-info inline">
        <p>
            <strong><?php esc_html_e( '当前轮转接入点：', 'langrouter-for-translatepress' ); ?></strong>
            <?php if ( ! empty( $current_endpoint_row['endpoint_id'] ) ) : ?>
                <code><?php echo esc_html( (string) $current_endpoint_row['endpoint_id'] ); ?></code>
            <?php else : ?>
                <?php esc_html_e( '尚未命中有效接入点', 'langrouter-for-translatepress' ); ?>
            <?php endif; ?>
        </p>
        <p class="description">
            <?php esc_html_e( '下面表格按号池顺序逐条显示全部接入点，并在当前轮转接入点后标记“当前”。', 'langrouter-for-translatepress' ); ?>
        </p>
    </div>
<?php endif; ?>

<?php if ( ! empty( $billing_rows ) ) : ?>
    <div class="tpre-volc-billing-header">
        <h3 class="tpre-volc-billing-title"><?php esc_html_e( '接入点官方用量概览', 'langrouter-for-translatepress' ); ?></h3>
        <a class="button button-secondary" href="<?php echo esc_url( $volc_refresh_url ); ?>" class="tpre-volc-billing-action"><?php esc_html_e( '刷新', 'langrouter-for-translatepress' ); ?></a>
    </div>
    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e( '接入点 ID', 'langrouter-for-translatepress' ); ?></th>
                <th><?php esc_html_e( '接入点名称 / 模式', 'langrouter-for-translatepress' ); ?></th>
                <th><?php esc_html_e( '统计日期', 'langrouter-for-translatepress' ); ?></th>
                <th><?php esc_html_e( '官方 InputTokens', 'langrouter-for-translatepress' ); ?></th>
                <th><?php esc_html_e( '官方 OutputTokens', 'langrouter-for-translatepress' ); ?></th>
                <th><?php esc_html_e( '官方 TotalTokens', 'langrouter-for-translatepress' ); ?></th>
                <th><?php esc_html_e( '官方请求数', 'langrouter-for-translatepress' ); ?></th>
                <th><?php esc_html_e( '安全阈值', 'langrouter-for-translatepress' ); ?></th>
                <th><?php esc_html_e( '状态', 'langrouter-for-translatepress' ); ?></th>
                <th><?php esc_html_e( '最近同步', 'langrouter-for-translatepress' ); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $billing_rows as $row ) : ?>
            <tr>
                <td><code><?php echo esc_html( $row['endpoint_id'] ?? '—' ); ?></code><?php if ( ! empty( $row['is_current'] ) ) : ?><span class="tpre-current-flag"><?php esc_html_e( '（当前）', 'langrouter-for-translatepress' ); ?></span><?php endif; ?></td>
                <td><?php echo esc_html( $row['endpoint_name'] ?? '' ); ?><?php if ( ! empty( $row['mode'] ) ) : ?><div><code><?php echo esc_html( (string) $row['mode'] ); ?></code></div><?php endif; ?></td>
                <td><?php echo esc_html( $row['stat_day'] ?? '—' ); ?></td>
                <td><?php echo esc_html( (string) ( $row['input_tokens'] ?? 0 ) ); ?></td>
                <td><?php echo esc_html( (string) ( $row['output_tokens'] ?? 0 ) ); ?></td>
                <td><?php echo esc_html( (string) ( $row['total_tokens'] ?? 0 ) ); ?></td>
                <td><?php echo esc_html( (string) ( $row['req_count'] ?? 0 ) ); ?></td>
                <td><?php echo ! empty( $row['safety_threshold'] ) ? esc_html( (string) $row['safety_threshold'] ) : '—'; ?></td>
                <td><?php echo esc_html( $row['status'] ?? '—' ); ?></td>
                <td><?php echo esc_html( $row['synced_at'] ?? '—' ); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<p class="description"><?php esc_html_e( '官方并非实时同步，同时间为 1 ~ 4 小时，需等官方同步后，此处才会同步。当前表格按接入点逐条显示。', 'langrouter-for-translatepress' ); ?></p>                    
<?php if ( ! empty( $diagnostics ) ) : ?>
    <h3 class="tpre-volc-diagnostics-title"><?php esc_html_e( '接入点同步诊断', 'langrouter-for-translatepress' ); ?></h3>
    <div class="notice notice-warning inline tpre-volc-diagnostics-notice">
        <?php foreach ( $diagnostics as $row ) : ?>
            <p class="tpre-volc-diagnostics-item"><strong><?php echo esc_html( $row['billing_id_short'] ?? '—' ); ?></strong>：<?php echo esc_html( $row['message'] ?? '' ); ?>
        </p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
