<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h3 style="margin-top:0;"><?php esc_html_e( '路由设置', 'langrouter-for-translatepress' ); ?></h3>

<p><?php esc_html_e( '这一页用于决定 Router 在真正发起翻译前，应该把请求交给哪个子引擎。你可以把它理解成“派单中心”：先判断是否命中文章类型规则，再判断是否命中语言分配，然后决定失败后的回退路径，最后才会落到默认引擎。', 'langrouter-for-translatepress' ); ?></p>

<div style="background:#fff8e5;border-left:4px solid #dba617;padding:12px 16px;margin:16px 0;">
	<strong><?php esc_html_e( '当前优先级：', 'langrouter-for-translatepress' ); ?></strong>
	<?php esc_html_e( '文章类型主引擎 → 语言分配 → 回退规则 → 默认引擎。文章类型分配仅对单篇内容页生效。', 'langrouter-for-translatepress' ); ?>
</div>

<h4><?php esc_html_e( '1. 默认引擎是什么', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '默认引擎不是“永远先走它”的引擎，而是在没有命中文章类型规则、没有命中语言分配，或者前面的可继续链路都没有成功时，最后使用的兜底引擎。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '如果你还在第一轮测试，建议把当前唯一已经验证成功的引擎设为默认引擎。这样最稳，也最容易排查问题。', 'langrouter-for-translatepress' ); ?></p>

<pre style="background:#f6f7f7;padding:12px 16px;border:1px solid #dcdcde;overflow:auto;"><?php echo esc_html__( '默认引擎：openai', 'langrouter-for-translatepress' ); ?></pre>
<p class="description"><?php esc_html_e( '上面的意思是：如果当前请求没有命中任何更高优先级规则，最后就使用 OpenAI。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '2. 文章类型分配是做什么的', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '文章类型分配是“按内容类型指定主引擎”。当当前翻译请求来自某个单篇内容对象，并且该对象的文章类型命中了你配置的规则时，Router 会优先使用这里指定的引擎。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '你可以把它理解成：不同类型的内容，先交给不同的主引擎处理。比如商品页优先走某个术语更稳定的引擎，案例页优先走另一个更适合长文本润色的引擎。', 'langrouter-for-translatepress' ); ?></p>

<pre style="background:#f6f7f7;padding:12px 16px;border:1px solid #dcdcde;overflow:auto;"><?php
echo esc_html__(
	'文章类型：post、page
指定引擎：openai

文章类型：product
指定引擎：deepl

文章类型：guides
指定引擎：qwen',
	'langrouter-for-translatepress'
);
?></pre>

<p class="description"><?php esc_html_e( '上面的意思是：普通文章和页面优先走 OpenAI，商品页优先走 DeepL，攻略页优先走 Qwen。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '3. 最重要：文章类型分配只对单篇内容页生效', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '这是最容易误解的地方。文章类型分配并不是“只要站点里存在这个文章类型，就让所有相关页面都按它走”，而是只有当前翻译对象能够识别为某个单篇内容页时，才会按该对象的文章类型匹配规则。', 'langrouter-for-translatepress' ); ?></p>

<ul style="list-style:disc;padding-left:18px;">
	<li><?php esc_html_e( '常见会生效的场景：单篇文章页、单个页面、单个商品页、单个作品页、单条自定义文章类型详情页。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '通常不会按文章类型规则处理的场景：文章列表页、分类归档页、标签页、搜索结果页、首页聚合流，以及各种非单篇上下文。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '所以如果你给 product 配了引擎，但在商品归档页、商品列表模块或搜索结果页测试时没有按 product 规则走，很多时候并不是设置错误，而是因为当前页面不属于单篇内容页上下文。', 'langrouter-for-translatepress' ); ?></li>
</ul>

<div style="background:#f0f6fc;border-left:4px solid #2271b1;padding:12px 16px;margin:16px 0;">
	<strong><?php esc_html_e( '实操理解：', 'langrouter-for-translatepress' ); ?></strong>
	<?php esc_html_e( '“给 guides 指定 Qwen”的真正含义是：当 Router 正在翻译某个 guides 单篇详情页内容时，优先走 Qwen；不是所有和 guides 有关的页面都强制走 Qwen。', 'langrouter-for-translatepress' ); ?>
</div>

<h4><?php esc_html_e( '4. 文章类型规则里的“失败后回退”是什么意思', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '每条文章类型规则都可以指定“主引擎失败后，接下来怎么处理”。这里的失败包括：引擎当前不可用、不支持目标语言、接口报错、翻译失败等无法继续的情况。', 'langrouter-for-translatepress' ); ?></p>

<h5><?php esc_html_e( '1）失败不翻译', 'langrouter-for-translatepress' ); ?></h5>
<p><?php esc_html_e( '命中文章类型规则后，先尝试该条规则指定的主引擎；如果该主引擎不能继续，则直接停止，不再继续走语言分配、回退规则或默认引擎。', 'langrouter-for-translatepress' ); ?></p>

<pre style="background:#f6f7f7;padding:12px 16px;border:1px solid #dcdcde;overflow:auto;"><?php
echo esc_html__(
	'默认引擎：deepl

文章类型分配：
  guides -> qwen（失败不翻译）

语言分配：
  am = volc

回退规则：
  am = hunyuan',
	'langrouter-for-translatepress'
);
?></pre>

<p class="description"><?php esc_html_e( '上面的意思是：攻略页先走 Qwen；如果 Qwen 不支持当前目标语言、不可用或翻译失败，则直接停止，不会继续尝试 volc、hunyuan 或默认的 DeepL。', 'langrouter-for-translatepress' ); ?></p>

<h5><?php esc_html_e( '2）仅默认引擎', 'langrouter-for-translatepress' ); ?></h5>
<p><?php esc_html_e( '命中文章类型规则后，先尝试该条规则指定的主引擎；如果该主引擎不能继续，则跳过语言分配和回退规则，直接尝试默认引擎。', 'langrouter-for-translatepress' ); ?></p>

<pre style="background:#f6f7f7;padding:12px 16px;border:1px solid #dcdcde;overflow:auto;"><?php
echo esc_html__(
	'默认引擎：openai

文章类型分配：
  guides -> qwen（仅默认引擎）

语言分配：
  am = volc

回退规则：
  am = hunyuan',
	'langrouter-for-translatepress'
);
?></pre>

<p class="description"><?php esc_html_e( '上面的意思是：攻略页先走 Qwen；如果 Qwen 不能继续，则不管语言分配和回退规则里写了什么，都直接尝试默认引擎 OpenAI。', 'langrouter-for-translatepress' ); ?></p>

<h5><?php esc_html_e( '3）全局规则', 'langrouter-for-translatepress' ); ?></h5>
<p><?php esc_html_e( '命中文章类型规则后，先尝试该条规则指定的主引擎；如果该主引擎不能继续，则继续按“语言分配 → 回退规则 → 默认引擎”的顺序往下尝试。', 'langrouter-for-translatepress' ); ?></p>

<pre style="background:#f6f7f7;padding:12px 16px;border:1px solid #dcdcde;overflow:auto;"><?php
echo esc_html__(
	'默认引擎：deepl

文章类型分配：
  guides -> qwen（全局规则）

语言分配：
  am = volc

回退规则：
  am = hunyuan',
	'langrouter-for-translatepress'
);
?></pre>

<p class="description"><?php esc_html_e( '上面的意思是：攻略页先走 Qwen；如果 Qwen 不能继续，则先尝试语言分配里的 volc，再尝试回退规则里的 hunyuan，最后才尝试默认的 DeepL。', 'langrouter-for-translatepress' ); ?></p>

<div style="background:#fff8e5;border-left:4px solid #dba617;padding:12px 16px;margin:16px 0;">
	<strong><?php esc_html_e( '建议：', 'langrouter-for-translatepress' ); ?></strong>
	<?php esc_html_e( '如果你希望某类内容必须只走指定主引擎，就选“失败不翻译”；如果你希望失败后还能自动兜底，一般选“仅默认引擎”或“全局规则”。其中“全局规则”最灵活，但排查时也最复杂。', 'langrouter-for-translatepress' ); ?>
</div>

<h4><?php esc_html_e( '5. 语言分配怎么写', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '语言分配是“按目标语言指定主引擎”。格式是一行一条：左边写语言代码，右边写引擎标识。这里必须填写引擎标识，不要填写页面显示名称。', 'langrouter-for-translatepress' ); ?></p>

<pre style="background:#f6f7f7;padding:12px 16px;border:1px solid #dcdcde;overflow:auto;"><?php
echo esc_html__(
	'en_US = hunyuan
yue = deepl
am = openai_compatible',
	'langrouter-for-translatepress'
);
?></pre>

<p class="description"><?php esc_html_e( '上面的意思是：英文（en_US）优先走Hunyuan，粤语（yue）优先走 DeepL，阿姆哈拉语（am）优先走兼容 OpenAI。', 'langrouter-for-translatepress' ); ?></p>

<p><?php esc_html_e( '语言分配只有在当前请求没有被文章类型主引擎拦截，或者文章类型规则设置为“全局规则”且主引擎失败后，才会真正参与后续决策。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '6. 回退规则怎么写', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '回退规则的格式与语言分配相同，但它不是主路由，而是“主引擎或前面链路不能继续时的后备规则”。主引擎成功时，不会进入回退链路。', 'langrouter-for-translatepress' ); ?></p>

<pre style="background:#f6f7f7;padding:12px 16px;border:1px solid #dcdcde;overflow:auto;"><?php
echo esc_html__(
	'en_US = volc
yue = openai
am = hunyuan',
	'langrouter-for-translatepress'
);
?></pre>

<p class="description"><?php esc_html_e( '上面的意思是：当英文当前主引擎不能继续时，尝试火山；当粤语当前主引擎不能继续时，尝试 OpenAI；当阿姆哈拉语当前主引擎不能继续时，尝试Hunyuan。', 'langrouter-for-translatepress' ); ?></p>

<p><?php esc_html_e( '需要注意：回退规则只有在当前请求允许继续回退时才会真正执行。如果文章类型规则选择的是“失败不翻译”，那么即使你在这里写了规则，也不会进入这一层。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '7. 完整优先级怎么理解', 'langrouter-for-translatepress' ); ?></h4>
<ol style="padding-left:18px;">
	<li><?php esc_html_e( '先判断当前请求是不是单篇内容页。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '如果是，并且命中了文章类型规则，就先使用该文章类型指定的主引擎。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '如果该文章类型规则的失败模式是“失败不翻译”，主引擎不能继续时，直接停止。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '如果失败模式是“仅默认引擎”，主引擎不能继续时，直接尝试默认引擎。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '如果失败模式是“全局规则”，主引擎不能继续时，再继续判断语言分配、回退规则和默认引擎。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '如果一开始就没有命中文章类型规则，则直接进入语言分配；语言分配不命中或不能继续时，再看回退规则，最后落到默认引擎。', 'langrouter-for-translatepress' ); ?></li>
</ol>

<h4><?php esc_html_e( '8. 几个最常见的实际示例', 'langrouter-for-translatepress' ); ?></h4>

<h5><?php esc_html_e( '示例 A：最简单，只设默认引擎', 'langrouter-for-translatepress' ); ?></h5>
<pre style="background:#f6f7f7;padding:12px 16px;border:1px solid #dcdcde;overflow:auto;"><?php
echo esc_html__(
	'默认引擎：openai

文章类型分配：不配置
语言分配：不配置
回退规则：不配置',
	'langrouter-for-translatepress'
);
?></pre>
<p><?php esc_html_e( '效果：所有请求最后都走 OpenAI。这是最简单、最容易排查的起步配置。', 'langrouter-for-translatepress' ); ?></p>

<h5><?php esc_html_e( '示例 B：默认引擎 + 语言分配', 'langrouter-for-translatepress' ); ?></h5>
<pre style="background:#f6f7f7;padding:12px 16px;border:1px solid #dcdcde;overflow:auto;"><?php
echo esc_html__(
	'默认引擎：openai

语言分配：
  en_US = hunyuan
  yue = deepl',
	'langrouter-for-translatepress'
);
?></pre>
<p><?php esc_html_e( '效果：大多数语言仍走 OpenAI；英文优先走Hunyuan，粤语优先走 DeepL。没有命中语言分配时，最后走 OpenAI。', 'langrouter-for-translatepress' ); ?></p>

<h5><?php esc_html_e( '示例 C：默认引擎 + 语言分配 + 回退规则', 'langrouter-for-translatepress' ); ?></h5>
<pre style="background:#f6f7f7;padding:12px 16px;border:1px solid #dcdcde;overflow:auto;"><?php
echo esc_html__(
	'默认引擎：openai

语言分配：
  en_US = hunyuan

回退规则：
  en_US = volc',
	'langrouter-for-translatepress'
);
?></pre>
<p><?php esc_html_e( '效果：英文优先走Hunyuan；如果Hunyuan不能继续，则尝试火山；如果火山仍不能继续，最后再尝试默认的 OpenAI。', 'langrouter-for-translatepress' ); ?></p>

<h5><?php esc_html_e( '示例 D：文章类型规则 + 失败不翻译', 'langrouter-for-translatepress' ); ?></h5>
<pre style="background:#f6f7f7;padding:12px 16px;border:1px solid #dcdcde;overflow:auto;"><?php
echo esc_html__(
	'默认引擎：deepl

文章类型分配：
  guides -> qwen（失败不翻译）

语言分配：
  am = volc

回退规则：
  am = hunyuan',
	'langrouter-for-translatepress'
);
?></pre>
<p><?php esc_html_e( '效果：guides 单篇详情页先走 Qwen；如果 Qwen 不支持当前目标语言、不可用或翻译失败，则直接停止，不会再尝试 volc、hunyuan 或默认的 DeepL。', 'langrouter-for-translatepress' ); ?></p>

<h5><?php esc_html_e( '示例 E：文章类型规则 + 仅默认引擎', 'langrouter-for-translatepress' ); ?></h5>
<pre style="background:#f6f7f7;padding:12px 16px;border:1px solid #dcdcde;overflow:auto;"><?php
echo esc_html__(
	'默认引擎：openai

文章类型分配：
  product -> deepl（仅默认引擎）

语言分配：
  en_US = hunyuan

回退规则：
  en_US = volc',
	'langrouter-for-translatepress'
);
?></pre>
<p><?php esc_html_e( '效果：商品详情页先走 DeepL；如果 DeepL 不能继续，则跳过Hunyuan和火山，直接尝试默认的 OpenAI。', 'langrouter-for-translatepress' ); ?></p>

<h5><?php esc_html_e( '示例 F：文章类型规则 + 全局规则', 'langrouter-for-translatepress' ); ?></h5>
<pre style="background:#f6f7f7;padding:12px 16px;border:1px solid #dcdcde;overflow:auto;"><?php
echo esc_html__(
	'默认引擎：deepl

文章类型分配：
  guides -> qwen（全局规则）

语言分配：
  am = volc

回退规则：
  am = hunyuan',
	'langrouter-for-translatepress'
);
?></pre>
<p><?php esc_html_e( '效果：guides 单篇详情页先走 Qwen；如果 Qwen 不能继续，则先尝试 volc，再尝试 hunyuan，最后尝试默认的 DeepL。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '9. 写规则时最容易错的地方', 'langrouter-for-translatepress' ); ?></h4>
<ul style="list-style:disc;padding-left:18px;">
	<li><?php esc_html_e( '语言分配和回退规则右边填写的是“引擎标识”，不是页面显示名称。比如要写 openai_compatible，而不是“兼容 OpenAI”。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '尽量一行一个规则，不要把多个规则写在同一行。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '先确认主引擎本身可用，再配置回退规则。否则你很难判断到底是主引擎问题，还是回退链问题。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '文章类型分配只对单篇内容页生效，不要用列表页、归档页或搜索结果页去判断文章类型规则有没有命中。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '如果文章类型规则选的是“失败不翻译”，那么即使语言分配和回退规则里写得再完整，也不会继续进入这些后续链路。', 'langrouter-for-translatepress' ); ?></li>
</ul>

<h4><?php esc_html_e( '10. 建议的配置顺序', 'langrouter-for-translatepress' ); ?></h4>
<ul style="list-style:disc;padding-left:18px;">
	<li><?php esc_html_e( '第一步：只设置默认引擎，确认 Router 已经可以正常工作。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '第二步：给少量确定需要特殊处理的语言增加语言分配。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '第三步：再给重要的目标语言增加回退规则。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '第四步：最后再给确实需要特殊策略的文章类型增加规则，例如 product、portfolio、guides、case。', 'langrouter-for-translatepress' ); ?></li>
</ul>

<p><?php esc_html_e( '这样做的好处是：一旦某一步出现问题，你更容易判断到底是默认引擎、语言分配、回退规则，还是文章类型主链路造成的。', 'langrouter-for-translatepress' ); ?></p>

<p style="margin-top:20px;text-align:right;">
	<a class="button button-primary" href="<?php echo esc_url( $translation_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '前往路由设置页面', 'langrouter-for-translatepress' ); ?></a>
	<a class="button button-secondary" href="<?php echo esc_url( $concepts_help_url ); ?>" style="margin-left:8px;"><?php esc_html_e( '返回看基本概念', 'langrouter-for-translatepress' ); ?></a>
</p>
