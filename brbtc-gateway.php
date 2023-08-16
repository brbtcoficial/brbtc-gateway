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

add_action( 'rest_api_init', 'register_route');
function register_route(){
    register_rest_route( 'brbtc_gateway', '/webhook', [
        'methods' => 'POST',
        'callback' => 'webhook'
    ]);
}

function debug($txt){
    if(is_string($txt)) {
        $file = plugin_dir_path( __FILE__ ) . '/errors.txt'; 
        $open = fopen( $file, "a" );
        $write = fputs( $open, "\n".$txt ); 
        fclose( $open );
    }
}

function webhook($request){
    $p = new WC_BRBTC_Gateway();
    $secret = $p->get_option( 'secret' );
    if($secret !== $request->get_header('x_brbtc_secret_token')) return;

    $POST = json_decode($request->get_body());

    $checkout_id = $POST->checkout_id ?? null;
    $status = $POST->status ?? null;
    if( !$checkout_id || !$status ) return;

    if(strpos($checkout_id, 'sandbox_') !== false){
        $checkout_id = intval(str_replace('sandbox_', '', $checkout_id));
    }

    $order = new WC_Order($checkout_id);
    if(!$order) return;

    if($status === 'credited'){
        $order->payment_complete();
        $order->reduce_order_stock();

        $order->add_order_note("Seu pedido foi pago com sucesso!");
    }
    elseif($status === 'processing'){
        $order->update_status('on-hold', 'Aguardando confirmação');
        $order->add_order_note("Seu pedido está aguardando a confirmação do pagamento pela rede da criptomoeda utilizada.");
    }

    return rest_ensure_response($checkout_id);
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
            $this->icon = $this->get_option( 'icon' ) === 'yes' ? plugin_dir_url( __FILE__ ) . 'images/logo.svg' : null;
            $this->testmode = 'yes' === $this->get_option( 'testmode' );
            $this->merchant_id = $this->testmode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );
            $this->receiveType = $this->get_option( 'receiveType' );
            $this->convert = $this->get_option( 'convert' );
            $this->webhook = $this->get_option( 'webhook' );
            $this->coin = $this->get_option( 'coin' );
            $this->secret = $this->get_option( 'secret' );

            // Saves the settings
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options'] );
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
                'secret' => [
                    'title' => 'Secret',
                    'type' => 'text',
                    'description' => 'Chave secreta para autenticação do webhook',
                    'desc_tip' => true,
                ],
                'webhook' => [
                    'title' => 'Webhook',
                    'type' => 'title',
                    'description' => "O webhook para atualização de pedidos é: $domain/wp-json/brbtc_gateway/webhook",
                    'default' => 'brbtc_gateway'
                ],
                'testmode' => array(
                    'title'       => 'Sandbox',
                    'label'       => 'Ativar modo sandbox',
                    'type'        => 'checkbox',
                    'description' => 'Utiliza o ambiente de testes do gateway de pagamento.',
                    'default'     => 'yes',
                    'desc_tip'    => true,
                ),
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
                    'default'     => 'yes',
                    'desc_tip' => true,
                ),
                'title' => array(
                    'title'       => 'Título',
                    'type'        => 'text',
                    'description' => 'Escolha o título que irá aparecer para essa opção de pagamento.',
                    'default'     => 'Criptomoedas (Brasil Bitcoin Pay)',
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

            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url( $order )."&brbtcUrl=$url"
            );
        }
    }
}
