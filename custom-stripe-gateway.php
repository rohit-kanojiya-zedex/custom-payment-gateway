<?php
/**
 * Plugin Name: Easy Stripe Gateway
 * Description: Make payments easy with Easy Stripe Gateway.
 * Version: 1.0.0
 * Author: Stripe Easy
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: easy-stripe-gateway
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load Composer autoload if needed.
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

// Define plugin constants.
define( 'EASY_STRIPE_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'EASY_STRIPE_PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'EASY_STRIPE_VERSION', '1.0.2' );
define( 'EASY_STRIPE_INCLUDES', EASY_STRIPE_PLUGIN_DIR . '/includes/' );
define( 'EASY_STRIPE_ASSETS', EASY_STRIPE_PLUGIN_URL . '/assets/' );
define( 'EASY_STRIPE_CSS', EASY_STRIPE_ASSETS . 'css/' );
define( 'EASY_STRIPE_JS', EASY_STRIPE_ASSETS . 'js/' );
define( 'EASY_STRIPE_TEMPLATES', EASY_STRIPE_PLUGIN_DIR . '/templates/' );


require_once EASY_STRIPE_INCLUDES . 'class-dependency-checker.php';

function initialize_custom_payment_gateway()
{
    if (Dependency_Checker::check_dependencies()) {
        if ( ! class_exists( 'Cpg' ) ) {
            include_once EASY_STRIPE_INCLUDES . 'class-cpg.php';
        }
    } else {
        deactivate_plugins(plugin_basename(__FILE__));
    }
}

Cpg::getInstance();
add_action( 'plugins_loaded', 'initialize_custom_payment_gateway' );



add_action( 'woocommerce_blocks_loaded', 'oawoo_register_order_approval_payment_method_type' );
function oawoo_register_order_approval_payment_method_type() {
    if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        return;
    }
    include_once EASY_STRIPE_INCLUDES . 'class-block.php';
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
            $payment_method_registry->register( new My_Custom_Gateway_Blocks );
        }
    );
}




//add methods my acount section
// Add custom endpoint to My Account menu
add_action('init', 'add_payment_methods_endpoint');
function add_payment_methods_endpoint() {
    add_rewrite_endpoint('payment-methods', EP_ROOT | EP_PAGES);
}

// Add link to My Account menu
add_filter('woocommerce_account_menu_items', 'add_payment_methods_link');
function add_payment_methods_link($items) {
    $items['payment-methods'] = __('Payment Methods', 'woocommerce');
    return $items;
}

// Display content for the custom endpoint
add_action('woocommerce_account_payment-methods_endpoint', 'payment_methods_content');
function payment_methods_content() {
    $user_id = get_current_user_id();
    $customer_id = get_user_meta($user_id, 'stripe_customer_id', true);

    if ($customer_id) {
        \Stripe\Stripe::setApiKey('sk_test_51PuvDJRsZwJIlbd4czCGerc6OvMnE1fJtXGsXX9T3UMgBpntgpam4umWXIIky3xgor8yRP63v15E9jK1o8aP1zn200si2Qe1KO');
        $customer = \Stripe\Customer::retrieve($customer_id);
        $payment_methods = \Stripe\PaymentMethod::all(['customer' => $customer_id, 'type' => 'card']);

        echo '<h2>' . __('Saved Payment Methods', 'woocommerce') . '</h2>';
        if (count($payment_methods->data) > 0) {
            foreach ($payment_methods->data as $payment_method) {
                echo '<div class="saved-card">';
                echo '<p>' . $payment_method->card->brand . ' ending in ' . $payment_method->card->last4 . '</p>';
                echo '<button class="delete-card" data-id="' . $payment_method->id . '">Remove</button>';
                echo '</div>';
            }
        } else {
            echo '<p>' . __('No saved payment methods found.', 'woocommerce') . '</p>';
        }
    } else {
        echo '<p>' . __('You do not have a Stripe customer ID.', 'woocommerce') . '</p>';
    }
}

//card remove
add_action('wp_ajax_remove_payment_method', 'remove_payment_method');
function remove_payment_method() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'stripe_nonce')) {
        wp_send_json_error(['message' => 'Nonce verification failed']);
    }

    $payment_method_id = isset($_POST['payment_method_id']) ? sanitize_text_field($_POST['payment_method_id']) : '';
    $user_id = get_current_user_id();
    $customer_id = get_user_meta($user_id, 'stripe_customer_id', true);

    if (empty($payment_method_id) || empty($customer_id)) {
        wp_send_json_error(['message' => 'Invalid request']);
    }

    \Stripe\Stripe::setApiKey('your-secret-key-here');
    $payment_method = \Stripe\PaymentMethod::retrieve($payment_method_id);
    $payment_method->detach();

    wp_send_json_success(['message' => 'Payment method removed']);
}




// Add New Card Option During Checkout
// Display saved cards and new card form on checkout page
add_action('woocommerce_after_checkout_form', 'display_saved_cards_checkout');
function display_saved_cards_checkout() {
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $customer_id = get_user_meta($user_id, 'stripe_customer_id', true);

        if ($customer_id) {
            \Stripe\Stripe::setApiKey('sk_test_51PuvDJRsZwJIlbd4czCGerc6OvMnE1fJtXGsXX9T3UMgBpntgpam4umWXIIky3xgor8yRP63v15E9jK1o8aP1zn200si2Qe1KO');
            $payment_methods = \Stripe\PaymentMethod::all(['customer' => $customer_id, 'type' => 'card']);

            if (count($payment_methods->data) > 0) {
                echo '<h3>' . __('Saved Payment Methods', 'woocommerce') . '</h3>';
                echo '<ul id="saved-cards">';
                foreach ($payment_methods->data as $payment_method) {
                    echo '<li>';
                    echo '<label><input type="radio" name="payment_method" value="' . esc_attr($payment_method->id) . '"> ' . $payment_method->card->brand . ' ending in ' . $payment_method->card->last4 . '</label>';
                    echo '</li>';
                }
                echo '</ul>';
            }
        }
        echo '<div id="new-card-form">';
        echo '<h3>' . __('Add New Card', 'woocommerce') . '</h3>';
        echo '<form id="payment-form">';
        echo '<div id="card-element"></div>';
        echo '<div id="card-errors" role="alert"></div>';
        echo '<button type="submit">Add Card</button>';
        echo '</form>';
        echo '</div>';
    }
}


?>