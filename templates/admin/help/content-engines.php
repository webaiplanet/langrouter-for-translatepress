<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<h3 style="margin-top:0;"><?php esc_html_e( '引擎配置', 'langrouter-for-translatepress' ); ?></h3>
<p><?php esc_html_e( '这一页只讲“怎么填”和“容易踩什么坑”，不讲内部代码。建议先让一个引擎稳定工作，再继续配置更多引擎。', 'langrouter-for-translatepress' ); ?></p>

<div style="background:#f6f7f7;border-left:4px solid #2271b1;padding:12px 16px;margin:16px 0;">
    <strong><?php esc_html_e( '先记住一个原则：', 'langrouter-for-translatepress' ); ?></strong>
    <?php esc_html_e( '模型设置页解决的是“这个引擎能不能工作”；路由设置页解决的是“工作时该选哪个引擎”。先把前者配通，再看后者。', 'langrouter-for-translatepress' ); ?>
</div>


<h4><?php esc_html_e( '并发设置怎么填', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '现在并发配置分为两层：路由设置里的“全局默认并发”是默认值；各子引擎里的“并发数”是覆盖值。子引擎填 0 时会继承全局；全局也填 0 时，会继续使用该引擎自己的内置默认值。', 'langrouter-for-translatepress' ); ?></p>
<ul style="list-style:disc;padding-left:18px;">
    <li><?php esc_html_e( '低配服务器（如 1C1G / 2C2G）：全局先试 1–2；Qwen / OpenAI / 兼容 OpenAI / 火山都可先填 0 继承；若仍然吃资源，再把具体引擎改成 1。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '中等服务器（如 2C4G / 4C4G）：全局可先试 3–4；大多数引擎先填 0 继承即可。只在某个引擎明显更稳时，再单独把它调高。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '高配服务器：可以把全局设为 4–6，再对单个更稳定的引擎单独提高。建议每次只上调 1–2，避免一次拉太高。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '如果出现 429、超时、CPU 持续高、内存顶满，优先先降并发，而不是先怀疑路由逻辑。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '腾讯官方 Hunyuan 翻译模型会按更保守的策略执行；即使填写了更高并发，实际也可能仍保持较低并发。', 'langrouter-for-translatepress' ); ?></li>
</ul>
<p><?php echo wp_kses_post( __( '推荐起步示例：<code>全局=2，OpenAI=0，Qwen=0，Hunyuan=0，Volc=0，兼容 OpenAI=0</code>。这代表先全部继承全局。如果你只想单独提高火山，可把它改成 <code>Volc=4</code>，其他保持 <code>0</code>。', 'langrouter-for-translatepress' ) ); ?></p>

<h4 id="help-engine-volc"><?php esc_html_e( '火山方舟', 'langrouter-for-translatepress' ); ?></h4>
<ul style="list-style:disc;padding-left:18px;">
    <li><?php esc_html_e( '火山支持把不同模型绑定到不同接入点，因此号池里可以同时放翻译接入点和聊天接入点。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '保持原来的翻译接入点写法不变即可；如果要额外增加聊天接入点，需加上 chat。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '当前实现会优先选择翻译接入点；当翻译接入点不可用、请求失败，或当前语言不在翻译模型支持范围内时，再自动回退到聊天接入点。', 'langrouter-for-translatepress' ); ?></li>
</ul>
<p class="description">
    <?php esc_html_e( '号池格式：', 'langrouter-for-translatepress' ); ?>
</p>
<p class="description">
    <?php echo wp_kses_post( __( '翻译模型：<code>接入点ID|APIKey|Access Key|Secret Access Key|安全阈值Tokens</code>。', 'langrouter-for-translatepress' ) ); ?>
</p>
<p class="description">
    <?php echo wp_kses_post( __( '例：<code>ep-xxxxxx|330xxxxx0ee|AKLxxZjg|WW1xxxxxUTQ==|4800000</code>。', 'langrouter-for-translatepress' ) ); ?>
</p>
<span style=" display: block; margin: 14px 0; "></span>
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
    <?php echo wp_kses_post( __( '例：<code>ep-xxxxxx|330xxxxx0ee|AKLxxZjg|WW1xxxxxUTQ==|4800000|chat</code>。', 'langrouter-for-translatepress' ) ); ?>
</p>

<span style=" display: block; margin: 14px 0; "></span>
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
<p class="description">
    <?php echo wp_kses_post( __( '例：<code>ep-xxxxxx|330xxxxx0ee|4800000</code>。', 'langrouter-for-translatepress' ) ); ?>
</p>

<p><?php esc_html_e( '安全阈值：Tokens最大限制，需为纯数字，达到阈值后将停止使用接入点，所有接入点都达到阈值时，引擎自动停止。', 'langrouter-for-translatepress' ); ?></p>


<p><?php esc_html_e( '注意：账号池输入框显示的是脱敏后的已保存账号池，中间字符会被星号替换，因此无法从页面复制出原始 APIKey / AK / SK。直接保存会保留原值；如需修改，请在这个框里全选后粘贴完整的新账号池，再保存即可整体替换。为避免号池较长时同时出现横向和竖向滚动条，这里会在显示时自动换行，但保存的仍然是你实际输入的原始内容。', 'langrouter-for-translatepress' ); ?></p>
<p><a href="<?php echo esc_url( $volc_model_url ); ?>" class="button button-secondary"><?php esc_html_e( '前往火山方舟设置', 'langrouter-for-translatepress' ); ?></a></p>

<h4 id="help-engine-qwen"><?php esc_html_e( 'Qwen 翻译', 'langrouter-for-translatepress' ); ?></h4>

<p>
    <?php
    printf(
        wp_kses(
            /* translators: 1: opening strong tag, 2: closing strong tag, 3: opening strong tag, 4: closing strong tag, 5: supported languages link */
            __(
                'Qwen 翻译已接入阿里云百炼 Qwen-MT 官方 OpenAI 兼容接口。不同地域填该地域的 API KEY：只配 %1$sAPI Key + 模型 + 地域%2$s；一般情况下只需选择地域即可，%3$s自定义 API%4$s 通常留空，%5$s。',
                'langrouter-for-translatepress'
            ),
            array(
                'strong' => array(),
                'a'      => array(
                    'href'   => array(),
                    'target' => array(),
                    'rel'    => array(),
                ),
            )
        ),
        '<strong>',
        '</strong>',
        '<strong>',
        '</strong>',
        '<a href="https://help.aliyun.com/zh/model-studio/machine-translation?source=5176.29345612&userCode=wme8tf09#14735a54e0rwb" target="_blank" rel="noopener noreferrer">' . esc_html( __('查看支持的语言', 'langrouter-for-translatepress') ) . '</a>'
    );
    ?>
</p>
<ul style="list-style:disc;padding-left:18px;">
    <li><?php esc_html_e( '先检查 API Key 是否有效，再看模型名和超时时间。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '不是所有语言都一定适合 Qwen，建议先确认目标语言是否被当前模型支持。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '如果你计划给某些语言做专门分配，先做一次真实翻译测试，再决定是否分流。', 'langrouter-for-translatepress' ); ?></li>
</ul>
<p><a href="<?php echo esc_url( $qwen_model_url ); ?>" class="button button-secondary"><?php esc_html_e( '前往 Qwen 设置', 'langrouter-for-translatepress' ); ?></a></p>

<h4 id="help-engine-hunyuan"><?php esc_html_e( 'Hunyuan 翻译', 'langrouter-for-translatepress' ); ?></h4>
<p class="description">
   <?php echo wp_kses_post( __( 'Hunyuan 翻译 现在同时支持两类接入：<strong>腾讯云官方翻译模型</strong>（<code>hunyuan-translation-lite</code> / <code>hunyuan-translation</code>）与 <strong>Hunyuan-MT-7B</strong>（默认走 SiliconFlow 的 OpenAI 兼容接口，可自定义 API 地址）。', 'langrouter-for-translatepress' ) ); ?>
</p>
<ul style="list-style:disc;padding-left:18px;">
    <li>
        <a href="https://cloud.siliconflow.cn/i/w7o0BWIo" target="_blank" rel="noopener noreferrer">
            <?php esc_html_e( 'SiliconFlow 中国', 'langrouter-for-translatepress' ); ?>
        </a>，
        <a href="https://www.siliconflow.com/models" target="_blank" rel="noopener noreferrer">
            <?php esc_html_e( 'SiliconFlow 国际', 'langrouter-for-translatepress' ); ?>
        </a>。
    </li>
    <li>
        <a href="https://console.cloud.tencent.com/cam/capi" target="_blank" rel="noopener noreferrer">
            <?php esc_html_e( '腾讯云中国 SecretKey', 'langrouter-for-translatepress' ); ?>
        </a>，
        <a href="https://console.tencentcloud.com/cam/capi" target="_blank" rel="noopener noreferrer">
            <?php esc_html_e( '腾讯云国际 SecretKey', 'langrouter-for-translatepress' ); ?>
        </a>。
    </li>
    <li>
        <?php esc_html_e( 'Hunyuan 模型支持的语言：', 'langrouter-for-translatepress' ); ?>
        <a href="https://cloud.tencent.com/act/cps/redirect?redirect=38132&cps_key=f983c03963c7a1b1c9441112620b2e9e" target="_blank" rel="noopener noreferrer">
            <?php esc_html_e( '腾讯云中国文档', 'langrouter-for-translatepress' ); ?>
        </a>
        ，
        <a href="https://www.tencentcloud.com/document/product/1284/77188" target="_blank" rel="noopener noreferrer">
            <?php esc_html_e( '腾讯云国际文档', 'langrouter-for-translatepress' ); ?>
        </a>
    </li>
</ul>
<ul style="list-style:disc;padding-left:18px;">
    <li><?php esc_html_e( '重点检查 SecretId、SecretKey 和模型名。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '如果日志出现 AuthFailure.SecretIdNotFound、SignatureFailure 等错误，通常就是凭证或权限有问题。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '如果你把某个语言分配给Hunyuan，但Hunyuan密钥本身无效，那么问题不是路由，而是凭证。', 'langrouter-for-translatepress' ); ?></li>
</ul>
<p><a href="<?php echo esc_url( $hunyuan_model_url ); ?>" class="button button-secondary"><?php esc_html_e( '前往 Hunyuan 设置', 'langrouter-for-translatepress' ); ?></a></p>

<h4 id="help-engine-openai"><?php esc_html_e( 'OpenAI', 'langrouter-for-translatepress' ); ?></h4>
<p class="description">
    <?php
    $tpre_openai_compatible_url  = esc_url( admin_url( 'options-general.php?page=tpre-model-settings&tab=openai_compatible' ) );
    $tpre_openai_compatible_link = sprintf(
        '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
        $tpre_openai_compatible_url,
        esc_html__( '自定义兼容 OpenAI API', 'langrouter-for-translatepress' )
    );

    printf(
        wp_kses_post(
            /* translators: %s: Link to the OpenAI-compatible engine settings page. */
            __(
                'OpenAI 支持 <code>OpenAI 官方 API</code> 与 <code>第三方兼容 OpenAI API</code>，并支持自定义 API 地址，建议只设置 OpenAI 官方接口，如第三方兼容 OpenAI API请到 %s 处启用。',
                'langrouter-for-translatepress'
            )
        ),
        wp_kses(
            $tpre_openai_compatible_link,
            [
                'a' => [
                    'href'   => [],
                    'target' => [],
                    'rel'    => [],
                ],
            ]
        )
    );
    ?>
</p>
<ul style="list-style:disc;padding-left:18px;">
    <li><?php esc_html_e( '适合作为新手第一条最小可用链路，因为它通常更容易先配通。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '重点检查 API Key、Base URL 和模型名。使用官方接口时，Base URL 一般不需要自定义。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '如果你只是想先确认整个插件能工作，优先推荐先配 OpenAI。', 'langrouter-for-translatepress' ); ?></li>
</ul>
<p><a href="<?php echo esc_url( $openai_model_url ); ?>" class="button button-secondary"><?php esc_html_e( '前往 OpenAI 设置', 'langrouter-for-translatepress' ); ?></a></p>

<h4 id="help-engine-deepl"><?php esc_html_e( 'DeepL', 'langrouter-for-translatepress' ); ?></h4>

<p><?php echo wp_kses_post( __( 'TranslatePress 内置的 DeepL 会直接使用配置的账号池，格式仍为一行一个 Key，可选前缀：<code>free:YOUR_KEY</code> 或 <code>pro:YOUR_KEY</code>；不写前缀时，统一按 <code>free</code> 处理，<code>:fx</code> key 也会自动识别为 <code>free</code>，添加前缀 <code>pro:</code> 时才走专业版接口。', 'langrouter-for-translatepress' ) ); ?></p>
<p><?php echo wp_kses_post( __( '左侧序号就是号池中的位置序号；运行状态里的“序号”会和号池保持一致。序号数量会跟随当前账号池总行数变化，滚动输入框时左侧序号也会同步滚动。号池输入框显示的是隐藏后的已保存账号池，中间字符会被星号替换，因此无法从页面复制出原始 Key。直接保存会保留原值；如需修改，请在输入框里全选后粘贴<strong>完整的新账号池</strong>并保存。', 'langrouter-for-translatepress' ) ); ?></p>
<p><?php echo wp_kses_post( __( '<strong>状态说明：</strong><code>403</code> 常见于 key 无效、free/pro 接口不匹配或权限受限；<code>456</code> 常见于额度用尽。', 'langrouter-for-translatepress' ) ); ?></p>
<ul style="list-style:disc;padding-left:18px;">
    <li><?php esc_html_e( '适合需要账号池或某些语言翻译质量更稳定的场景。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '如果启用了账号池，日志中会出现候选 key 选择、尝试条目等记录，这通常是正常现象。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '并不是所有语言都一定适合 DeepL，建议结合日志确认目标语言是否支持。', 'langrouter-for-translatepress' ); ?></li>
</ul>
<p><a href="<?php echo esc_url( $deepl_model_url ); ?>" class="button button-secondary"><?php esc_html_e( '前往 DeepL 设置', 'langrouter-for-translatepress' ); ?></a></p>

<h4 id="help-engine-openai-compatible"><?php esc_html_e( '自定义兼容 OpenAI API', 'langrouter-for-translatepress' ); ?></h4>
<p><?php echo wp_kses_post( __( '这个引擎就是用来接 <strong>第三方 OpenAI 风格接口</strong> 的，比如中转平台、企业网关、自建兼容接口。你可以把它理解成：<strong>只要对方接口长得像 OpenAI，就可以尝试在这里接入</strong>。路由规则里使用的引擎标识仍然是 <code>openai_compatible</code>。', 'langrouter-for-translatepress' ) ); ?></p>

<div style="margin:12px 0 16px 0;padding:14px 16px;border:1px solid #dcdcde;border-left:4px solid #2271b1;background:#fff;max-width:1100px;">
    <p style="margin:0 0 8px 0;"><strong><?php esc_html_e( '如果你看不懂参数，就按这个最省事的流程来。', 'langrouter-for-translatepress' ); ?></strong></p>
    <ol style="margin:0;padding-left:18px;">
        <li><?php esc_html_e( '先只填 3 项：API Key、模型名称、接口地址。', 'langrouter-for-translatepress' ); ?></li>
        <li><?php esc_html_e( '回到设置页，点击“套用推荐起步参数”。', 'langrouter-for-translatepress' ); ?></li>
        <li><?php esc_html_e( '保存后直接翻一篇真实文章测试。能正常翻就先不要碰高级参数。', 'langrouter-for-translatepress' ); ?></li>
    </ol>
</div>

<h5><?php esc_html_e( '推荐起步参数是什么', 'langrouter-for-translatepress' ); ?></h5>
<p><?php echo wp_kses_post( __( '设置页里已经预留了一套“<strong>稳妥起步值</strong>”，专门照顾 <strong>第三方兼容网关 + 长文章 + HTML 页面 + 容易超时</strong> 这些场景。你不需要死记这些数，知道它的思路就够了。', 'langrouter-for-translatepress' ) ); ?></p>
<p><?php echo wp_kses_post( __( '<code>timeout=60</code> <br><code>concurrency=4</code> <br><code>max_tokens=2200</code> <br><code>retry=2</code> <br><code>long_text_threshold=1800</code> <br><code>long_text_chunk_chars=1200</code> <br><code>long_html_chunk_chars=1600</code>', 'langrouter-for-translatepress' ) ); ?></p>
<p><?php esc_html_e( '大多数人接第三方兼容接口，先用这套就够了。只有出现“文章一长就超时”“经常 429”“翻译被截断”“模型太会发挥”这类问题时，才需要继续往下调。', 'langrouter-for-translatepress' ); ?></p>

<h5><?php esc_html_e( '设置页每一项是什么意思（按页面顺序）', 'langrouter-for-translatepress' ); ?></h5>
<table class="widefat striped" style="max-width:1100px;">
    <thead>
        <tr>
            <th style="width:210px;"><?php esc_html_e( '设置页里的名字', 'langrouter-for-translatepress' ); ?></th>
            <th><?php esc_html_e( '你只需要这样理解', 'langrouter-for-translatepress' ); ?></th>
            <th style="width:34%;"><?php esc_html_e( '不会调就这样填', 'langrouter-for-translatepress' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><?php esc_html_e( '启用', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '就是开关。勾上后，这个兼容 OpenAI 引擎才会参与翻译或路由。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '要用它就勾上。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
        <tr>
            <td><code>API Key</code></td>
            <td><?php esc_html_e( '调用这个平台时用的密钥。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '去平台后台复制原值粘贴。保存后页面不回显，后续留空保存会继续沿用旧值。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e( '模型名称', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '真正发给对方接口的模型名。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '按平台文档一字不差填写。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e( '接口地址', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '请求要发到哪里。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '优先填平台文档给你的完整地址。只拿到 /v1 也可以，插件会尝试补全。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e( '超时（秒）', 'langrouter-for-translatepress' ); ?><br /><small><code>timeout</code></small></td>
            <td><?php esc_html_e( '一条请求最多等多久。文章越长、模型越慢，这个越重要。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '先用 60。文章一长就 timeout，就改 90；还不够再试 120。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e( '并发数', 'langrouter-for-translatepress' ); ?><br /><small><code>concurrency</code></small></td>
            <td><?php esc_html_e( '一次同时发几条请求。大了更快，但更容易把第三方网关打挂。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '先用 4。经常 429、超时、站点吃满 CPU 时，先降到 2。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e( '单请求最大输出 Token', 'langrouter-for-translatepress' ); ?><br /><small><code>max_tokens</code></small></td>
            <td><?php esc_html_e( '这次翻译允许模型最多输出多少内容。太小会截断，太大可能更慢。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '先用 2200。若翻译老是半截就加大。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e( '重试次数', 'langrouter-for-translatepress' ); ?><br /><small><code>retry_count</code></small></td>
            <td><?php esc_html_e( '失败后再试几次。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '先用 2。别设太高，不然会把慢问题放大。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e( '短文本合并阈值', 'langrouter-for-translatepress' ); ?><br /><small><code>short_text_merge_threshold</code></small></td>
            <td><?php esc_html_e( '很多很短的小句子时，插件会先拼一拼再翻，减少请求数。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '看不懂就保持 36。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
        <tr>
            <td><code>temperature</code></td>
            <td><?php esc_html_e( '控制“自由发挥”程度。翻译通常越低越稳。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '先用 0。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
        <tr>
            <td><code>top_p</code></td>
            <td><?php esc_html_e( '也是控制随机性，但大多数翻译场景不用折腾它。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '先用 1。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e( '自定义 system prompt', 'langrouter-for-translatepress' ); ?><br /><small><code>system_prompt</code></small></td>
            <td><?php esc_html_e( '额外加给模型的要求，比如术语、语气、品牌口吻。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '没有明确需求就留空。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e( '高级运行参数', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '专门处理长文章、HTML 大段落、自动切段、动态超时、长文自动降并发。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '没遇到问题就别动。真要改，优先只看“超过多少字开始切段”“普通长文每段多大”“单条请求基础超时”这 3 个。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e( '附加请求头', 'langrouter-for-translatepress' ); ?><br /><small><code>extra_headers</code></small></td>
            <td><?php esc_html_e( '给请求额外塞 HTTP 头。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '只有平台文档明确要求时才填。没要求就留空。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e( '附加请求 JSON', 'langrouter-for-translatepress' ); ?><br /><small><code>extra_body_json</code></small></td>
            <td><?php esc_html_e( '给请求体额外塞 JSON 参数，也可能覆盖上面的基础设置。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '只有平台文档明确给了额外字段时才填，不确定就留空。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e( '备注', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '只是给你自己做记录。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '随便写，不会影响请求。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
    </tbody>
</table>

<h5><?php esc_html_e( '高级参数别全背，只记最常改的 3 个', 'langrouter-for-translatepress' ); ?></h5>
<ul style="list-style:disc;padding-left:18px;">
    <li><?php esc_html_e( '超过多少字开始切段（long_text_threshold）：文章一长就超时时，把它调小。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '普通长文每段多大（long_text_chunk_chars）：长段落还是慢，就把它调小一点。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '单条请求基础超时（single_request_timeout_base）：某一大段总是超时，就把它调大一点。', 'langrouter-for-translatepress' ); ?></li>
</ul>
<p><?php esc_html_e( '下面是高级参数的对照表。你不用全部掌握，只有排查问题时再回来对照。', 'langrouter-for-translatepress' ); ?></p>
<table class="widefat striped" style="max-width:1100px;">
    <thead>
        <tr>
            <th style="width:260px;"><?php esc_html_e( '设置页里的名字', 'langrouter-for-translatepress' ); ?></th>
            <th><?php esc_html_e( '它到底在管什么', 'langrouter-for-translatepress' ); ?></th>
            <th style="width:32%;"><?php esc_html_e( '什么时候才需要改', 'langrouter-for-translatepress' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><?php esc_html_e( '短句打包条数', 'langrouter-for-translatepress' ); ?><br /><small><code>batch_size</code></small></td>
            <td><?php esc_html_e( '一次把多少条短句打包到同一请求里。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '批量结果容易乱、容易超时就调小。平时别动。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e( '每批总字符上限', 'langrouter-for-translatepress' ); ?><br /><small><code>batch_max_chars</code></small></td>
            <td><?php esc_html_e( '一批短句加起来最多多长。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '批量经常超时就调小。平时别动。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e( '批量标签输出上限', 'langrouter-for-translatepress' ); ?><br /><small><code>label_max_tokens</code></small></td>
            <td><?php esc_html_e( '批量翻译时给结果打标记所允许的输出上限。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '一般保持 0。除非某个兼容接口特别挑这个。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e( '超过多少字开始切段', 'langrouter-for-translatepress' ); ?><br /><small><code>long_text_threshold</code></small></td>
            <td><?php esc_html_e( '一段文本长到这里，就不再整段硬发，而是自动切开翻。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '长文章经常 timeout，就把它调小。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e( '普通长文每段多大', 'langrouter-for-translatepress' ); ?><br /><small><code>long_text_chunk_chars</code></small></td>
            <td><?php esc_html_e( '普通长文本切成多大的小段。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '长段落还是慢就调小；请求太碎太多就略调大。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e( 'HTML 长段每段多大', 'langrouter-for-translatepress' ); ?><br /><small><code>long_html_chunk_chars</code></small></td>
            <td><?php esc_html_e( 'HTML、富文本、带很多标签的内容切多大。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( 'HTML 页面慢或超时就调小。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e( '长文降并发阈值 1 / 长文并发上限 1', 'langrouter-for-translatepress' ); ?><br /><small><code>long_text_medium_threshold</code> / <code>long_text_concurrency_medium</code></small></td>
            <td><?php esc_html_e( '一旦文本到第一档长度，就自动把并发压到这个上限。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '长段一多就不稳、429 多时再改。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e( '长文降并发阈值 2 / 长文并发上限 2', 'langrouter-for-translatepress' ); ?><br /><small><code>long_text_large_threshold</code> / <code>long_text_concurrency_large</code></small></td>
            <td><?php esc_html_e( '更长的第二档文本再进一步压并发。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '超长段落多时再改。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e( '长文降并发阈值 3 / 长文并发上限 3', 'langrouter-for-translatepress' ); ?><br /><small><code>long_text_extreme_threshold</code> / <code>long_text_concurrency_extreme</code></small></td>
            <td><?php esc_html_e( '极长文本时最保守的并发保护。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '特别慢的第三方网关才需要碰它。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e( '单条请求基础超时', 'langrouter-for-translatepress' ); ?><br /><small><code>single_request_timeout_base</code></small></td>
            <td><?php esc_html_e( '单独处理某个长段时，最少先等多久。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '某个大段总是超时就加大。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e( '每隔多少字加一次超时', 'langrouter-for-translatepress' ); ?><br /><small><code>single_request_timeout_step_chars</code></small></td>
            <td><?php esc_html_e( '文本越长，按这个步长继续给单条请求加等待时间。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '很长的段落仍然来不及返回时再改。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e( '每次额外加几秒', 'langrouter-for-translatepress' ); ?><br /><small><code>single_request_timeout_step_sec</code></small></td>
            <td><?php esc_html_e( '每走一档，就多给几秒等待。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '慢模型、慢网关才需要改。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e( 'HTML 额外加时', 'langrouter-for-translatepress' ); ?><br /><small><code>single_request_timeout_html_bonus</code></small></td>
            <td><?php esc_html_e( '遇到 HTML、短码、富文本时，再额外多等一会儿。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '页面标签很多、HTML 页面慢时再改。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e( '单条请求最长可等多久', 'langrouter-for-translatepress' ); ?><br /><small><code>single_request_timeout_cap</code></small></td>
            <td><?php esc_html_e( '不管怎么动态加时，最后都不会超过这个上限。', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '只有你明确知道接口特别慢，才考虑加大。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
    </tbody>
</table>

<h5><?php esc_html_e( '常见问题直接照着改', 'langrouter-for-translatepress' ); ?></h5>
<ul style="list-style:disc;padding-left:18px;">
    <li><?php esc_html_e( '文章一长就 timeout：先把 timeout 改 90，再把 concurrency 改 2；还不行，再把“超过多少字开始切段”和“普通长文每段多大”都调小一点。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '第三方网关经常 429：优先降 concurrency，不要先乱加 retry。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '翻译总是半截：把 max_tokens 调大。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '翻译太会发挥：temperature 保持 0；必要时再写 system prompt。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '实在看不懂：重新套用推荐起步参数，一次只改一个值，再看日志。', 'langrouter-for-translatepress' ); ?></li>
</ul>
<p><a href="<?php echo esc_url( $compatible_openai_url ); ?>" class="button button-secondary"><?php esc_html_e( '前往兼容 OpenAI API 设置', 'langrouter-for-translatepress' ); ?></a></p>

<h4 id="help-engine-opencc"><?php esc_html_e( 'OpenCC（繁体中文前台全页转换）', 'langrouter-for-translatepress' ); ?></h4>
<p><?php echo wp_kses_post( __( 'OpenCC 不是翻译引擎，它更像是 <strong>把已经输出出来的简体中文页面，在前台最终响应阶段整体转换成繁体中文</strong>。当路由设置里的“繁体中文处理方式”选择为 <strong>由 OpenCC 转换</strong> 时，繁体中文目标语言会跳过自动翻译入库，前台保持原文输出，再由插件内置的 OpenCC 接管整页转换。', 'langrouter-for-translatepress' ) ); ?></p>
<p><?php esc_html_e( '所以它适合解决的是：你不想把 zh_TW / zh_HK / zh_Hant 单独送进机器翻译，也不想往 TranslatePress 翻译表里写入这些繁体结果，而是希望前台访问时直接把最终页面转换为繁体。', 'langrouter-for-translatepress' ); ?></p>

<div style="margin:12px 0 16px 0;padding:14px 16px;border:1px solid #dcdcde;border-left:4px solid #2271b1;background:#fff;max-width:1100px;">
    <p style="margin:0 0 8px 0;"><strong><?php esc_html_e( '先记住三个前提。', 'langrouter-for-translatepress' ); ?></strong></p>
    <ol style="margin:0;padding-left:18px;">
        <li><?php esc_html_e( '服务器里要真的安装了 opencc 命令。它不是 PHP 扩展。', 'langrouter-for-translatepress' ); ?></li>
        <li><?php esc_html_e( 'PHP 需要允许 shell_exec。很多主机面板或安全策略会禁用它。', 'langrouter-for-translatepress' ); ?></li>
        <li><?php esc_html_e( '网站运行用户必须能创建临时文件，并且能执行 opencc。', 'langrouter-for-translatepress' ); ?></li>
    </ol>
</div>

<h5><?php esc_html_e( 'OpenCC 是什么，不是什么', 'langrouter-for-translatepress' ); ?></h5>
<ul style="list-style:disc;padding-left:18px;">
    <li><?php esc_html_e( '它是“简体转繁体/地区繁体用字转换”工具，不是 LLM，不会自动润色或重写句子。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '它不会把英文、德语、法语翻成中文；它只处理中文简繁和地区用字差异。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '它处理的是前台最终 HTML，所以更像“显示层转换”，不是数据库翻译入库。', 'langrouter-for-translatepress' ); ?></li>
</ul>

<h5><?php esc_html_e( '服务器怎么安装 OpenCC', 'langrouter-for-translatepress' ); ?></h5>
<p><?php esc_html_e( '不同系统安装方式不完全一样，下面给你常见示例。实际以你服务器系统的软件仓库为准。', 'langrouter-for-translatepress' ); ?></p>
<table class="widefat striped" style="max-width:1100px;">
    <thead>
        <tr>
            <th style="width:210px;"><?php esc_html_e( '系统/环境', 'langrouter-for-translatepress' ); ?></th>
            <th><?php esc_html_e( '常见安装示例', 'langrouter-for-translatepress' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><?php esc_html_e( 'Debian / Ubuntu', 'langrouter-for-translatepress' ); ?></td>
            <td><code>apt update &amp;&amp; apt install -y opencc</code></td>
        </tr>
        <tr>
            <td><?php esc_html_e( 'CentOS / AlmaLinux / RockyLinux', 'langrouter-for-translatepress' ); ?></td>
            <td><code>yum install -y opencc</code><br /><small><?php esc_html_e( '如果仓库里没有，可能要先启用额外仓库，或自行编译安装。', 'langrouter-for-translatepress' ); ?></small></td>
        </tr>
        <tr>
            <td><?php esc_html_e( 'macOS（本地调试）', 'langrouter-for-translatepress' ); ?></td>
            <td><code>brew install opencc</code></td>
        </tr>
        <tr>
            <td><?php esc_html_e( 'Docker 镜像', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '在 Dockerfile 里直接安装 opencc，并确保 PHP 运行用户可执行该命令。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e( '宝塔 / 面板环境', 'langrouter-for-translatepress' ); ?></td>
            <td><?php esc_html_e( '本质还是在系统层安装 opencc，不是在 PHP 扩展页里安装。安装完再检查 PHP 的 disable_functions 和站点运行用户权限。', 'langrouter-for-translatepress' ); ?></td>
        </tr>
    </tbody>
</table>

<h5><?php esc_html_e( '安装后怎么确认是否真的可用', 'langrouter-for-translatepress' ); ?></h5>
<ol style="padding-left:18px;">
    <li><?php esc_html_e( '先在 SSH 里执行：', 'langrouter-for-translatepress' ); ?> <code>which opencc</code> <?php esc_html_e( '或', 'langrouter-for-translatepress' ); ?> <code>command -v opencc</code></li>
    <li><?php esc_html_e( '如果能返回路径，例如', 'langrouter-for-translatepress' ); ?> <code>/usr/bin/opencc</code> <?php esc_html_e( '，说明系统里有这个命令。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '再执行一条最简单的转换测试，例如：', 'langrouter-for-translatepress' ); ?><br /><code>echo '简体中文测试' | opencc -c s2t.json</code></li>
    <li><?php esc_html_e( '如果输出类似“簡體中文測試”，说明 opencc 命令本身没问题。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '最后再回到网站前台访问一个繁体页面，并查看插件日志里是否出现“OpenCC 命令执行完成”“OpenCC 最外层回调已完成最终响应转换”这两条。', 'langrouter-for-translatepress' ); ?></li>
</ol>

<h5><?php esc_html_e( '如果服务器已安装，但网站里还是不生效，优先查这几项', 'langrouter-for-translatepress' ); ?></h5>
<ul style="list-style:disc;padding-left:18px;">
    <li><?php esc_html_e( 'PHP 是否禁用了 shell_exec。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( 'opencc 路径是否和服务器实际路径一致。插件会自动尝试 /usr/bin/opencc、/usr/local/bin/opencc 和 opencc。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( 'PHP 的临时目录是否可写，因为插件会先写临时输入文件和输出文件。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '站点运行用户是否有权限执行该命令。SSH 下 root 能跑，不代表 PHP 运行用户也能跑。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '是否真的把“繁体中文处理方式”切成了 OpenCC 处理。', 'langrouter-for-translatepress' ); ?></li>
</ul>

<h5><?php esc_html_e( '插件日志里看到什么，才算 OpenCC 生效', 'langrouter-for-translatepress' ); ?></h5>
<ul style="list-style:disc;padding-left:18px;">
    <li><code>OpenCC 已尽早启动全局输出缓冲，等待最外层回调在最终输出阶段统一转换</code></li>
    <li><code>OpenCC 命令执行完成</code></li>
    <li><code>OpenCC 最外层回调已完成最终响应转换</code></li>
</ul>
<p><?php esc_html_e( '如果只有“未执行”或“当前请求类型不支持前台整页转换”，那通常是访问的不是普通前台页面，或者当前页面语言没有命中繁体处理规则。', 'langrouter-for-translatepress' ); ?></p>

<h5><?php esc_html_e( '最常见误区', 'langrouter-for-translatepress' ); ?></h5>
<ul style="list-style:disc;padding-left:18px;">
    <li><?php esc_html_e( '误以为 OpenCC 是 PHP 扩展，其实不是。它是系统命令。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '误以为装了 opencc 命令就一定能用，还需要 PHP 没禁 shell_exec、临时目录可写、站点用户可执行。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '误以为 OpenCC 会把繁体写回 TranslatePress 翻译表。当前模式下它是不入库、只处理前台最终输出。', 'langrouter-for-translatepress' ); ?></li>
</ul>

<h4><?php esc_html_e( '配置引擎时的通用建议', 'langrouter-for-translatepress' ); ?></h4>

<ul style="list-style:disc;padding-left:18px;">
    <li><?php esc_html_e( '先配一个，再配第二个，不建议一次性把所有引擎都填上。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '改完某个引擎后，最好立刻做一次真实翻译测试。', 'langrouter-for-translatepress' ); ?></li>
    <li><?php esc_html_e( '如果一个引擎只准备拿来给某个语言做专用分配，也建议先单独验证它本身可用。', 'langrouter-for-translatepress' ); ?></li>
</ul>

<p style="margin-top:20px;text-align:right;">
    <a class="button button-secondary" href="<?php echo esc_url( $routing_help_url ); ?>"><?php esc_html_e( '查看路由设置帮助', 'langrouter-for-translatepress' ); ?></a>
    <a class="button button-secondary" href="<?php echo esc_url( $logs_help_url ); ?>" style="margin-left:8px;"><?php esc_html_e( '查看日志与排错帮助', 'langrouter-for-translatepress' ); ?></a>
</p>
