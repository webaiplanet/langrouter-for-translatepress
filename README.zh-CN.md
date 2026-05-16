# langrouter-for-translatepress

🌐 **Languages**: [English](README.md) | [简体中文](README.zh-CN.md) 

=== LangRouter for TranslatePress ===
* 贡献者: WebAIPlanet
* 标签: translatepress, automatic translation, translation router, multilingual, deepl
* 最低 WordPress 版本: 6.4
* 测试到: 6.9
* 最低 PHP 版本: 7.4
* 稳定版本: 1.0.1
* 许可证: GPLv2 或更高版本
* 许可证链接: https://www.gnu.org/licenses/gpl-2.0.html

* 为 TranslatePress 自动翻译增加智能路由、回退控制和多引擎配置能力。

== 说明 ==

LangRouter for TranslatePress 为 TranslatePress 自动翻译增加了一层更实用的路由能力。

它不会把所有翻译请求都发送到同一个提供商，而是允许你根据当前内容类型、目标语言和回退策略，决定具体由哪个引擎处理翻译请求。这样可以在多语言 WordPress 网站中更好地平衡翻译质量、语言覆盖范围、运营成本以及失败处理方式。

本插件是面向 TranslatePress 的独立扩展，并非 TranslatePress 官方插件，也不代表 TranslatePress。

这个插件适合站长、开发者、服务商和内容团队使用，特别适合那些需要比“单一引擎”方案更高控制力的场景。

= 核心功能 =

* 设置一个默认引擎作为基础路由。
* 将文章、页面、商品和自定义文章类型等单篇内容路由到指定引擎。
* 按目标语言分配不同的翻译引擎。
* 当当前引擎无法继续时，按设定的回退规则继续处理。
* 在独立的模型设置页面中分别配置各个引擎。
* 在后台直接查询语言支持情况，再决定是否修改线上路由规则。
* 通过内置运行日志查看真实路由行为。
* 支持 DeepL 号池和火山方舟账号用量辅助功能。
* 支持接入兼容 OpenAI API 的网关与第三方兼容接口。

= 支持的翻译引擎 =

LangRouter 当前支持：

* 火山方舟 Volcengine Ark
* Qwen
* Hunyuan
* OpenAI
* DeepL
* 兼容 OpenAI API

= 路由工作方式 =

LangRouter 使用以下主要优先级顺序：

1. 文章类型路由
2. 语言分配
3. 默认引擎

如果某个单篇内容请求命中了文章类型规则，那么该引擎就会成为主路由。
如果没有命中文章类型规则，LangRouter 会继续检查语言分配规则。
如果语言规则也没有命中，则使用默认引擎。

当当前引擎无法继续时，LangRouter 会按你配置的回退方式继续处理。
根据设置不同，请求可以立即停止、直接跳到默认引擎，或者继续执行全局回退链路。

= 文章类型路由 =

当不同内容类型需要不同翻译策略时，文章类型路由会非常有用。
例如：

* 将商品内容路由到更适合术语一致性的引擎；
* 将编辑类内容路由到更适合语气和流畅度的引擎；
* 将某些自定义文章类型固定交给专用提供商处理。

文章类型路由只在单篇内容上下文中生效，例如单篇文章、单个页面、单个商品或单个自定义文章类型内容。
归档页、分类页、搜索结果页和其他非单篇视图不会使用文章类型路由。

= 语言分配与回退规则 =

语言分配用于为目标语言选择主引擎。
回退规则只会在当前主引擎无法继续时才生效，用来决定下一步尝试哪个引擎。

这种分离方式可以让你的路由策略更清晰：

* 先为目标语言选择最适合的主引擎；
* 再为失败场景定义另一个回退引擎；
* 最后把默认引擎作为基础方案或最终兜底。

= 独立的模型设置 =

路由规则和引擎凭证是分开管理的。
这样更方便操作，也更利于后期维护。

在模型设置区域中，你可以配置例如：

* API 密钥和密钥对
* 模型名称
* base URL 和兼容接口地址
* 请求超时
* 额外请求 JSON
* 各引擎的备注与运行参数
* 支持引擎的账号池相关设置

= 日志与排错 =

LangRouter 内置了运行日志查看器。
你可以启用文件日志，在后台查看最近日志内容，也可以下载日志文件做进一步排查。

这对以下场景非常有帮助：

* 确认最终选择了哪个引擎；
* 确认是否命中了文章类型路由；
* 确认是否由语言分配接管请求；
* 查看为什么触发了回退决策；
* 确认某个提供商是否真的进入了执行链路。

= 典型使用场景 =

* 大多数流量走一个主引擎，少数语言单独分流。
* 将商品、指南和普通文章分别路由到不同引擎。
* 对关键内容类型使用更严格的回退策略。
* 在同一套流程里同时使用 DeepL 号池、火山方舟和兼容 OpenAI API 服务。
* 在一个统一界面中比较多个提供商，并结合日志观察实际效果。

== 安装 ==

1. 将插件上传到 `/wp-content/plugins/` 目录，或者通过 WordPress 后台上传 ZIP 安装。
2. 安装并启用 `TranslatePress Multilingual`。
3. 启用 `LangRouter for TranslatePress`。
4. 打开 TranslatePress 自动翻译设置，并将引擎选择为 `LangRouter Smart Translation`。
5. 在路由设置中设置默认引擎。
6. 根据需要添加文章类型路由、语言分配和回退规则。
7. 打开模型设置页面，配置你要使用的各个引擎。
8. 如有需要，可启用文件日志并测试实际路由行为。

== 外部服务 ==

第三方服务说明

本插件支持将自动翻译请求发送到你所选择的第三方翻译或大模型服务。

当你启用某个翻译引擎并发起翻译请求时，插件会将待翻译内容以及完成该请求所需的相关参数发送到对应的第三方服务。
部分服务在你主动使用相关功能时，也可能会发送查询支持语言、账号信息或用量信息所必需的数据到对应服务。

你可以自行决定是否启用某个第三方服务。未启用的服务不会被本插件用于处理翻译请求。

1. OpenAI API

服务名称：OpenAI API

服务地址：
https://api.openai.com/v1/chat/completions

用途：通过 OpenAI 模型完成文本翻译

发送的数据：待翻译文本、源语言和目标语言相关提示信息，以及翻译请求所需的模型参数

发送时机：仅当你启用了 OpenAI 引擎，并且插件实际使用 OpenAI 执行翻译请求时

服务条款：
https://openai.com/policies/services-agreement/

隐私政策：
https://openai.com/policies/row-privacy-policy/


2. DeepL API

服务名称：DeepL API

服务地址：
https://api.deepl.com/v2
https://api-free.deepl.com/v2

用途：通过 DeepL 完成文本翻译，并在需要时查询 DeepL 支持的目标语言

发送的数据：
- 发起翻译时：待翻译文本、源语言代码、目标语言代码，以及相关翻译参数
- 查询支持语言时：请求目标语言列表所需的接口参数

发送时机：
- 仅当你启用了 DeepL 引擎并发起翻译请求时
- 或当插件需要查询 DeepL 支持语言时

服务条款：
https://www.deepl.com/en/pro-license

隐私政策：
https://www.deepl.com/en/privacy.html


3. 腾讯云中国 Hunyuan

服务名称：Tencent Cloud Hunyuan API

服务地址：
https://hunyuan.tencentcloudapi.com/

用途：通过腾讯云 Hunyuan 模型完成文本翻译

发送的数据：待翻译文本、源语言代码、目标语言代码，以及翻译请求所需的模型参数

发送时机：仅当你启用了 Hunyuan 引擎，且选择使用腾讯云中国站对应接口进行翻译时

服务条款：
https://cloud.tencent.com/document/product/301/97822

隐私政策：
https://cloud.tencent.com/document/product/301/11470


4. 腾讯云国际 Hunyuan

服务名称：Tencent Cloud Hunyuan API

服务地址：
https://hunyuan.ai.intl.tencentcloudapi.com/

用途：通过腾讯云国际站 Hunyuan 模型完成文本翻译

发送的数据：待翻译文本、源语言代码、目标语言代码，以及翻译请求所需的模型参数

发送时机：仅当你启用了 Hunyuan 引擎，且选择使用腾讯云国际站对应接口进行翻译时

服务条款：
https://www.tencentcloud.com/document/product/1284/75295

隐私政策：
https://www.tencentcloud.com/document/product/1284/75293


5. SiliconFlow 中国（用于 Hunyuan-MT-7B 兼容接口）

服务名称：SiliconFlow API

服务地址：
https://api.siliconflow.cn/v1/chat/completions

用途：当你使用 Hunyuan-MT-7B 的兼容接口模式时，用于完成文本翻译

发送的数据：待翻译文本、源语言和目标语言相关提示信息，以及翻译请求所需的模型参数

发送时机：仅当你启用了对应引擎，并使用该兼容接口模式发起翻译时

服务条款：
https://docs.siliconflow.cn/cn/legals/terms-of-service

隐私政策：
https://docs.siliconflow.cn/cn/legals/privacy-policy


6. 火山方舟 Volcengine Ark

服务名称：Volcengine Ark

服务地址：
翻译接口：https://ark.cn-beijing.volces.com/api/v3
相关服务域名：https://ark.cn-beijing.volcengineapi.com/

用途：
- 通过火山方舟模型完成文本翻译
- 在你主动使用相关功能时查询账号用量信息

发送的数据：
- 发起翻译时：待翻译文本，以及翻译请求所需的模型参数
- 查询用量时：与用量查询相关的账号标识、查询日期范围及请求签名信息

发送时机：
- 仅当你启用了 Volcengine Ark 引擎并发起翻译请求时
- 或当你主动使用插件中的相关用量查询功能时

服务条款：
https://www.volcengine.com/docs/6456/70590

隐私政策：
https://www.volcengine.com/docs/6256/64902


7. 阿里云中国 Qwen

服务名称：Alibaba Cloud DashScope / Qwen Compatible API

服务地址：
https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions

用途：通过阿里云中国站 Qwen 兼容模型完成文本翻译

发送的数据：待翻译文本、源语言代码、目标语言代码，以及翻译请求所需的模型参数

发送时机：仅当你启用了 Qwen 引擎，并发起翻译请求时

服务条款：
https://terms.alicdn.com/legal-agreement/terms/common_platform_service/20230728213935489/20230728213935489.html

隐私政策：
https://terms.aliyun.com/legal-agreement/terms/suit_bu1_ali_cloud/suit_bu1_ali_cloud202107091605_49213.html


8. 阿里云国际 Qwen

服务名称：Alibaba Cloud DashScope / Qwen Compatible API

服务地址：
https://dashscope-intl.aliyuncs.com/compatible-mode/v1/chat/completions
https://dashscope-us.aliyuncs.com/compatible-mode/v1/chat/completions

用途：通过阿里云国际站 Qwen 兼容模型完成文本翻译

发送的数据：待翻译文本、源语言代码、目标语言代码，以及翻译请求所需的模型参数

发送时机：仅当你启用了对应国际区域接口并发起翻译请求时

服务条款：
https://www.alibabacloud.com/help/en/legal/latest/alibaba-cloud-international-website-terms-of-use-alibaba-cloud-international-website-terms-of-use

隐私政策：
https://www.alibabacloud.com/help/en/legal/latest/alibaba-cloud-international-website-privacy-policy


9. 兼容 OpenAI API 的第三方服务

服务名称：用户自行配置的兼容 OpenAI API 第三方服务

服务地址：取决于你在插件设置中填写的兼容 OpenAI API 地址

用途：通过实现 OpenAI 兼容协议的第三方模型服务、企业网关、代理服务或自建兼容接口完成文本翻译

发送的数据：待翻译文本、源语言和目标语言相关提示信息、你配置的模型名称，以及翻译请求所需的请求头和请求参数

发送时机：仅当你启用了“兼容 OpenAI API”引擎，并发起翻译请求时

服务条款：取决于你自行选择的服务提供商

隐私政策：取决于你自行选择的服务提供商

说明：使用前请你自行确认该服务的服务条款、隐私政策及数据处理方式。


在你主动选择并配置某个支持的引擎之前，本插件不要求你接入任何外部服务。

== 常见问题 ==

= 这个插件会替代 TranslatePress 吗？ =

不会。LangRouter for TranslatePress 是 TranslatePress 自动翻译的扩展插件，它只是增加了一层路由能力，并不会替代 TranslatePress 本身。

= 文章类型路由什么时候生效？ =

文章类型路由只在单篇内容上下文中生效，例如单篇文章、单个页面、单个商品或单个自定义文章类型内容。它不会用于归档页、分类列表页、搜索结果页或其他非单篇视图。

= 如果文章类型路由失败会怎样？ =

这取决于该规则配置的回退模式。你可以让翻译立即停止、直接跳到默认引擎，或者继续执行全局回退链路。

= 语言分配和回退规则是一回事吗？ =

不是。语言分配用于为目标语言选择主引擎；回退规则只会在当前主引擎无法继续时才会使用。

= 在哪里配置 API 密钥和模型？ =

请使用独立的模型设置页面。路由规则与引擎凭证、模型参数是分开配置的。

= 可以使用兼容 OpenAI API 的服务吗？ =

可以。插件支持兼容 OpenAI API 的接口，适合第三方网关、企业代理或自建兼容服务。

= 我能查看实际命中的路由吗？ =

可以。LangRouter 提供内置运行文件日志，你可以查看所选引擎、回退决策以及执行结果。

== 截图 ==

1. TranslatePress 自动翻译中的路由设置页面，包括默认引擎选择器。
2. 带有单条规则回退行为的文章类型路由设置，可用于文章、页面、商品和自定义文章类型。
3. 语言分配、回退规则、语言支持查询，以及与 TranslatePress 测试按钮相关的联动界面。
4. 日志页面，包含内置运行日志查看器和日志文件管理操作。
5. 火山方舟设置页面，包含账号池输入、用量概览和诊断信息。
6. Qwen 设置页面，包含 API Key、模型、区域、自定义接口、超时和额外请求 JSON。
7. Hunyuan 设置页面，支持腾讯云官方翻译模型和兼容第三方模型端点。
8. OpenAI 设置页面，包含官方模型选择、自定义模型名、自定义接口、超时和请求 JSON。
9. DeepL 设置页面，包含号池管理、冷却时间控制和密钥运行状态。
10. 兼容 OpenAI API 设置页面，适用于第三方网关和兼容服务，并支持额外请求头与请求 JSON。

<img width="1925" height="2188" alt="screenshot-1" src="https://github.com/user-attachments/assets/7b9b3fa0-8a99-435f-a3cd-ec0ba8572e2b" />
TranslatePress 自动翻译中的路由设置页面，包括默认引擎选择器。

<img width="1927" height="2290" alt="screenshot-2" src="https://github.com/user-attachments/assets/b2e88019-892d-4657-a9dd-224293937cdd" />
带有单条规则回退行为的文章类型路由设置，可用于文章、页面、商品和自定义文章类型。

<img width="1930" height="3092" alt="screenshot-3" src="https://github.com/user-attachments/assets/0dfbd645-37fa-4daf-9699-577d001cc432" />
语言分配、回退规则、语言支持查询，以及与 TranslatePress 测试按钮相关的联动界面。

<img width="1933" height="1619" alt="screenshot-4" src="https://github.com/user-attachments/assets/3bd84a07-d1e4-458e-b5dc-d3ae0c84e123" />
日志页面，包含内置运行日志查看器和日志文件管理操作。

<img width="1938" height="1781" alt="screenshot-5" src="https://github.com/user-attachments/assets/ac20787e-b9d2-4161-89b6-26c3767362f7" />
火山方舟设置页面，包含账号池输入、用量概览和诊断信息。

<img width="1936" height="1627" alt="screenshot-6" src="https://github.com/user-attachments/assets/7381b12e-0370-4f56-83a9-91674ad21549" />
Qwen 设置页面，包含 API Key、模型、区域、自定义接口、超时和额外请求 JSON。

<img width="1935" height="1962" alt="screenshot-7" src="https://github.com/user-attachments/assets/5ece9c36-3417-47a6-8cc5-d5d986316539" />
Hunyuan 设置页面，支持腾讯云官方翻译模型和兼容第三方模型端点。

<img width="1938" height="1594" alt="screenshot-8" src="https://github.com/user-attachments/assets/5c6a3d8e-0df5-4115-bb45-53aef1bc8f69" />
OpenAI 设置页面，包含官方模型选择、自定义模型名、自定义接口、超时和请求 JSON。

<img width="1934" height="2385" alt="screenshot-9" src="https://github.com/user-attachments/assets/ab8986bd-f6c7-4e73-8411-f8704f07ea38" />
DeepL 设置页面，包含号池管理、冷却时间控制和密钥运行状态。

<img width="1935" height="2679" alt="screenshot-10" src="https://github.com/user-attachments/assets/9dcab596-7cfd-46b2-9c51-06231d40dd42" />
兼容 OpenAI API 设置页面，适用于第三方网关和兼容服务，并支持额外请求头与请求 JSON。

== 更新日志 ==

= 1.1.3 =

* 首次发布。
* 在 TranslatePress 路由设置中新增全局默认并发。
* 为 OpenAI、兼容 OpenAI API、Qwen、Hunyuan、火山方舟新增子引擎并发覆盖设置。
* 在帮助页补充了低配、中配、高配服务器的并发示例和说明。
* 优化运行日志删除与空状态显示，清空或 0 字节日志文件不再被当作可查看日志。
* 修正火山方舟计费状态显示，欠费时会优先显示欠费状态。
