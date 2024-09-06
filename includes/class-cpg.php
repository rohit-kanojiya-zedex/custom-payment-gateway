<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Cpg' ) ) {
    class Cpg {
        protected static ?Cpg $instance = null;
        public Cpg_payment $cpgPayment;

        public static function getInstance(): Cpg {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function __construct() {
            add_action('plugins_loaded', array($this, 'initSetup'));
        }

        public function initSetup() {
            $this->includes();
            $this->init();
            $this->add_gateway();
        }

        public function includes() {
            require_once EASY_STRIPE_INCLUDES . 'class-cpg-payment.php';
        }

        public function init() {
            $this->cpgPayment = Cpg_payment::getInstance();
        }

        public function add_gateway() {
            add_filter('woocommerce_payment_gateways', array($this, 'add_gateway_class'));
        }

        public function add_gateway_class($methods) {
            $methods[] = 'Cpg_payment'; // Add your gateway class here
            return $methods;
        }
    }
}
