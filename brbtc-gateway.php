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

// shortcode to get iframe
add_shortcode( 'brbtc_gateway_iframe', 'get_iframe' );
function get_iframe(){
    if(isset($_GET['brbtcUrl']) && $_GET['brbtcUrl']){
        echo "<iframe src='https://brasilbitcoin.com.br".$_GET['brbtcUrl']."' width='100%' height='100%' style='min-height:35rem' frameborder='0'></iframe>";
    }
}

add_action( 'plugins_loaded', 'brbtc_init_gateway_class' );
function brbtc_init_gateway_class(){
    class WC_BRBTC_Gateway extends WC_Payment_Gateway {
        public function __construct(){
            $this->id = 'brbtc_gateway';
            $this->has_fields = false;
            $this->method_title = 'Brasil Bitcoin Pay';
            $this->method_description = 'O plugin para Brasil Bitcoin Pay com WooCommerce. Aceite pagamentos em Bitcoin, Litecoin, Ethereum e outras criptomoedas.';

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
            $this->webhook = $this->get_option( 'webhook' );
            $this->coin = $this->get_option( 'coin' );

            // styles options
            $this->code_size = $this->get_option( 'code_size' );
            $this->text_color = $this->get_option( 'text_color' );
            $this->bg_color = $this->get_option( 'bg_color' );
            $this->selector_text_color = $this->get_option( 'selector_text_color' );
            $this->selector_color = $this->get_option( 'selector_color' );
            $this->button_text_color = $this->get_option( 'button_text_color' );
            $this->button_color = $this->get_option( 'button_color' );

            // Saves the settings
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options'] );

            // call webhook
            // add_action( 'woocommerce_api_'.strtolower($this->webhook), [ $this, 'webhook' ] );
            add_action( 'rest_api_init', [$this, 'custom_rest_api_init']);
        }

        public function custom_rest_api_init(){
            $webhook = $this->get_option('webhook');
            register_rest_route( strtolower($webhook), '/', [
                'methods' => 'POST',
                'callback' => [ $this, 'webhook' ]
            ] );
        }

        public function init_form_fields() {
            $domain = get_site_url();
            $webhookPath = $this->get_option( 'webhook' );

            $this->form_fields = [
                [
                    'title' => 'Configurações',
                    'type' => 'title',
                    'description' => 'Configurações gerais do Gateway de pagamento.',
                ],
                'enabled' => [
                    'title'       => 'Ativar/Desativar',
                    'label'       => 'Ativar Gateway de Pagamento',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'yes'
                ],
                'merchant_id' => [
                    'title'       => 'Merchant ID',
                    'type'        => 'text'
                ],
                'webhook' => [
                    'title' => 'Webhook',
                    'type' => 'text',
                    'description' => "O webhook será $domain/wc-api/$webhookPath",
                    'default' => 'brbtc_gateway'
                ],
                'testmode' => [
                    'title'       => 'Sandbox',
                    'label'       => 'Ativar modo sandbox',
                    'type'        => 'checkbox',
                    'description' => 'Utiliza o ambiente de testes do gateway de pagamento.',
                    'default'     => 'no',
                    'desc_tip'    => true,
                ],
                [
                    'title' => 'Configurações de exibição',
                    'type' => 'title',
                    'description' => 'Configurações das informações textuais que são exibidas ao cliente na seleção de pagamento.',
                ],
                'icon' => array(
                    'title'       => 'Ícone',
                    'label'       => 'Mostrar ícone',
                    'type'        => 'checkbox',
                    'description' => 'Escolha se quer que o logo da Brasil Bitcoin Pay seja ou nào mostrado para o usuário.',
                    'default'     => 'no',
                    'desc_tip' => true,
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
                    'desc_tip'    => true,
                ),
                [
                    'title' => 'Configurações de pagamento',
                    'type' => 'title',
                    'description' => 'Configurações para definir parâmetros relacionados ao pagamento/recebimento.',
                ],
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
                'coin' => array(
                    'title'       => 'Moeda',
                    'type'        => 'select',
                    'description' => 'Selecione a moeda que deseja disponibilizar para o cliente como forma de pagamento',
                    'options'     => [
                        'ANY'   => 'Todas as moedas',
                        'BTC'   => 'Bitcoin',
                        'BCH'   => 'Bitcoin Cash',
                        'LTC'   => 'Litecoin',
                        'DOGE'  => 'Dogecoin',
                    ],
                    'default'     => 'ANY',
                    'desc_tip'    => true,
                ),
                [
                    'title' => 'Configurações de estilo',
                    'type' => 'title',
                    'description' => 'Configurações de estivo e visuais do iFrame que será exibido para pagamento.',
                ],
                'code_size' => [
                    'title'       => 'Tamanho do QRCode',
                    'type'        => 'number',
                    'description' => 'Escolha o tamanho do código QR em pixels que será exibido para o usuário.',
                    'default'     => '140',
                    'desc_tip'    => true,
                ],
                'text_color' => [
                    'title'       => 'Cor do texto',
                    'type'        => 'color',
                    'description' => 'Código HEX para a cor do texto que será exibido para o usuário.',
                    'default'     => '#6B6B6B',
                    'desc_tip'    => true,
                ],
                'bg_color' => [
                    'title'       => 'Cor de fundo',
                    'type'        => 'color',
                    'description' => 'Código HEX para a cor de fundo do iframe que será exibido para o usuário.',
                    'default'     => '#F0F0F0',
                    'desc_tip'    => true,
                ],
                'selector_text_color' => [
                    'title'       => 'Text do seletor de moedas',
                    'type'        => 'color',
                    'description' => 'Código HEX para a cor do texto do seletor de moedas.',
                    'default'     => '#242424',
                    'desc_tip'    => true,
                ],
                'selector_color' => [
                    'title'       => 'Fundo do seletor de moedas',
                    'type'        => 'color',
                    'description' => 'Código HEX para a cor de fundo do seletor de moedas.',
                    'default'     => '#E3E3E3',
                    'desc_tip'    => true,
                ],
                'button_text_color' => [
                    'title'       => 'Cor do texto do botão',
                    'type'        => 'color',
                    'description' => 'Código HEX para a cor do texto do botão.',
                    'default'     => '#FFFFFF',
                    'desc_tip'    => true,
                ],
                'button_color' => [
                    'title'       => 'Cor do botão',
                    'type'        => 'color',
                    'description' => 'Código HEX para a cor do botão.',
                    'default'     => '#00b9fc',
                    'desc_tip'    => true,
                ],
            ];
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
            $coin = $this->get_option( 'coin' ) ?? 'ANY';

            // Styles options
            $code_size = $this->get_option( 'code_size' );
            $text_color = str_replace('#', '', $this->get_option( 'text_color' ));
            $bg_color = str_replace('#', '', $this->get_option( 'bg_color' ));
            $selector_text_color = str_replace('#', '', $this->get_option( 'selector_text_color' ));
            $selector_color = str_replace('#', '', $this->get_option( 'selector_color' ));
            $button_text_color = str_replace('#', '', $this->get_option( 'button_text_color' ));
            $button_color = str_replace('#', '', $this->get_option( 'button_color' ));

            $url = $merchant_id ? rawurlencode("/newPayment/$type/$merchant_id/$order_id/$value/$coin/$convert?sandbox=$sandbox&codeSize=$code_size&textColor=$text_color&bgColor=$bg_color&selectorTextColor=$selector_text_color&selectorColor=$selector_color&buttonTextColor=$button_text_color&buttonColor=$button_color") : false;
            
            $woocommerce->cart->empty_cart();

            return array(
				'result' => 'success',
				'redirect' => $this->get_return_url( $order )."&brbtcUrl=$url"
			);
        }

        public function webhook($request){
            $data = $request->get_params();

            if(!isset($data['checkout_id']) || !isset($data['is_paid']) || !isset($data['status'])) return;
            
            $order = wc_get_order( $data['checkout_id'] );
            if(!$order) return;

            $isPaid = $data['is_paid'];
            $status = $data['status'];

            if($status === 'credited'){
                $value = number_format(($data['value_brl'] ?? 0), 2, ',', '.');
                $cripto = number_format(($data['value_cripto'] ?? 0), 8, '.', '');
                $price = number_format(($data['price_cripto'] ?? 0), 2, ',', '.');
                $coin_tag = $data['coin_tag'] ?? '';

                $order->payment_complete();
                $order->reduce_order_stock();

                $order->add_order_note("Seu pedido foi pago com sucesso!\n\nValor pago: R$ $value\nCripto: $cripto $coin_tag\nPreço unitário: R$ $price");
            }
            elseif($status === 'pending' && $isPaid){
                $order->update_status('on-hold');
                $order->add_order_note("Seu pedido está aguardando a confirmação do pagamento pela rede da criptomoeda utilizada.");
            }

            $debug = json_encode($data);
            rest_ensure_response($debug);
        }
    }
}