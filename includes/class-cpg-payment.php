<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists('Cpg_payment') ) {
    class Cpg_payment extends WC_Payment_Gateway {
        public function __construct() {
            $this->id                 = 'custom_stripe';
            $this->method_title       = __( 'Custom Stripe Gateway', 'woocommerce' );
            $this->method_description = __( 'Allows payments with Stripe.', 'woocommerce' );
            $this->has_fields         = true;

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title        = $this->get_option( 'title' );
            $this->description  = $this->get_option( 'description' );
            $this->testmode     = 'yes' === $this->get_option( 'testmode' );
            $this->liveurl      = 'https://api.stripe.com';
            $this->testurl      = 'https://api.stripe.com';

            // Actions
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
            add_action( 'woocommerce_api_wc_gateway_' . $this->id, array( $this, 'check_ipn_response' ) );
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
            $order = wc_get_order( $order_id );

            // Include Stripe PHP library
            \Stripe\Stripe::setApiKey($this->testmode ? 'your_test_api_key' : 'your_live_api_key');

            try {
                // Create payment intent
                $payment_intent = \Stripe\PaymentIntent::create([
                    'amount' => $order->get_total() * 100, // amount in cents
                    'currency' => get_woocommerce_currency(),
                    'payment_method' => $_POST['payment_method_id'],
                    'confirmation_method' => 'manual',
                    'confirm' => true,
                ]);

                if ($payment_intent->status === 'succeeded') {
                    $order->payment_complete();
                    return array(
                        'result'   => 'success',
                        'redirect' => $this->get_return_url( $order ),
                    );
                } else {
                    // Handle payment failure
                }
            } catch (Exception $e) {
                wc_add_notice( $e->getMessage(), 'error' );
                return;
            }
        }

        public function receipt_page( $order ) {
            echo '<p>' . __( 'Thank you for your order, please click the button below to pay.', 'woocommerce' ) . '</p>';
        }

        public function check_ipn_response() {
            // Handle IPN response from Stripe
        }
    }
}
