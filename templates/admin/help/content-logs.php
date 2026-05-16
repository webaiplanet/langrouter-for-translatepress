<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h3 style="margin-top:0;"><?php esc_html_e( '日志与排错', 'langrouter-for-translatepress' ); ?></h3>

<p>
	<?php
	echo wp_kses_post(
			__(
					'日志选项统一管理插件文件日志开关，并直接查看插件日志目录中的最近日志文件。日志目录会自动写入访问保护文件，且正文、预览、密钥等敏感字段会按安全模式脱敏记录。',
					'langrouter-for-translatepress'
			)
	);
	?>
</p>

<p><?php esc_html_e( '对这类带多引擎、多规则、多层回退的插件来说，日志是排错效率最高的入口。先确认日志里“这次请求到底命中了谁、为什么继续、为什么停止”，再去猜配置问题，速度会快很多。', 'langrouter-for-translatepress' ); ?></p>

<div style="background:#fff8e5;border-left:4px solid #dba617;padding:12px 16px;margin:16px 0;">
	<strong><?php esc_html_e( '先记住一条：', 'langrouter-for-translatepress' ); ?></strong>
	<?php esc_html_e( '不要只看“日志里出现了哪个引擎名”，要优先围绕同一个 route_id 看完整链路。很多时候，日志里出现某个引擎，只表示它被检查过、被实例化过、被当成回退候选，或者做了语言支持判断，不一定表示它就是这次真正负责翻译的主引擎。', 'langrouter-for-translatepress' ); ?>
</div>

<h4><?php esc_html_e( '1. 先确认日志是否已启用', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '请先在“日志”标签页启用文件日志。当前日志文件默认写入 WordPress uploads 目录下的插件子目录。', 'langrouter-for-translatepress' ); ?></p>
<pre style="background:#f6f7f7;padding:12px 16px;border:1px solid #dcdcde;overflow:auto;"><?php echo esc_html( $uploads_log_relative_path ); ?></pre>
<p><?php esc_html_e( '如果没有启用日志，后面的路由命中、回退决策、失败类型和最终状态都无法快速确认。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '2. 看日志时，第一步永远先找 route_id', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( 'route_id 是同一次请求的关联编号。排查时不要东看一条、西看一条，而是要先找到同一个 route_id，再把它下面的一整组日志串起来读。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '同一次页面翻译，甚至同一篇内容，有时会出现不止一个 route_id。这并不一定是异常，可能只是同一页里不同来源文本、不同源语言片段，或者不同批次请求被拆开处理。遇到这种情况，要分别按 route_id 阅读，不要混着看。', 'langrouter-for-translatepress' ); ?></p>

<pre style="background:#f6f7f7;padding:12px 16px;border:1px solid #dcdcde;overflow:auto;"><?php
echo esc_html__(
	'[DEBUG] Router 路由命中 {"route_id":"r-20260318-092016-0001", ...}
[DEBUG] Router 开始分发翻译请求 {"route_id":"r-20260318-092016-0001", ...}
[DEBUG] 回退决策 {"route_id":"r-20260318-092016-0001", ...}
[DEBUG] Router 路由完成 {"route_id":"r-20260318-092016-0001", ...}',
	'langrouter-for-translatepress'
);
?></pre>

<p class="description"><?php esc_html_e( '上面的四条如果 route_id 相同，就属于同一次路由链路，应该放在一起看。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '3. 最重要的几个字段，先看这些就够了', 'langrouter-for-translatepress' ); ?></h4>
<ul style="list-style:disc;padding-left:18px;">
	<li><code>route_id</code>：<?php esc_html_e( '同一次请求的关联编号。', 'langrouter-for-translatepress' ); ?></li>
	<li><code>selected_engine</code>：<?php esc_html_e( '这次主路由最开始命中的引擎。要判断“到底先走了谁”，优先看它。', 'langrouter-for-translatepress' ); ?></li>
	<li><code>route_source</code>：<?php esc_html_e( '主路由是从哪一层命中的，例如 post_type_map、language_map、default_engine。', 'langrouter-for-translatepress' ); ?></li>
	<li><code>matched_rule</code>：<?php esc_html_e( '具体命中了哪条规则，例如 guides = qwen 或 am = volc。', 'langrouter-for-translatepress' ); ?></li>
	<li><code>post_type_rule_found</code>：<?php esc_html_e( '是否真的命中了文章类型规则。判断“文章类型分配有没有生效”时很重要。', 'langrouter-for-translatepress' ); ?></li>
	<li><code>fallback_mode</code> / <code>runtime_fallback_mode</code>：<?php esc_html_e( '文章类型主引擎失败后允许怎么继续，例如 none、default_only、global_chain。', 'langrouter-for-translatepress' ); ?></li>
	<li><code>planned_fallback_chain</code>：<?php esc_html_e( '本次预先计算出来的回退链。它表示“理论上接下来准备试谁”，不等于每个都一定会真正执行成功。', 'langrouter-for-translatepress' ); ?></li>
	<li><code>fallback_engine</code>：<?php esc_html_e( '这次实际正在尝试的回退引擎。', 'langrouter-for-translatepress' ); ?></li>
	<li><code>failure_type</code>：<?php esc_html_e( '失败类型，例如 unsupported_target_language、empty_result、auth_error 等。', 'langrouter-for-translatepress' ); ?></li>
	<li><code>final_engine</code>：<?php esc_html_e( '最后停在哪个引擎上，或者最终由哪个引擎完成。', 'langrouter-for-translatepress' ); ?></li>
	<li><code>final_status</code>：<?php esc_html_e( '这次路由最终是成功、停止、预检查失败，还是其他终态。', 'langrouter-for-translatepress' ); ?></li>
	<li><code>translated_count</code> / <code>final_result_count</code>：<?php esc_html_e( '本次真正翻译成功了多少条。判断“是完全失败、部分成功，还是完整成功”时很有用。', 'langrouter-for-translatepress' ); ?></li>
</ul>

<h4><?php esc_html_e( '4. 推荐的排错顺序', 'langrouter-for-translatepress' ); ?></h4>
<ol style="margin-left:18px;">
	<li><?php esc_html_e( '先找同一个 route_id。不要把不同 route_id 的日志混在一起判断。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '看 selected_engine，确认这次主引擎到底是谁。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '看 route_source 和 matched_rule，确认这次到底是文章类型规则命中、语言分配命中，还是默认引擎兜底。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '如果你怀疑文章类型分配没有生效，就重点看 post_type_rule_found、post_type_rule_lookup_reason 和 available_post_type_rule_keys。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '如果主引擎失败，再看 fallback_mode、planned_fallback_chain、fallback_engine 和 fallback_source。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '最后看 failure_type、final_engine、final_status 和 final_result_count，确认到底是直接成功、回退后成功、预检查失败，还是完全停止。', 'langrouter-for-translatepress' ); ?></li>
</ol>

<h4><?php esc_html_e( '5. route_source 到底是什么意思', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( 'route_source 用来说明“主引擎是从哪一层规则选出来的”。新人最容易在这里看错。', 'langrouter-for-translatepress' ); ?></p>

<ul style="list-style:disc;padding-left:18px;">
	<li><code>post_type_map</code>：<?php esc_html_e( '表示这次是文章类型规则命中的，例如 guides 单篇内容页命中了 guides -> qwen。', 'langrouter-for-translatepress' ); ?></li>
	<li><code>language_map</code>：<?php esc_html_e( '表示这次没有被文章类型主引擎拦截，而是目标语言命中了语言分配，例如 am = volc。', 'langrouter-for-translatepress' ); ?></li>
	<li><code>default_engine</code>：<?php esc_html_e( '表示前面都没有命中，或者前面的链路都没有成功，最后落到默认引擎。', 'langrouter-for-translatepress' ); ?></li>
</ul>

<pre style="background:#f6f7f7;padding:12px 16px;border:1px solid #dcdcde;overflow:auto;"><?php
echo esc_html__(
	'selected_engine: "qwen"
route_source: "post_type_map"
matched_rule: "guides = qwen"',
	'langrouter-for-translatepress'
);
?></pre>

<p class="description"><?php esc_html_e( '这表示当前主路由是由文章类型规则命中的，不是语言分配，也不是默认引擎。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '6. 为什么日志里会写 configured_language_rule_engine = volc，但这次实际没有走 volc？', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '因为有些字段只是“配置快照”，不是“实际执行结果”。例如 configured_language_rule_engine 表示当前目标语言在语言分配里配置的是哪个引擎，它告诉你“配置里写了谁”，但不等于“这次请求就一定真的执行了谁”。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '如果这次请求先命中了文章类型规则，并且文章类型规则的失败模式是“失败不翻译”，那么即使 configured_language_rule_engine 显示的是 volc，实际也不会进入 volc。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '7. 怎么看文章类型规则到底有没有生效', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '排查文章类型分配时，不要只看后台界面里有没有选中某个文章类型，而要看运行时日志是不是确认命中了。', 'langrouter-for-translatepress' ); ?></p>

<p><?php esc_html_e( '最关键的几个字段是：', 'langrouter-for-translatepress' ); ?></p>
<ul style="list-style:disc;padding-left:18px;">
	<li><code>post_type</code>：<?php esc_html_e( '当前对象的文章类型，例如 guides、product、post。', 'langrouter-for-translatepress' ); ?></li>
	<li><code>post_type_rule_found</code>：<?php esc_html_e( '是否命中了文章类型规则。1 表示命中，0 表示没有。', 'langrouter-for-translatepress' ); ?></li>
	<li><code>post_type_rule_lookup_reason</code>：<?php esc_html_e( '为什么命中或没命中。例如 matched_post_type_map 表示命中成功。', 'langrouter-for-translatepress' ); ?></li>
	<li><code>matched_post_type_rule_engine</code>：<?php esc_html_e( '命中的文章类型规则对应哪个引擎。', 'langrouter-for-translatepress' ); ?></li>
	<li><code>available_post_type_rule_keys</code>：<?php esc_html_e( '当前运行时可用的文章类型规则键。可以用它确认 guides、product 等规则是不是真的进入了运行时配置。', 'langrouter-for-translatepress' ); ?></li>
</ul>

<h4><?php esc_html_e( '8. 怎么看“失败不翻译”“仅默认引擎”“全局规则”是否按预期执行', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '这几种模式最容易在日志里看混。判断时，建议优先看 fallback_mode、runtime_fallback_mode 和 planned_fallback_chain。', 'langrouter-for-translatepress' ); ?></p>

<ul style="list-style:disc;padding-left:18px;">
	<li><code>none</code>：<?php esc_html_e( '失败不翻译。planned_fallback_chain 通常为空，fallback_engine 也通常为空。', 'langrouter-for-translatepress' ); ?></li>
	<li><code>default_only</code>：<?php esc_html_e( '仅默认引擎。planned_fallback_chain 里通常只有默认引擎。', 'langrouter-for-translatepress' ); ?></li>
	<li><code>global_chain</code>：<?php esc_html_e( '全局规则。planned_fallback_chain 里通常会按“语言分配 → 回退规则 → 默认引擎”的顺序列出候选。', 'langrouter-for-translatepress' ); ?></li>
</ul>

<pre style="background:#f6f7f7;padding:12px 16px;border:1px solid #dcdcde;overflow:auto;"><?php
echo esc_html__(
	'fallback_mode: "none"
runtime_fallback_mode: "none"
planned_fallback_chain: []',
	'langrouter-for-translatepress'
);
?></pre>

<p class="description"><?php esc_html_e( '上面这组最常见于“失败不翻译”，表示主引擎失败后不再继续尝试后续链路。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '9. 为什么一次日志里会出现多个引擎？', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '因为一次请求可能经历主引擎、回退引擎，最后再到默认引擎兜底；另外也可能混入语言支持查询、缓存命中、账号池检查、引擎实例化等记录。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '所以看到 DeepL、Qwen、Hunyuan或火山同时出现在同一组日志里，不代表它们都真正承担了翻译任务。它们可能只是被尝试过、被跳过、被预检查过，或者只是作为回退候选被记录。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '10. 为什么我看见 DeepL 的日志，但这次想测的是Hunyuan、Qwen 或火山？', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '看到某个引擎的日志，不一定表示主请求先走了这个引擎。它可能是回退命中，也可能是语言支持查询、缓存读取、账号池 key 检查，或者只是被实例化后很快跳过。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '真正判断“这次主路由先走了谁”，要优先看 selected_engine 和 route_source，而不是只看“某个引擎名有没有出现在日志里”。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '11. 常见报错怎么理解', 'langrouter-for-translatepress' ); ?></h4>
<ul style="list-style:disc;padding-left:18px;">
	<li><code>AuthFailure.SecretIdNotFound</code>：<?php esc_html_e( '通常表示腾讯云或Hunyuan相关凭证错误，不是路由规则选错了引擎。', 'langrouter-for-translatepress' ); ?></li>
	<li><code>unsupported_target_language</code> / <code>unsupported_language</code>：<?php esc_html_e( '当前引擎不支持该目标语言。后面是否继续，要看当前链路的失败模式和回退设置。', 'langrouter-for-translatepress' ); ?></li>
	<li><code>empty_result</code>：<?php esc_html_e( '请求发出去了，但没有拿到有效翻译结果。此时要继续看上游接口返回、失败类型和回退决策。', 'langrouter-for-translatepress' ); ?></li>
	<li><code>fallback_precheck_failed</code>：<?php esc_html_e( '说明回退引擎在真正发请求前的预检查阶段就没通过，例如语言不支持或引擎不可用。', 'langrouter-for-translatepress' ); ?></li>
</ul>

<h4><?php esc_html_e( '12. 为什么已经进入回退链，最终还是没有结果？', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '因为“发生了回退”不等于“回退一定成功”。如果回退引擎本身也不支持目标语言、不可用，或者请求仍然失败，那么整条链即使继续往后尝试，也可能最终没有翻译结果。', 'langrouter-for-translatepress' ); ?></p>

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

<h4><?php esc_html_e( '13. 为什么 source_language 有时是 zh_CN，有时是 en_US？', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '因为同一页里的不同文本片段，来源语言不一定完全一致。日志里的 source_language 表示这次批次在发起翻译时，系统识别或记录到的源语言。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '因此，同一篇内容在不同 route_id 里出现不同 source_language，并不一定是异常。排查时还是要优先看每个 route_id 的完整执行链，而不是单独盯某一个 source_language 字段。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '14. 为什么 primary_result_count 是 0，看起来却没有明显报错？', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '因为“没有翻译结果”不一定等于“接口抛出了明显错误”。有些情况是在真正发请求前就被预检查拦下了，例如目标语言不支持；也有些情况是请求成功返回了 200，但实际没有拿到有效译文。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '遇到这种情况，不要只看 HTTP 状态码，要同时看 failure_type、final_status、fallback_source 和 final_result_count。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '15. 新手最推荐的排错方式是什么？', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '最推荐的顺序是：先确认同一个 route_id 下到底命中了哪个引擎；再确认这次是文章类型规则、语言分配还是默认引擎生效；然后再去看该引擎本身是否支持当前语言、密钥是否正确、模型名是否可用。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '很多时候问题不是规则写错，而是命中的引擎本身不支持该语言，或者引擎配置本身有问题。先看清“命中了谁”，比一上来就反复改规则更省时间。', 'langrouter-for-translatepress' ); ?></p>

<div style="background:#f0f6fc;border-left:4px solid #72aee6;padding:12px 16px;margin:16px 0;">
	<strong><?php esc_html_e( '最省时间的经验：', 'langrouter-for-translatepress' ); ?></strong>
	<?php esc_html_e( '先确定实际命中了哪个引擎，再判断该引擎本身是否配置正确。很多时候问题不是规则写错，而是密钥、模型名、接口地址或语言支持本身有问题。', 'langrouter-for-translatepress' ); ?>
</div>

<p style="margin-top:20px;text-align:right;">
	<a class="button button-secondary" href="<?php echo esc_url( $logs_model_url ); ?>"><?php esc_html_e( '前往日志标签页', 'langrouter-for-translatepress' ); ?></a>
	<a class="button button-secondary" href="<?php echo esc_url( $faq_help_url ); ?>" style="margin-left:8px;"><?php esc_html_e( '查看常见问题', 'langrouter-for-translatepress' ); ?></a>
</p>
