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
define( 'EASY_STRIPE_VERSION', '1.0.0' );
define( 'EASY_STRIPE_INCLUDES', EASY_STRIPE_PLUGIN_DIR . '/includes/' );
define( 'EASY_STRIPE_ASSETS', EASY_STRIPE_PLUGIN_URL . '/assets/' );
define( 'EASY_STRIPE_CSS', EASY_STRIPE_ASSETS . 'css/' );
define( 'EASY_STRIPE_JS', EASY_STRIPE_ASSETS . 'js/' );
define( 'EASY_STRIPE_TEMPLATES', EASY_STRIPE_PLUGIN_DIR . '/templates/' );


require_once EASY_STRIPE_INCLUDES . 'class-dependency-checker.php';
require_once EASY_STRIPE_INCLUDES . 'class-cpg.php';


function initialize_custom_payment_gateway() {
    if ( Dependency_Checker::check_dependencies() ) {
        if ( ! class_exists( 'Cpg' ) ) {
            include_once plugin_dir_path( __FILE__ ) . 'includes/class-cpg.php';
        }
        Cpg::getInstance();
    } else {
        deactivate_plugins( plugin_basename( __FILE__ ) );
    }
}
add_action( 'plugins_loaded', 'initialize_custom_payment_gateway' );

