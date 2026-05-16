<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row"><?php esc_html_e( '启用', 'langrouter-for-translatepress' ); ?></th>
        <td>
            <label><input type="checkbox" name="tpre_settings[models][openai][enabled]" value="1" <?php checked( ! empty( $item['enabled'] ) ); ?>> <?php esc_html_e( '启用 OpenAI 子引擎', 'langrouter-for-translatepress' ); ?></label>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="tpre-openai-api-key"><?php esc_html_e( 'API Key', 'langrouter-for-translatepress' ); ?></label></th>
        <td>
            <input id="tpre-openai-api-key" type="password" class="regular-text code" name="tpre_settings[models][openai][api_key]" value="" autocomplete="off" placeholder="<?php echo wp_kses_post( __( '留空 = 已保存 API Key', 'langrouter-for-translatepress' ) ); ?>" />
            <p class="description">
                <?php
                printf(
                    wp_kses(
                        /* translators: %1$s: Link to create an OpenAI API key. */
                        __(
                            '填写 OpenAI 官方 API Key，%1$s。',
                            'langrouter-for-translatepress'
                        ),
                        array(
                            'a' => array(
                                'href'   => array(),
                                'target' => array(),
                                'rel'    => array(),
                            ),
                        )
                    ),
                    '<a href="https://platform.openai.com/account/api-keys" target="_blank" rel="noopener noreferrer">' . esc_html__( '创建 OpenAI API Key', 'langrouter-for-translatepress' ) . '</a>'
                );
                ?>
            </p>
            <?php if ( ! empty( $item['api_key'] ) ) : ?>
                <p class="description"><?php esc_html_e( '当前已保存 API Key。为了避免泄露，这里不会显示；留空保存会继续沿用当前密钥。', 'langrouter-for-translatepress' ); ?></p>
            <?php endif; ?>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="tpre-openai-model"><?php esc_html_e( '官方模型', 'langrouter-for-translatepress' ); ?></label></th>
        <td>
            <select id="tpre-openai-model" name="tpre_settings[models][openai][model]">
                <option value="gpt-4o-mini" <?php selected( $item['model'] ?? 'gpt-4o-mini', 'gpt-4o-mini' ); ?>><?php esc_html_e( 'gpt-4o-mini（快速、低成本）', 'langrouter-for-translatepress' ); ?></option>
                <option value="gpt-4.1-mini" <?php selected( $item['model'] ?? '', 'gpt-4.1-mini' ); ?>><?php esc_html_e( 'gpt-4.1-mini（均衡推荐）', 'langrouter-for-translatepress' ); ?></option>
                <option value="gpt-4.1-nano" <?php selected( $item['model'] ?? '', 'gpt-4.1-nano' ); ?>><?php esc_html_e( 'gpt-4.1-nano（更轻量）', 'langrouter-for-translatepress' ); ?></option>
                <option value="gpt-4.1" <?php selected( $item['model'] ?? '', 'gpt-4.1' ); ?>><?php esc_html_e( 'gpt-4.1（质量优先）', 'langrouter-for-translatepress' ); ?></option>
                <option value="gpt-4o" <?php selected( $item['model'] ?? '', 'gpt-4o' ); ?>><?php esc_html_e( 'gpt-4o（通用高质量）', 'langrouter-for-translatepress' ); ?></option>
                <option value="custom" <?php selected( $item['model'] ?? '', 'custom' ); ?>><?php esc_html_e( '自定义模型', 'langrouter-for-translatepress' ); ?></option>
            </select>
            <p class="description"><?php echo wp_kses_post( __( '建议默认先用 <code>gpt-4o-mini</code> 或 <code>gpt-4.1-mini</code>。若下拉列表没有你要的官方模型，可在下面“自定义模型”直接填写。', 'langrouter-for-translatepress' ) ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="tpre-openai-custom-model"><?php esc_html_e( '自定义模型', 'langrouter-for-translatepress' ); ?></label></th>
        <td>
            <input id="tpre-openai-custom-model" type="text" class="regular-text" name="tpre_settings[models][openai][custom_model]" value="<?php echo esc_attr( $item['custom_model'] ?? '' ); ?>" placeholder="<?php esc_html_e('例如：gpt-4.1-mini 或其他兼容模型名', 'langrouter-for-translatepress'); ?>" />
            <p class="description"><?php echo wp_kses_post( __( '例: <code>gpt-4o-mini</code> ，此处填写模型时，将优先使用这里的模型名。', 'langrouter-for-translatepress' ) ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="tpre-openai-endpoint"><?php esc_html_e( '自定义API', 'langrouter-for-translatepress' ); ?></label></th>
        <td>
            <input id="tpre-openai-endpoint" type="text" class="regular-text code" name="tpre_settings[models][openai][endpoint]" value="<?php echo esc_attr( $item['endpoint'] ?? '' ); ?>" placeholder="<?php echo esc_attr__( 'Optional custom endpoint URL', 'langrouter-for-translatepress' ); ?>" />
            <p class="description">
                <?php
                printf(
                    wp_kses(
                        /* translators: %1$s: Example base API endpoint wrapped in <code>. */
                        __(
                            '比如 %1$s 不加斜杠，留空时默认使用 OpenAI 官方接口。',
                            'langrouter-for-translatepress'
                        ),
                        array(
                            'code' => array(), 
                        )
                    ),
                    '<code>provider base URL</code>'
                );
                ?>
            </p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( '超时（秒）', 'langrouter-for-translatepress' ); ?></th>
        <td><input type="number" min="5" name="tpre_settings[models][openai][timeout]" value="<?php echo esc_attr( (string) ( $item['timeout'] ?? 25 ) ); ?>" /></td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( '并发数', 'langrouter-for-translatepress' ); ?></th>
        <td>
            <input type="number" min="0" max="32" name="tpre_settings[models][openai][concurrency]" value="<?php echo esc_attr( (string) ( $item['concurrency'] ?? 0 ) ); ?>" />
            <p class="description"><?php esc_html_e( '同时发起的请求数量。填 0 表示继承路由设置里的全局默认并发；如果全局也为 0，则继续使用当前模型的内置默认值。低配服务器可先试 1–3。', 'langrouter-for-translatepress' ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( '附加请求 JSON', 'langrouter-for-translatepress' ); ?></th>
        <td>
            <textarea name="tpre_settings[models][openai][extra_body_json]" rows="6" class="large-text code" placeholder='{"max_tokens":1024}'><?php echo esc_textarea( $item['extra_body_json'] ?? '' ); ?></textarea>
            <p class="description"><?php echo wp_kses_post( __( '可选。用于覆盖或补充 OpenAI 请求体参数，例如 <code>max_tokens</code> 等。当前默认已固定 <code>temperature=0</code>，并启用“只取译文”解析；请填写合法 JSON 对象；若填写无效，保存时会保留上一次有效值。', 'langrouter-for-translatepress' ) ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( '备注', 'langrouter-for-translatepress' ); ?></th>
        <td><textarea name="tpre_settings[models][openai][note]" rows="3" class="large-text"><?php echo esc_textarea( $item['note'] ?? '' ); ?></textarea></td>
    </tr>
</table>
