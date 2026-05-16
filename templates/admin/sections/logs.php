<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-local variables provided by the renderer.
?>


<?php $tpre_log_enabled = ! empty( $router_settings['log_enabled'] ); ?>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row"><?php esc_html_e( '启用', 'langrouter-for-translatepress' ); ?></th>
        <td>
            <label><input type="checkbox" name="tpre_log_enabled" value="1" <?php checked( ! empty( $router_settings['log_enabled'] ) ); ?>> <?php esc_html_e( '启用文件日志', 'langrouter-for-translatepress' ); ?></label>
            <p class="description" style="margin-top:20px;">
                <?php 
                printf( 
                    wp_kses_post( 
                        /* translators: %s: Absolute log directory path. */ 
                        __(
                            '日志目录：<code>%s</code>',
                            'langrouter-for-translatepress'
                        )
                    ), 
                    esc_html( $log_dir ) 
                ); 
                ?>
            </p>
            <p class="description"><?php esc_html_e( '日志文件名与日志行时间会优先跟随 WordPress 后台时区；若 WordPress 未设置时区，则回退到当前 PHP 时区。', 'langrouter-for-translatepress' ); ?></p>
        </td>
    </tr>
</table>

<hr style="margin-top:24px;">
<h2><?php esc_html_e( '运行日志查看', 'langrouter-for-translatepress' ); ?></h2>
<p class="description"><?php echo wp_kses_post( __( '这里直接查看日志目录下的文件，无需再进服务器。当前只展示所选日志文件最后一段内容，便于排查 Router / 火山 / DeepL 是否真正进入执行链路。', 'langrouter-for-translatepress' ) ); ?></p>

<?php if ( ! $tpre_log_enabled ) : ?>
    <p><?php esc_html_e( '当前未启用文件日志。启用后并重新触发一次前台翻译，才会显示可查看的日志文件。', 'langrouter-for-translatepress' ); ?></p>
<?php elseif ( empty( $available_logs ) ) : ?>
    <p>
        <?php
        printf(
            wp_kses_post(
                /* translators: %s: Link to the TranslatePress automatic translation settings page. */
                __(
                    '当前还没有生成任何日志文件。请先启用文件日志，然后重新触发一次前台翻译或前往 <a href="%s" target="_blank" rel="noopener noreferrer">TranslatePress 自动翻译设置</a> 执行测试。',
                    'langrouter-for-translatepress'
                )
            ),
            esc_url( $translation_url )
        );
        ?>
    </p>
<?php else : ?>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php esc_html_e( '日志文件', 'langrouter-for-translatepress' ); ?></th>
            <td>
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <select onchange="if(this.value){window.location.href=this.value;}">
                        <?php foreach ( $available_logs as $log_item ) : ?>
                            <?php $log_url = add_query_arg( [ 'tpre_log_file' => $log_item['basename'] ], $log_view_base_url ); ?>
                            <option value="<?php echo esc_url( $log_url ); ?>" <?php selected( $selected_log_file, $log_item['basename'] ); ?>><?php echo esc_html( $log_item['basename'] . ' (' . size_format( (int) $log_item['size'] ) . ')' ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ( '' !== $selected_log_file ) : ?>
                        <?php $open_url = add_query_arg( [ 'tpre_log_file' => $selected_log_file ], $log_view_base_url ); ?>
                        <?php $download_url = wp_nonce_url( add_query_arg( [ 'tpre_action' => 'download_log', 'tpre_log_file' => $selected_log_file ], TPRE_Admin_Settings::get_model_tab_url( 'logs' ) ), 'tpre_download_log_file' ); ?>
                        <?php $delete_url = wp_nonce_url( add_query_arg( [ 'tpre_action' => 'delete_log', 'tpre_log_file' => $selected_log_file ], TPRE_Admin_Settings::get_model_tab_url( 'logs' ) ), 'tpre_delete_log_file' ); ?>
                        <a class="button button-secondary" href="<?php echo esc_url( $open_url ); ?>"><?php esc_html_e( '刷新当前日志', 'langrouter-for-translatepress' ); ?></a>
                        <a class="button button-secondary" href="<?php echo esc_url( $download_url ); ?>"><?php esc_html_e( '下载日志文件', 'langrouter-for-translatepress' ); ?></a>
                        <a class="button button-secondary" href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('<?php echo esc_js( __( '确定删除当前日志文件吗？若删除的是今天的日志，插件会优先清空内容，后续有新请求时日志可能再次写入。', 'langrouter-for-translatepress' ) ); ?>');"><?php esc_html_e( '删除 / 清空日志文件', 'langrouter-for-translatepress' ); ?></a>
                    <?php endif; ?>
                </div>
                <p class="description"><?php echo wp_kses_post( __( '默认展示最新日志文件。日志内容中若出现 <code>Router 开始分发翻译请求</code>、<code>调用 DeepL 翻译</code>、<code>DeepL 尝试账号池条目</code> 等记录，就说明请求已经进入对应执行链路。删除的是当天日志时，插件会优先清空内容；如果随后仍有新请求写日志，文件会再次出现。', 'langrouter-for-translatepress' ) ); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( '日志内容', 'langrouter-for-translatepress' ); ?></th>
            <td>
                <?php if ( ! empty( $selected_log_data['exists'] ) ) : ?>
                    <textarea readonly rows="20" class="large-text code" spellcheck="false" style="font-family:Consolas,Monaco,monospace;"><?php echo esc_textarea( $selected_log_data['content'] ); ?></textarea>
                    <?php if ( ! empty( $selected_log_data['truncated'] ) ) : ?>
                        <p class="description"><?php esc_html_e( '当前文件较大，已只显示尾部内容。', 'langrouter-for-translatepress' ); ?></p>
                    <?php endif; ?>
                <?php else : ?>
                    <p><?php esc_html_e( '未找到可读取的日志文件。', 'langrouter-for-translatepress' ); ?></p>
                <?php endif; ?>
            </td>
        </tr>
    </table>
<?php endif; ?>
