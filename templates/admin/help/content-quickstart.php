<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h3 style="margin-top:0;"><?php esc_html_e( '快速开始', 'langrouter-for-translatepress' ); ?></h3>

<p><?php esc_html_e( '第一次使用时，最容易出问题的通常不是某一个字段，而是一次性把默认引擎、文章类型分配、语言分配、回退规则和账号池都打开了。建议先把最小链路跑通，再逐步增加复杂度。', 'langrouter-for-translatepress' ); ?></p>

<div style="background:#f6f7f7;border-left:4px solid #2271b1;padding:12px 16px;margin:16px 0;">
	<strong><?php esc_html_e( '最推荐的新手路线：', 'langrouter-for-translatepress' ); ?></strong>
	<?php esc_html_e( '先只配置一个你最熟悉、最稳定的引擎，把它设成默认引擎并完成一次成功翻译；成功以后，再给特定语言增加语言分配；最后才加回退规则、文章类型分配和账号池。', 'langrouter-for-translatepress' ); ?>
</div>

<h4><?php esc_html_e( '第一轮建议只做这 5 件事', 'langrouter-for-translatepress' ); ?></h4>
<ol style="margin-left:18px;">
	<li>
		<strong><?php esc_html_e( '配置一个可用引擎', 'langrouter-for-translatepress' ); ?></strong>
		<p>
			<?php
			printf(
					wp_kses_post(
							/* translators: 1: OpenAI tab URL, 2: Compatible OpenAI tab URL, 3: DeepL tab URL. */
							__(
									'例如先进入 <a href="%1$s">OpenAI</a>、<a href="%2$s">兼容 OpenAI</a> 或 <a href="%3$s">DeepL</a> 标签页，填写必要的密钥、模型和接口参数。',
									'langrouter-for-translatepress'
							)
					),
					esc_url( $openai_model_url ),
					esc_url( $compatible_openai_url ),
					esc_url( $deepl_model_url )
			);
			?>
		</p>
		<p class="description"><?php esc_html_e( '如果你已经确认Hunyuan、火山或 Qwen 的账号可用，也可以先用它们，但建议第一轮先选你最熟的一条链路。第一轮的目标不是“功能最全”，而是“先成功一次”。', 'langrouter-for-translatepress' ); ?></p>
	</li>

	<li>
		<strong><?php esc_html_e( '去自动翻译页只设置默认引擎', 'langrouter-for-translatepress' ); ?></strong>
		<p>
			<?php
			printf(
					wp_kses_post(
							/* translators: %s: TranslatePress automatic translation settings URL. */
							__(
									'前往 <a href="%s" target="_blank" rel="noopener noreferrer">TranslatePress → 自动翻译</a> 页面，在“路由设置”中把刚才配置好的引擎设为默认引擎。',
									'langrouter-for-translatepress'
							)
					),
					esc_url( $translation_url )
			);
			?>
		</p>
		<p class="description"><?php esc_html_e( '这一轮先只设默认引擎，不要急着增加语言分配、文章类型分配或回退规则。', 'langrouter-for-translatepress' ); ?></p>
	</li>

	<li>
		<strong><?php esc_html_e( '先不要配置文章类型分配、语言分配和回退规则', 'langrouter-for-translatepress' ); ?></strong>
		<p><?php esc_html_e( '如果默认引擎本身都还没有成功翻译，继续叠加文章类型规则、语言分配和回退规则，只会让问题更难定位。第一轮最重要的是确认“最简单的一条链路”已经能通。', 'langrouter-for-translatepress' ); ?></p>
	</li>

	<li>
		<strong><?php esc_html_e( '做一次真实翻译测试', 'langrouter-for-translatepress' ); ?></strong>
		<p><?php esc_html_e( '建议先找一个文本不太复杂、结构也不太复杂的页面测试。只要确认能正常出结果，就说明最小链路已经通了。', 'langrouter-for-translatepress' ); ?></p>
		<p class="description"><?php esc_html_e( '这里的“成功”不是指页面点开没报错，而是指实际有翻译结果，并且日志里能看到本次请求确实命中了你设置的默认引擎。', 'langrouter-for-translatepress' ); ?></p>
	</li>

	<li>
		<strong><?php esc_html_e( '如果失败，先看日志，不要立刻乱改规则', 'langrouter-for-translatepress' ); ?></strong>
		<p><?php esc_html_e( '第一轮失败时，最省时间的做法不是一口气改很多地方，而是先确认：这次到底有没有命中你以为的那个引擎、它是配置错误、语言不支持，还是请求本身失败。', 'langrouter-for-translatepress' ); ?></p>
	</li>
</ol>

<h4><?php esc_html_e( '第一轮成功后，再按这个顺序逐步增加复杂度', 'langrouter-for-translatepress' ); ?></h4>
<ul style="list-style:disc;padding-left:18px;">
	<li><?php esc_html_e( '第二轮：如果某个目标语言更适合某个引擎，再增加语言分配。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '第三轮：如果你担心主引擎失败后完全中断，再增加回退规则。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '第四轮：如果某些内容类型必须优先走特定引擎，再增加文章类型分配。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '第五轮：如果是 DeepL 或多 Key 场景，再考虑账号池。', 'langrouter-for-translatepress' ); ?></li>
</ul>

<h4><?php esc_html_e( '为什么不建议一开始就把所有功能都打开', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '因为一旦默认引擎、文章类型分配、语言分配、回退规则同时存在，你在页面上看到的现象就不一定来自你以为的那一层。很多新人以为“当前走的是默认引擎”，实际可能已经被文章类型规则或语言分配拦截了。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '所以快速开始的核心不是“把功能全部配置完”，而是“先确认最简单的主链路已经成功，再一层层往上加”。这样每加一步，你都知道问题是从哪一步开始出现的。', 'langrouter-for-translatepress' ); ?></p>

<h4><?php esc_html_e( '第一次配置时最容易忽略的事', 'langrouter-for-translatepress' ); ?></h4>
<ul style="list-style:disc;padding-left:18px;">
	<li><?php esc_html_e( '模型设置页负责填参数；路由设置页负责决定“走哪个引擎”。这两个页面不是一回事。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '默认引擎不是最高优先级。只要命中了文章类型规则或语言分配，就会优先走更高优先级的那一层。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '文章类型分配只对单篇内容页生效。不要用列表页、归档页或搜索结果页去判断文章类型规则有没有命中。', 'langrouter-for-translatepress' ); ?></li>
	<li><?php esc_html_e( '有时候不是引擎选错了，而是密钥、模型名、接口地址或目标语言支持本身有问题。', 'langrouter-for-translatepress' ); ?></li>
</ul>

<h4><?php esc_html_e( '什么叫“最小链路已经跑通”', 'langrouter-for-translatepress' ); ?></h4>
<p><?php esc_html_e( '对新人来说，最小链路跑通通常指的是：你只配置了一个引擎，把它设成默认引擎，不使用文章类型分配、语言分配和回退规则，然后做一次真实翻译测试，并且成功拿到有效译文。', 'langrouter-for-translatepress' ); ?></p>
<p><?php esc_html_e( '一旦这一步成功，就说明至少下面几件事是正常的：引擎配置本身可用、密钥或接口参数可用、插件能够实际发起请求、默认引擎能够承担翻译。这样后面再增加语言分配或回退规则时，排错范围就会小很多。', 'langrouter-for-translatepress' ); ?></p>

<pre style="background:#f6f7f7;padding:12px 16px;border:1px solid #dcdcde;overflow:auto;"><?php
echo esc_html__(
	'第一轮推荐配置：
默认引擎：openai
文章类型分配：不配置
语言分配：不配置
回退规则：不配置',
	'langrouter-for-translatepress'
);
?></pre>

<p class="description"><?php esc_html_e( '这是最适合新手的起步方式。先确认这一条最简单的链路能成功，再往上增加复杂规则。', 'langrouter-for-translatepress' ); ?></p>

<div style="background:#fff8e5;border-left:4px solid #dba617;padding:12px 16px;margin:16px 0;">
	<strong><?php esc_html_e( '遇到问题时不要先猜。', 'langrouter-for-translatepress' ); ?></strong>
	<?php
	printf(
			wp_kses_post(
					/* translators: %s: Help page URL for logs and troubleshooting. */
					__(
							'最省时间的做法是直接查看 <a href="%s">日志与排错</a>，先确认本次请求实际命中了哪个引擎、从哪一层规则命中，以及有没有进入回退链。',
							'langrouter-for-translatepress'
					)
			),
			esc_url( $logs_help_url )
	);
	?>
</div>

<p style="margin-top:20px;text-align:right;">
	<a class="button button-secondary" href="<?php echo esc_url( $openai_model_url ); ?>"><?php esc_html_e( '前往 OpenAI 设置', 'langrouter-for-translatepress' ); ?></a>
	<a class="button button-secondary" href="<?php echo esc_url( $deepl_model_url ); ?>" style="margin-left:8px;"><?php esc_html_e( '前往 DeepL 设置', 'langrouter-for-translatepress' ); ?></a>
	<a class="button button-primary" href="<?php echo esc_url( $translation_url ); ?>" target="_blank" rel="noopener noreferrer" style="margin-left:8px;"><?php esc_html_e( '前往路由设置', 'langrouter-for-translatepress' ); ?></a>
</p>
