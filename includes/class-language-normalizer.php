<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPRE_Language_Normalizer {
    public function normalize( $language_code ) {
        $language_code = trim( (string) $language_code );
        $language_code = str_replace( '-', '_', $language_code );

        $aliases = [
            'zh_Hant'    => 'zh_Hant',
            'zh_hant'    => 'zh_Hant',
            'zh_Hant_TW' => 'zh_TW',
            'zh_hant_tw' => 'zh_TW',
            'zh_Hant_HK' => 'zh_HK',
            'zh_hant_hk' => 'zh_HK',
            'zh_Hant_MO' => 'zh_HK',
            'zh_hant_mo' => 'zh_HK',
            'zh_TW'      => 'zh_TW',
            'zh_tw'      => 'zh_TW',
            'zh_HK'      => 'zh_HK',
            'zh_hk'      => 'zh_HK',
            'zh_MO'      => 'zh_HK',
            'zh_mo'      => 'zh_HK',
            'yue_HK'     => 'yue',
            'yue_hk'     => 'yue',
        ];

        return $aliases[ $language_code ] ?? $language_code;
    }
}
