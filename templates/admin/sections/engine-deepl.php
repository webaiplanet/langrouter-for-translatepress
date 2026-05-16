<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-local variables provided by the renderer.
?>
<?php
$pool_entries_for_display = function_exists( 'tpre_deepl_parse_pool_entries_for_admin' ) ? tpre_deepl_parse_pool_entries_for_admin( $item['keys_text'] ?? '' ) : [];
$masked_pool_text         = class_exists( 'TPRE_Admin_Settings' ) ? TPRE_Admin_Settings::build_masked_deepl_keys_text( $item['keys_text'] ?? '' ) : '';
$pool_line_count          = max( 3, substr_count( (string) ( $item['keys_text'] ?? '' ), "\n" ) + 1 );
$runtime                  = get_option( function_exists( 'tpre_deepl_get_runtime_option_name' ) ? tpre_deepl_get_runtime_option_name() : 'tpre_deepl_runtime', [] );
$runtime_entries          = [];
$pool_index_map           = [];
$pool_mask_map            = [];
foreach ( $pool_entries_for_display as $pool_entry ) {
    $pool_index_map[ $pool_entry['hash'] ] = $pool_entry['line_no'];
    $pool_mask_map[ $pool_entry['type'] . '|' . $pool_entry['masked_key'] ] = $pool_entry['line_no'];
}
if ( ! empty( $runtime['entries'] ) && is_array( $runtime['entries'] ) ) {
    foreach ( $runtime['entries'] as $hash => $runtime_entry ) {
        if ( ! is_array( $runtime_entry ) ) {
            continue;
        }

        $line_no = '';
        if ( isset( $runtime_entry['line_no'] ) && (int) $runtime_entry['line_no'] > 0 ) {
            $line_no = (int) $runtime_entry['line_no'];
        } elseif ( isset( $pool_index_map[ $hash ] ) ) {
            $line_no = $pool_index_map[ $hash ];
        } else {
            $lookup = ( isset( $runtime_entry['type'] ) ? (string) $runtime_entry['type'] : '' ) . '|' . ( isset( $runtime_entry['masked_key'] ) ? (string) $runtime_entry['masked_key'] : '' );
            if ( isset( $pool_mask_map[ $lookup ] ) ) {
                $line_no = $pool_mask_map[ $lookup ];
            }
        }

        $runtime_entries[] = array_merge( $runtime_entry, [ '_line_no' => $line_no ] );
    }

    usort(
        $runtime_entries,
        function( $a, $b ) {
            $a_line = isset( $a['_line_no'] ) && '' !== (string) $a['_line_no'] ? (int) $a['_line_no'] : PHP_INT_MAX;
            $b_line = isset( $b['_line_no'] ) && '' !== (string) $b['_line_no'] ? (int) $b['_line_no'] : PHP_INT_MAX;
            if ( $a_line === $b_line ) {
                return strcmp( isset( $a['masked_key'] ) ? (string) $a['masked_key'] : '', isset( $b['masked_key'] ) ? (string) $b['masked_key'] : '' );
            }
            return $a_line <=> $b_line;
        }
    );
}
$internal_class_ready = class_exists( 'TPRE_DeepL_Key_Pool_Machine_Translator' );
$base_class           = function_exists( 'tpre_deepl_get_base_translator_class_name' ) ? tpre_deepl_get_base_translator_class_name() : '';
?>
<table class="form-table" role="presentation">
    <tr>
        <th scope="row"><?php esc_html_e( '启用', 'langrouter-for-translatepress' ); ?></th>
        <td>
            <label><input type="checkbox" name="tpre_settings[models][deepl][enabled]" value="1" <?php checked( ! empty( $item['enabled'] ) ); ?>> <?php esc_html_e( '启用 DeepL 子引擎', 'langrouter-for-translatepress' ); ?></label>
        </td>
    </tr>
</table>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row"><label for="tpre-deepl-keys-text"><?php esc_html_e( '账号池', 'langrouter-for-translatepress' ); ?></label></th>
        <td>
            <div class="tpre-deepl-pool-editor">
                <div id="tpre-deepl-line-numbers" class="tpre-deepl-line-numbers" aria-hidden="true"><?php for ( $i = 1; $i <= $pool_line_count; $i++ ) { echo '<div>' . esc_html( (string) $i ) . '</div>'; } ?></div>
                <textarea id="tpre-deepl-keys-text" name="tpre_settings[models][deepl][keys_text]" rows="3" cols="90" class="large-text code tpre-deepl-keys-text" spellcheck="false" wrap="off" data-masked-value="<?php echo esc_attr( $masked_pool_text ); ?>" data-unchanged-flag-name="tpre_settings[models][deepl][keys_text_unchanged]"><?php echo esc_textarea( $masked_pool_text ); ?></textarea>
            </div>
            <p class="description"><?php echo wp_kses_post( __( '一行一个，前缀 <code>free:</code> / <code>pro:</code>；若不写前缀，默认按 <code>free</code> 处理，添加前缀 <code>pro:</code> 时才走专业版接口，<code>:fx</code> key 也会自动识别为 <code>free</code>，TranslatePress 内置的 DeepL 会直接使用这里配置的账号池。', 'langrouter-for-translatepress' ) ); ?></p>
            <p style="margin-top:10px;">
                <label><input type="checkbox" name="tpre_settings[models][deepl][clear_keys_pool]" value="1" /> <?php esc_html_e( '保存时清空当前账号池', 'langrouter-for-translatepress' ); ?></label>
            </p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( '429 冷却秒数', 'langrouter-for-translatepress' ); ?></th>
        <td><input type="number" min="1" name="tpre_settings[models][deepl][throttle_seconds]" value="<?php echo esc_attr( (string) ( $item['throttle_seconds'] ?? 15 ) ); ?>" /></td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( '网络错误 / 5xx 冷却秒数', 'langrouter-for-translatepress' ); ?></th>
        <td><input type="number" min="1" name="tpre_settings[models][deepl][error_cooldown]" value="<?php echo esc_attr( (string) ( $item['error_cooldown'] ?? 120 ) ); ?>" /></td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( '456 配额冷却秒数', 'langrouter-for-translatepress' ); ?></th>
        <td><input type="number" min="1" name="tpre_settings[models][deepl][quota_cooldown]" value="<?php echo esc_attr( (string) ( $item['quota_cooldown'] ?? 1800 ) ); ?>" /></td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( '403 冷却秒数', 'langrouter-for-translatepress' ); ?></th>
        <td>
            <input type="number" min="1" name="tpre_settings[models][deepl][forbidden_cooldown]" value="<?php echo esc_attr( (string) ( $item['forbidden_cooldown'] ?? 600 ) ); ?>" />
            <p class="description"><?php esc_html_e( '403 一般不是额度问题，通常是 key 无效、接口类型不匹配或权限受限。', 'langrouter-for-translatepress' ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( '备注', 'langrouter-for-translatepress' ); ?></th>
        <td><textarea name="tpre_settings[models][deepl][note]" rows="3" class="large-text"><?php echo esc_textarea( $item['note'] ?? '' ); ?></textarea></td>
    </tr>
</table>

<table class="widefat striped tpre-deepl-status-box">
    <tbody>
        <tr>
            <td class="tpre-deepl-status-label-cell"><strong><?php esc_html_e( '内置桥接类', 'langrouter-for-translatepress' ); ?></strong></td>
            <td><?php echo $internal_class_ready ? '<span class="tpre-status-ready">' . esc_html__( '已就绪', 'langrouter-for-translatepress' ) . '</span>' : '<span class="tpre-status-not-ready">' . esc_html__( '未就绪', 'langrouter-for-translatepress' ) . '</span>'; ?></td>
        </tr>
        <tr>
            <td><strong><?php esc_html_e( '检测到的 DeepL 基类', 'langrouter-for-translatepress' ); ?></strong></td>
            <td><code><?php echo esc_html( $base_class ? $base_class : __( '未检测到', 'langrouter-for-translatepress' ) ); ?></code></td>
        </tr>
        <tr>
            <td><strong><?php esc_html_e( '优先级说明', 'langrouter-for-translatepress' ); ?></strong></td>
            <td><?php esc_html_e( '优先且只走账号池；路由版不再回退 TranslatePress 原始 DeepL key。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
    </tbody>
</table>

<h3><?php esc_html_e( '运行状态', 'langrouter-for-translatepress' ); ?></h3>
<p class="description"><?php echo wp_kses_post( __( '<strong>状态说明：</strong><code>403</code> 常见于 key 无效、free/pro 接口不匹配或权限受限；<code>456</code> 常见于额度用尽; <code>403</code> 一般用于 Key 被限制。', 'langrouter-for-translatepress' ) ); ?></p>
<?php if ( empty( $runtime_entries ) ) : ?>
    <p><?php esc_html_e( '暂时还没有运行记录。保存设置后，触发一次 DeepL 翻译，即可看到状态。', 'langrouter-for-translatepress' ); ?></p>
<?php else : ?>
    <div class="tpre-deepl-runtime-table-wrap">
        <table class="widefat striped tpre-deepl-runtime-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Key', 'langrouter-for-translatepress' ); ?></th>
                    <th><?php esc_html_e( '序号', 'langrouter-for-translatepress' ); ?></th>
                    <th><?php esc_html_e( '类型', 'langrouter-for-translatepress' ); ?></th>
                    <th><?php esc_html_e( '角色', 'langrouter-for-translatepress' ); ?></th>
                    <th><?php esc_html_e( '最近状态码', 'langrouter-for-translatepress' ); ?></th>
                    <th><?php esc_html_e( '状态', 'langrouter-for-translatepress' ); ?></th>
                    <th><?php esc_html_e( '冷却到期', 'langrouter-for-translatepress' ); ?></th>
                    <th><?php esc_html_e( '最近成功', 'langrouter-for-translatepress' ); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $runtime_entries as $entry ) : ?>
                <tr>
                    <td><code><?php echo esc_html( $entry['masked_key'] ?? '-' ); ?></code></td>
                    <td><?php echo esc_html( isset( $entry['_line_no'] ) && '' !== (string) $entry['_line_no'] ? (string) $entry['_line_no'] : '-' ); ?></td>
                    <td><?php echo esc_html( $entry['type'] ?? '-' ); ?></td>
                    <td><?php echo esc_html( ( isset( $entry['is_fallback'] ) && 'yes' === $entry['is_fallback'] ) ? __( '兜底', 'langrouter-for-translatepress' ) : __( '账号池', 'langrouter-for-translatepress' ) ); ?></td>
                    <td><?php echo esc_html( isset( $entry['last_code'] ) ? (string) $entry['last_code'] : '-' ); ?></td>
                    <td><?php echo esc_html( function_exists( 'tpre_deepl_status_label' ) ? tpre_deepl_status_label( $entry['status'] ?? '-' ) : ( $entry['status'] ?? '-' ) ); ?></td>
                    <td><?php echo esc_html( ! empty( $entry['cooldown_until'] ) ? TPRE_Logger::format_local_time( 'Y-m-d H:i:s', (int) $entry['cooldown_until'] ) : '-' ); ?></td>
                    <td><?php echo esc_html( ! empty( $entry['last_success_at'] ) ? TPRE_Logger::format_local_time( 'Y-m-d H:i:s', (int) $entry['last_success_at'] ) : '-' ); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
