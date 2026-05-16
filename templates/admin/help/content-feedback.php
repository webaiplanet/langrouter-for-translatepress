<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap langrouter-feedback-wrap">
	<div class="langrouter-feedback-card">
		<h2 class="langrouter-feedback-title"><?php esc_html_e( '反馈和建议', 'langrouter-for-translatepress' ); ?></h2>
		<p class="langrouter-feedback-desc">
			<?php esc_html_e( '感谢你使用 LangRouter for TranslatePress。若在使用过程中遇到问题、发现 Bug，或有功能建议，欢迎通过下面的方式与我们联系。', 'langrouter-for-translatepress' ); ?>
		</p>

		<div class="langrouter-feedback-links">
			<a class="langrouter-feedback-link-item" href="<?php echo esc_url( 'https://github.com/iluozhen/langrouter-for-translatepress' ); ?>" target="_blank" rel="noopener noreferrer">
				<span class="langrouter-feedback-link-title"><?php esc_html_e( 'GitHub Issues', 'langrouter-for-translatepress' ); ?></span>
				<span class="langrouter-feedback-link-desc"><?php esc_html_e( '提交 Bug、功能建议，或查看已知问题与更新进展。', 'langrouter-for-translatepress' ); ?></span>
			</a>
			<a class="langrouter-feedback-link-item" href="<?php echo esc_url( 'https://wordpress.org/support/plugin/langrouter-for-translatepress/' ); ?>" target="_blank" rel="noopener noreferrer">
				<span class="langrouter-feedback-link-title"><?php esc_html_e( 'WordPress 社区支持', 'langrouter-for-translatepress' ); ?></span>
				<span class="langrouter-feedback-link-desc"><?php esc_html_e( '在 WordPress 社区提问、交流使用经验，获取更多帮助。', 'langrouter-for-translatepress' ); ?></span>
			</a>
		</div>
	</div>

	<div class="langrouter-feedback-card">
		<h3 class="langrouter-feedback-section-title"><?php esc_html_e( '关注作者', 'langrouter-for-translatepress' ); ?></h3>
		<p class="langrouter-feedback-desc">
			<?php esc_html_e( '欢迎扫码关注，获取插件更新、使用技巧和后续新功能信息。', 'langrouter-for-translatepress' ); ?>
		</p>

		<div class="langrouter-feedback-qr-grid">
			<div class="langrouter-feedback-qr-item">
				<img src="<?php echo esc_url( TPRE_PLUGIN_URL . 'assets/images/follow-wechat.jpg' ); ?>" alt="<?php esc_attr_e( '微信公众号二维码', 'langrouter-for-translatepress' ); ?>">
				<div class="langrouter-feedback-qr-title"><?php esc_html_e( '微信公众号', 'langrouter-for-translatepress' ); ?></div>
				<div class="langrouter-feedback-qr-desc"><?php esc_html_e( '扫码关注公众号', 'langrouter-for-translatepress' ); ?></div>
			</div>
		</div>
	</div>

	<div class="langrouter-feedback-card">
		<h3 class="langrouter-feedback-section-title"><?php esc_html_e( '支持作者', 'langrouter-for-translatepress' ); ?></h3>
		<p class="langrouter-feedback-desc">
			<?php esc_html_e( '欢迎通过以下方式支持作者，帮助插件持续改进。', 'langrouter-for-translatepress' ); ?>
		</p>

		<div class="langrouter-feedback-qr-grid">
			<div class="langrouter-feedback-qr-item">
				<img src="<?php echo esc_url( TPRE_PLUGIN_URL . 'assets/images/donate-wechat.jpg' ); ?>" alt="<?php esc_attr_e( '微信打赏二维码', 'langrouter-for-translatepress' ); ?>">
				<div class="langrouter-feedback-qr-title"><?php esc_html_e( '微信打赏', 'langrouter-for-translatepress' ); ?></div>
				<div class="langrouter-feedback-qr-desc"><?php esc_html_e( '使用微信扫一扫支持作者', 'langrouter-for-translatepress' ); ?></div>
			</div>
			<div class="langrouter-feedback-qr-item">
				<img src="<?php echo esc_url( TPRE_PLUGIN_URL . 'assets/images/donate-alipay.jpg' ); ?>" alt="<?php esc_attr_e( '支付宝打赏二维码', 'langrouter-for-translatepress' ); ?>">
				<div class="langrouter-feedback-qr-title"><?php esc_html_e( '支付宝打赏', 'langrouter-for-translatepress' ); ?></div>
				<div class="langrouter-feedback-qr-desc"><?php esc_html_e( '使用支付宝扫一扫支持作者', 'langrouter-for-translatepress' ); ?></div>
			</div>
			<div class="langrouter-feedback-qr-item">
				<a href="<?php echo esc_url( 'https://paypal.me/iluozhen' ); ?>" target="_blank" rel="noopener noreferrer">
					<img src="<?php echo esc_url( TPRE_PLUGIN_URL . 'assets/images/donate-paypal.png' ); ?>" alt="<?php echo esc_html( 'PayPal.Me' ); ?>" class="langrouter-feedback-donate-paypal">
					<div class="langrouter-feedback-qr-title"><?php echo esc_html( 'PayPal.Me' ); ?></div>
					<span class="button button-primary paypal"><?php esc_html_e( '通过 PayPal 支持作者', 'langrouter-for-translatepress' ); ?></span>
				</a>
			</div>
		</div>

		<div class="langrouter-feedback-note">
			<?php esc_html_e( 'LangRouter for TranslatePress 是免费使用的。若它恰好帮到了你，欢迎自愿支持作者。每一份支持，都会转化为后续维护与更新的动力。', 'langrouter-for-translatepress' ); ?>
		</div>
	</div>
</div>
