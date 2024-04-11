<?php
/**
 * Plugin Name: Pay Now Gateway for WooCommerce
 * Description: Custom payment gateway for WooCommerce to send payments to iWantPay.
 * Author: Your Name
 * Version: 1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Add the gateway to WooCommerce
add_filter('woocommerce_payment_gateways', 'add_paynow_gateway');
function add_paynow_gateway($methods) {
    $methods[] = 'WC_Gateway_Pay_Now';
    return $methods;
}

// Define the gateway
add_action('plugins_loaded', 'init_paynow_gateway');
function init_paynow_gateway() {
    class WC_Gateway_Pay_Now extends WC_Payment_Gateway {
        public function __construct() {
            $this->id = 'pay_now';
            $this->method_title = __('Pay Now', 'woocommerce');
            $this->method_description = __('Allows payments with Pay Now gateway.', 'woocommerce');
            $this->has_fields = true;

            $this->init_form_fields();
            $this->init_settings();

            $this->title = $this->get_option('title');

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'title' => array(
                    'title' => __('Title', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                    'default' => __('Pay Now', 'woocommerce'),
                    'desc_tip' => true,
                ),
            );
        }

        public function process_payment($order_id) {
            $order = wc_get_order($order_id);

            // Get the total amount and shipping address
            $amount = $order->get_total();
            $ship_address = $order->get_shipping_address_1();

            // Your API endpoint
            $api_url = 'https://iwantpay.com.tw/api/paynow';

            // Prepare the data to be sent to the API
            $payment_data = array(
                'amount' => $amount,
                'ship_address' => $ship_address
            );

            // Use wp_remote_post to send data to the API
            $response = wp_remote_post($api_url, array(
                'method' => 'POST',
                'body' => http_build_query($payment_data),
                'timeout' => 45,
                'sslverify' => false,
            ));

            if (is_wp_error($response)) {
                wc_add_notice(__('Payment error:', 'woocommerce') . $response->get_error_message(), 'error');
                return;
            }

            // Redirect to the thank you page after successful payment
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order),
            );
        }
    }
}
