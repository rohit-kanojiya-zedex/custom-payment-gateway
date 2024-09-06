<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists('CpgPayment') ) {
    class CpgPayment extends WC_Payment_Gateway {
        public static ?CpgPayment $instance = null;
        public function __construct() {
            $this->id                 = 'custom_stripe';
            $this->method_title       = __( 'Custom Stripe Gateway', 'woocommerce' );
            $this->method_description = __( 'Allows payments with Stripe.', 'woocommerce' );
            $this->has_fields         = true;
            $this->supports = array(
                'products'
            );


            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title        = $this->get_option( 'title' );
            $this->description  = $this->get_option( 'description' );
            $this->testmode     = 'yes' === $this->get_option( 'testmode' );


            // Actions
           add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'   => __( 'Enable/Disable', 'woocommerce' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable Custom Stripe Gateway', 'woocommerce' ),
                    'default' => 'no',
                ),
                'title' => array(
                    'title'       => __( 'Title', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                    'default'     => __( 'Credit Card (Stripe)', 'woocommerce' ),
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => __( 'Description', 'woocommerce' ),
                    'type'        => 'textarea',
                    'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
                    'default'     => __( 'Pay with your credit card via Stripe.', 'woocommerce' ),
                    'desc_tip'    => true,
                ),
                'testmode' => array(
                    'title'       => __( 'Test mode', 'woocommerce' ),
                    'type'        => 'checkbox',
                    'label'       => __( 'Enable Test Mode', 'woocommerce' ),
                    'default'     => 'yes',
                    'description' => __( 'Place the payment gateway in test mode using test API keys.', 'woocommerce' ),
                ),
            );
        }

        public function process_payment( $order_id ) {
            // Get the order object
            $order = wc_get_order( $order_id );

            // Mark as on-hold (we're awaiting the cheque)
            $order->update_status('on-hold', __( 'Awaiting cheque payment', 'woocommerce' ));

            // Remove cart
            WC()->cart->empty_cart();

            // Return thank you redirect
            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url( $order )
            );
        }


        public static function getInstance(): CpgPayment {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
            }
            return self::$instance;
        }
    }
}
