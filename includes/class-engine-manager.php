<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPRE_Engine_Manager {
    protected $tp_settings;
    protected $router_settings;
    protected $logger;
    protected $instances = [];

    public function __construct( array $tp_settings, TPRE_Logger $logger ) {
        $this->tp_settings     = $tp_settings;
        $this->router_settings = TPRE_Admin_Settings::get_settings();
        $this->logger          = $logger;
    }

    public function get_engine( $slug ) {
        if ( isset( $this->instances[ $slug ] ) ) {
            return $this->instances[ $slug ];
        }

        $models = isset( $this->router_settings['models'] ) && is_array( $this->router_settings['models'] ) ? $this->router_settings['models'] : [];
        if ( isset( $models[ $slug ]['enabled'] ) && empty( $models[ $slug ]['enabled'] ) ) {
            $this->logger->debug( __( '子引擎已禁用，跳过实例化', 'langrouter-for-translatepress' ), [ 'engine' => $slug ] );
            $this->instances[ $slug ] = null;
            return null;
        }

        $this->logger->debug( __( '实例化子引擎', 'langrouter-for-translatepress' ), [ 'engine' => $slug ] );

        $engine = TPRE_Engine_Registry::create_engine( $slug, $this->tp_settings, $this->router_settings, $this->logger );
        if ( null === $engine ) {
            $this->logger->debug( __( '未找到子引擎工厂，跳过实例化', 'langrouter-for-translatepress' ), [ 'engine' => $slug ] );
        }

        $this->instances[ $slug ] = $engine;

        return $this->instances[ $slug ];
    }
}
