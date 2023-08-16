# Plugin Brasil Bitcoin Pay para WooCommerce
## Introdução
Este é o plugin de implementação do Gateway de Pagamentos Brasil Bitcoin Pay. É necessário entrar em contato com um consultor para solicitar o serviço.

### Requisitos
É necessário ter uma conta na [Brasil Bitcoin](https://brasilbitcoin.com.br), com a opção Brasil Bitcoin Pay habilitada.

#### IMPORTANTE
Nós nunca entraremos em contato com você para oferecer investimentos, promessas de lucros ou qualquer coisa do gênero, nunca pediremos seu token 2FA, nem sua senha ou chave API. Você pode consultar todos os nossos canais oficiais [clicando aqui](https://brasilbitcoin.com.br/verificar-autenticidade).

## Download
Baixe o plugin [clicando aqui](https://github.com/brbtcoficial/brbtc-gateway/archive/refs/heads/main.zip).

## Instalação
1. Adicione o plugin ao seu WordPress;
2. Acesse as `Configurações` do WooCommerce, e na aba de `Pagamentos`, ative o plugin Brasil Bitcoin Pay;
3. Defina as configurações do plugin para começar usar;
4. Realize testes em sanbox;

## Configuração
| Configuração       |Descrição                                | Default       |
| ------------------ | --------------------------------------- | ------------- |
| Ativar/Desativar   |Ativar ou desativar o método de pagamento| `enabled`      |
| Merchant ID        |[Obrigatório] Número de ID do lojista.                 | `undefined`  |
| Secret | [Obrigatório] Chave de autenticação para ativar o webook.| `undefined`|
| Webhook            |Mostra o link de webhook para atualiar o status do pedido após notificação de recebimento e confirmação de recebimento|`https://seu-site.com.br/wp-json/brbtc_gateway/webhook`|
| Sandbox            |Se ativado, um botão para simular o pagamento aparecerá no iframe, esse botão acionará o webhook. |`disabled`|
| Ícone | Mostra o ícone da Brasil Bitcoin Pay na seleção de métodos de pagamento| `enabled`|
| Título | Título do método de pagamento que será exibido para o cliente. |`Criptomoedas (Brasil Bitcoin Pay)`|
| Descrição | Descrição do método de pagamento, que será mostrado para o cliente ao selecioná-lo.| `Pague utilizando as principais criptomoedas do mercado.`|
| Preços em | Define se os preços mostrados para os clientes estão em Real, ou em Cripto. | `Real` |
| Converter | Ativa a conversão automática dos valores recebidos para fiat (Real)| `enabled` |
| Moeda | Defina a moeda que será aceita em sua plataforma, ou deixe todas selecionadas, para que o cliente escolha no ato do pagamento. | `Todas as moedas`|
| Tamanho do QR Code | Defina o tamanho (em pixels) do QR Code que será exibido para o cliente.| `120` |
| Cor do texto | Cor do texto do iframe em hex color. | `6b6b6b`|
| Cor de fundo | Cor do background do iframe em hex color. | `f0f0f0` |
| Texto do seletor de moedas | Cor do texto do seletor de moedas do iframe em hex color. | `242424` |
| Fundo do seletor de moedas | Cor do background do seletor de moedas do iframe em hex color. | `e3e3e3` |
| Cor do texto do botão | Cor do texto do botão em hex color. | `ffffff` |
| Cor do botão | Cor do background do botão do iframe em hex color. | `00b9fc` |

## Webhook
O webhook padrão será `https://seu-site.com.br/wp-json/brbtc_gateway/webhook`, mas pdoe ser alterado para qualquer valor desejado (feature será liberada em momento oportuno).

Após a instalação e configuração, você poderá acessar o iframe para pagamento após o encerramento do pedido, basta adicionar o shortcode `[brbtc_gateway_iframe]` na tela de finalização de compra, ou onde desejar.
