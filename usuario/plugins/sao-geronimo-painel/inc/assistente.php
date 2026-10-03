<?php
/**
 * Primeiros passos: instala os plugins necessários e explica frete e pagamento.
 *
 * @package sao-geronimo-painel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugins que a loja usa.
 *
 * @return array
 */
function sgp_plugins() {
	return array(
		'woocommerce' => array(
			'slug'      => 'woocommerce',
			'arquivo'   => 'woocommerce/woocommerce.php',
			'nome'      => 'WooCommerce',
			'papel'     => __( 'A loja em si: produtos, carrinho, pedidos e clientes. Sem ele, nada funciona.', 'sao-geronimo-painel' ),
			'essencial' => true,
		),
		'woocommerce-mercadopago' => array(
			'slug'      => 'woocommerce-mercadopago',
			'arquivo'   => 'woocommerce-mercadopago/woocommerce-mercadopago.php',
			'nome'      => __( 'Mercado Pago (oficial)', 'sao-geronimo-painel' ),
			'papel'     => __( 'Recebe por Pix, cartão e boleto. É o plugin oficial do próprio Mercado Pago.', 'sao-geronimo-painel' ),
			'essencial' => true,
			'config'    => 'admin.php?page=mercadopago-settings',
		),
		'melhor-envio-cotacao' => array(
			'slug'      => 'melhor-envio-cotacao',
			'arquivo'   => 'melhor-envio-cotacao/melhorenvio.php',
			'nome'      => __( 'Melhor Envio', 'sao-geronimo-painel' ),
			'papel'     => __( 'Cotação de frete de Correios, Jadlog, Azul e outras — sem precisar de contrato próprio.', 'sao-geronimo-painel' ),
			'essencial' => true,
			'config'    => 'admin.php?page=melhorenvio-settings',
		),
		'woocommerce-extra-checkout-fields-for-brazil' => array(
			'slug'      => 'woocommerce-extra-checkout-fields-for-brazil',
			'arquivo'   => 'woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php',
			'nome'      => __( 'Brazilian Market on WooCommerce', 'sao-geronimo-painel' ),
			'papel'     => __( 'Põe CPF/CNPJ, bairro, número e busca de CEP no checkout. O Mercado Pago precisa do CPF.', 'sao-geronimo-painel' ),
			'essencial' => true,
		),
	);
}

/**
 * Em que pé está um plugin.
 *
 * @param array $p Definição.
 * @return string instalado|ativo|ausente
 */
function sgp_estado_plugin( $p ) {
	if ( is_plugin_active( $p['arquivo'] ) ) {
		return 'ativo';
	}
	if ( file_exists( WP_PLUGIN_DIR . '/' . $p['arquivo'] ) ) {
		return 'instalado';
	}
	return 'ausente';
}

/**
 * Instala (e ativa) um plugin do repositório oficial.
 *
 * @param string $slug    Atalho do plugin.
 * @param string $arquivo Caminho do arquivo principal.
 * @return true|WP_Error
 */
function sgp_instala_plugin( $slug, $arquivo ) {
	if ( ! current_user_can( 'install_plugins' ) ) {
		return new WP_Error( 'sem_permissao', __( 'Seu usuário não pode instalar plugins.', 'sao-geronimo-painel' ) );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/misc.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
	require_once ABSPATH . 'wp-admin/includes/plugin.php';

	if ( ! file_exists( WP_PLUGIN_DIR . '/' . $arquivo ) ) {
		$info = plugins_api( 'plugin_information', array(
			'slug'   => $slug,
			'fields' => array( 'sections' => false ),
		) );
		if ( is_wp_error( $info ) ) {
			return $info;
		}

		$upgrader = new Plugin_Upgrader( new WP_Ajax_Upgrader_Skin() );
		$ok       = $upgrader->install( $info->download_link );

		if ( is_wp_error( $ok ) ) {
			return $ok;
		}
		if ( ! $ok ) {
			return new WP_Error( 'falhou', __( 'A instalação não concluiu. Pode ser permissão de pasta no servidor.', 'sao-geronimo-painel' ) );
		}
	}

	$ativa = activate_plugin( $arquivo );
	if ( is_wp_error( $ativa ) ) {
		return $ativa;
	}

	return true;
}

/**
 * Trata o pedido de instalação.
 */
function sgp_trata_instalacao() {
	if ( ! isset( $_POST['sgp_instalar'], $_POST['sgp_inst_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sgp_inst_nonce'] ) ), 'sgp_instalar' ) ) {
		return;
	}
	if ( ! current_user_can( 'install_plugins' ) ) {
		return;
	}

	$alvo    = sanitize_key( wp_unslash( $_POST['sgp_instalar'] ) );
	$plugins = sgp_plugins();
	if ( ! isset( $plugins[ $alvo ] ) ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	$res = sgp_instala_plugin( $plugins[ $alvo ]['slug'], $plugins[ $alvo ]['arquivo'] );

	set_transient(
		'sgp_inst_resultado',
		is_wp_error( $res )
			? array( 'erro', $plugins[ $alvo ]['nome'], $res->get_error_message() )
			: array( 'ok', $plugins[ $alvo ]['nome'], '' ),
		60
	);

	wp_safe_redirect( admin_url( 'admin.php?page=sg-assistente' ) );
	exit;
}
add_action( 'admin_init', 'sgp_trata_instalacao' );

/**
 * Tela de primeiros passos.
 */
function sgp_tela_assistente() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Sem permissão.', 'sao-geronimo-painel' ) );
	}
	require_once ABSPATH . 'wp-admin/includes/plugin.php';

	echo '<div class="wrap sgp">';
	sgp_cabecalho( __( 'Primeiros passos', 'sao-geronimo-painel' ), __( 'Faça na ordem. Cada passo leva poucos minutos.', 'sao-geronimo-painel' ) );

	$res = get_transient( 'sgp_inst_resultado' );
	if ( $res ) {
		delete_transient( 'sgp_inst_resultado' );
		if ( 'ok' === $res[0] ) {
			printf( '<div class="notice notice-success"><p>%s</p></div>', esc_html( sprintf( __( '%s instalado e ativado.', 'sao-geronimo-painel' ), $res[1] ) ) );
		} else {
			printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( sprintf( __( 'Não deu para instalar %1$s: %2$s', 'sao-geronimo-painel' ), $res[1], $res[2] ) ) );
		}
	}

	/* --------------------------------------------------- 1. plugins */
	echo '<section class="sgp-card"><h2>' . esc_html__( '1. Plugins necessários', 'sao-geronimo-painel' ) . '</h2>';
	echo '<p class="sgp-resumo">' . esc_html__( 'Todos são gratuitos e vêm do repositório oficial do WordPress. Clique em instalar e espere a página recarregar.', 'sao-geronimo-painel' ) . '</p>';
	echo '<table class="sgp-tabela sgp-tabela--plugins"><tbody>';

	foreach ( sgp_plugins() as $id => $p ) {
		$estado = sgp_estado_plugin( $p );
		echo '<tr><td class="sgp-plug">';
		echo '<b>' . esc_html( $p['nome'] ) . '</b><span>' . esc_html( $p['papel'] ) . '</span></td>';

		echo '<td class="sgp-plug__estado">';
		if ( 'ativo' === $estado ) {
			echo '<span class="sgp-pilula sgp-pilula--completed">' . esc_html__( 'ativo', 'sao-geronimo-painel' ) . '</span>';
		} elseif ( 'instalado' === $estado ) {
			echo '<span class="sgp-pilula sgp-pilula--on-hold">' . esc_html__( 'instalado, desligado', 'sao-geronimo-painel' ) . '</span>';
		} else {
			echo '<span class="sgp-pilula sgp-pilula--pending">' . esc_html__( 'falta instalar', 'sao-geronimo-painel' ) . '</span>';
		}
		echo '</td><td class="sgp-plug__acao">';

		if ( 'ativo' === $estado ) {
			if ( ! empty( $p['config'] ) ) {
				printf( '<a class="button" href="%s">%s</a>', esc_url( admin_url( $p['config'] ) ), esc_html__( 'Configurar', 'sao-geronimo-painel' ) );
			} else {
				echo '<span class="dashicons dashicons-yes-alt" style="color:#1a9b57"></span>';
			}
		} else {
			echo '<form method="post" style="margin:0">';
			wp_nonce_field( 'sgp_instalar', 'sgp_inst_nonce' );
			printf( '<input type="hidden" name="sgp_instalar" value="%s">', esc_attr( $id ) );
			printf(
				'<button class="button button-primary">%s</button>',
				esc_html( 'instalado' === $estado ? __( 'Ativar', 'sao-geronimo-painel' ) : __( 'Instalar e ativar', 'sao-geronimo-painel' ) )
			);
			echo '</form>';
			printf(
				'<a class="sgp-link-pequeno" href="https://wordpress.org/plugins/%s/" target="_blank" rel="noopener">%s</a>',
				esc_attr( $p['slug'] ),
				esc_html__( 'instalar à mão', 'sao-geronimo-painel' )
			);
		}
		echo '</td></tr>';
	}
	echo '</tbody></table></section>';

	/* ------------------------------------------- 2. dados da loja */
	$passos = array(
		array(
			__( '2. Dados da loja', 'sao-geronimo-painel' ),
			__( 'Endereço de origem das encomendas, moeda e unidades. Sem o CEP de origem, o frete não calcula.', 'sao-geronimo-painel' ),
			'admin.php?page=wc-settings',
			__( 'Abrir configurações do WooCommerce', 'sao-geronimo-painel' ),
		),
		array(
			__( '3. Identidade visual', 'sao-geronimo-painel' ),
			__( 'Logo, cores e letras do site.', 'sao-geronimo-painel' ),
			'admin.php?page=sg-painel-identidade',
			__( 'Abrir Identidade', 'sao-geronimo-painel' ),
		),
		array(
			__( '4. Importar os produtos', 'sao-geronimo-painel' ),
			__( 'Traz os 293 produtos do site antigo com fotos, preços, medidas e categorias.', 'sao-geronimo-painel' ),
			'admin.php?page=sg-importar',
			__( 'Abrir o importador', 'sao-geronimo-painel' ),
		),
		array(
			__( '5. Pagamento', 'sao-geronimo-painel' ),
			__( 'Ligar o Mercado Pago com as suas credenciais.', 'sao-geronimo-painel' ),
			'admin.php?page=sg-pagamentos',
			__( 'Ver o passo a passo', 'sao-geronimo-painel' ),
		),
		array(
			__( '6. Frete', 'sao-geronimo-painel' ),
			__( 'Ligar o Melhor Envio e, se quiser, cadastrar transportadora própria.', 'sao-geronimo-painel' ),
			'admin.php?page=sg-frete',
			__( 'Ver o passo a passo', 'sao-geronimo-painel' ),
		),
		array(
			__( '7. SEO', 'sao-geronimo-painel' ),
			__( 'Endereço, coordenadas, perguntas frequentes e os textos que o Google mostra.', 'sao-geronimo-painel' ),
			'admin.php?page=sg-seo',
			__( 'Abrir SEO', 'sao-geronimo-painel' ),
		),
		array(
			__( '8. Páginas e menu', 'sao-geronimo-painel' ),
			__( 'Crie as páginas de Sobre, Contato, Trocas e Privacidade, e monte o menu do topo.', 'sao-geronimo-painel' ),
			'nav-menus.php',
			__( 'Abrir os menus', 'sao-geronimo-painel' ),
		),
	);

	foreach ( $passos as $p ) {
		printf(
			'<section class="sgp-card sgp-card--passo"><h2>%s</h2><p>%s</p><a class="button button-secondary" href="%s">%s</a></section>',
			esc_html( $p[0] ), esc_html( $p[1] ), esc_url( admin_url( $p[2] ) ), esc_html( $p[3] )
		);
	}

	echo '</div>';
}

/* -------------------------------------------------------------------------
 * Tela de pagamentos
 * ---------------------------------------------------------------------- */

/**
 * Passo a passo do Mercado Pago.
 */
function sgp_tela_pagamentos() {
	if ( ! current_user_can( sgp_cap() ) ) {
		wp_die( esc_html__( 'Sem permissão.', 'sao-geronimo-painel' ) );
	}
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	$tem = is_plugin_active( 'woocommerce-mercadopago/woocommerce-mercadopago.php' );

	echo '<div class="wrap sgp">';
	sgp_cabecalho( __( 'Pagamentos', 'sao-geronimo-painel' ), __( 'Receber por Pix, cartão e boleto pelo Mercado Pago.', 'sao-geronimo-painel' ) );

	if ( ! $tem ) {
		printf(
			'<div class="sgp-card sgp-card--alerta"><p>%s</p><a class="button button-primary" href="%s">%s</a></div>',
			esc_html__( 'O plugin do Mercado Pago ainda não está ativo.', 'sao-geronimo-painel' ),
			esc_url( admin_url( 'admin.php?page=sg-assistente' ) ),
			esc_html__( 'Instalar agora', 'sao-geronimo-painel' )
		);
	}

	$passos = array(
		array(
			__( 'Entre no painel de desenvolvedor do Mercado Pago', 'sao-geronimo-painel' ),
			__( 'Acesse mercadopago.com.br/developers com a mesma conta que recebe o dinheiro da loja. É a conta da empresa, não a sua pessoal.', 'sao-geronimo-painel' ),
			'https://www.mercadopago.com.br/developers/panel',
			__( 'Abrir o Mercado Pago', 'sao-geronimo-painel' ),
		),
		array(
			__( 'Crie uma aplicação', 'sao-geronimo-painel' ),
			__( 'Em "Suas integrações", clique em Criar aplicação. Dê o nome da loja, escolha "Pagamentos online", modelo "CheckoutPro" ou "Checkout transparente", e plataforma WooCommerce.', 'sao-geronimo-painel' ),
			'', '',
		),
		array(
			__( 'Copie as quatro credenciais', 'sao-geronimo-painel' ),
			__( 'Dentro da aplicação, em "Credenciais de produção", copie o Public Key e o Access Token. Faça o mesmo em "Credenciais de teste". São quatro valores ao todo.', 'sao-geronimo-painel' ),
			'', '',
		),
		array(
			__( 'Cole no plugin', 'sao-geronimo-painel' ),
			__( 'Abra a configuração do Mercado Pago aqui no WordPress e cole cada credencial no campo correspondente. Preste atenção para não trocar produção com teste.', 'sao-geronimo-painel' ),
			$tem ? admin_url( 'admin.php?page=mercadopago-settings' ) : '',
			__( 'Abrir a configuração', 'sao-geronimo-painel' ),
		),
		array(
			__( 'Teste em modo sandbox', 'sao-geronimo-painel' ),
			__( 'Deixe o modo de teste ligado e faça uma compra de ponta a ponta com os cartões de teste do Mercado Pago. Confira se o pedido aparece em Pedidos e se o cliente recebe o e-mail.', 'sao-geronimo-painel' ),
			'https://www.mercadopago.com.br/developers/pt/docs/checkout-pro/additional-content/your-integrations/test/cards',
			__( 'Cartões de teste', 'sao-geronimo-painel' ),
		),
		array(
			__( 'Vire a chave para produção', 'sao-geronimo-painel' ),
			__( 'Desligue o modo de teste e faça uma compra real de valor baixo, com o seu próprio cartão. Confirme que o dinheiro caiu no Mercado Pago e estorne depois.', 'sao-geronimo-painel' ),
			'', '',
		),
		array(
			__( 'Escolha quais formas aceitar', 'sao-geronimo-painel' ),
			__( 'Dentro do plugin, cada meio (Pix, cartão, boleto) se liga e desliga separadamente. É lá também que se configura o parcelamento e o desconto no Pix que sai de verdade na conta.', 'sao-geronimo-painel' ),
			'', '',
		),
	);

	sgp_lista_passos( $passos );

	echo '<section class="sgp-card sgp-card--info"><h2>' . esc_html__( 'Por que aparecem dois valores de Pix?', 'sao-geronimo-painel' ) . '</h2>';
	echo '<p>' . esc_html__( 'No painel da loja, em "Loja", existe um campo de desconto no Pix. Aquilo é só o texto que o cliente lê na vitrine, para ele saber que compensa pagar no Pix. O desconto que realmente é aplicado no total vem do Mercado Pago. Mantenha os dois iguais.', 'sao-geronimo-painel' ) . '</p>';
	echo '<a class="button" href="' . esc_url( admin_url( 'admin.php?page=sg-painel-loja' ) ) . '">' . esc_html__( 'Ajustar o texto da vitrine', 'sao-geronimo-painel' ) . '</a></section>';

	echo '</div>';
}

/* -------------------------------------------------------------------------
 * Tela de frete
 * ---------------------------------------------------------------------- */

/**
 * Passo a passo do frete.
 */
function sgp_tela_frete() {
	if ( ! current_user_can( sgp_cap() ) ) {
		wp_die( esc_html__( 'Sem permissão.', 'sao-geronimo-painel' ) );
	}
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	$tem = is_plugin_active( 'melhor-envio-cotacao/melhorenvio.php' );

	echo '<div class="wrap sgp">';
	sgp_cabecalho( __( 'Frete e entrega', 'sao-geronimo-painel' ), __( 'Cotação automática, transportadora própria e retirada na loja.', 'sao-geronimo-painel' ) );

	echo '<section class="sgp-card sgp-card--info"><h2>' . esc_html__( 'Antes de tudo: o CEP de origem', 'sao-geronimo-painel' ) . '</h2>';
	echo '<p>' . esc_html__( 'Nenhuma cotação funciona sem o endereço de onde as encomendas saem, e sem peso e medidas em cada produto. Se um produto estiver sem peso, o frete dele vem errado ou nem aparece.', 'sao-geronimo-painel' ) . '</p>';
	printf(
		'<a class="button button-primary" href="%s">%s</a> <a class="button" href="%s">%s</a></section>',
		esc_url( admin_url( 'admin.php?page=wc-settings' ) ),
		esc_html__( 'Definir o endereço da loja', 'sao-geronimo-painel' ),
		esc_url( admin_url( 'edit.php?post_type=product' ) ),
		esc_html__( 'Conferir peso dos produtos', 'sao-geronimo-painel' )
	);

	if ( ! $tem ) {
		printf(
			'<div class="sgp-card sgp-card--alerta"><p>%s</p><a class="button button-primary" href="%s">%s</a></div>',
			esc_html__( 'O plugin do Melhor Envio ainda não está ativo.', 'sao-geronimo-painel' ),
			esc_url( admin_url( 'admin.php?page=sg-assistente' ) ),
			esc_html__( 'Instalar agora', 'sao-geronimo-painel' )
		);
	}

	echo '<h2 class="sgp-sub">' . esc_html__( 'Cotação automática (Melhor Envio)', 'sao-geronimo-painel' ) . '</h2>';
	sgp_lista_passos( array(
		array(
			__( 'Crie a conta no Melhor Envio', 'sao-geronimo-painel' ),
			__( 'A conta é gratuita. O Melhor Envio é um intermediário: ele cota e vende etiquetas de Correios, Jadlog, Azul, Loggi e outras, com desconto e sem exigir contrato com cada transportadora.', 'sao-geronimo-painel' ),
			'https://melhorenvio.com.br/', __( 'Abrir o Melhor Envio', 'sao-geronimo-painel' ),
		),
		array(
			__( 'Conecte o plugin à sua conta', 'sao-geronimo-painel' ),
			__( 'Na configuração do plugin, clique em autorizar. Abre uma janela do Melhor Envio pedindo permissão — confirme e volte. Não é preciso copiar token nenhum.', 'sao-geronimo-painel' ),
			$tem ? admin_url( 'admin.php?page=melhorenvio-settings' ) : '',
			__( 'Abrir a configuração', 'sao-geronimo-painel' ),
		),
		array(
			__( 'Escolha quais transportadoras cotar', 'sao-geronimo-painel' ),
			__( 'Ligue só as que você realmente usa. Quanto menos opções, mais rápido o checkout e menos o cliente se perde.', 'sao-geronimo-painel' ),
			'', '',
		),
		array(
			__( 'Acrescente folga de embalagem', 'sao-geronimo-painel' ),
			__( 'Nas opções do plugin dá para somar alguns dias ao prazo e uma margem ao valor, cobrindo caixa, plástico-bolha e o tempo de preparar o pedido.', 'sao-geronimo-painel' ),
			'', '',
		),
		array(
			__( 'Teste com um CEP distante', 'sao-geronimo-painel' ),
			__( 'Ponha um produto no carrinho e calcule para um CEP do Norte e outro vizinho. Se vier vazio, quase sempre é peso faltando no produto ou CEP de origem em branco.', 'sao-geronimo-painel' ),
			'', '',
		),
	) );

	echo '<h2 class="sgp-sub">' . esc_html__( 'Transportadora própria, frete fixo e grátis', 'sao-geronimo-painel' ) . '</h2>';
	echo '<section class="sgp-card">';
	echo '<p>' . esc_html__( 'Isso é nativo do WooCommerce e não depende de plugin nenhum. Funciona por "zonas": você cria uma região e diz quanto cobra nela.', 'sao-geronimo-painel' ) . '</p>';
	echo '<ol class="sgp-passos-simples">';
	$itens = array(
		__( 'Vá em WooCommerce → Configurações → Entrega.', 'sao-geronimo-painel' ),
		__( 'Clique em "Adicionar zona de entrega" e dê um nome, como "Grande Florianópolis" ou "Transportadora Sul".', 'sao-geronimo-painel' ),
		__( 'Escolha as regiões: pode ser por estado, ou colar uma lista de CEPs e faixas (ex.: 88000...88999).', 'sao-geronimo-painel' ),
		__( 'Em "Adicionar método", escolha: Taxa fixa (você digita o valor), Entrega gratuita (com ou sem valor mínimo) ou Retirada local.', 'sao-geronimo-painel' ),
		__( 'Para frete grátis acima de um valor, use "Entrega gratuita" e marque "Um pedido de valor mínimo".', 'sao-geronimo-painel' ),
		__( 'A ordem importa: a primeira zona que bate com o CEP do cliente é a que vale. Deixe as específicas em cima e a geral embaixo.', 'sao-geronimo-painel' ),
	);
	foreach ( $itens as $i ) {
		echo '<li>' . esc_html( $i ) . '</li>';
	}
	echo '</ol>';
	printf(
		'<a class="button button-primary" href="%s">%s</a>',
		esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping' ) ),
		esc_html__( 'Abrir as zonas de entrega', 'sao-geronimo-painel' )
	);
	echo '</section>';

	echo '<section class="sgp-card sgp-card--info"><h2>' . esc_html__( 'E os Correios direto?', 'sao-geronimo-painel' ) . '</h2>';
	echo '<p>' . esc_html__( 'Os Correios encerraram a cotação aberta: hoje é preciso contrato e código administrativo para usar a API deles. Se você já tem contrato, dá para instalar um plugin específico de Correios e usar em paralelo ao Melhor Envio. Se não tem, o Melhor Envio já cota PAC e SEDEX e costuma sair mais barato que o balcão.', 'sao-geronimo-painel' ) . '</p></section>';

	echo '</div>';
}

/**
 * Desenha uma lista de passos numerados.
 *
 * @param array $passos Passos.
 */
function sgp_lista_passos( $passos ) {
	echo '<ol class="sgp-passos">';
	foreach ( $passos as $p ) {
		echo '<li><div><b>' . esc_html( $p[0] ) . '</b><p>' . esc_html( $p[1] ) . '</p>';
		if ( ! empty( $p[2] ) ) {
			$externo = 0 === strpos( $p[2], 'http' ) && false === strpos( $p[2], admin_url() );
			printf(
				'<a class="button button-small" href="%s"%s>%s%s</a>',
				esc_url( $p[2] ),
				$externo ? ' target="_blank" rel="noopener"' : '',
				esc_html( $p[3] ),
				$externo ? ' ↗' : ''
			);
		}
		echo '</div></li>';
	}
	echo '</ol>';
}
