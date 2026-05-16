<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Static gettext registrations for log strings so Loco Translate can scan them.
 * Runtime log translation is handled separately by tpre_log_translate()/tpre_log_translatef().
 */
/* translators: Placeholder tokens in this log message are runtime values such as engine names, language codes, or fallback engine slugs. */
__( '%1$s不支持 %2$s 语言，跳过主调用并准备回退。', 'langrouter-for-translatepress' );
/* translators: Placeholder tokens in this log message are runtime values such as engine names, language codes, or fallback engine slugs. */
__( '%1$s不支持源语言 %2$s，跳过主调用并准备回退。', 'langrouter-for-translatepress' );
/* translators: Placeholder tokens in this log message are runtime values such as engine names, language codes, or fallback engine slugs. */
__( '%s当前不可用，跳过主调用并准备回退。', 'langrouter-for-translatepress' );
/* translators: Placeholder tokens in this log message are runtime values such as engine names, language codes, or fallback engine slugs. */
__( '未找到主引擎 %s，跳过主调用并准备回退。', 'langrouter-for-translatepress' );
/* translators: Placeholder tokens in this log message are runtime values such as engine names, language codes, or fallback engine slugs. */
__( '%s预检查未通过，跳过主调用并准备回退。', 'langrouter-for-translatepress' );
/* translators: Placeholder tokens in this log message are runtime values such as engine names, language codes, or fallback engine slugs. */
__( '回退引擎 %1$s 不支持 %2$s 语言，无法继续回退。', 'langrouter-for-translatepress' );
/* translators: Placeholder tokens in this log message are runtime values such as engine names, language codes, or fallback engine slugs. */
__( '回退引擎 %1$s 不支持源语言 %2$s，无法继续回退。', 'langrouter-for-translatepress' );
/* translators: Placeholder tokens in this log message are runtime values such as engine names, language codes, or fallback engine slugs. */
__( '回退引擎 %s 当前不可用，无法继续回退。', 'langrouter-for-translatepress' );
/* translators: Placeholder tokens in this log message are runtime values such as engine names, language codes, or fallback engine slugs. */
__( '未找到回退引擎 %s，无法继续回退。', 'langrouter-for-translatepress' );
/* translators: Placeholder tokens in this log message are runtime values such as engine names, language codes, or fallback engine slugs. */
__( '回退引擎 %s 预检查未通过，无法继续回退。', 'langrouter-for-translatepress' );
/* translators: Placeholder tokens in this log message are runtime values such as engine names, language codes, or fallback engine slugs. */
__( '火山方舟不支持 %s 语言，直接跳过主调用。', 'langrouter-for-translatepress' );
/* translators: Placeholder tokens in this log message are runtime values such as engine names, language codes, or fallback engine slugs. */
__( 'Qwen不支持 %s 语言，直接跳过主调用。', 'langrouter-for-translatepress' );
/* translators: Placeholder tokens in this log message are runtime values such as engine names, language codes, or fallback engine slugs. */
__( 'DeepL不支持 %s 语言，直接跳过主调用。', 'langrouter-for-translatepress' );
/* translators: Placeholder tokens in this log message are runtime values such as engine names, language codes, or fallback engine slugs. */
__( '回退引擎 %1$s 未通过预检查，继续尝试下一个候选 %2$s。', 'langrouter-for-translatepress' );
/* translators: Placeholder tokens in this log message are runtime values such as engine names, language codes, or fallback engine slugs. */
__( '回退到默认引擎 %s，开始批量翻译', 'langrouter-for-translatepress' );
/* translators: Placeholder tokens in this log message are runtime values such as engine names, language codes, or fallback engine slugs. */
__( '回退引擎 %1$s 返回空结果，继续尝试下一个候选 %2$s。', 'langrouter-for-translatepress' );
