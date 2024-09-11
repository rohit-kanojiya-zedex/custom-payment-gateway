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
            $this->stripe_key   = $this->testmode ? $this->get_option( 'testmode_key' ) : $this->get_option( 'live_key' );
            $this->payment_method = $this->get_option('payment_method');

            \Stripe\Stripe::setApiKey($this->stripe_key);

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
                'testmode_key' => array(
                    'title'       => __( 'Test Secret Key', 'woocommerce' ),
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

        public function helloMount($fragments){
            ob_start();
            ?>
            <script>
                helloMount();
            </script>
            <?php
            $html = ob_get_clean();
            $fragments['.woocommerce-checkout-payment'] = $fragments['.woocommerce-checkout-payment'] . $html;
            return $fragments;
         }

        public function payment_fields() {
            if (is_checkout() && $this->payment_method === 'direct') {
                add_filter('woocommerce_update_order_review_fragments', [$this , 'helloMount']);
                load_template(EASY_STRIPE_TEMPLATES . 'direct-payment-form.php', false);
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
            }
        }

//        public function validate_fields() {
//            if ($this->payment_method === 'direct' && empty($_POST['stripeToken'])) {
//                wc_add_notice(__('Credit card details are required.', 'woocommerce'), 'error');
//            }
//        }

        public function process_payment( $order_id ) {
            $order = wc_get_order( $order_id );

            if ($this->payment_method === 'direct') {
                $stripeToken = isset($_POST['stripeToken']) ? sanitize_text_field($_POST['stripeToken']) : '';

                if (empty($stripeToken)) {
                    wc_add_notice(__('Payment error: Missing payment token.', 'woocommerce'), 'error');
                    return array(
                        'result'   => 'failure',
                        'redirect' => ''
                    );
                }

                try {
                    // Set Stripe API Key
                    \Stripe\Stripe::setApiKey($this->testmode ? $this->get_option('testmode_key') : $this->get_option('live_key'));

                    // Create a charge
                    $charge = \Stripe\Charge::create([
                        'amount'      => $order->get_total() * 100, // Amount in cents
                        'currency'    => get_woocommerce_currency(),
                        'description' => 'Order #' . $order_id,
                        'source'      => $stripeToken,
                        'metadata'    => ['order_id' => $order_id],
                    ]);

                    // Payment was successful
                    $order->payment_complete();
                    WC()->cart->empty_cart();

                    // Return success result and redirect to the thank you page
                    return array(
                        'result'   => 'success',
                        'redirect' => $this->get_return_url( $order )
                    );

                } catch (\Stripe\Exception\CardException $e) {
                    // Card error (e.g., insufficient funds, expired card)
                    wc_add_notice(__('Payment error:', 'woocommerce') . ' ' . $e->getError()->message, 'error');
                    return array(
                        'result'   => 'failure',
                        'redirect' => ''
                    );
                } catch (\Stripe\Exception\RateLimitException $e) {
                    // Rate limit error
                    wc_add_notice(__('Payment error: Too many requests made to the API too quickly.', 'woocommerce'), 'error');
                    return array(
                        'result'   => 'failure',
                        'redirect' => ''
                    );
                } catch (\Stripe\Exception\InvalidRequestException $e) {
                    // Invalid parameters error
                    wc_add_notice(__('Payment error: Invalid parameters.', 'woocommerce'), 'error');
                    return array(
                        'result'   => 'failure',
                        'redirect' => ''
                    );
                } catch (\Stripe\Exception\AuthenticationException $e) {
                    // Authentication error (e.g., invalid API key)
                    wc_add_notice(__('Payment error: Authentication with Stripe failed.', 'woocommerce'), 'error');
                    return array(
                        'result'   => 'failure',
                        'redirect' => ''
                    );
                } catch (\Stripe\Exception\ApiConnectionException $e) {
                    // Network error
                    wc_add_notice(__('Payment error: Network communication with Stripe failed.', 'woocommerce'), 'error');
                    return array(
                        'result'   => 'failure',
                        'redirect' => ''
                    );
                } catch (\Exception $e) {
                    // General error
                    wc_add_notice(__('Payment error:', 'woocommerce') . ' ' . $e->getMessage(), 'error');
                    return array(
                        'result'   => 'failure',
                        'redirect' => ''
                    );
                }

            } else {
                // Redirect payment method
                $order->update_status('on-hold', __('Awaiting redirect payment.', 'woocommerce'));

                // Return success result and redirect to the checkout page or a custom URL if needed
                return array(
                    'result'   => 'success',
                    'redirect' => $this->get_return_url( $order ) // Adjust if needed for redirect
                );
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

