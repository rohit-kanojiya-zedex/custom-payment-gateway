<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Cpg' ) ) {
    class Cpg {
        public static ?Cpg $instance = null;
        public CpgPayment $cpgPayment;
        public Cpg_Ajax $ajax;

        public static function getInstance(): Cpg {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function __construct() {
            add_action('plugins_loaded', array($this, 'initSetup'));
            add_filter('woocommerce_payment_gateways', array($this, 'add_gateway_class'));
        }

        public function initSetup() {
            $this->includes();
            $this->init();
        }

        public function includes() {
            require_once EASY_STRIPE_INCLUDES . 'class-cpg-payment.php';
            require_once EASY_STRIPE_INCLUDES . 'class-cpg-ajex.php';
        }

        public function init() {
            $this->cpgPayment = CpgPayment::getInstance();
            $this->ajax = Cpg_Ajax::getInstance();
        }

        public function add_gateway_class($methods) {
            $methods[] = 'CpgPayment';
            return $methods;
        }
    }
}
