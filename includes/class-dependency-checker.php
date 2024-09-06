<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Dependency_Checker {
    public static function is_woocommerce_active() {
        return class_exists('WC_Payment_Gateway');
    }

    public static function check_dependencies() {
        $all_met = true;

        if ( ! self::is_woocommerce_active() ) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p><strong>Easy Stripe Gateway</strong> requires WooCommerce to be installed and activated.</p></div>';
            });
            $all_met = false;
        }
        return $all_met;
    }
}
