<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="tpre-router-settings-header">
    <h2 class="tpre-router-settings-header-title"><?php esc_html_e( '路由设置', 'langrouter-for-translatepress' ); ?></h2>
    <div style="display:flex;gap:20px;">
        <span class="tpre-router-settings-header-link">
            <a class="button button-secondary" href="<?php echo esc_url( $tpre_model_settings_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '前往模型设置', 'langrouter-for-translatepress' ); ?></a>
        </span>
        <span>
            <a class="button button-secondary" href="<?php echo esc_url( $tpre_routing_help_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '查看帮助', 'langrouter-for-translatepress' ); ?></a>
        </span>
    </div>
</div>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row"><?php esc_html_e( '默认引擎', 'langrouter-for-translatepress' ); ?></th>
        <td>
            <select name="trp_machine_translation_settings[tpre_default_engine]">
                <?php foreach ( $tpre_engine_choices as $tpre_slug => $tpre_label ) : ?>
                    <option value="<?php echo esc_attr( $tpre_slug ); ?>" <?php selected( $tpre_default_engine, $tpre_slug ); ?>><?php echo esc_html( $tpre_label ); ?></option>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php esc_html_e( '未命中文章类型规则、语言分配和回退规则时，将走这里选中的引擎。', 'langrouter-for-translatepress' ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( '全局默认并发', 'langrouter-for-translatepress' ); ?></th>
        <td>
            <input type="number" min="0" max="32" name="trp_machine_translation_settings[tpre_global_concurrency_limit]" value="<?php echo esc_attr( (string) $tpre_global_concurrency_limit ); ?>" />
            <p class="description"><?php esc_html_e( '作为所有子引擎的默认并发值。子引擎并发填 0 时会继承这里；全局也填 0 时，表示继续使用各引擎自己的内置默认值。低配服务器可先设为 1–3，中等服务器可先试 4–6。', 'langrouter-for-translatepress' ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( '文章类型分配', 'langrouter-for-translatepress' ); ?></th>
        <td>
            <div class="tpre-post-type-card">
                <?php if ( ! empty( $tpre_post_type_choices ) ) : ?>
                    <input type="hidden" name="trp_machine_translation_settings[tpre_post_type_rules_present]" value="1" />
                    <input type="hidden" class="tpre-post-type-rules-json" name="trp_machine_translation_settings[tpre_post_type_rules_json]" value="<?php echo esc_attr( wp_json_encode( $tpre_post_type_rule_rows, JSON_UNESCAPED_UNICODE ) ); ?>" />
                    <div
                        class="tpre-post-type-rules"
                        data-post-types="<?php echo esc_attr( wp_json_encode( $tpre_post_type_choices, JSON_UNESCAPED_UNICODE ) ); ?>"
                        data-engines="<?php echo esc_attr( wp_json_encode( $tpre_engine_choices, JSON_UNESCAPED_UNICODE ) ); ?>"
                        data-empty-post-type-label="<?php echo esc_attr__( '添加', 'langrouter-for-translatepress' ); ?>"
                        data-empty-engine-label="<?php echo esc_attr__( '选择引擎', 'langrouter-for-translatepress' ); ?>"
                        data-remove-label="<?php echo esc_attr__( '删除', 'langrouter-for-translatepress' ); ?>"
                        data-add-label="<?php echo esc_attr__( '添加文章类型规则', 'langrouter-for-translatepress' ); ?>"
                        data-fallback-none-label="<?php echo esc_attr__( '停止不翻译', 'langrouter-for-translatepress' ); ?>"
                        data-fallback-default-only-label="<?php echo esc_attr__( '默认引擎', 'langrouter-for-translatepress' ); ?>"
                        data-fallback-global-chain-label="<?php echo esc_attr__( '全局规则', 'langrouter-for-translatepress' ); ?>"
                        data-no-more-label="<?php echo esc_attr__( '已无可添加', 'langrouter-for-translatepress' ); ?>"
                    >

                        <table class="widefat striped tpre-post-type-rule-table">
                            <thead>
                                <tr class="tpre-post-type-rule-table-th">
                                    <th><?php esc_html_e( '文章类型', 'langrouter-for-translatepress' ); ?></th>
                                    <th><?php esc_html_e( '指定引擎', 'langrouter-for-translatepress' ); ?></th>
                                    <th><?php esc_html_e( '失败后回退', 'langrouter-for-translatepress' ); ?></th>
                                    <th><?php esc_html_e( '删除', 'langrouter-for-translatepress' ); ?></th>
                                </tr>
                            </thead>
                            <tbody class="tpre-post-type-rule-body">
                                <?php foreach ( $tpre_post_type_rule_rows as $tpre_rule_row ) : ?>
                                    <tr class="tpre-post-type-rule-row">
                                        <td>
                                            <div class="tpre-post-type-picker">
                                                <div class="tpre-post-type-tags"></div>
                                                <select class="tpre-post-type-add-select"></select>
                                                <input type="hidden" class="tpre-post-types-hidden" name="trp_machine_translation_settings[tpre_post_type_rule_post_types][]" value="<?php echo esc_attr( wp_json_encode( array_values( (array) $tpre_rule_row['post_types'] ) ) ); ?>" />
                                            </div>
                                        </td>
                                        <td>
                                            <select class="tpre-engine-select" name="trp_machine_translation_settings[tpre_post_type_rule_engine][]">
                                                <option value=""><?php esc_html_e( '请选择引擎', 'langrouter-for-translatepress' ); ?></option>
                                                <?php foreach ( $tpre_engine_choices as $tpre_slug => $tpre_label ) : ?>
                                                    <option value="<?php echo esc_attr( $tpre_slug ); ?>" <?php selected( $tpre_rule_row['engine'], $tpre_slug ); ?>><?php echo esc_html( $tpre_label ); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td class="tpre-post-type-chain-cell">
                                            <select class="tpre-fallback-mode-select" name="trp_machine_translation_settings[tpre_post_type_rule_fallback_mode][]">
                                                <option value="default_only" <?php selected( $tpre_rule_row['fallback_mode'] ?? 'default_only', 'default_only' ); ?>><?php esc_html_e( '默认引擎', 'langrouter-for-translatepress' ); ?></option>
                                                <option value="global_chain" <?php selected( $tpre_rule_row['fallback_mode'] ?? 'default_only', 'global_chain' ); ?>><?php esc_html_e( '全局规则', 'langrouter-for-translatepress' ); ?></option>
                                                <option value="none" <?php selected( $tpre_rule_row['fallback_mode'] ?? 'default_only', 'none' ); ?>><?php esc_html_e( '停止不翻译', 'langrouter-for-translatepress' ); ?></option>
                                            </select>
                                        </td>
                                        <td>
                                            <button type="button" class="button-link-delete tpre-remove-rule tpre-post-type-remove-button"><?php esc_html_e( '删除', 'langrouter-for-translatepress' ); ?></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p class="tpre-post-type-empty-state description<?php if ( ! empty( $tpre_post_type_rule_rows ) ) : ?> tpre-is-hidden<?php endif; ?>"><?php esc_html_e( '你可以只为少数需要单独指定主引擎的文章类型添加规则。', 'langrouter-for-translatepress' ); ?></p>
                        <p class="tpre-post-type-actions">
                            <button type="button" class="button button-secondary tpre-add-rule"><?php esc_html_e( '添加文章类型规则', 'langrouter-for-translatepress' ); ?></button>
                            <span class="tpre-post-type-remaining description"></span>
                        </p>
                    </div>
                <?php else : ?>
                    <p class="description"><?php esc_html_e( '当前没有可用于路由的公开文章类型。', 'langrouter-for-translatepress' ); ?></p>
                <?php endif; ?>
            </div>
                <p class="description tpre-post-type-desc"><?php esc_html_e( '同一文章类型只能出现在一条规则中；同一条规则里可以绑定多个文章类型到同一个引擎。命中文章类型规则后会先走指定引擎；若该引擎不支持目标语言、不可用或翻译失败，再按这里选中的模式继续：默认引擎 / 全局规则（语言分配 → 回退规则 → 默认引擎）/ 失败不翻译。', 'langrouter-for-translatepress' ); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( '繁体中文翻译', 'langrouter-for-translatepress' ); ?></th>
        <td>
            <select name="trp_machine_translation_settings[tpre_traditional_chinese_mode]">
                <option value="translatepress" <?php selected( $tpre_traditional_chinese_mode, 'translatepress' ); ?>><?php esc_html_e( 'TranslatePress 翻译', 'langrouter-for-translatepress' ); ?></option>
                <option value="opencc" <?php selected( $tpre_traditional_chinese_mode, 'opencc' ); ?>><?php esc_html_e( 'OpenCC 转换', 'langrouter-for-translatepress' ); ?></option>
            </select>
            <p class="description">
                <?php
                printf(
                    wp_kses_post(
                        /* translators: %s: Traditional Chinese language code aliases joined with Chinese punctuation. */
                        __( '这个选项只影响繁体中文目标语言。识别范围会优先自动读取当前 TranslatePress 已启用语言代码及其 URL slug，并附带插件兜底别名：<code>%s</code>。选择 OpenCC 后，这些语言不会进入任何翻译引擎，也不会写入自动翻译结果；前台页面保持原文输出，再由插件内置的 OpenCC 全页转换接管。选择 TranslatePress 时，则和其他语言一样正常进入翻译流程。', 'langrouter-for-translatepress' )
                    ),
                    esc_html( implode( '、', (array) $tpre_traditional_chinese_aliases ) )
                );
                ?>
            </p>
            <p style="margin-top:8px;">
                <a href="<?php echo esc_url( $tpre_opencc_help_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '查看 OpenCC 帮助', 'langrouter-for-translatepress' ); ?></a>
            </p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( '语言分配', 'langrouter-for-translatepress' ); ?></th>
        <td>
            <textarea name="trp_machine_translation_settings[tpre_language_engine_map_raw]" rows="5" class="large-text code"><?php echo esc_textarea( trim( $tpre_language_map_raw ) ); ?></textarea>
            <p class="description">
                <?php
                printf(
                    wp_kses_post(
                        /* translators: %s: Comma-separated engine slugs. */
                        __(
                            '不同语言分配不同引擎，一行一个，格式：<code>bs_BA = deepl</code>。可用引擎标识（语言分配与回退请填写 <code>%s</code> 这里的标识，不要写标签名称）。',
                            'langrouter-for-translatepress'
                        )
                    ),
                    esc_html( implode( '、', array_keys( $tpre_engine_choices ) ) )
                );
                ?>
            </p>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( '回退规则', 'langrouter-for-translatepress' ); ?></th>
        <td>
            <textarea name="trp_machine_translation_settings[tpre_fallback_map_raw]" rows="5" class="large-text code"><?php echo esc_textarea( trim( $tpre_fallback_map_raw ) ); ?></textarea>
            <p class="description">
                <?php
                printf(
                    wp_kses_post(
                        /* translators: %s: Comma-separated engine slugs. */
                        __(
                            '当语言分配或文章类型主引擎不能继续时，按这里的一行一条规则继续尝试，格式：<code>bs_BA = deepl</code>。引擎标识：<code>%s</code>。',
                            'langrouter-for-translatepress'
                        )
                    ),
                    esc_html( implode( '、', array_keys( $tpre_engine_choices ) ) )
                );
                ?>
            </p>
        </td>
    </tr>
    <tr class="tpre-language-query-module">
        <th scope="row"><?php esc_html_e( '语言支持查询', 'langrouter-for-translatepress' ); ?></th>
        <td>
            <div class="tpre-language-query-card" data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-nonce="<?php echo esc_attr( $tpre_query_nonce ); ?>">
                <div class="tpre-language-query-row">
                    <input type="text" id="tpre-language-query-input" class="regular-text" placeholder="<?php esc_html_e('例如：英文 / English / en / en_US / bs_BA', 'langrouter-for-translatepress'); ?>" autocomplete="off" />
                    <select id="tpre-language-query-engine">
                        <?php foreach ( $tpre_query_engine_choices as $tpre_slug => $tpre_label ) : ?>
                            <option value="<?php echo esc_attr( $tpre_slug ); ?>" <?php selected( $tpre_query_default_engine, $tpre_slug ); ?>><?php echo esc_html( $tpre_label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="button button-secondary" id="tpre-language-query-button"><?php esc_html_e( '查询', 'langrouter-for-translatepress' ); ?></button>
                </div>
                <p class="description"><?php esc_html_e( '输入语言名称、语言代码或 locale 均可；输入后会自动查询一次，右侧按钮可手动重试。', 'langrouter-for-translatepress' ); ?></p>
                <div id="tpre-language-query-result" class="tpre-language-query-result"></div>
            </div>
        </td>
    </tr>
</table>

<p class="description">
    <?php
    printf(
        wp_kses_post(
            /* translators: %s: Model settings page URL. */
            __(
                '这里负责默认引擎、按文章类型分配、按语言分配以及回退规则。模型参数、接口地址、密钥等内容请到 <a href="%s" target="_blank" rel="noopener noreferrer">模型设置</a> 页面维护；文件日志开关与日志查看已移至该页面的“日志”标签页。',
                'langrouter-for-translatepress'
            )
        ),
        esc_url( $tpre_model_settings_url )
    );
    ?>
</p>
<p class="description">
    <?php
    echo wp_kses_post(
        __(
            '当前优先级：<strong>文章类型主引擎 → 语言分配 → 回退规则 → 默认引擎</strong>。文章类型分配仅对单篇内容页生效。',
            'langrouter-for-translatepress'
        )
    );
    ?>
</p>

<p class="description">
    <?php
    echo wp_kses_post(
        __(
            '提示：当当前引擎为 Router 时，TranslatePress 的 <strong>测试 API 凭据</strong> 按钮现在会优先尝试测试默认子引擎；如果仍显示 <code>router-ok ...</code>，表示 Router 已接管，但未能执行更深的子引擎联通性测试。',
            'langrouter-for-translatepress'
        )
    );
    ?>
</p>


