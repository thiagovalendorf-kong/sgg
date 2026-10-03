<?php
/**
 * Pagamento e frete: tudo o que precisa estar pronto para vender no Brasil.
 *
 * - Deixa a loja em Real (R$), com vírgula nos centavos e endereço brasileiro.
 * - Mostra quais plugins faltam (WooCommerce, Mercado Pago, Melhor Envio, CPF/CNPJ)
 *   com botão de instalar e ativar.
 * - Guia de configuração do checkout transparente do Mercado Pago.
 *
 * @package sao-geronimo-central
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugins necessários.
 *
 * @return array
 */
function sgc_plugins_necessarios() {
	return array(
		'woocommerce' => array(
			'nome' => 'WooCommerce', 'pasta' => 'woocommerce',
			'para' => 'A loja em si: produtos, carrinho, pedidos e clientes.',
			'config' => admin_url( 'admin.php?page=wc-settings' ), 'config_txt' => 'Abrir configurações',
		),
		'mercadopago' => array(
			'nome' => 'Mercado Pago (pagamentos)', 'pasta' => 'woocommerce-mercadopago',
			'para' => 'Cartão, Pix e boleto com checkout transparente (o cliente paga sem sair do seu site).',
			'config' => admin_url( 'admin.php?page=wc-settings&tab=checkout' ), 'config_txt' => 'Configurar pagamentos',
		),
		'brasil' => array(
			'nome' => 'Brazilian Market (CPF/CNPJ, número, bairro)', 'pasta' => 'woocommerce-extra-checkout-fields-for-brazil',
			'para' => 'Campos que o checkout brasileiro e o Mercado Pago exigem: CPF/CNPJ, número e bairro.',
			'config' => admin_url( 'admin.php?page=wc-settings&tab=wcbcf' ), 'config_txt' => 'Configurar campos',
		),
		'melhorenvio' => array(
			'nome' => 'Melhor Envio (frete Correios e transportadoras)', 'pasta' => 'melhor-envio-cotacao',
			'para' => 'Cotação de frete dos Correios e de transportadoras, etiquetas e rastreio.',
			'config' => admin_url( 'admin.php?page=wc-settings&tab=shipping' ), 'config_txt' => 'Configurar frete',
		),
	);
}

/**
 * Estado de um plugin: instalado/ativo e o arquivo principal.
 *
 * @param string $pasta Pasta do plugin.
 * @return array
 */
function sgc_estado_plugin( $pasta ) {
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	foreach ( get_plugins() as $arquivo => $dados ) {
		if ( 0 === strpos( $arquivo, $pasta . '/' ) ) {
			return array( 'instalado' => true, 'ativo' => is_plugin_active( $arquivo ), 'arquivo' => $arquivo );
		}
	}
	return array( 'instalado' => false, 'ativo' => false, 'arquivo' => '' );
}

/**
 * Padrão brasileiro da loja.
 *
 * @return array Lista do que foi alterado.
 */
function sgc_aplicar_brasil() {
	$ops = array(
		'woocommerce_currency'                => 'BRL',
		'woocommerce_currency_pos'            => 'left_space',
		'woocommerce_price_thousand_sep'      => '.',
		'woocommerce_price_decimal_sep'       => ',',
		'woocommerce_price_num_decimals'      => 2,
		'woocommerce_default_country'         => 'BR:SC',
		'woocommerce_allowed_countries'       => 'specific',
		'woocommerce_specific_allowed_countries' => array( 'BR' ),
		'woocommerce_weight_unit'             => 'kg',
		'woocommerce_dimension_unit'          => 'cm',
		'timezone_string'                     => 'America/Sao_Paulo',
		'date_format'                         => 'd/m/Y',
		'time_format'                         => 'H:i',
		'start_of_week'                       => 0,
	);
	$mudou = array();
	foreach ( $ops as $k => $v ) {
		if ( get_option( $k ) !== $v ) {
			update_option( $k, $v );
			$mudou[] = $k;
		}
	}
	if ( '/%postname%/' !== get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
		flush_rewrite_rules( false );
		$mudou[] = 'permalink_structure';
	}
	delete_transient( 'sg_faixa_precos' );
	update_option( 'sgc_brasil_aplicado', 1 );
	return $mudou;
}

/**
 * Se o WooCommerce está em dólar (ou outro país), coloca em Real uma vez só.
 */
function sgc_brasil_automatico() {
	if ( get_option( 'sgc_brasil_aplicado' ) || ! class_exists( 'WooCommerce' ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( 'BRL' !== get_option( 'woocommerce_currency', 'USD' ) || 'BR' !== substr( (string) get_option( 'woocommerce_default_country', '' ), 0, 2 ) ) {
		sgc_aplicar_brasil();
	} else {
		update_option( 'sgc_brasil_aplicado', 1 );
	}
}
add_action( 'admin_init', 'sgc_brasil_automatico' );

/**
 * Botão "Aplicar padrão Brasil".
 */
function sgc_acao_brasil() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Sem permissão.' );
	}
	check_admin_referer( 'sgc_brasil' );
	sgc_aplicar_brasil();
	wp_safe_redirect( admin_url( 'admin.php?page=sgc-integracoes&feito=brasil' ) );
	exit;
}
add_action( 'admin_post_sgc_brasil', 'sgc_acao_brasil' );

/**
 * A página "Pagamento e frete".
 */
function sgc_pagina_integracoes() {
	$moeda   = get_option( 'woocommerce_currency', '' );
	$pais    = get_option( 'woocommerce_default_country', '' );
	$brasil  = 'BRL' === $moeda && 'BR' === substr( (string) $pais, 0, 2 );
	$https   = is_ssl();
	$woo_ok  = class_exists( 'WooCommerce' );
	?>
	<div class="wrap sgc-loja">
		<h1>Pagamento e frete</h1>
		<p class="sgc-loja__sub">Deixe a loja pronta para vender: moeda, Mercado Pago, Correios e transportadoras.</p>

		<?php if ( isset( $_GET['feito'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="sgc-ok">✅ Pronto! A loja agora está em Real (R$), com endereço e medidas do Brasil.</div>
		<?php endif; ?>

		<h2 class="sgc-loja__tit">1. Loja no padrão Brasil</h2>
		<div class="sgc-passo">
			<ul class="sgc-checks">
				<li class="<?php echo $brasil ? 'ok' : 'no'; ?>">Moeda: <b><?php echo esc_html( $moeda ? $moeda : 'não definida' ); ?></b> <?php echo $brasil ? '' : '— deveria ser Real (BRL)'; ?></li>
				<li class="<?php echo $brasil ? 'ok' : 'no'; ?>">País da loja: <b><?php echo esc_html( $pais ? $pais : 'não definido' ); ?></b></li>
				<li class="<?php echo $https ? 'ok' : 'no'; ?>">Site com cadeado (HTTPS): <b><?php echo $https ? 'sim' : 'não'; ?></b> <?php echo $https ? '' : '— o Mercado Pago exige. Peça à hospedagem para ativar o SSL gratuito.'; ?></li>
			</ul>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="sgc_brasil">
				<?php wp_nonce_field( 'sgc_brasil' ); ?>
				<button type="submit" class="sgc-bt sgc-bt--principal">Aplicar padrão Brasil</button>
				<span class="sgc-nota">Define Real (R$ 1.234,56), Brasil como único país, kg e cm, fuso de São Paulo e links bonitos.</span>
			</form>
		</div>

		<h2 class="sgc-loja__tit">2. Plugins necessários</h2>
		<div class="sgc-plugins">
			<?php
			foreach ( sgc_plugins_necessarios() as $id => $pl ) :
				$e = sgc_estado_plugin( $pl['pasta'] );
				?>
				<div class="sgc-plugin <?php echo $e['ativo'] ? 'ok' : ''; ?>">
					<div>
						<b><?php echo esc_html( $pl['nome'] ); ?></b>
						<p><?php echo esc_html( $pl['para'] ); ?></p>
					</div>
					<div class="sgc-plugin__acao">
						<?php if ( $e['ativo'] ) : ?>
							<span class="sgc-selo-ok">Ativo ✓</span>
							<a class="sgc-bt" href="<?php echo esc_url( $pl['config'] ); ?>"><?php echo esc_html( $pl['config_txt'] ); ?></a>
						<?php elseif ( $e['instalado'] ) : ?>
							<a class="sgc-bt sgc-bt--principal" href="<?php echo esc_url( wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( $e['arquivo'] ) ), 'activate-plugin_' . $e['arquivo'] ) ); ?>">Ativar</a>
						<?php else : ?>
							<a class="sgc-bt sgc-bt--principal" href="<?php echo esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $pl['pasta'] ), 'install-plugin_' . $pl['pasta'] ) ); ?>">Instalar</a>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<h2 class="sgc-loja__tit">3. Checkout transparente do Mercado Pago</h2>
		<div class="sgc-passo">
			<p>No checkout transparente o cliente paga <b>dentro do seu site</b>, sem ser levado ao Mercado Pago. Para ligar:</p>
			<ol class="sgc-passos">
				<li>Entre em <a href="https://www.mercadopago.com.br/developers/panel/app" target="_blank" rel="noopener">mercadopago.com.br/developers</a> e crie uma <b>aplicação</b> do tipo "Pagamentos online → Checkout Transparente".</li>
				<li>Copie as credenciais de <b>produção</b>: <i>Public Key</i> e <i>Access Token</i>. (Use as de <b>teste</b> primeiro para ensaiar.)</li>
				<li>Aqui no site: <?php if ( $woo_ok ) : ?><a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ); ?>">Gestão → Configurações da loja → Pagamentos</a><?php else : ?>Gestão → Configurações da loja → Pagamentos<?php endif; ?> → <b>Mercado Pago</b> → cole as credenciais.</li>
				<li>Ligue <b>Cartão de crédito (Checkout Transparente)</b>, <b>Pix</b> e, se quiser, <b>Boleto</b>.</li>
				<li>Confirme que o <b>Brazilian Market</b> está ativo: ele coloca CPF/CNPJ, número e bairro no checkout, que o Mercado Pago exige.</li>
				<li>Faça uma compra de teste com o cartão de teste do Mercado Pago e confira se o pedido muda para "Processando" no painel.</li>
			</ol>
			<p class="sgc-nota">Os nomes das telas podem variar um pouco conforme a versão do plugin do Mercado Pago.</p>
		</div>

		<h2 class="sgc-loja__tit">4. Frete: Correios e transportadoras</h2>
		<div class="sgc-passo">
			<ol class="sgc-passos">
				<li><b>Correios e transportadoras:</b> instale e ative o <b>Melhor Envio</b> (acima), crie a conta em <a href="https://melhorenvio.com.br" target="_blank" rel="noopener">melhorenvio.com.br</a> e conecte pelo botão "Configurar frete". Ele calcula PAC, SEDEX e transportadoras no carrinho.</li>
				<li><b>Preencha peso e medidas de cada produto</b> (aba "Entrega" do produto). Sem isso o cálculo não funciona. Os produtos importados já trazem as medidas.</li>
				<li><b>Adicionar uma transportadora sua:</b> Gestão → Configurações da loja → <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping' ) ); ?>">Entrega</a> → escolha uma região → "Adicionar método de entrega" → <i>Taxa fixa</i> (valor que você define), <i>Frete grátis</i> ou <i>Retirada local</i>. Pode criar quantos quiser, um por transportadora.</li>
				<li><b>Frete grátis acima de um valor:</b> crie o método "Frete grátis" e escolha "valor mínimo do pedido". A barrinha do carrinho é configurada em <a href="<?php echo esc_url( admin_url( 'admin.php?page=sgc-central#carrinho' ) ); ?>">Meu Site → Carrinho</a>.</li>
			</ol>
		</div>
	</div>
	<?php
}
