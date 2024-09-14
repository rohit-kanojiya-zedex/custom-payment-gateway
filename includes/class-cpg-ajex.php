<?php
//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Cpg_Ajax')) {
    class Cpg_Ajax
    {
        protected static ?Cpg_Ajax $instance = null;

        public static function getInstance(): Cpg_Ajax
        {
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        public function __construct()
        {
            add_action('wp_ajax_stripAction', array($this, 'handle_stripe_token'));
            add_action('wp_ajax_nopriv_stripAction', array($this, 'handle_stripe_token'));
        }

        public function handle_stripe_token()
        {


            if (!isset($_POST['data']['token'])) {
                wp_send_json_error(['status' => false, 'message' => 'Token is missing']);
            }
            $token = sanitize_text_field($_POST['data']['token']);
            $save_card = $_POST['data']['save_card'];
            WC()->session->set('stripe_token', $token);


            if ($save_card==='1' && is_user_logged_in()) {

                \Stripe\Stripe::setApiKey('sk_test_51PuvDJRsZwJIlbd4czCGerc6OvMnE1fJtXGsXX9T3UMgBpntgpam4umWXIIky3xgor8yRP63v15E9jK1o8aP1zn200si2Qe1KO');

                $user_id = get_current_user_id();
                $customer_id = get_user_meta($user_id, 'stripe_customer_id', true);

                if (!$customer_id) {
                    // Create a new Stripe customer
                    $customer = \Stripe\Customer::create([
                        'email' => wp_get_current_user()->user_email,
                    ]);
                    $customer_id = $customer->id;
                    update_user_meta($user_id, 'stripe_customer_id', $customer_id);
                }

                // Attach the payment method to the customer
                $payment_method = \Stripe\PaymentMethod::retrieve($token);
                echo $payment_method;
                $payment_method->attach(['customer' => $customer_id]);

                // Optionally set the default payment method
                \Stripe\Customer::update($customer_id, [
                    'invoice_settings' => [
                        'default_payment_method' => $token,
                    ],
                ]);

            }

            wp_send_json_success(['status' => true]);
        }
    }
}