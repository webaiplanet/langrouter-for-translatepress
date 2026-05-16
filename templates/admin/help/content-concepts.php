<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h3 style="margin-top:0;"><?php esc_html_e( '基本概念', 'langrouter-for-translatepress' ); ?></h3>

<p><?php esc_html_e( '这一页不是教你填具体配置，而是先帮你把几个最容易混淆的概念理顺。只要先搞明白“每一块是做什么的、什么时候生效、谁先谁后”，后面看模型设置、路由设置和日志时就会轻松很多。', 'langrouter-for-translatepress' ); ?></p>

<div style="background:#f0f6fc;border-left:4px solid #72aee6;padding:12px 16px;margin:16px 0;">
	<strong><?php esc_html_e( '你可以先这样理解整个插件：', 'langrouter-for-translatepress' ); ?></strong>
	<?php esc_html_e( '模型设置页负责“把每个引擎准备好”，路由设置页负责“决定这次请求交给谁”，日志页负责“出了问题后看它到底怎么走的”。', 'langrouter-for-translatepress' ); ?>
</div>

<h4><?php esc_html_e( '1. 模型设置页是什么', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '模型设置页负责维护每个引擎自己的配置参数，例如 API Key、SecretId、SecretKey、Base URL、模型名、账号池和备注。你可以把它理解成“把每台机器先接好电、插好网、确认能启动”。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '这一页不负责决定请求最终走哪个引擎，它只负责让某个引擎本身具备可用条件。也就是说，一个引擎就算在模型设置页里已经填好了参数，如果路由设置没有选中它，这次请求也未必会走它。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '2. 路由设置页是什么', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '路由设置页负责决定“这次翻译请求最后交给哪个引擎”。例如：默认引擎是什么、某个目标语言是否优先分配给某个引擎、某个文章类型是否固定先走某个引擎、主引擎失败后要不要继续尝试别的引擎。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '你可以把路由设置理解成“派单规则”。模型设置页是把多个引擎准备好，路由设置页则是决定这次请求先派给谁、失败后要不要改派给别人。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '3. 默认引擎是什么', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '默认引擎是兜底选项。它的意思不是“永远先走这个引擎”，而是“前面的规则都没有命中，或者前面的可继续链路都没有成功时，最后再用它”。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '很多新人最容易误解这里，以为把 OpenAI 设成默认引擎后，所有请求都会先走 OpenAI。其实不是。默认引擎只是最后兜底，前面如果有文章类型规则、语言分配或回退规则命中，它都会排在后面。', 'langrouter-for-translatepress' ); ?></p>

<pre style="background:#f6f7f7;padding:12px 16px;border:1px solid #dcdcde;overflow:auto;"><?php echo esc_html__( '默认引擎：openai', 'langrouter-for-translatepress' ); ?></pre>

<p class="description"><?php esc_html_e( '上面的意思是：如果这次请求没有命中任何更高优先级规则，最后就用 OpenAI。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '4. 文章类型分配是什么', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '文章类型分配是“按内容类型指定主引擎”。当当前翻译请求来自某个单篇内容对象，并且它的文章类型命中了你配置的规则时，Router 会优先使用这里指定的引擎。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '你可以把它理解成：不同类型的内容，先交给不同的主引擎。比如商品页优先走某个术语更稳定的引擎，攻略页优先走某个更适合说明文的引擎。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '这里最重要的一点是：文章类型分配只对单篇内容页生效。它不是“只要站点里有这个文章类型，就让所有相关页面都按它走”，而是只有当前翻译对象本身能识别为某个单篇内容页时，才会按这个文章类型去匹配规则。', 'langrouter-for-translatepress' ); ?></p>

<pre style="background:#f6f7f7;padding:12px 16px;border:1px solid #dcdcde;overflow:auto;"><?php
echo esc_html__(
	'文章类型分配：
  product -> deepl
  guides -> qwen',
	'langrouter-for-translatepress'
);
?></pre>

<p class="description"><?php esc_html_e( '上面的意思是：商品详情页优先走 DeepL，攻略详情页优先走 Qwen。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '5. 语言分配是什么', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '语言分配用于指定“某个目标语言优先走哪个引擎”。它不是看当前内容是什么类型，而是看“这次要翻成什么语言”。只要命中了语言分配，请求就会优先走这里指定的引擎，除非前面已经被文章类型主引擎拦住。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '例如你可以规定：英文优先走Hunyuan，粤语优先走 DeepL，阿姆哈拉语优先走兼容 OpenAI。', 'langrouter-for-translatepress' ); ?></p>

<pre style="background:#f6f7f7;padding:12px 16px;border:1px solid #dcdcde;overflow:auto;"><?php
echo esc_html__(
	'en_US = hunyuan
yue = deepl
am = openai_compatible',
	'langrouter-for-translatepress'
);
?></pre>

<p class="description"><?php esc_html_e( '上面的意思是：英文优先走Hunyuan，粤语优先走 DeepL，阿姆哈拉语优先走兼容 OpenAI。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '6. 回退规则是什么', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '回退规则是“主引擎不能继续时，接下来尝试谁”的规则。它不是每次都执行，只有主引擎不可用、不支持目标语言、返回空结果、翻译失败，或者因为其他原因无法继续时，才会进入这一层。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '所以回退规则的作用，不是替代主引擎，而是在主引擎失败时提供后备链路。主引擎成功时，不会触发回退。', 'langrouter-for-translatepress' ); ?></p>

<pre style="background:#f6f7f7;padding:12px 16px;border:1px solid #dcdcde;overflow:auto;"><?php
echo esc_html__(
	'en_US = volc
yue = openai',
	'langrouter-for-translatepress'
);
?></pre>

<p class="description"><?php esc_html_e( '上面的意思是：如果英文当前主引擎不能继续，就尝试火山；如果粤语当前主引擎不能继续，就尝试 OpenAI。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '7. 文章类型规则里的失败模式是什么', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '当文章类型分配命中后，还可以决定“这个文章类型的主引擎失败时，后面怎么继续”。当前常见有三种模式：失败不翻译、仅默认引擎、全局规则。', 'langrouter-for-translatepress' ); ?></p>

<ul style="list-style:disc;padding-left:18px;">
	<li><?php esc_html_e( '失败不翻译：文章类型主引擎失败后，直接停止，不再进入语言分配、回退规则或默认引擎。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '仅默认引擎：文章类型主引擎失败后，跳过语言分配和回退规则，直接尝试默认引擎。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '全局规则：文章类型主引擎失败后，继续按“语言分配 → 回退规则 → 默认引擎”的顺序往下尝试。', 'langrouter-for-translatepress' ); ?></li>
</ul>

<p><?php esc_html_e( '这一块是整套路由里最容易混淆的地方。你可以简单记住：文章类型规则决定“先走谁”，失败模式决定“先走的那个不行后，还让不让继续往后试”。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '8. 账号池是什么', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '账号池通常出现在 DeepL 或多 Key 场景里。它的作用是从多个 key 或多个账号里选择可用候选项继续请求，用来提高可用性、分摊请求压力，或者在单个 key 失效时继续尝试其他候选。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '所以日志里如果出现“开始选择候选 key”“候选 key 数量”“跳过当前 key”一类记录，多半和账号池有关。它不一定表示路由选错了引擎，只表示这个引擎内部正在从多个凭证里继续挑选可用候选。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '9. 日志是什么', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '日志不是普通设置项，而是排错入口。它会告诉你本次请求命中了哪一层规则、实际先走了哪个引擎、有没有继续回退、失败原因是什么、最终停在了哪里。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '看这类多引擎、多规则插件时，不建议只凭页面现象猜问题。最快的方法通常是先看日志，再确认到底是规则没命中、引擎不支持语言，还是凭证、模型名、接口地址本身有问题。', 'langrouter-for-translatepress' ); ?></p>

<div style="background:#fff8e5;border-left:4px solid #dba617;padding:12px 16px;margin:16px 0;">
	<strong><?php esc_html_e( '最重要的执行顺序：', 'langrouter-for-translatepress' ); ?></strong>
	<?php esc_html_e( '通常先看文章类型主引擎；如果没有命中，再看语言分配；如果当前链路失败，再看回退规则；最后才会落到默认引擎。', 'langrouter-for-translatepress' ); ?>
</div>

<h4><?php esc_html_e( '10. 一个最容易理解的例子', 'langrouter-for-translatepress' ); ?></h4>
<pre style="background:#f6f7f7;padding:12px 16px;border:1px solid #dcdcde;overflow:auto;"><?php
echo esc_html__(
	'默认引擎：openai

文章类型分配：
  guides -> qwen（全局规则）

语言分配：
  en_US = hunyuan

回退规则：
  en_US = volc',
	'langrouter-for-translatepress'
);
?></pre>

<p><?php esc_html_e( '这组配置可以这样理解：如果当前是 guides 单篇内容页，就先走 Qwen；如果 Qwen 不能继续，而且 guides 这条规则允许走全局规则，那么再看 en_US 是否命中了语言分配；命中了就先尝试Hunyuan；如果Hunyuan也不能继续，再按回退规则尝试火山；最后才落到默认引擎 OpenAI。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '11. 新人最容易误解的几件事', 'langrouter-for-translatepress' ); ?></h4>
<ul style="list-style:disc;padding-left:18px;">
	<li><?php esc_html_e( '把某个引擎设成默认引擎，不代表所有请求都会先走它。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '语言分配优先级高于默认引擎，但低于命中的文章类型主引擎。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '文章类型分配只对单篇内容页生效，不要用列表页、归档页或搜索结果页来判断它有没有命中。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '回退规则不是每次都执行，只有当前链路不能继续时才会触发。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '日志里出现某个引擎名，不一定表示这次主请求就是先走了它。', 'langrouter-for-translatepress' ); ?></li>
</ul>

<h4><?php esc_html_e( '12. 给小白的最简单理解方式', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '如果你刚接触这个插件，可以先只记住下面这三句话：', 'langrouter-for-translatepress' ); ?></p>
<ol style="padding-left:18px;">
	<li><?php esc_html_e( '模型设置页是把每个引擎先配好。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '路由设置页是决定这次请求该走谁。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '日志页是出了问题后最快的排错入口。', 'langrouter-for-translatepress' ); ?></li>
</ol>

<p><?php esc_html_e( '先把这三句记住，再去理解默认引擎、语言分配、回退规则和文章类型分配，基本就不会乱。', 'langrouter-for-translatepress' ); ?></p>

<p style="margin-top:20px;text-align:right;">
	<a class="button button-secondary" href="<?php echo esc_url( $routing_help_url ); ?>"><?php esc_html_e( '查看路由设置说明', 'langrouter-for-translatepress' ); ?></a>
	<a class="button button-secondary" href="<?php echo esc_url( $logs_help_url ); ?>" style="margin-left:8px;"><?php esc_html_e( '查看日志与排错', 'langrouter-for-translatepress' ); ?></a>
</p>
