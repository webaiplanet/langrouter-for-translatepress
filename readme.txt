=== LangRouter for TranslatePress ===
Contributors: WebAIPlanet
Tags: translatepress, multilingual, translation, automatic translation, deepl
Requires at least: 6.4
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add routing, fallback control, and engine-specific settings to TranslatePress automatic translation.

== Description ==

LangRouter for TranslatePress adds routing and fallback control to TranslatePress automatic translation.

Instead of sending every request to a single provider, it lets you choose which translation engine should handle a request based on the current content type, target language, and fallback policy. This gives you more control over translation flow on multilingual WordPress sites.

This plugin is an independent extension for TranslatePress. It is not affiliated with, endorsed by, or maintained by TranslatePress.

It is intended for site owners, developers, agencies, and content teams that need more control than a single-engine setup can provide.

= Key features =

* Set one default engine as the baseline route.
* Route singular content types such as posts, pages, products, and custom post types to a preferred engine.
* Assign target languages to different engines.
* Define fallback behavior when the current engine cannot continue.
* Configure each engine separately in a dedicated settings page.
* Query language support from the admin area before changing live routing rules.
* Inspect routing behavior through built-in runtime logs.
* Support DeepL multi-key configuration and Volcengine Ark usage tools.
* Connect OpenAI-compatible third-party services and custom compatible endpoints.

= Supported translation engines =

LangRouter currently supports:

* Volcengine Ark
* Qwen
* Hunyuan
* OpenAI
* DeepL
* Compatible OpenAI API

= How routing works =

LangRouter uses the following priority order:

1. Post type route
2. Language assignment
3. Default engine

If a singular content request matches a post type rule, that engine becomes the primary route.
If no post type rule matches, LangRouter checks the language assignment rules.
If no language rule matches, the plugin uses the default engine.

If the current engine cannot continue, LangRouter applies the configured fallback behavior.
Depending on your settings, the request can stop immediately, jump directly to the default engine, or continue through the global fallback chain.

= Post type routing =

Post type routing is useful when different content types need different translation behavior.

For example, you can:

* route products to one engine for terminology consistency;
* route editorial content to another engine for tone and fluency;
* keep specific custom post types on a dedicated provider.

Post type routing applies only to singular content contexts, such as a single post, page, product, or custom post type entry.
Archive pages, taxonomy pages, search results, and other non-singular views do not use post type routing.

= Language assignment and fallback rules =

Language assignment selects the primary engine for a target language.
Fallback rules define what to try next only after the current primary engine cannot continue.

This separation helps you build a clearer routing strategy:

* choose the preferred engine for a target language;
* define a different engine as the fallback;
* keep the default engine as the final baseline or safety net.

= Dedicated engine settings =

Routing rules and engine credentials are managed separately.
This makes the plugin easier to operate and maintain.

In the engine settings area, you can configure items such as:

* API keys and secrets
* model names
* base URLs and compatible endpoints
* request timeouts
* additional request JSON
* per-engine notes and operational options
* multi-key or account-related settings for supported engines

= Logging and troubleshooting =

LangRouter includes a built-in runtime log viewer.
You can enable file logging, view recent log content inside the admin area, and download log files for debugging.

This is useful when you want to confirm:

* which engine was selected;
* whether a post type route was matched;
* whether language assignment handled the request;
* why a fallback decision was triggered;
* whether a provider entered the execution chain successfully.

= Typical use cases =

* Use one engine for most traffic and reroute selected languages.
* Route products, guides, and regular posts to different engines.
* Keep important content on stricter fallback behavior.
* Use DeepL, Volcengine Ark, and OpenAI-compatible services in one workflow.
* Compare providers while keeping routing and logging in one place.

== Installation ==

1. Upload the plugin to the `/wp-content/plugins/` directory, or install the ZIP through the WordPress admin.
2. Install and activate `TranslatePress Multilingual`.
3. Activate `LangRouter for TranslatePress`.
4. Open TranslatePress automatic translation settings and select `LangRouter Smart Translation` as the engine.
5. Set a default engine in the router settings.
6. Add post type routes, language assignments, and fallback rules as needed.
7. Open the engine settings page and configure the engines you want to use.
8. Optionally enable file logging and test your routing behavior.

== External services ==

This plugin can connect to third-party translation and AI services. These services are optional and are used only for translation, supported-language checks, usage checks, or diagnostic actions requested by an administrator.

No request is sent to a third-party service unless an administrator configures that engine and triggers translation or a related engine-specific action, such as a language support query, usage query, or diagnostic request.

The plugin may connect to the following services:

* OpenAI API. This service is provided by OpenAI and is used to translate content through OpenAI models.
  Data sent: text selected for translation, source and target language instructions, model settings, request parameters, and related request metadata required to complete the request.
  Sent only when this engine is configured and used by an administrator.
  Terms of Service: https://openai.com/policies/services-agreement/
  Privacy Policy: https://openai.com/policies/row-privacy-policy/

* DeepL API. This service is provided by DeepL and is used to translate content through DeepL and, when needed, query supported languages.
  Data sent: text selected for translation, source language, target language, translation parameters, or request parameters required for supported-language queries.
  Sent only when this engine is configured and used by an administrator.
  Terms and Conditions: https://www.deepl.com/en/pro-license
  Privacy Policy: https://www.deepl.com/en/privacy.html

* Tencent Cloud Hunyuan (China). This service is provided by Tencent Cloud and is used to translate content through Tencent Cloud Hunyuan endpoints configured for the China service.
  Data sent: text selected for translation, source language, target language, model settings, request parameters, and related metadata required to complete the request.
  Sent only when this engine is configured for Tencent Cloud China and used by an administrator.
  Terms of Service: https://cloud.tencent.com/document/product/301/97822
  Privacy Policy: https://cloud.tencent.com/document/product/301/11470

* Tencent Cloud Hunyuan (International). This service is provided by Tencent Cloud International and is used to translate content through Tencent Cloud Hunyuan international endpoints.
  Data sent: text selected for translation, source language, target language, model settings, request parameters, and related metadata required to complete the request.
  Sent only when this engine is configured for Tencent Cloud international endpoints and used by an administrator.
  Terms of Service: https://www.tencentcloud.com/document/product/1284/75295
  Privacy Policy: https://www.tencentcloud.com/document/product/1284/75293

* SiliconFlow API. This service is provided by SiliconFlow and is used when Hunyuan-compatible translation is configured through the SiliconFlow compatible endpoint.
  Data sent: text selected for translation, source and target language instructions, model settings, request parameters, and related metadata required to complete the request.
  Sent only when this compatible endpoint is configured and used by an administrator.
  Terms of Service: https://docs.siliconflow.cn/cn/legals/terms-of-service
  Privacy Policy: https://docs.siliconflow.cn/cn/legals/privacy-policy

* Volcengine Ark. This service is provided by Volcengine and is used to translate content through Volcengine models and, when requested by the administrator, perform usage-related queries.
  Data sent: text selected for translation, model settings, request parameters, and, for usage-related requests, the account identifiers, date ranges, and signed request data required by the service.
  Sent only when this engine is configured and used by an administrator, or when the administrator manually triggers a usage-related request.
  Terms of Service: https://www.volcengine.com/docs/6456/70590
  Privacy Policy: https://www.volcengine.com/docs/6256/64902

* Alibaba Cloud DashScope / Qwen (China). This service is provided by Alibaba Cloud and is used to translate content through Qwen-compatible endpoints configured for Alibaba Cloud China.
  Data sent: text selected for translation, source language, target language, model settings, request parameters, and related metadata required to complete the request.
  Sent only when this engine is configured for Alibaba Cloud China and used by an administrator.
  Terms of Service: https://terms.alicdn.com/legal-agreement/terms/common_platform_service/20230728213935489/20230728213935489.html
  Privacy Policy: https://terms.aliyun.com/legal-agreement/terms/suit_bu1_ali_cloud/suit_bu1_ali_cloud202107091605_49213.html

* Alibaba Cloud DashScope / Qwen (International). This service is provided by Alibaba Cloud International and is used to translate content through international Qwen-compatible endpoints configured by the administrator.
  Data sent: text selected for translation, source language, target language, model settings, request parameters, and related metadata required to complete the request.
  Sent only when this engine is configured for international Alibaba Cloud endpoints and used by an administrator.
  Terms of Service: https://www.alibabacloud.com/help/en/legal/latest/alibaba-cloud-international-website-terms-of-use-alibaba-cloud-international-website-terms-of-use
  Privacy Policy: https://www.alibabacloud.com/help/en/legal/latest/alibaba-cloud-international-website-privacy-policy

* Compatible OpenAI API endpoints. These services are provided by the third-party provider selected by the administrator and are used when you configure a third-party service that implements an OpenAI-compatible API.
  Data sent: text selected for translation, source and target language instructions, configured model name, request headers, request parameters, and related request metadata required to complete the request.
  Sent only when a compatible third-party service is configured and used by an administrator.
  The provider, terms, privacy policy, and data handling depend on the service you choose. Review that provider before use.

This plugin does not require any external service unless you choose to configure and use a supported engine.

== Frequently Asked Questions ==

= Does this plugin replace TranslatePress? =

No. LangRouter for TranslatePress is an extension for TranslatePress automatic translation. It adds a routing layer and does not replace TranslatePress itself.

= When does post type routing apply? =

Post type routing applies only to singular content contexts, such as a single post, page, product, or custom post type entry. It does not apply to archive pages, taxonomy listings, search results, or other non-singular views.

= What happens if a post type route fails? =

That depends on the fallback mode configured for the matched rule. You can stop translation immediately, jump directly to the default engine, or continue through the global fallback chain.

= Are language assignment and fallback rules the same thing? =

No. Language assignment selects the primary engine for a target language. Fallback rules are used only after the current primary engine cannot continue.

= Where do I configure API keys and models? =

Use the dedicated engine settings page. Routing rules are configured separately from engine credentials and model parameters.

= Can I use OpenAI-compatible services? =

Yes. The plugin supports compatible OpenAI API endpoints, which is useful for third-party gateways, enterprise proxies, or self-hosted compatible services.

= Can I inspect actual routing behavior? =

Yes. LangRouter provides built-in runtime file logging so you can inspect selected engines, fallback decisions, and execution results.

== Screenshots ==

1. Router settings inside TranslatePress automatic translation, including the default engine selector.
2. Post type routing with per-rule fallback behavior for posts, pages, products, and custom post types.
3. Language assignment, fallback rules, language support query, and TranslatePress test integration.
4. Logs page with the built-in runtime log viewer and log file management actions.
5. Volcengine Ark settings with account input, usage overview, and diagnostics.
6. Qwen settings with API key, model, region, custom API, timeout, and additional request JSON.
7. Hunyuan settings for Tencent Cloud endpoints and compatible third-party model endpoints.
8. OpenAI settings with model selection, custom model name, custom API, timeout, and request JSON.
9. DeepL settings with multi-key management, cooldown controls, and key status.
10. Compatible OpenAI API settings for third-party gateways and compatible providers, including headers and request JSON.

== Changelog ==

= 1.1.3 =

* First release.
* Added global default concurrency in the TranslatePress router settings.
* Added per-engine concurrency overrides for OpenAI, Compatible OpenAI API, Qwen, Hunyuan, and Volcengine Ark.
* Added help page examples and guidance for low, medium, and high server configurations.
* Improved runtime log deletion and empty-state handling so cleared or zero-byte log files no longer appear as active logs.
* Improved Volcengine Ark status display so overdue billing states are shown correctly.
