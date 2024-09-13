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
            WC()->session->set('stripe_token', $token);
            wp_send_json_success(['status' => true]);
        }
    }
}