<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row"><?php esc_html_e( '启用', 'langrouter-for-translatepress' ); ?></th>
        <td>
            <label><input type="checkbox" name="tpre_settings[models][qwen][enabled]" value="1" <?php checked( ! empty( $item['enabled'] ) ); ?>> <?php esc_html_e( '启用 Qwen 子引擎', 'langrouter-for-translatepress' ); ?></label>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="tpre-qwen-api-key"><?php esc_html_e( 'API Key', 'langrouter-for-translatepress' ); ?></label></th>
        <td>
            <input id="tpre-qwen-api-key" type="password" class="regular-text code" name="tpre_settings[models][qwen][api_key]" value="" autocomplete="off" placeholder="<?php echo wp_kses_post( __( '留空 = 已保存 API Key', 'langrouter-for-translatepress' ) ); ?>" />
            <p class="description">
                <?php esc_html_e( '不同地域需进入阿里百炼后台根据对应地域创建 API Key。', 'langrouter-for-translatepress' ); ?>
                <br>

                <?php esc_html_e( '阿里云中国 KEY：', 'langrouter-for-translatepress' ); ?>
                <a href="https://bailian.console.aliyun.com/cn-beijing?tab=model#/api-key" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e( '北京', 'langrouter-for-translatepress' ); ?>
                </a> ,
                <a href="https://modelstudio.console.aliyun.com/ap-southeast-1?tab=dashboard#/authority" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e( '新加坡', 'langrouter-for-translatepress' ); ?>
                </a> ,
                <a href="https://modelstudio.console.aliyun.com/us-east-1?tab=dashboard#/api-key" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e( '美国', 'langrouter-for-translatepress' ); ?>
                </a>。

                <?php esc_html_e( '阿里云国际 KEY：', 'langrouter-for-translatepress' ); ?>
                <a href="https://bailian.console.alibabacloud.com/cn-beijing?tab=model#/api-key" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e( '北京', 'langrouter-for-translatepress' ); ?>
                </a> ,
                <a href="https://modelstudio.console.alibabacloud.com/ap-southeast-1/?tab=dashboard#/api-key" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e( '新加坡', 'langrouter-for-translatepress' ); ?>
                </a> ,
                <a href="https://modelstudio.console.alibabacloud.com/us-east-1?tab=dashboard#/api-key" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e( '美国', 'langrouter-for-translatepress' ); ?>
                </a>。
            </p>
            <?php if ( ! empty( $item['api_key'] ) ) : ?>
                <p class="description"><?php esc_html_e( '当前已保存 API Key。为了避免泄露，这里不会显示；留空保存会继续沿用当前密钥。', 'langrouter-for-translatepress' ); ?></p>
            <?php endif; ?>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="tpre-qwen-model"><?php esc_html_e( '模型', 'langrouter-for-translatepress' ); ?></label></th>
        <td>
            <select id="tpre-qwen-model" name="tpre_settings[models][qwen][model]">
                <option value="qwen-mt-flash" <?php selected( $item['model'] ?? 'qwen-mt-flash', 'qwen-mt-flash' ); ?>><?php esc_html_e( 'qwen-mt-flash（均衡推荐）', 'langrouter-for-translatepress' ); ?></option>
                <option value="qwen-mt-lite" <?php selected( $item['model'] ?? '', 'qwen-mt-lite' ); ?>><?php esc_html_e( 'qwen-mt-lite（更便宜、更快）', 'langrouter-for-translatepress' ); ?></option>
                <option value="qwen-mt-plus" <?php selected( $item['model'] ?? '', 'qwen-mt-plus' ); ?>><?php esc_html_e( 'qwen-mt-plus（质量优先）', 'langrouter-for-translatepress' ); ?></option>
            </select>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="tpre-qwen-region"><?php esc_html_e( '地域', 'langrouter-for-translatepress' ); ?></label></th>
        <td>
            <select id="tpre-qwen-region" name="tpre_settings[models][qwen][region]">
                <option value="beijing" <?php selected( $item['region'] ?? 'beijing', 'beijing' ); ?>><?php esc_html_e( '北京', 'langrouter-for-translatepress' ); ?></option>
                <option value="singapore" <?php selected( $item['region'] ?? '', 'singapore' ); ?>><?php esc_html_e( '新加坡', 'langrouter-for-translatepress' ); ?></option>
                <option value="us" <?php selected( $item['region'] ?? '', 'us' ); ?>><?php esc_html_e( '美国（弗吉尼亚）', 'langrouter-for-translatepress' ); ?></option>
            </select>
            <p class="description"><?php esc_html_e( '将自动使用该地域对应的官方兼容接口地址。', 'langrouter-for-translatepress' ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="tpre-qwen-endpoint"><?php esc_html_e( '自定义API', 'langrouter-for-translatepress' ); ?></label></th>
        <td>
            <input id="tpre-qwen-endpoint" type="url" class="large-text code" name="tpre_settings[models][qwen][endpoint]" value="<?php echo esc_attr( $item['endpoint'] ?? '' ); ?>" placeholder="<?php echo esc_attr__( 'Optional custom endpoint URL', 'langrouter-for-translatepress' ); ?>" />
            <p class="description"><?php esc_html_e( '通常留空即可。留空时会按上面的地域自动选择官方地址；这里只在反向代理、企业网关或自建转发场景下填写自定义 API 地址。', 'langrouter-for-translatepress' ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="tpre-qwen-timeout"><?php esc_html_e( '超时（秒）', 'langrouter-for-translatepress' ); ?></label></th>
        <td>
            <input id="tpre-qwen-timeout" type="number" min="5" name="tpre_settings[models][qwen][timeout]" value="<?php echo esc_attr( (string) ( $item['timeout'] ?? 20 ) ); ?>" />
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="tpre-qwen-concurrency"><?php esc_html_e( '并发数', 'langrouter-for-translatepress' ); ?></label></th>
        <td>
            <input id="tpre-qwen-concurrency" type="number" min="0" max="32" name="tpre_settings[models][qwen][concurrency]" value="<?php echo esc_attr( (string) ( $item['concurrency'] ?? 0 ) ); ?>" />
            <p class="description"><?php esc_html_e( '同时发起的请求数量。填 0 表示继承路由设置里的全局默认并发；如果全局也为 0，则使用 Qwen 的内置默认值。遇到 429、超时或 CPU 占用过高时，可先降到 1–3。', 'langrouter-for-translatepress' ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="tpre-qwen-extra-body-json"><?php esc_html_e( '附加请求 JSON', 'langrouter-for-translatepress' ); ?></label></th>
        <td>
            <textarea id="tpre-qwen-extra-body-json" name="tpre_settings[models][qwen][extra_body_json]" rows="6" class="large-text code" placeholder='{"translation_options":{"domains":"IT domain"}}'><?php echo esc_textarea( $item['extra_body_json'] ?? '' ); ?></textarea>
            <p class="description">
                <?php
                echo wp_kses_post(
                    __(
                        '可选。用于附加 Qwen-MT 的高级参数，例如 <code>terms</code>、<code>tm_list</code>、<code>domains</code> 等；插件会把这里的 JSON 递归合并进请求体。请填写合法 JSON 对象；若填写无效，保存时会保留上一次有效值。',
                        'langrouter-for-translatepress'
                    )
                );
                ?>
            </p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( '备注', 'langrouter-for-translatepress' ); ?></th>
        <td><textarea name="tpre_settings[models][qwen][note]" rows="3" class="large-text"><?php echo esc_textarea( $item['note'] ?? '' ); ?></textarea></td>
    </tr>
</table>


