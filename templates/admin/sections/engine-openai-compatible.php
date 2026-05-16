<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$tpre_compatible_help_url = '';
if ( class_exists( 'TPRE_Admin_Settings' ) ) {
    $tpre_compatible_help_url = TPRE_Admin_Settings::get_model_tab_url( 'help' );
    $tpre_compatible_help_url = add_query_arg( [ 'tpre_help_section' => 'engines' ], $tpre_compatible_help_url ) . '#help-engine-openai-compatible';
}
?>

<div style="margin:0 0 16px 0;padding:14px 16px;border:1px solid #dcdcde;border-left:4px solid #2271b1;background:#fff;max-width:1100px;">
    <p style="margin:0 0 8px 0;"><strong><?php esc_html_e( '不会调参数？先用推荐起步参数。', 'langrouter-for-translatepress' ); ?></strong></p>
    <p style="margin:0 0 10px 0;"><?php esc_html_e( '新手只要先做 3 件事：填 API Key、填模型名称、填接口地址；然后点“套用推荐起步参数”；最后保存并实际翻一篇文章测试。能正常翻译就先别动下面那些参数。', 'langrouter-for-translatepress' ); ?></p>
    <p style="margin:0 0 10px 0;"><?php echo wp_kses_post( __( '推荐起步值已经内置好了：<code>timeout=60</code>、<code>concurrency=4</code>、<code>max_tokens=2200</code>、<code>retry=2</code>、<code>长文本阈值=1800</code>、<code>长文本切块=1200</code>、<code>长 HTML 切块=1600</code>。你不需要背这些，点按钮套用就行。', 'langrouter-for-translatepress' ) ); ?></p>
    <p style="margin:0;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <button type="button" class="button button-secondary" id="tpre-openai-compatible-apply-safe-preset" data-success-message="<?php echo esc_attr__( '已套用兼容 OpenAI 的推荐起步参数。请记得点击页面底部的“保存设置”。', 'langrouter-for-translatepress' ); ?>"><?php esc_html_e( '套用推荐起步参数', 'langrouter-for-translatepress' ); ?></button>
        <?php if ( '' !== $tpre_compatible_help_url ) : ?>
            <a class="button button-link" href="<?php echo esc_url( $tpre_compatible_help_url ); ?>"><?php esc_html_e( '查看兼容 OpenAI 参数说明', 'langrouter-for-translatepress' ); ?></a>
        <?php endif; ?>
    </p>
</div>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row"><?php esc_html_e( '启用', 'langrouter-for-translatepress' ); ?></th>
        <td>
            <label><input type="checkbox" name="tpre_settings[models][openai_compatible][enabled]" value="1" <?php checked( ! empty( $item['enabled'] ) ); ?>> <?php esc_html_e( '启用兼容 OpenAI API 子引擎', 'langrouter-for-translatepress' ); ?></label>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="tpre-openai-compatible-api-key"><?php esc_html_e( 'API Key', 'langrouter-for-translatepress' ); ?></label></th>
        <td>
            <input id="tpre-openai-compatible-api-key" type="password" class="regular-text code" name="tpre_settings[models][openai_compatible][api_key]" value="" autocomplete="off" placeholder="<?php echo wp_kses_post( __( '留空 = 已保存 API Key', 'langrouter-for-translatepress' ) ); ?>" />
            <p class="description"><?php esc_html_e( '填你这个平台给你的密钥。不会填别猜，去平台后台原样复制。', 'langrouter-for-translatepress' ); ?></p>
            <?php if ( ! empty( $item['api_key'] ) ) : ?>
                <p class="description"><?php esc_html_e( '当前已保存 API Key。为了避免泄露，这里不会显示；留空保存会继续沿用当前密钥。', 'langrouter-for-translatepress' ); ?></p>
            <?php endif; ?>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="tpre-openai-compatible-model"><?php esc_html_e( '模型名称', 'langrouter-for-translatepress' ); ?></label></th>
        <td>
            <input id="tpre-openai-compatible-model" type="text" class="regular-text" name="tpre_settings[models][openai_compatible][model]" value="<?php echo esc_attr( $item['model'] ?? '' ); ?>" placeholder="<?php echo esc_attr( __( '例如：provider-model-name', 'langrouter-for-translatepress' ) ); ?>" />
            <p class="description"><?php echo wp_kses_post( __( '填平台文档要求的模型名，必须一字不差。填错模型名，请求通常会直接失败。', 'langrouter-for-translatepress' ) ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="tpre-openai-compatible-endpoint"><?php esc_html_e( '接口地址', 'langrouter-for-translatepress' ); ?></label></th>
        <td>
            <input id="tpre-openai-compatible-endpoint" type="text" class="regular-text code" name="tpre_settings[models][openai_compatible][endpoint]" value="<?php echo esc_attr( $item['endpoint'] ?? '' ); ?>" placeholder="<?php echo esc_attr__( 'Full chat completions endpoint URL', 'langrouter-for-translatepress' ); ?>" />
            <p class="description">
                <?php
                printf(
                    wp_kses(
                        /* translators: 1: generic API base path example, 2: appended endpoint path. */
                        __(
                            '优先填平台给你的完整接口地址。若你只拿到 %1$s 这种基础路径，插件会自动补成 %2$s。最常见的报错之一就是接口地址填错。',
                            'langrouter-for-translatepress'
                        ),
                        array(
                            'code' => array(),
                        )
                    ),
                    '<code>.../v1</code>',
                    '<code>/chat/completions</code>'
                );
                ?>
            </p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( '超时（秒）', 'langrouter-for-translatepress' ); ?></th>
        <td>
            <input type="number" min="5" name="tpre_settings[models][openai_compatible][timeout]" value="<?php echo esc_attr( (string) ( $item['timeout'] ?? 60 ) ); ?>" />
            <p class="description"><?php esc_html_e( '一条请求最多等多久。文章一长就报 timeout，就把它调大。新手直接用 60。', 'langrouter-for-translatepress' ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( '并发数', 'langrouter-for-translatepress' ); ?></th>
        <td>
            <input type="number" min="0" max="32" name="tpre_settings[models][openai_compatible][concurrency]" value="<?php echo esc_attr( (string) ( $item['concurrency'] ?? 4 ) ); ?>" />
            <p class="description"><?php esc_html_e( '一次同时发几条请求。越大越快，但越容易把第三方网关打崩。新手用 4；经常 429 或超时就先改成 2。', 'langrouter-for-translatepress' ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( '单请求最大输出 Token', 'langrouter-for-translatepress' ); ?></th>
        <td>
            <input type="number" min="128" step="1" name="tpre_settings[models][openai_compatible][max_tokens]" value="<?php echo esc_attr( (string) ( $item['max_tokens'] ?? 2200 ) ); ?>" />
            <p class="description"><?php esc_html_e( '控制模型这一次最多吐多少内容。翻到一半被截断就加大；没遇到截断就保持 2200。', 'langrouter-for-translatepress' ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( '重试次数', 'langrouter-for-translatepress' ); ?></th>
        <td>
            <input type="number" min="0" max="3" step="1" name="tpre_settings[models][openai_compatible][retry_count]" value="<?php echo esc_attr( (string) ( $item['retry_count'] ?? 2 ) ); ?>" />
            <p class="description"><?php esc_html_e( '失败后再试几次。偶发报错有用，但别设太高。新手用 2 就行。', 'langrouter-for-translatepress' ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( '短文本合并阈值', 'langrouter-for-translatepress' ); ?></th>
        <td>
            <input type="number" min="0" step="1" name="tpre_settings[models][openai_compatible][short_text_merge_threshold]" value="<?php echo esc_attr( (string) ( $item['short_text_merge_threshold'] ?? 36 ) ); ?>" />
            <p class="description"><?php esc_html_e( '页面里很多很短的小句子时，插件会尝试先拼一拼再翻，减少请求数。看不懂就别动，保持 36。', 'langrouter-for-translatepress' ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( 'temperature', 'langrouter-for-translatepress' ); ?></th>
        <td>
            <input type="number" min="0" max="2" step="0.1" name="tpre_settings[models][openai_compatible][temperature]" value="<?php echo esc_attr( (string) ( $item['temperature'] ?? 0 ) ); ?>" />
            <p class="description"><?php echo wp_kses_post( __( '翻译要稳，不要太会发挥。不会调就用 <code>0</code>。', 'langrouter-for-translatepress' ) ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( 'top_p', 'langrouter-for-translatepress' ); ?></th>
        <td>
            <input type="number" min="0" max="1" step="0.1" name="tpre_settings[models][openai_compatible][top_p]" value="<?php echo esc_attr( (string) ( $item['top_p'] ?? 1 ) ); ?>" />
            <p class="description"><?php echo wp_kses_post( __( '通常保持 <code>1</code> 就行。你已经把 temperature 设低时，top_p 一般别乱改。', 'langrouter-for-translatepress' ) ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( '自定义 system prompt', 'langrouter-for-translatepress' ); ?></th>
        <td>
            <textarea name="tpre_settings[models][openai_compatible][system_prompt]" rows="5" class="large-text code" placeholder="Prefer concise Japanese website copy. Keep honorifics minimal."><?php echo esc_textarea( $item['system_prompt'] ?? '' ); ?></textarea>
            <p class="description"><?php esc_html_e( '没特殊要求就留空。只有你明确要控制术语、语气、敬语、品牌口吻时再写。', 'langrouter-for-translatepress' ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( '高级运行参数', 'langrouter-for-translatepress' ); ?></th>
        <td>
            <details style="max-width:1100px;">
                <summary style="cursor:pointer;font-weight:600;"><?php esc_html_e( '展开高级参数（只有遇到长文超时、HTML 大段落慢、429 时再看）', 'langrouter-for-translatepress' ); ?></summary>
                <fieldset class="openai-compatible-advanced-parameters" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px 16px;max-width:980px;align-items:end;margin-top:14px;">
                    <label>
                        <span><?php esc_html_e( '短句打包条数', 'langrouter-for-translatepress' ); ?></span><br /><small style="color:#646970;"><code>batch_size</code></small><br />
                        <input type="number" min="1" max="50" step="1" name="tpre_settings[models][openai_compatible][batch_size]" value="<?php echo esc_attr( (string) ( $item['batch_size'] ?? 6 ) ); ?>" />
                    </label>
                    <label>
                        <span><?php esc_html_e( '每批总字符上限', 'langrouter-for-translatepress' ); ?></span><br /><small style="color:#646970;"><code>batch_max_chars</code></small><br />
                        <input type="number" min="200" step="1" name="tpre_settings[models][openai_compatible][batch_max_chars]" value="<?php echo esc_attr( (string) ( $item['batch_max_chars'] ?? 1200 ) ); ?>" />
                    </label>
                    <label>
                        <span><?php esc_html_e( '批量标签输出上限', 'langrouter-for-translatepress' ); ?></span><br /><small style="color:#646970;"><code>label_max_tokens</code></small><br />
                        <input type="number" min="0" max="512" step="1" name="tpre_settings[models][openai_compatible][label_max_tokens]" value="<?php echo esc_attr( (string) ( $item['label_max_tokens'] ?? 0 ) ); ?>" />
                    </label>
                    <label>
                        <span><?php esc_html_e( '超过多少字开始切段', 'langrouter-for-translatepress' ); ?></span><br /><small style="color:#646970;"><code>long_text_threshold</code></small><br />
                        <input type="number" min="400" step="1" name="tpre_settings[models][openai_compatible][long_text_threshold]" value="<?php echo esc_attr( (string) ( $item['long_text_threshold'] ?? 1800 ) ); ?>" />
                    </label>
                    <label>
                        <span><?php esc_html_e( '普通长文每段多大', 'langrouter-for-translatepress' ); ?></span><br /><small style="color:#646970;"><code>long_text_chunk_chars</code></small><br />
                        <input type="number" min="200" step="1" name="tpre_settings[models][openai_compatible][long_text_chunk_chars]" value="<?php echo esc_attr( (string) ( $item['long_text_chunk_chars'] ?? 1200 ) ); ?>" />
                    </label>
                    <label>
                        <span><?php esc_html_e( 'HTML 长段每段多大', 'langrouter-for-translatepress' ); ?></span><br /><small style="color:#646970;"><code>long_html_chunk_chars</code></small><br />
                        <input type="number" min="200" step="1" name="tpre_settings[models][openai_compatible][long_html_chunk_chars]" value="<?php echo esc_attr( (string) ( $item['long_html_chunk_chars'] ?? 1600 ) ); ?>" />
                    </label>
                    <label>
                        <span><?php esc_html_e( '长文降并发阈值 1', 'langrouter-for-translatepress' ); ?></span><br /><small style="color:#646970;"><code>long_text_medium_threshold</code></small><br />
                        <input type="number" min="400" step="1" name="tpre_settings[models][openai_compatible][long_text_medium_threshold]" value="<?php echo esc_attr( (string) ( $item['long_text_medium_threshold'] ?? 1600 ) ); ?>" />
                    </label>
                    <label>
                        <span><?php esc_html_e( '长文并发上限 1', 'langrouter-for-translatepress' ); ?></span><br /><small style="color:#646970;"><code>long_text_concurrency_medium</code></small><br />
                        <input type="number" min="1" max="32" step="1" name="tpre_settings[models][openai_compatible][long_text_concurrency_medium]" value="<?php echo esc_attr( (string) ( $item['long_text_concurrency_medium'] ?? 4 ) ); ?>" />
                    </label>
                    <label>
                        <span><?php esc_html_e( '长文降并发阈值 2', 'langrouter-for-translatepress' ); ?></span><br /><small style="color:#646970;"><code>long_text_large_threshold</code></small><br />
                        <input type="number" min="400" step="1" name="tpre_settings[models][openai_compatible][long_text_large_threshold]" value="<?php echo esc_attr( (string) ( $item['long_text_large_threshold'] ?? 2400 ) ); ?>" />
                    </label>
                    <label>
                        <span><?php esc_html_e( '长文并发上限 2', 'langrouter-for-translatepress' ); ?></span><br /><small style="color:#646970;"><code>long_text_concurrency_large</code></small><br />
                        <input type="number" min="1" max="32" step="1" name="tpre_settings[models][openai_compatible][long_text_concurrency_large]" value="<?php echo esc_attr( (string) ( $item['long_text_concurrency_large'] ?? 3 ) ); ?>" />
                    </label>
                    <label>
                        <span><?php esc_html_e( '长文降并发阈值 3', 'langrouter-for-translatepress' ); ?></span><br /><small style="color:#646970;"><code>long_text_extreme_threshold</code></small><br />
                        <input type="number" min="400" step="1" name="tpre_settings[models][openai_compatible][long_text_extreme_threshold]" value="<?php echo esc_attr( (string) ( $item['long_text_extreme_threshold'] ?? 3200 ) ); ?>" />
                    </label>
                    <label>
                        <span><?php esc_html_e( '长文并发上限 3', 'langrouter-for-translatepress' ); ?></span><br /><small style="color:#646970;"><code>long_text_concurrency_extreme</code></small><br />
                        <input type="number" min="1" max="32" step="1" name="tpre_settings[models][openai_compatible][long_text_concurrency_extreme]" value="<?php echo esc_attr( (string) ( $item['long_text_concurrency_extreme'] ?? 2 ) ); ?>" />
                    </label>
                    <label>
                        <span><?php esc_html_e( '单条请求基础超时', 'langrouter-for-translatepress' ); ?></span><br /><small style="color:#646970;"><code>single_request_timeout_base</code></small><br />
                        <input type="number" min="5" step="1" name="tpre_settings[models][openai_compatible][single_request_timeout_base]" value="<?php echo esc_attr( (string) ( $item['single_request_timeout_base'] ?? 45 ) ); ?>" />
                    </label>
                    <label>
                        <span><?php esc_html_e( '每隔多少字加一次超时', 'langrouter-for-translatepress' ); ?></span><br /><small style="color:#646970;"><code>single_request_timeout_step_chars</code></small><br />
                        <input type="number" min="50" step="1" name="tpre_settings[models][openai_compatible][single_request_timeout_step_chars]" value="<?php echo esc_attr( (string) ( $item['single_request_timeout_step_chars'] ?? 700 ) ); ?>" />
                    </label>
                    <label>
                        <span><?php esc_html_e( '每次额外加几秒', 'langrouter-for-translatepress' ); ?></span><br /><small style="color:#646970;"><code>single_request_timeout_step_sec</code></small><br />
                        <input type="number" min="0" step="1" name="tpre_settings[models][openai_compatible][single_request_timeout_step_sec]" value="<?php echo esc_attr( (string) ( $item['single_request_timeout_step_sec'] ?? 10 ) ); ?>" />
                    </label>
                    <label>
                        <span><?php esc_html_e( 'HTML 额外加时', 'langrouter-for-translatepress' ); ?></span><br /><small style="color:#646970;"><code>single_request_timeout_html_bonus</code></small><br />
                        <input type="number" min="0" step="1" name="tpre_settings[models][openai_compatible][single_request_timeout_html_bonus]" value="<?php echo esc_attr( (string) ( $item['single_request_timeout_html_bonus'] ?? 10 ) ); ?>" />
                    </label>
                    <label>
                        <span><?php esc_html_e( '单条请求最长可等多久', 'langrouter-for-translatepress' ); ?></span><br /><small style="color:#646970;"><code>single_request_timeout_cap</code></small><br />
                        <input type="number" min="5" step="1" name="tpre_settings[models][openai_compatible][single_request_timeout_cap]" value="<?php echo esc_attr( (string) ( $item['single_request_timeout_cap'] ?? 180 ) ); ?>" />
                    </label>
                </fieldset>
            </details>
            <p class="description"><?php esc_html_e( '上面这一整块主要给排查问题的人用。正常情况下不用改。真要动，优先只看这 3 个："超过多少字开始切段"、"普通长文每段多大"、"单条请求基础超时"。', 'langrouter-for-translatepress' ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( '附加请求头', 'langrouter-for-translatepress' ); ?></th>
        <td>
            <textarea name="tpre_settings[models][openai_compatible][extra_headers]" rows="4" class="large-text code" placeholder="X-API-Key: your-value&#10;X-Workspace: your-workspace"><?php echo esc_textarea( $item['extra_headers'] ?? '' ); ?></textarea>
            <p class="description"><?php echo wp_kses_post( __( '只有平台文档明确让你加请求头时再填。一行一个，格式：<code>Header-Name: value</code>。没要求就留空。', 'langrouter-for-translatepress' ) ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( '附加请求 JSON', 'langrouter-for-translatepress' ); ?></th>
        <td>
            <textarea name="tpre_settings[models][openai_compatible][extra_body_json]" rows="6" class="large-text code" placeholder='{"max_tokens":1024}'><?php echo esc_textarea( $item['extra_body_json'] ?? '' ); ?></textarea>
            <p class="description"><?php esc_html_e( '只有平台文档明确给了额外 JSON 参数时再填，比如关闭 thinking 或指定某个特殊字段。这里会覆盖上面同名设置，所以不确定就留空。', 'langrouter-for-translatepress' ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( '备注', 'langrouter-for-translatepress' ); ?></th>
        <td>
            <textarea name="tpre_settings[models][openai_compatible][note]" rows="3" class="large-text"><?php echo esc_textarea( $item['note'] ?? '' ); ?></textarea>
            <p class="description"><?php esc_html_e( '只是给你自己做备注，不参与请求。比如可写“日语站专用”“便宜但慢”“容易 429”。', 'langrouter-for-translatepress' ); ?></p>
        </td>
    </tr>
</table>

