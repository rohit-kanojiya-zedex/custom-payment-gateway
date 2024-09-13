<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists('CpgPayment') ) {
    class CpgPayment extends WC_Payment_Gateway {
        public static ?CpgPayment $instance = null;
        public $stripeToken = null;


        public function __construct() {
            $this->id                 = 'custom_stripe';
            $this->method_title       = __( 'Custom Stripe Gateway', 'woocommerce' );
            $this->method_description = __( 'Allows payments with Stripe.', 'woocommerce' );
            $this->has_fields         = true; // Required for direct gateway
            $this->supports = array(
                'products',
                'refunds',
                'subscriptions',
            );

            $this->init_form_fields();
            $this->init_settings();

            $this->title        = $this->get_option( 'title' );
            $this->description  = $this->get_option( 'description' );
            $this->testmode     = 'yes' === $this->get_option( 'testmode' );
            $this->stripe_key   = $this->testmode ? $this->get_option( 'secretmode_key' ) : $this->get_option( 'live_key' );
            $this->publishable_key   = $this->testmode ? $this->get_option( 'publishable_key' ) : '';
            $this->payment_method = $this->get_option('payment_method');


            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
            add_action( 'woocommerce_checkout_process', array( $this, 'validate_fields' ) );
            add_action( 'wp_enqueue_scripts', array($this, 'enqueue_scripts'));
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
                'payment_method' => array(
                    'title'       => __( 'Payment Method', 'woocommerce' ),
                    'type'        => 'select',
                    'options'     => array(
                        'direct'   => __( 'Direct', 'woocommerce' ),
                        'redirect' => __( 'Redirect', 'woocommerce' ),
                    ),
                    'description' => __( 'Choose whether to use direct payment or redirect to Stripe for payment.', 'woocommerce' ),
                    'default'     => 'direct',
                ),
                'testmode' => array(
                    'title'       => __( 'Test mode', 'woocommerce' ),
                    'type'        => 'checkbox',
                    'label'       => __( 'Enable Test Mode', 'woocommerce' ),
                    'default'     => 'yes',
                    'description' => __( 'Place the payment gateway in test mode using test API keys.', 'woocommerce' ),
                ),
                'secretmode_key' => array(
                    'title'       => __( 'Test Secret Key', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'This is the secret key used in test mode.', 'woocommerce' ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'publishable_key' => array(
                    'title'       => __( 'Test Publishable key', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'This is the secret key used in test mode.', 'woocommerce' ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'live_key' => array(
                    'title'       => __( 'Live Secret Key', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'This is the secret key used in live mode.', 'woocommerce' ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
            );
        }

        public function GenerateToken($fragments){
            ob_start();
            ?>
            <script>
                mountAndGenerateToken();
            </script>
            <?php
            $html = ob_get_clean();
            $fragments['.woocommerce-checkout-payment'] = $fragments['.woocommerce-checkout-payment'] . $html;
            return $fragments;
        }


        public function payment_fields() {
            if (is_checkout() && $this->payment_method === 'direct') {
                if (wp_doing_ajax()) {
                    load_template(EASY_STRIPE_TEMPLATES . 'direct-payment-form.php', false);
                    add_filter('woocommerce_update_order_review_fragments', [$this, 'GenerateToken']);
                }
                wp_enqueue_script('my-strip');
                wp_enqueue_script('stripe-js');
            } else {
                echo '<p>Payment will be handled by redirecting to Stripe.</p>';
            }
        }

        public function enqueue_scripts() {
            if (is_checkout() && $this->payment_method === 'direct') {
                wp_register_script('stripe-js', 'https://js.stripe.com/v3/', array('jquery'), EASY_STRIPE_VERSION, true);
                wp_register_script('my-strip' , EASY_STRIPE_JS .'my-strip.js');
                wp_localize_script('stripe-js', 'stripeObj', array('ajaxurl' => admin_url('admin-ajax.php'),'publishable_key'=>$this->publishable_key));
            }
        }

//        public function validate_fields() {
//            if ($this->payment_method === 'direct' && empty($_POST['stripeToken'])) {
//                wc_add_notice(__('Credit card details are required.', 'woocommerce'), 'error');
//            }
//        }

        public function process_payment($order_id) {
            $order = wc_get_order($order_id);
            $this->stripeToken = WC()->session->get('stripe_token');

            if ($this->payment_method === 'direct') {
                if (empty($this->stripeToken)) {
                    wc_add_notice(__('Payment error: Missing payment token.', 'woocommerce'), 'error');
                    return ['result' => 'failure', 'redirect' => ''];
                }

                try {
                    \Stripe\Stripe::setApiKey($this->stripe_key);

                    $charge = \Stripe\Charge::create([
                        'amount'      => $order->get_total() * 100, // Amount in cents
                        'currency'    => get_woocommerce_currency(),
                        'description' => 'Order #' . $order_id,
                        'source'      => $this->stripeToken,
                        'metadata'    => ['order_id' => $order_id],
                    ]);

                    $order->update_meta_data('_stripe_charge_id', $charge->id);
                    $order->save();

                    $order->payment_complete();
                    WC()->cart->empty_cart();

                    WC()->session->__unset('stripe_token');

                    return ['result' => 'success', 'redirect' => $this->get_return_url($order)];
                }
                catch (\Stripe\Exception\CardException $e) {
                    $message = __('Payment error:', 'woocommerce') . ' ' . $e->getError()->message;
                } catch (\Stripe\Exception\RateLimitException $e) {
                    $message = __('Payment error: Too many requests made to the API too quickly.', 'woocommerce');
                } catch (\Stripe\Exception\AuthenticationException $e) {
                    $message = __('Payment error: Authentication with Stripe failed.', 'woocommerce');
                } catch (\Stripe\Exception\ApiConnectionException $e) {
                    $message = __('Payment error: Network communication with Stripe failed.', 'woocommerce');
                } catch (\Exception $e) {
                    $message = __('Payment error:', 'woocommerce') . ' ' . $e->getMessage();
                }

                wc_add_notice($message, 'error');
                return ['result' => 'failure', 'redirect' => ''];

            } else {
                // Redirect payment method
                $order->update_status('on-hold', __('Awaiting redirect payment.', 'woocommerce'));
                return ['result' => 'success', 'redirect' => $this->get_return_url($order)];
            }
        }


        public function process_refund($order_id, $amount = null, $reason = '') {
            $order = wc_get_order($order_id);

            if (!$order) {
                return new WP_Error('order_not_found', __('Order not found.', 'woocommerce'));
            }

            $charge_id = $order->get_meta('_stripe_charge_id'); // Store the charge ID when the payment is processed

            if (empty($charge_id)) {
                return new WP_Error('charge_id_missing', __('Charge ID is missing for this order.', 'woocommerce'));
            }

            try {
                \Stripe\Stripe::setApiKey($this->stripe_key);

                $refund = \Stripe\Refund::create([
                    'charge' => $charge_id,
                    'amount'  => $amount ? $amount * 100 : null,
                ]);

                if ($refund->status === 'succeeded') {
                    $order->add_order_note(sprintf(__('Refunded %s', 'woocommerce'), wc_price($amount)));
                    return true;
                } else {
                    return new WP_Error('refund_failed', __('Refund failed.', 'woocommerce'));
                }
            } catch (\Stripe\Exception\CardException $e) {
                return new WP_Error('card_error', $e->getError()->message);
            } catch (\Stripe\Exception\RateLimitException $e) {
                return new WP_Error('rate_limit_error', __('Too many requests made to the API too quickly.', 'woocommerce'));
            } catch (\Stripe\Exception\AuthenticationException $e) {
                return new WP_Error('authentication_error', __('Authentication with Stripe failed.', 'woocommerce'));
            } catch (\Stripe\Exception\ApiConnectionException $e) {
                return new WP_Error('api_connection_error', __('Network communication with Stripe failed.', 'woocommerce'));
            }
        }


        public static function getInstance(): CpgPayment {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
            }
            return self::$instance;
        }
    }
}

