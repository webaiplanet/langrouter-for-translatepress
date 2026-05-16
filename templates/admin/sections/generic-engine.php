<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
            <p class="description"><?php printf( wp_kses_post( /* translators: %s: Current model tab label. */ __( '当前页面只显示 <strong>%s</strong> 的设置项。其他模型通过上方导航切换，避免所有字段堆在同一页。', 'langrouter-for-translatepress' ) ), esc_html( $label ) ); ?></p>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e( '启用', 'langrouter-for-translatepress' ); ?></th>
                    <td>
                        <label><input type="checkbox" name="tpre_settings[models][<?php echo esc_attr( $current_tab ); ?>][enabled]" value="1" <?php checked( ! empty( $item['enabled'] ) ); ?>> <?php esc_html_e( '启用该模型配置', 'langrouter-for-translatepress' ); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( '接口地址', 'langrouter-for-translatepress' ); ?></th>
                    <td><input type="text" name="tpre_settings[models][<?php echo esc_attr( $current_tab ); ?>][endpoint]" value="<?php echo esc_attr( $item['endpoint'] ); ?>" class="regular-text code" /></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( '模型名称', 'langrouter-for-translatepress' ); ?></th>
                    <td><input type="text" name="tpre_settings[models][<?php echo esc_attr( $current_tab ); ?>][model]" value="<?php echo esc_attr( $item['model'] ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'API Key / Access Key', 'langrouter-for-translatepress' ); ?></th>
                    <td><input type="text" name="tpre_settings[models][<?php echo esc_attr( $current_tab ); ?>][api_key]" value="<?php echo esc_attr( $item['api_key'] ); ?>" class="regular-text code" /></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Secret Key / Secret', 'langrouter-for-translatepress' ); ?></th>
                    <td><input type="text" name="tpre_settings[models][<?php echo esc_attr( $current_tab ); ?>][secret_key]" value="<?php echo esc_attr( $item['secret_key'] ); ?>" class="regular-text code" /></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( '区域 / Region', 'langrouter-for-translatepress' ); ?></th>
                    <td><input type="text" name="tpre_settings[models][<?php echo esc_attr( $current_tab ); ?>][region]" value="<?php echo esc_attr( $item['region'] ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( '请求超时（秒）', 'langrouter-for-translatepress' ); ?></th>
                    <td><input type="number" min="1" step="1" name="tpre_settings[models][<?php echo esc_attr( $current_tab ); ?>][timeout]" value="<?php echo esc_attr( (string) ( $item['timeout'] ?? 30 ) ); ?>" class="small-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( '系统提示词', 'langrouter-for-translatepress' ); ?></th>
                    <td>
                        <textarea name="tpre_settings[models][<?php echo esc_attr( $current_tab ); ?>][system_prompt]" rows="4" class="large-text"><?php echo esc_textarea( $item['system_prompt'] ?? '' ); ?></textarea>
                        <p class="description"><?php esc_html_e( '给大模型类接口使用的系统提示词，可留空。', 'langrouter-for-translatepress' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( '附加请求头', 'langrouter-for-translatepress' ); ?></th>
                    <td>
                        <textarea name="tpre_settings[models][<?php echo esc_attr( $current_tab ); ?>][extra_headers]" rows="4" class="large-text code"><?php echo esc_textarea( $item['extra_headers'] ?? '' ); ?></textarea>
                        <p class="description"><?php echo wp_kses_post( __( '一行一个，格式：<code>Header-Name: value</code>。', 'langrouter-for-translatepress' ) ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( '附加请求体 JSON', 'langrouter-for-translatepress' ); ?></th>
                    <td>
                        <textarea name="tpre_settings[models][<?php echo esc_attr( $current_tab ); ?>][extra_body_json]" rows="6" class="large-text code"><?php echo esc_textarea( $item['extra_body_json'] ?? '' ); ?></textarea>
                        <p class="description"><?php esc_html_e( '用于存放该模型的额外 body 参数，填写 JSON 片段即可。', 'langrouter-for-translatepress' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( '备注', 'langrouter-for-translatepress' ); ?></th>
                    <td>
                        <textarea name="tpre_settings[models][<?php echo esc_attr( $current_tab ); ?>][note]" rows="3" class="large-text"><?php echo esc_textarea( $item['note'] ?? '' ); ?></textarea>

                    </td>
                </tr>
            </table>
