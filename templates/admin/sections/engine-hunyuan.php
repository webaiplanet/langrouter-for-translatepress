<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row"><?php esc_html_e( '启用', 'langrouter-for-translatepress' ); ?></th>
        <td>
            <label><input type="checkbox" name="tpre_settings[models][hunyuan][enabled]" value="1" <?php checked( ! empty( $item['enabled'] ) ); ?>> <?php esc_html_e( '启用 Hunyuan 子引擎', 'langrouter-for-translatepress' ); ?></label>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="tpre-hunyuan-model"><?php esc_html_e( '模型', 'langrouter-for-translatepress' ); ?></label></th>
        <td>
            <select id="tpre-hunyuan-model" name="tpre_settings[models][hunyuan][model]">
                <option value="hunyuan-translation-lite" <?php selected( $item['model'] ?? 'hunyuan-translation-lite', 'hunyuan-translation-lite' ); ?>>hunyuan-translation-lite</option>
                <option value="hunyuan-translation" <?php selected( $item['model'] ?? '', 'hunyuan-translation' ); ?>>hunyuan-translation</option>
                <option value="hunyuan-mt-7b" <?php selected( $item['model'] ?? '', 'hunyuan-mt-7b' ); ?>>hunyuan-mt-7b</option>
            </select>
            <p class="description">
                <?php
                printf(
                    wp_kses(
                        /* translators: %1$s and %2$s: Tencent model codes in <code>; %3$s: compatible model code in <code>; %4$s and %5$s: SiliconFlow links. */
                        __(
                            '%1$s 与 %2$s 只能使用腾讯云中国和腾讯云国际 KEY，%3$s 是第三方 / 硅基兼容模型入口，%4$s，%5$s。',
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
                    ),
                    '<code>hunyuan-translation-lite</code>',
                    '<code>hunyuan-translation</code>',
                    '<code>hunyuan-mt-7b</code>',
                    '<a href="https://cloud.siliconflow.cn/i/w7o0BWIo" target="_blank" rel="noopener noreferrer">' . esc_html__( 'SiliconFlow 中国', 'langrouter-for-translatepress' ) . '</a>',
                    '<a href="https://www.siliconflow.com/models" target="_blank" rel="noopener noreferrer">' . esc_html__( 'SiliconFlow 国际', 'langrouter-for-translatepress' ) . '</a>'
                );
                ?>
            </p>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="tpre-hunyuan-api-key"><?php esc_html_e( 'SecretId / API Key', 'langrouter-for-translatepress' ); ?></label></th>
        <td>
            <input id="tpre-hunyuan-api-key" type="password" class="regular-text code" name="tpre_settings[models][hunyuan][api_key]" value="" autocomplete="off" placeholder="<?php echo wp_kses_post( __( '留空 = 已保存 API Key', 'langrouter-for-translatepress' ) ); ?>" />
            <p class="description"><?php echo wp_kses_post( __( '模型是 <code>hunyuan-translation*</code>，填写 <strong>SecretId</strong>；若模型是 <code>hunyuan-mt-7b</code>，这里填写第三方平台的 <strong>API Key</strong>（默认是 SiliconFlow）。', 'langrouter-for-translatepress' ) ); ?></p>
            <?php if ( ! empty( $item['api_key'] ) ) : ?>
                <p class="description"><?php esc_html_e( '当前已保存 SecretId / API Key。为了避免泄露，这里不会显示；留空保存会继续沿用当前密钥。', 'langrouter-for-translatepress' ); ?></p>
            <?php endif; ?>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="tpre-hunyuan-secret-key"><?php esc_html_e( 'SecretKey', 'langrouter-for-translatepress' ); ?></label></th>
        <td>
            <input id="tpre-hunyuan-secret-key" type="password" class="regular-text code" name="tpre_settings[models][hunyuan][secret_key]" value="" autocomplete="off" placeholder="<?php echo wp_kses_post( __( '留空 = 已保存 SecretKey', 'langrouter-for-translatepress' ) ); ?>" />
            <p class="description">
                <?php
                printf(
                    wp_kses(
                        /* translators: %1$s and %2$s: Links to Tencent Cloud China and International SecretKey pages. */
                        __(
                            '仅腾讯云官方模型需要填写，%1$s，%2$s。',
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
                    '<a href="https://console.cloud.tencent.com/cam/capi" target="_blank" rel="noopener noreferrer">' . esc_html__( '腾讯云中国 SecretKey', 'langrouter-for-translatepress' ) . '</a>',
                    '<a href="https://console.tencentcloud.com/cam/capi" target="_blank" rel="noopener noreferrer">' . esc_html__( '腾讯云国际 SecretKey', 'langrouter-for-translatepress' ) . '</a>'
                );
                ?>
            </p>
            <?php if ( ! empty( $item['secret_key'] ) ) : ?>
                <p class="description"><?php esc_html_e( '当前已保存 SecretKey。为了避免泄露，这里不会显示；留空保存会继续沿用当前密钥。', 'langrouter-for-translatepress' ); ?></p>
            <?php endif; ?>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="tpre-hunyuan-site"><?php esc_html_e( '地域', 'langrouter-for-translatepress' ); ?></label></th>
        <td>
            <select id="tpre-hunyuan-site" name="tpre_settings[models][hunyuan][site]">
                <option value="cn" <?php selected( $item['site'] ?? 'cn', 'cn' ); ?>><?php esc_html_e( '腾讯云中国', 'langrouter-for-translatepress' ); ?></option>
                <option value="intl" <?php selected( $item['site'] ?? '', 'intl' ); ?>><?php esc_html_e( '腾讯云国际', 'langrouter-for-translatepress' ); ?></option>
            </select>
            <p class="description"><?php echo wp_kses_post( __( '仅腾讯云官方翻译模型使用。选择后，若“自定义API”留空，将自动绑定对应官方地址；<code>hunyuan-mt-7b</code> 不受此项影响。', 'langrouter-for-translatepress' ) ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="tpre-hunyuan-endpoint"><?php esc_html_e( '自定义API', 'langrouter-for-translatepress' ); ?></label></th>
        <td>
            <input id="tpre-hunyuan-endpoint" type="url" class="large-text code" name="tpre_settings[models][hunyuan][endpoint]" value="<?php echo esc_attr( $item['endpoint'] ?? '' ); ?>" placeholder="<?php echo esc_attr__( 'Optional custom endpoint URL', 'langrouter-for-translatepress' ); ?>" />
            <p class="description"><?php esc_html_e( '通常留空即可。官方翻译模型会按上方“地域”自动绑定腾讯云中国 / 国际 API；若你接的是第三方兼容提供商，再手动填写这里。', 'langrouter-for-translatepress' ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="tpre-hunyuan-timeout"><?php esc_html_e( '超时（秒）', 'langrouter-for-translatepress' ); ?></label></th>
        <td>
            <input id="tpre-hunyuan-timeout" type="number" min="5" name="tpre_settings[models][hunyuan][timeout]" value="<?php echo esc_attr( (string) ( $item['timeout'] ?? 20 ) ); ?>" />
            <p class="description"><?php echo wp_kses_post( __( '腾讯官方文档当前写明默认单账号并发数限制为 5；路由版在官方模型下会保守处理，在 <code>hunyuan-mt-7b</code> 下保留并发翻译优化。', 'langrouter-for-translatepress' ) ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="tpre-hunyuan-concurrency"><?php esc_html_e( '并发数', 'langrouter-for-translatepress' ); ?></label></th>
        <td>
            <input id="tpre-hunyuan-concurrency" type="number" min="0" max="32" name="tpre_settings[models][hunyuan][concurrency]" value="<?php echo esc_attr( (string) ( $item['concurrency'] ?? 0 ) ); ?>" />
            <p class="description"><?php echo wp_kses_post( __( '用于控制并发翻译请求数。填 <code>0</code> 表示继承路由设置里的全局默认并发；如果全局也为 <code>0</code>，则使用内置默认值。<code>hunyuan-mt-7b</code> 会实际使用这里的值；腾讯官方翻译模型仍会按更保守的串行/低并发策略执行。', 'langrouter-for-translatepress' ) ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="tpre-hunyuan-extra-body-json"><?php esc_html_e( '附加请求 JSON', 'langrouter-for-translatepress' ); ?></label></th>
        <td>
            <textarea id="tpre-hunyuan-extra-body-json" name="tpre_settings[models][hunyuan][extra_body_json]" rows="6" class="large-text code" placeholder='<?php echo esc_attr__( "{\"Field\":\"游戏剧情\"}", "langrouter-for-translatepress" ); ?>'><?php echo esc_textarea( $item['extra_body_json'] ?? '' ); ?></textarea>
            <p class="description"><?php echo wp_kses_post( __( '可选。腾讯官方模型可在这里透传 <code>Field</code>、<code>GlossaryIDs</code>、<code>References</code> 等参数；<code>hunyuan-mt-7b</code> 则会把这里的 JSON 合并进 OpenAI 兼容请求体。请填写合法 JSON 对象；若填写无效，保存时会保留上一次有效值。', 'langrouter-for-translatepress' ) ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( '备注', 'langrouter-for-translatepress' ); ?></th>
        <td><textarea name="tpre_settings[models][hunyuan][note]" rows="3" class="large-text"><?php echo esc_textarea( $item['note'] ?? '' ); ?></textarea></td>
    </tr>
</table>

