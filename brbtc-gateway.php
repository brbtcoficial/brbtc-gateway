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

add_shortcode( 'brbtc_gateway_iframe', 'get_iframe' );
function get_iframe(){
    if(isset($_GET['brbtcUrl']) && $_GET['brbtcUrl']){
        echo "<iframe src='https://brasilbitcoin.com.br".$_GET['brbtcUrl']."' width='100%' height='100%' style='min-height:35rem' frameborder='0' scrolling='no'></iframe>";
    }
}

add_action( 'plugins_loaded', 'brbtc_init_gateway_class' );
function brbtc_init_gateway_class(){
    class WC_BRBTC_Gateway extends WC_Payment_Gateway {
        public function __construct(){
            $this->id = 'brbtc_gateway';
            $this->has_fields = false;
            $this->method_title = 'Brasil Bitcoin Pay';
            $this->method_description = 'O plugin oficial da Brasil Bitcoi Pay para WooCommerce. Aceite pagamentos em Bitcoin, Litecoin, Ethereum e outras criptomoedas.';

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
            $this->icon = $this->get_option( 'icon' ) === 'yes' ? 'https://brasilbitcoin.com.br/images/logo/logo_s.png' : null;
            $this->testmode = 'yes' === $this->get_option( 'testmode' );
            $this->merchant_id = $this->testmode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );
            $this->receiveType = $this->get_option( 'receiveType' );
            $this->convert = $this->get_option( 'convert' );

            // Saves the settings
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

            // call webhook
            add_action( 'woocommerce_api_brbtc_gateway', array( $this, 'webhook' ) );
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'       => 'Ativar/Desativar',
                    'label'       => 'Ativar Gateway de Pagamento',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'yes'
                ),
                'icon' => array(
                    'title'       => 'Ícone',
                    'label'       => 'Mostrar ícone',
                    'type'        => 'checkbox',
                    'description' => 'Escolha se quer que o logo da Brasil Bitcoin seja ou nào mostrado para o usuário.',
                    'default'     => 'yes'
                ),
                'title' => array(
                    'title'       => 'Título',
                    'type'        => 'text',
                    'description' => 'Escolha o título que irá aparecer para essa opção de pagamento.',
                    'default'     => 'Criptomoedas',
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => 'Descrição',
                    'type'        => 'textarea',
                    'description' => 'Define a descrição que irá aparecer para o usuário, nessa opção de pagamento.',
                    'default'     => 'Pague utilizando as principais criptomoedas do mercado.',
                ),
                'testmode' => array(
                    'title'       => 'Sandbox',
                    'label'       => 'Ativar modo sandbox',
                    'type'        => 'checkbox',
                    'description' => 'Utiliza o ambiente de testes do gateway de pagamento.',
                    'default'     => 'no',
                    'desc_tip'    => true,
                ),
                'receiveType' => array(
                    'title'       => 'Preços em',
                    'description' => 'Seus preços estão em Real ou Cripto?',
                    'type'        => 'select',
                    'options' => [
                        'real' => 'Real',
                        'cripto' => 'Cripto',
                    ],
                    'desc_tip'    => true,
                    'default'     => 'real',
                ),
                'convert' => array(
                    'title'       => 'Converter',
                    'type'        => 'checkbox',
                    'description' => 'Deseja converter o valor do pedido para Real automaticamente?',
                    'default'     => 'yes',
                    'desc_tip'    => true,
                ),
                'merchant_id' => array(
                    'title'       => 'Merchant ID',
                    'type'        => 'text'
                )
            );
        }

        public function process_payment( $order_id ){
            global $woocommerce;

            $order = wc_get_order( $order_id );
            $merchant_id = $this->get_option( 'merchant_id' ) ?? null;
            if(!$merchant_id) {
                wc_add_notice(  'Essa loja ainda não está credenciada.', 'error' );
                return;
            }

            $value = $order->get_total();
            $type = $this->get_option( 'receiveType' ) ?? null;
            $sandbox = $this->get_option( 'testmode' ) ? 'true' : 'false';
            $convert = $this->get_option( 'convert' ) ? '1' : '0';

            $url = $merchant_id ? rawurlencode("/newPayment/$type/$merchant_id/$order_id/$value/BTC/$convert?sandbox=$sandbox") : false;

            return array(
				'result' => 'success',
				'redirect' => $this->get_return_url( $order )."&brbtcUrl=$url"
			);
        }

        public function webhook(){}
    }
}