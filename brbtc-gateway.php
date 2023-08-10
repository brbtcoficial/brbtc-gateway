<?php
/**
 * Plugin Name: BRBTC Gateway for WooComerce
 * Plugin URI: https://github.com/brbtcoficial/brbtc-gateway
 * Description: Official BRBTC Gateway for WooCommerce
 * Author: Brasil Bitcoin
 * Author URI: https://brasilbitcoin.com.br
 * Version: 0.0.1
 */

/**
 * Add BRBTC Gateway to WooCommerce
*/
add_filter( 'woocommerce_payment_gateways', 'brbtc_gateway_class' );
function brbtc_gateway_class( $gateways ) {
    $gateways[] = 'WC_BRBTC_Gateway';
    return $gateways;
}

add_action( 'plugins_loaded', 'brbtc_init_gateway_class' );
function brbtc_init_gateway_class(){
    class WC_BRBTC_Gateway extends WC_Payment_Gateway {
        public function __construct(){
            $this->id = 'brbtc_gateway';
            $this->icon = plugins_url( 'https://brasilbitcoin.com.br/images/logo/logo2.png', __FILE__ );
            $this->has_fields = false;
            $this->method_title = 'BRBTC Gateway';
            $this->method_description = 'BRBTC Payment Gateway for WooCommerce';

            $this->supports = [
                'products'
            ];
            
            // Load form fields.
            $this->init_form_fields();

            // Load the settings.
            $this->init_settings();
            $this->title = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );
            $this->enabled = $this->get_option( 'enabled' );
            $this->testmode = 'yes' === $this->get_option( 'testmode' );
            $this->private_key = $this->testmode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );
            $this->publishable_key = $this->testmode ? $this->get_option( 'test_publishable_key' ) : $this->get_option( 'publishable_key' );

            // Saves the settings
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

            // custom javascript
            add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );

            // call webhook
            add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'       => 'Enable/Disable',
                    'label'       => 'Enable Misha Gateway',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ),
                'title' => array(
                    'title'       => 'Title',
                    'type'        => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default'     => 'Credit Card',
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => 'Description',
                    'type'        => 'textarea',
                    'description' => 'This controls the description which the user sees during checkout.',
                    'default'     => 'Pay with your credit card via our super-cool payment gateway.',
                ),
                'testmode' => array(
                    'title'       => 'Test mode',
                    'label'       => 'Enable Test Mode',
                    'type'        => 'checkbox',
                    'description' => 'Place the payment gateway in test mode using test API keys.',
                    'default'     => 'yes',
                    'desc_tip'    => true,
                ),
                'test_publishable_key' => array(
                    'title'       => 'Test Publishable Key',
                    'type'        => 'text'
                ),
                'test_private_key' => array(
                    'title'       => 'Test Private Key',
                    'type'        => 'password',
                ),
                'publishable_key' => array(
                    'title'       => 'Live Publishable Key',
                    'type'        => 'text'
                ),
                'private_key' => array(
                    'title'       => 'Live Private Key',
                    'type'        => 'password'
                )
            );
        }

        public function payment_fields(){}

        public function payment_scripts(){}

        public function validate_fields(){}

        public function process_payment( $order_id ){}

        public function webhook(){}
    }
}