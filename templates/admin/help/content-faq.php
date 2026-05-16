<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h3 style="margin-top:0;"><?php esc_html_e( '常见问题', 'langrouter-for-translatepress' ); ?></h3>

<p><?php esc_html_e( '这一页主要回答路由设置、引擎命中、回退链路和日志排查中最常见的问题。如果你已经配置了默认引擎、语言分配、文章类型分配和回退规则，但实际运行结果和预期不一致，建议先看这里。', 'langrouter-for-translatepress' ); ?></p>

<div style="background:#fff8e5;border-left:4px solid #dba617;padding:12px 16px;margin:16px 0;">
	<strong><?php esc_html_e( '先记住一条总规则：', 'langrouter-for-translatepress' ); ?></strong>
	<?php esc_html_e( '当前优先级是：文章类型主引擎 → 语言分配 → 回退规则 → 默认引擎。文章类型分配仅对单篇内容页生效。', 'langrouter-for-translatepress' ); ?>
</div>

<h4><?php esc_html_e( '为什么我设置了默认引擎，实际却走了别的引擎？', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '因为默认引擎只是兜底引擎，不是“永远先走它”的引擎。只要当前请求命中了更高优先级规则，就会优先走更高优先级的那个引擎。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '最常见的情况有两种：第一，命中了文章类型分配；第二，虽然没有命中文章类型规则，但命中了语言分配。只要这两层有命中，默认引擎就会退到后面。', 'langrouter-for-translatepress' ); ?></p>

<pre style="background:#f6f7f7;padding:12px 16px;border:1px solid #dcdcde;overflow:auto;"><?php
echo esc_html__(
	'默认引擎：openai

文章类型分配：
  guides -> qwen（失败不翻译）

语言分配：
  am = volc',
	'langrouter-for-translatepress'
);
?></pre>

<p class="description"><?php esc_html_e( '上面的配置里，如果当前请求是 guides 单篇内容页，那么会先走 Qwen；如果不是 guides 单篇，但目标语言是 am，则会走 volc；只有两者都没命中时，最后才走默认的 OpenAI。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '为什么我明明写了语言分配，结果还是先走了文章类型指定引擎？', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '因为文章类型规则的优先级高于语言分配。只要当前请求是单篇内容页，并且它的文章类型命中了规则，就会优先使用文章类型里指定的主引擎。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '语言分配并没有失效，只是它排在文章类型规则后面。只有文章类型规则没有命中，或者文章类型规则允许继续全局规则并且主引擎失败后，语言分配才会真正参与后续决策。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '为什么我设置了文章类型分配，但看起来没有生效？', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '最常见的原因不是规则没保存，而是当前页面根本不属于“单篇内容页”上下文。文章类型分配只对单篇内容对象生效，不会因为站点里存在某个文章类型，就让所有相关页面都按它走。', 'langrouter-for-translatepress' ); ?></p>

<ul style="list-style:disc;padding-left:18px;">
	<li><?php esc_html_e( '常见会命中文章类型规则的场景：单篇文章页、单个页面、单个商品页、单条自定义文章类型详情页。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '常见不会按文章类型规则处理的场景：文章列表页、分类归档页、标签页、搜索结果页、首页聚合流。', 'langrouter-for-translatepress' ); ?></li>
</ul>

<p><?php esc_html_e( '所以如果你给 guides 配了 Qwen，但你是在 guides 列表页、搜索结果页或归档页测试，看到的就可能不是 Qwen，而是语言分配或默认引擎。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '为什么我设置了回退规则，但没有触发？', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '回退规则只有在前面的主引擎不能继续时才会生效。这里的“不能继续”包括：引擎不可用、不支持目标语言、请求失败、返回空结果，或者其他导致当前链路无法完成翻译的情况。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '如果主引擎成功了，那就不会进入回退规则。回退规则不是主路由，它只是失败后的后备链路。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '为什么文章类型规则设置成“失败不翻译”后，还是看到了语言分配或默认引擎？', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '如果真的命中了文章类型规则，并且该规则的失败模式是“失败不翻译”，那么主引擎失败后就不会继续走语言分配、回退规则或默认引擎。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '如果你仍然在日志里看到了别的引擎，通常说明是下面几种情况之一：第一，当前请求根本没有命中文章类型规则；第二，你看的不是同一个 route_id；第三，看到的是别的请求、语言支持检查或其他辅助日志，而不是这次主链路的结果。', 'langrouter-for-translatepress' ); ?></p>

<pre style="background:#f6f7f7;padding:12px 16px;border:1px solid #dcdcde;overflow:auto;"><?php
echo esc_html__(
	'文章类型分配：
  guides -> qwen（失败不翻译）

语言分配：
  am = volc

回退规则：
  am = hunyuan

默认引擎：
  deepl',
	'langrouter-for-translatepress'
);
?></pre>

<p class="description"><?php esc_html_e( '这组配置真正命中 guides 单篇内容页时，Qwen 失败后应该直接停止，不会再继续尝试 volc、hunyuan 或 DeepL。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '“失败不翻译”“仅默认引擎”“全局规则”到底有什么区别？', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '这三种模式都只针对“文章类型规则命中后，主引擎失败时怎么继续”这个问题。区别不在于主引擎怎么选，而在于主引擎失败后的后续链路是否继续。', 'langrouter-for-translatepress' ); ?></p>

<ul style="list-style:disc;padding-left:18px;">
	<li><?php esc_html_e( '失败不翻译：主引擎失败后直接停止，不再继续后续链路。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '仅默认引擎：主引擎失败后跳过语言分配和回退规则，直接尝试默认引擎。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '全局规则：主引擎失败后，继续按“语言分配 → 回退规则 → 默认引擎”的顺序往下尝试。', 'langrouter-for-translatepress' ); ?></li>
</ul>

<h4><?php esc_html_e( '为什么一次日志里会出现多个引擎？', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '因为一次请求可能不只经过一个引擎。最常见的情况是：先尝试主引擎，主引擎失败后再尝试回退引擎，最后再尝试默认引擎。这样同一个 route_id 下面就会连续出现多个引擎。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '另外，日志里也可能混入语言支持判断、缓存命中、账号池检查或引擎实例化等记录，所以看到某个引擎名，不代表它一定承担了这次最终翻译。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '为什么我看见 DeepL 的日志，但这次想测的是 Qwen 或Hunyuan？', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '看到 DeepL 的日志，不一定表示主请求先走了 DeepL。它可能是默认引擎回退命中，也可能只是 DeepL 的语言支持检查、缓存读取或其他预检查日志。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '排查时不要只看“出现了哪个引擎名”，而要优先看同一个 route_id 下的 selected_engine、route_source、fallback_source 和 final_engine。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '看日志时，最应该先看哪几个字段？', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '排查路由问题时，建议优先按下面这组顺序看日志。这样最快判断到底是规则没命中，还是命中了但失败后走了别的链路。', 'langrouter-for-translatepress' ); ?></p>

<ul style="list-style:disc;padding-left:18px;">
	<li><?php esc_html_e( 'route_id：先确认你看的确实是同一次请求。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( 'selected_engine：这次主路由最开始选中了哪个引擎。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( 'route_source：主路由是从文章类型规则、语言分配，还是默认引擎命中的。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( 'post_type_rule_found：是否真的命中了文章类型规则。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( 'fallback_mode / runtime_fallback_mode：文章类型规则失败后允许怎么继续。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( 'fallback_source：这次如果发生回退，是从哪一层继续的。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( 'final_engine / final_status：最终是哪个引擎完成，或者在哪一步停止。', 'langrouter-for-translatepress' ); ?></li>
</ul>

<h4><?php esc_html_e( '为什么日志里写着语言分配是 volc，但这次实际并没有走 volc？', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '因为日志里有些字段只是“配置快照”，不是“实际执行结果”。例如 configured_language_rule_engine 表示当前目标语言在语言分配里配置的是哪个引擎，但如果这次请求先命中了文章类型规则，并且文章类型规则不允许继续全局规则，那么这个配置虽然会出现在日志里，却不会真正执行。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '判断有没有真正执行某个引擎，不要只看 configured_language_rule_engine 或 configured_fallback_rule_engine，而要结合 selected_engine、route_source、实例化子引擎、开始分发翻译请求和回退决策一起看。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '为什么保存一个标签页，其他引擎设置还在？', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '这是插件的设计行为。保存当前标签页时，会尽量保留其他未提交标签页的已有配置，避免误清空。这样做的目的是减少多标签配置时的误删风险。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '为什么我已经配好了某个引擎，翻译还是失败？', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '“配好了”不一定等于“真的能翻译成功”。很多失败来自密钥错误、模型名不匹配、接口地址错误、目标语言不支持，或者上游接口虽然返回了 200，但实际上携带了错误信息。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '如果你已经确认路由命中的是正确引擎，但翻译仍然失败，就不要再纠结路由规则，而应直接看该引擎本身的运行日志、响应内容和失败类型。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '为什么我设置了回退规则，但最终还是没有结果？', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '因为“进入了回退链”不等于“回退一定会成功”。如果回退引擎本身也不支持目标语言、不可用或请求失败，那么即使回退规则被触发了，最终仍然可能没有翻译结果。', 'langrouter-for-translatepress' ); ?></p>

<pre style="background:#f6f7f7;padding:12px 16px;border:1px solid #dcdcde;overflow:auto;"><?php
echo esc_html__(
	'默认引擎：deepl

语言分配：
  am = volc

回退规则：
  am = hunyuan',
	'langrouter-for-translatepress'
);
?></pre>

<p class="description"><?php esc_html_e( '如果 volc 不支持 am，而 hunyuan 也不支持 am，那么这条链虽然发生了回退，但最终仍然会失败。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '新手最推荐的起步方式是什么？', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '最推荐的顺序是：先配置一个引擎并设为默认引擎；确认它可以稳定翻译后，再增加语言分配；之后再增加回退规则；最后再配置文章类型分配和更复杂的失败模式。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '这个顺序最容易排错，因为你可以很清楚地知道当前问题到底出在默认引擎、语言分配、回退规则，还是文章类型主链路。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '如果以后还会继续增加引擎，当前结构好维护吗？', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '当前版本已经把帮助模板、引擎注册、模型配置、路由规则和运行时工厂做了拆分，比早期版本更容易维护。后面新增引擎时，建议继续按现有结构补充配置类、模板、注册信息和运行时实现。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '如果你后续继续扩展路由能力，也建议保持现在这种“先命中、再失败分支、再回退链”的清晰结构，并让日志字段保持稳定。这样以后排查会轻松很多。', 'langrouter-for-translatepress' ); ?></p>

<p style="margin-top:20px;text-align:right;">
	<a class="button button-secondary" href="<?php echo esc_url( $quickstart_url ); ?>"><?php esc_html_e( '返回快速开始', 'langrouter-for-translatepress' ); ?></a>
	<a class="button button-secondary" href="<?php echo esc_url( $engines_help_url ); ?>" style="margin-left:8px;"><?php esc_html_e( '查看引擎配置说明', 'langrouter-for-translatepress' ); ?></a>
</p>
