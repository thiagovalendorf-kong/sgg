<?php
/**
 * Importador do catálogo do site antigo.
 *
 * Lê um arquivo catalogo.json e uma pasta de imagens, e cria os produtos no
 * WooCommerce. Trabalha em lotes pequenos, por AJAX, para não estourar o tempo
 * limite do servidor em hospedagem compartilhada.
 *
 * @package sao-geronimo-painel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pasta onde o conteúdo de importação deve ficar.
 *
 * @return string
 */
function sgp_pasta_import() {
	$up = wp_upload_dir();
	return trailingslashit( $up['basedir'] ) . 'sg-importacao';
}

/**
 * Lê o catálogo, se existir.
 *
 * @return array|WP_Error
 */
function sgp_le_catalogo() {
	$arq = sgp_pasta_import() . '/catalogo.json';
	if ( ! file_exists( $arq ) ) {
		return new WP_Error( 'sem_arquivo', __( 'Não achei o catalogo.json.', 'sao-geronimo-painel' ) );
	}
	$dados = json_decode( file_get_contents( $arq ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! is_array( $dados ) || empty( $dados['produtos'] ) ) {
		return new WP_Error( 'invalido', __( 'O catalogo.json está num formato que não reconheço.', 'sao-geronimo-painel' ) );
	}
	return $dados;
}

/**
 * Endereço do site antigo, de onde as fotos podem ser baixadas.
 *
 * @return string
 */
function sgp_site_antigo() {
	$u = get_option( 'sgp_site_antigo', '' );
	return $u ? trailingslashit( $u ) : '';
}

/**
 * Recebe um zip enviado pelo próprio painel e descompacta no lugar certo.
 *
 * Evita que o lojista tenha de abrir cPanel ou FTP para pôr os arquivos na pasta.
 */
function sgp_trata_upload_import() {
	if ( ! isset( $_POST['sgp_up_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sgp_up_nonce'] ) ), 'sgp_upload_import' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( empty( $_FILES['sgp_zip']['name'] ) ) {
		set_transient( 'sgp_up_msg', array( 'erro', __( 'Nenhum arquivo escolhido.', 'sao-geronimo-painel' ) ), 60 );
		wp_safe_redirect( admin_url( 'admin.php?page=sg-importar' ) );
		exit;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	WP_Filesystem();

	$arquivo = array(
		'name'     => sanitize_file_name( wp_unslash( $_FILES['sgp_zip']['name'] ) ),
		'type'     => isset( $_FILES['sgp_zip']['type'] ) ? sanitize_text_field( wp_unslash( $_FILES['sgp_zip']['type'] ) ) : '',
		'tmp_name' => isset( $_FILES['sgp_zip']['tmp_name'] ) ? $_FILES['sgp_zip']['tmp_name'] : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		'error'    => isset( $_FILES['sgp_zip']['error'] ) ? (int) $_FILES['sgp_zip']['error'] : 0,
		'size'     => isset( $_FILES['sgp_zip']['size'] ) ? (int) $_FILES['sgp_zip']['size'] : 0,
	);

	if ( 'zip' !== strtolower( pathinfo( $arquivo['name'], PATHINFO_EXTENSION ) ) ) {
		set_transient( 'sgp_up_msg', array( 'erro', __( 'O arquivo precisa ser um .zip.', 'sao-geronimo-painel' ) ), 60 );
		wp_safe_redirect( admin_url( 'admin.php?page=sg-importar' ) );
		exit;
	}

	$movido = wp_handle_upload( $arquivo, array( 'test_form' => false, 'mimes' => array( 'zip' => 'application/zip' ) ) );

	if ( isset( $movido['error'] ) ) {
		set_transient( 'sgp_up_msg', array( 'erro', $movido['error'] ), 60 );
		wp_safe_redirect( admin_url( 'admin.php?page=sg-importar' ) );
		exit;
	}

	$up      = wp_upload_dir();
	$destino = trailingslashit( $up['basedir'] );
	$ok      = unzip_file( $movido['file'], $destino );

	wp_delete_file( $movido['file'] );

	if ( is_wp_error( $ok ) ) {
		set_transient( 'sgp_up_msg', array( 'erro', $ok->get_error_message() ), 60 );
	} else {
		set_transient( 'sgp_up_msg', array( 'ok', __( 'Arquivo recebido e descompactado.', 'sao-geronimo-painel' ) ), 60 );
	}

	wp_safe_redirect( admin_url( 'admin.php?page=sg-importar' ) );
	exit;
}
add_action( 'admin_init', 'sgp_trata_upload_import' );

/**
 * Tela do importador.
 */
function sgp_tela_importar() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Sem permissão.', 'sao-geronimo-painel' ) );
	}

	echo '<div class="wrap sgp">';
	sgp_cabecalho( __( 'Importar produtos', 'sao-geronimo-painel' ), __( 'Traz o catálogo do site antigo para o WooCommerce.', 'sao-geronimo-painel' ) );

	if ( ! function_exists( 'wc_get_product' ) ) {
		echo '<div class="sgp-card sgp-card--alerta"><p>' . esc_html__( 'Ative o WooCommerce antes de importar.', 'sao-geronimo-painel' ) . '</p></div></div>';
		return;
	}

	$cat    = sgp_le_catalogo();
	$pasta  = sgp_pasta_import();
	$existe = ! is_wp_error( $cat );

	/* ------------------------------------------------ onde pôr os arquivos */
	$msg = get_transient( 'sgp_up_msg' );
	if ( $msg ) {
		delete_transient( 'sgp_up_msg' );
		printf(
			'<div class="notice notice-%s"><p>%s</p></div>',
			'ok' === $msg[0] ? 'success' : 'error',
			esc_html( $msg[1] )
		);
	}

	echo '<section class="sgp-card"><h2>' . esc_html__( '1. O arquivo do catálogo', 'sao-geronimo-painel' ) . '</h2>';

	if ( $existe ) {
		$n_prod = count( $cat['produtos'] );
		$n_img  = is_dir( $pasta . '/imagens' ) ? iterator_count( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $pasta . '/imagens', FilesystemIterator::SKIP_DOTS ) ) ) : 0;
		printf(
			'<div class="sgp-ok-caixa"><b>%s</b><p>%s</p></div>',
			esc_html__( 'Catálogo encontrado.', 'sao-geronimo-painel' ),
			esc_html( sprintf(
				/* translators: 1: produtos, 2: imagens */
				__( '%1$d produtos. Imagens já no servidor: %2$d.', 'sao-geronimo-painel' ),
				$n_prod,
				$n_img
			) )
		);
	} else {
		echo '<p>' . esc_html__( 'Envie aqui o arquivo conteudo-sao-geronimo.zip. Ele tem só 150 KB — as fotos vêm depois, direto do site antigo.', 'sao-geronimo-painel' ) . '</p>';

		echo '<form method="post" enctype="multipart/form-data" class="sgp-upload">';
		wp_nonce_field( 'sgp_upload_import', 'sgp_up_nonce' );
		echo '<input type="file" name="sgp_zip" accept=".zip" required> ';
		echo '<button class="button button-primary">' . esc_html__( 'Enviar arquivo', 'sao-geronimo-painel' ) . '</button>';
		echo '</form>';

		echo '<p class="sgp-dica">' . esc_html__( 'Se o envio falhar por limite do servidor, dá para pôr o arquivo à mão: pelo Gerenciador de Arquivos do cPanel, entre nesta pasta e extraia o zip ali.', 'sao-geronimo-painel' ) . '</p>';
		echo '<code class="sgp-caminho">' . esc_html( str_replace( ABSPATH, '', trailingslashit( dirname( $pasta ) ) ) ) . '</code>';
	}
	echo '</section>';

	if ( ! $existe ) {
		echo '</div>';
		return;
	}

	/* ----------------------------------------------------------- opções */
	$feitos = get_option( 'sgp_import_feitos', array() );
	?>
	<section class="sgp-card"><h2><?php esc_html_e( '2. Como importar', 'sao-geronimo-painel' ); ?></h2>

		<div class="sgp-linha">
			<div class="sgp-rotulo"><label><?php esc_html_e( 'Se o produto já existir (mesma referência)', 'sao-geronimo-painel' ); ?></label></div>
			<div class="sgp-controle">
				<select id="sgp-imp-repetido">
					<option value="pular"><?php esc_html_e( 'Pular — não mexer no que já está lá', 'sao-geronimo-painel' ); ?></option>
					<option value="atualizar"><?php esc_html_e( 'Atualizar preço, descrição e medidas', 'sao-geronimo-painel' ); ?></option>
				</select>
			</div>
		</div>

		<div class="sgp-linha">
			<div class="sgp-rotulo"><label><?php esc_html_e( 'Imagens', 'sao-geronimo-painel' ); ?></label></div>
			<div class="sgp-controle">
				<label class="sgp-liga"><input type="checkbox" id="sgp-imp-imagens" checked><span class="sgp-liga__pino"></span>
					<span class="sgp-liga__txt"><?php esc_html_e( 'Importar as fotos junto', 'sao-geronimo-painel' ); ?></span></label>
				<p class="sgp-dica"><?php esc_html_e( 'É a parte demorada. Se der erro de tempo, desmarque, importe só os dados e rode de novo com as imagens.', 'sao-geronimo-painel' ); ?></p>
			</div>
		</div>

		<div class="sgp-linha">
			<div class="sgp-rotulo"><label for="sgp-imp-site"><?php esc_html_e( 'Buscar fotos que faltarem em', 'sao-geronimo-painel' ); ?></label>
				<p class="sgp-dica"><?php esc_html_e( 'Deixe vazio — todas as fotos já vêm no arquivo. Só preencha se tiver um site antigo no ar de onde baixar o que faltar.', 'sao-geronimo-painel' ); ?></p></div>
			<div class="sgp-controle">
				<input type="url" id="sgp-imp-site" value="<?php echo esc_attr( sgp_site_antigo() ); ?>" placeholder="<?php esc_attr_e( 'nenhum', 'sao-geronimo-painel' ); ?>" style="max-width:420px">
			</div>
		</div>

		<div class="sgp-linha">
			<div class="sgp-rotulo"><label><?php esc_html_e( 'Situação dos produtos', 'sao-geronimo-painel' ); ?></label></div>
			<div class="sgp-controle">
				<select id="sgp-imp-status">
					<option value="publish"><?php esc_html_e( 'Publicados, visíveis na loja', 'sao-geronimo-painel' ); ?></option>
					<option value="draft"><?php esc_html_e( 'Rascunho, para eu revisar antes', 'sao-geronimo-painel' ); ?></option>
				</select>
			</div>
		</div>

		<div class="sgp-linha">
			<div class="sgp-rotulo"><label><?php esc_html_e( 'Quantos por vez', 'sao-geronimo-painel' ); ?></label>
				<p class="sgp-dica"><?php esc_html_e( 'Se o servidor for lento, baixe para 3.', 'sao-geronimo-painel' ); ?></p></div>
			<div class="sgp-controle"><input type="number" id="sgp-imp-lote" value="5" min="1" max="20" class="sgp-curto"></div>
		</div>

		<?php if ( $feitos ) : ?>
			<div class="sgp-aviso">
				<?php
				printf(
					esc_html__( 'Já importamos %d produtos antes. Eles serão pulados automaticamente.', 'sao-geronimo-painel' ),
					count( $feitos )
				);
				?>
				<button type="button" class="button-link" id="sgp-imp-zerar"><?php esc_html_e( 'esquecer e começar do zero', 'sao-geronimo-painel' ); ?></button>
			</div>
		<?php endif; ?>

		<div class="sgp-salvar">
			<button type="button" class="button button-primary button-hero" id="sgp-imp-comecar"><?php esc_html_e( 'Começar a importar', 'sao-geronimo-painel' ); ?></button>
			<span class="sgp-salvar__dica"><?php esc_html_e( 'Não feche esta aba enquanto roda.', 'sao-geronimo-painel' ); ?></span>
		</div>
	</section>

	<section class="sgp-card" id="sgp-imp-painel" hidden>
		<h2><?php esc_html_e( 'Andamento', 'sao-geronimo-painel' ); ?></h2>
		<div class="sgp-progresso"><span id="sgp-imp-barra" style="width:0"></span></div>
		<p id="sgp-imp-texto" class="sgp-resumo"></p>
		<div id="sgp-imp-log" class="sgp-log"></div>
	</section>
	</div>

	<script>
	(function () {
		const $ = (s) => document.querySelector(s);
		const total = <?php echo (int) count( $cat['produtos'] ); ?>;
		const nonce = <?php echo wp_json_encode( wp_create_nonce( 'sgp_importar' ) ); ?>;
		const ajax = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
		let parado = false;

		$('#sgp-imp-zerar')?.addEventListener('click', async () => {
			if (!confirm(<?php echo wp_json_encode( __( 'Esquecer o que já foi importado? Os produtos continuam na loja; só perdemos a lista de controle.', 'sao-geronimo-painel' ) ); ?>)) return;
			await fetch(ajax + '?action=sgp_importar_zerar&nonce=' + nonce, { credentials: 'same-origin' });
			location.reload();
		});

		$('#sgp-imp-comecar').addEventListener('click', async function () {
			this.disabled = true;
			$('#sgp-imp-painel').hidden = false;
			$('#sgp-imp-painel').scrollIntoView({ behavior: 'smooth', block: 'nearest' });

			const opcoes = {
				repetido: $('#sgp-imp-repetido').value,
				imagens: $('#sgp-imp-imagens').checked ? '1' : '0',
				status: $('#sgp-imp-status').value,
				lote: $('#sgp-imp-lote').value,
				site: $('#sgp-imp-site').value.trim(),
			};

			let inicio = 0, criados = 0, pulados = 0, erros = 0;

			while (!parado && inicio < total) {
				let dados;
				try {
					const r = await fetch(ajax, {
						method: 'POST',
						credentials: 'same-origin',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
						body: new URLSearchParams(Object.assign({ action: 'sgp_importar', nonce, inicio }, opcoes)),
					});
					dados = await r.json();
				} catch (e) {
					log('<b class="erro">' + <?php echo wp_json_encode( __( 'A conexão caiu. Recarregue a página e clique em importar de novo — continuamos de onde parou.', 'sao-geronimo-painel' ) ); ?> + '</b>');
					break;
				}

				if (!dados || !dados.success) {
					log('<b class="erro">' + ((dados && dados.data) || 'erro') + '</b>');
					break;
				}

				const d = dados.data;
				criados += d.criados; pulados += d.pulados; erros += d.erros;
				inicio = d.proximo;

				d.linhas.forEach(log);
				const pct = Math.round(inicio / total * 100);
				$('#sgp-imp-barra').style.width = pct + '%';
				$('#sgp-imp-texto').textContent =
					pct + '% · ' + inicio + ' de ' + total + ' · ' +
					criados + <?php echo wp_json_encode( ' ' . __( 'criados', 'sao-geronimo-painel' ) ); ?> + ', ' +
					pulados + <?php echo wp_json_encode( ' ' . __( 'pulados', 'sao-geronimo-painel' ) ); ?> + ', ' +
					erros + <?php echo wp_json_encode( ' ' . __( 'com erro', 'sao-geronimo-painel' ) ); ?>;

				if (d.fim) break;
			}

			$('#sgp-imp-barra').style.width = '100%';
			log('<b class="ok">' + <?php echo wp_json_encode( __( 'Terminou.', 'sao-geronimo-painel' ) ); ?> + '</b>');
			this.disabled = false;
		});

		function log(html) {
			const d = document.createElement('div');
			d.innerHTML = html;
			$('#sgp-imp-log').appendChild(d);
			$('#sgp-imp-log').scrollTop = $('#sgp-imp-log').scrollHeight;
		}
	})();
	</script>
	<?php
}

/**
 * Esquece a lista do que já foi importado.
 */
function sgp_ajax_import_zerar() {
	check_ajax_referer( 'sgp_importar', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'sem permissão' );
	}
	delete_option( 'sgp_import_feitos' );
	wp_send_json_success();
}
add_action( 'wp_ajax_sgp_importar_zerar', 'sgp_ajax_import_zerar' );

/**
 * Importa um lote.
 */
function sgp_ajax_importar() {
	check_ajax_referer( 'sgp_importar', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( __( 'Sem permissão.', 'sao-geronimo-painel' ) );
	}

	$cat = sgp_le_catalogo();
	if ( is_wp_error( $cat ) ) {
		wp_send_json_error( $cat->get_error_message() );
	}

	$inicio   = isset( $_POST['inicio'] ) ? absint( $_POST['inicio'] ) : 0;
	$lote     = isset( $_POST['lote'] ) ? max( 1, min( 20, absint( $_POST['lote'] ) ) ) : 5;
	$repetido = isset( $_POST['repetido'] ) && 'atualizar' === $_POST['repetido'] ? 'atualizar' : 'pular';
	$com_img  = isset( $_POST['imagens'] ) && '1' === $_POST['imagens'];
	$status   = isset( $_POST['status'] ) && 'draft' === $_POST['status'] ? 'draft' : 'publish';

	$site = isset( $_POST['site'] ) ? esc_url_raw( wp_unslash( $_POST['site'] ) ) : '';
	$site = $site ? trailingslashit( $site ) : '';
	if ( $site ) {
		update_option( 'sgp_site_antigo', untrailingslashit( $site ), false );
	}

	$produtos = $cat['produtos'];
	$fatia    = array_slice( $produtos, $inicio, $lote );

	$feitos = get_option( 'sgp_import_feitos', array() );
	if ( ! is_array( $feitos ) ) {
		$feitos = array();
	}

	$linhas = array();

	// No primeiro lote, cria as categorias (na ordem do site antigo) e instala os banners.
	if ( 0 === $inicio ) {
		sgp_prepara_categorias( $cat, $site );
		$n_site = sgp_instala_imagens_site();
		if ( $n_site ) {
			$linhas[] = '<span class="ok">' . sprintf(
				/* translators: quantidade de imagens */
				esc_html__( '%d imagens do site instaladas (banners e Sobre nós)', 'sao-geronimo-painel' ),
				$n_site
			) . '</span>';
		}
	}
	$criados = 0;
	$pulados = 0;
	$erros   = 0;

	foreach ( $fatia as $p ) {
		$sku = isset( $p['sku'] ) ? $p['sku'] : '';
		if ( ! $sku ) {
			$erros++;
			$linhas[] = '<span class="erro">' . esc_html__( 'produto sem referência — pulei', 'sao-geronimo-painel' ) . '</span>';
			continue;
		}

		$id_existente = wc_get_product_id_by_sku( $sku );

		if ( $id_existente && 'pular' === $repetido ) {
			$pulados++;
			$linhas[] = '<span class="pulado">' . esc_html( $sku ) . ' — ' . esc_html__( 'já existe', 'sao-geronimo-painel' ) . '</span>';
			continue;
		}

		$res = sgp_importa_um( $p, $cat, $id_existente, $com_img, $status, $site );

		if ( is_wp_error( $res ) ) {
			$erros++;
			$linhas[] = '<span class="erro">' . esc_html( $sku ) . ' — ' . esc_html( $res->get_error_message() ) . '</span>';
			continue;
		}

		$criados++;
		$feitos[ $sku ] = $res;

		$aviso = $com_img && empty( $p['imagens'] ) ? '' : '';
		if ( $com_img && ! empty( $p['imagens'] ) && ! has_post_thumbnail( $res ) ) {
			$aviso = ' <span class="pulado">(' . esc_html__( 'sem foto', 'sao-geronimo-painel' ) . ')</span>';
		}
		$linhas[] = '<span class="ok">' . esc_html( $sku ) . ' — ' . esc_html( $p['nome'] ) . '</span>' . $aviso;
	}

	update_option( 'sgp_import_feitos', $feitos, false );

	$proximo = $inicio + count( $fatia );

	wp_send_json_success( array(
		'proximo' => $proximo,
		'fim'     => $proximo >= count( $produtos ),
		'criados' => $criados,
		'pulados' => $pulados,
		'erros'   => $erros,
		'linhas'  => $linhas,
	) );
}
add_action( 'wp_ajax_sgp_importar', 'sgp_ajax_importar' );

/**
 * Instala as imagens do site (banners, Sobre nós, blog) na biblioteca de mídia
 * e já aponta o painel para elas — mas só onde o lojista ainda não escolheu
 * nada, para nunca sobrescrever o que ele montou.
 *
 * @return int Quantas imagens entraram.
 */
function sgp_instala_imagens_site() {
	$pasta = sgp_pasta_import() . '/site';
	if ( ! is_dir( $pasta ) ) {
		return 0;
	}

	$opcoes = get_option( 'sg_opcoes', array() );
	if ( ! is_array( $opcoes ) ) {
		$opcoes = array();
	}

	$mapa = array(
		'inauguracao-incenso-noa' => array( 'banner', 'Inauguração do site: em compras acima de R$ 300,00, ganhe um Incenso Noa Orgânico de cortesia.' ),
		'sao-geronimo-contato'    => array( 'banner', 'São Gerônimo — artigos místicos e religiosos. Velas, muranos e incensos.' ),
		'sobre-nos-espiritualidade' => array( 'sobre', 'Espiritualidade em cada detalhe' ),
	);

	$banners = array();
	$n       = 0;

	foreach ( glob( $pasta . '/*' ) as $arq ) {
		$base = pathinfo( $arq, PATHINFO_FILENAME );
		if ( ! isset( $mapa[ $base ] ) ) {
			continue;
		}

		list( $papel, $alt ) = $mapa[ $base ];

		$id = sgp_sobe_imagem( 'site/' . basename( $arq ), $alt, 0 );
		if ( ! $id ) {
			// o caminho relativo acima é dentro de "imagens"; aqui o arquivo está em "site"
			$id = sgp_sobe_arquivo_solto( $arq, $alt );
		}
		if ( ! $id ) {
			continue;
		}

		$n++;
		$url = wp_get_attachment_url( $id );

		if ( 'banner' === $papel ) {
			$banners[] = array(
				'img'     => $url,
				'img_mob' => '',
				'link'    => 'inauguracao-incenso-noa' === $base
					? ( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) )
					: '',
				'alt'     => $alt,
			);
		} elseif ( 'sobre' === $papel && empty( $opcoes['sobre_img'] ) ) {
			$opcoes['sobre_img'] = $url;
		}
	}

	if ( $banners && empty( $opcoes['banners'] ) ) {
		$opcoes['banners'] = $banners;
	}

	if ( $n ) {
		update_option( 'sg_opcoes', $opcoes );
	}

	return $n;
}

/**
 * Sobe um arquivo que está fora da pasta "imagens".
 *
 * @param string $caminho Caminho completo.
 * @param string $alt     Texto alternativo.
 * @return int
 */
function sgp_sobe_arquivo_solto( $caminho, $alt ) {
	if ( ! file_exists( $caminho ) ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$chave  = md5_file( $caminho );
	$achado = get_posts( array(
		'post_type'   => 'attachment',
		'numberposts' => 1,
		'fields'      => 'ids',
		'meta_key'    => '_sgp_hash', // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_value'  => $chave, // phpcs:ignore WordPress.DB.SlowDBQuery
	) );
	if ( $achado ) {
		return (int) $achado[0];
	}

	$up   = wp_upload_dir();
	$para = trailingslashit( $up['path'] ) . wp_unique_filename( $up['path'], sanitize_file_name( basename( $caminho ) ) );

	if ( ! copy( $caminho, $para ) ) {
		return 0;
	}

	$tipo = wp_check_filetype( $para );
	$id   = wp_insert_attachment( array(
		'post_mime_type' => $tipo['type'],
		'post_title'     => $alt,
		'post_status'    => 'inherit',
	), $para );

	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}

	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $para ) );
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	update_post_meta( $id, '_sgp_hash', $chave );

	return (int) $id;
}

/**
 * Importa um produto.
 *
 * @param array $p         Dados do produto.
 * @param array $cat       Catálogo inteiro (para nomes de categoria).
 * @param int   $id        ID existente, ou 0.
 * @param bool  $com_img   Importar imagens.
 * @param string $status   publish ou draft.
 * @param string $site     Endereço do site antigo, para baixar as fotos que faltarem.
 * @return int|WP_Error
 */
function sgp_importa_um( $p, $cat, $id, $com_img, $status, $site = '' ) {
	$prod = $id ? wc_get_product( $id ) : new WC_Product_Simple();
	if ( ! $prod ) {
		$prod = new WC_Product_Simple();
	}

	$prod->set_name( $p['nome'] );
	$prod->set_status( $status );
	$prod->set_catalog_visibility( 'visible' );

	if ( ! $id ) {
		$prod->set_sku( $p['sku'] );
	}

	if ( ! empty( $p['preco'] ) ) {
		$prod->set_regular_price( (string) $p['preco'] );
	}

	// A descrição é o texto "Sobre o produto"; composição, modo de usar e notas
	// olfativas viram campos próprios (aba São Gerônimo), cada um com seu bloco.
	$desc = isset( $p['descricao'] ) ? trim( $p['descricao'] ) : '';
	$prod->set_description( $desc );
	$prod->set_short_description( $desc ? wp_trim_words( $desc, 40 ) : '' );

	// peso (o catálogo traz "92 g" ou "1,2 kg")
	if ( ! empty( $p['peso'] ) ) {
		$peso = sgp_converte_peso( $p['peso'] );
		if ( $peso ) {
			$prod->set_weight( $peso );
		}
	}

	// categoria
	if ( ! empty( $p['cat_slug'] ) ) {
		$nome   = ! empty( $p['cat_nome'] ) ? $p['cat_nome'] : $p['cat_slug'];
		$termo  = get_term_by( 'slug', $p['cat_slug'], 'product_cat' );
		if ( ! $termo ) {
			$novo = wp_insert_term( $nome, 'product_cat', array( 'slug' => $p['cat_slug'] ) );
			if ( ! is_wp_error( $novo ) ) {
				$termo_id = $novo['term_id'];
				update_term_meta( $termo_id, '_sg_icone', $p['cat_slug'] );
			} else {
				$termo_id = 0;
			}
		} else {
			$termo_id = $termo->term_id;
		}
		if ( $termo_id ) {
			$prod->set_category_ids( array( $termo_id ) );
		}
	}

	// etiqueta com a sublinha, para filtrar depois
	if ( ! empty( $p['sub'] ) ) {
		$prod->set_tag_ids( sgp_termo_ids( array( $p['sub'] ), 'product_tag' ) );
	}

	// destaque
	if ( ! empty( $p['destaques'] ) && in_array( 'Coleção Premium', $p['destaques'], true ) ) {
		$prod->set_featured( true );
	}

	$novo_id = $prod->save();
	if ( ! $novo_id ) {
		return new WP_Error( 'falhou', __( 'não consegui salvar', 'sao-geronimo-painel' ) );
	}

	// campos próprios
	$ficha = '';
	if ( ! empty( $p['ficha'] ) ) {
		foreach ( $p['ficha'] as $linha ) {
			$ficha .= $linha[0] . ': ' . $linha[1] . "\n";
		}
	}
	$metas = array(
		'_sg_dimensoes'    => isset( $p['dimensoes'] ) ? $p['dimensoes'] : '',
		'_sg_marca'        => isset( $p['marca'] ) ? $p['marca'] : '',
		'_sg_conteudo'     => isset( $p['conteudo'] ) ? $p['conteudo'] : '',
		'_sg_peso_txt'     => isset( $p['peso_txt'] ) ? $p['peso_txt'] : '',
		'_sg_sobre_titulo' => isset( $p['sobre_titulo'] ) ? $p['sobre_titulo'] : '',
		'_sg_ficha'        => trim( $ficha ),
		'_sg_nota'         => ! empty( $p['nota'] ) ? $p['nota'] : '',
		'_sg_med_titulo'   => isset( $p['med_titulo'] ) ? $p['med_titulo'] : '',
		'_sg_med_aviso'    => isset( $p['med_aviso'] ) ? $p['med_aviso'] : '',
		'_sg_composicao'   => '',
		'_sg_modo_usar'    => '',
		'_sg_notas'        => '',
	);
	foreach ( $p['blocos'] ?? array() as $b ) {
		if ( 'Composição' === $b['titulo'] ) {
			$metas['_sg_composicao'] = $b['texto'];
		}
		if ( 'Modo de usar' === $b['titulo'] ) {
			$metas['_sg_modo_usar'] = $b['texto'];
		}
		if ( 'Notas olfativas' === $b['titulo'] ) {
			$metas['_sg_notas'] = $b['texto'];
		}
	}
	foreach ( $metas as $k => $v ) {
		if ( '' !== $v && null !== $v ) {
			update_post_meta( $novo_id, $k, $v );
		} else {
			delete_post_meta( $novo_id, $k ); // ao atualizar, limpa o que o catálogo não tem mais
		}
	}

	if ( empty( $p['preco'] ) ) {
		update_post_meta( $novo_id, '_sg_sob_consulta', 'yes' );
	}

	update_post_meta( $novo_id, '_sgp_origem', isset( $p['url_antiga'] ) ? $p['url_antiga'] : '' );

	// imagens: a galeria completa do produto, na ordem do site original
	$galeria = array();
	if ( ! empty( $p['galeria'] ) ) {
		foreach ( $p['galeria'] as $g ) {
			$galeria[] = array(
				'rel'  => ! empty( $g['local'] ) ? $g['local'] : 'remota',
				'urls' => ! empty( $g['urls'] ) ? $g['urls'] : array(),
			);
		}
	} elseif ( ! empty( $p['imagens'] ) ) {
		foreach ( $p['imagens'] as $rel ) {
			$galeria[] = array( 'rel' => $rel, 'urls' => array() );
		}
	}

	if ( $com_img && $galeria ) {
		$ids = array();
		foreach ( $galeria as $g ) {
			$aid = sgp_sobe_imagem( $g['rel'], $p['nome'], $novo_id, $site, $g['urls'] );
			if ( $aid && ! in_array( $aid, $ids, true ) ) {
				$ids[] = $aid;
			}
		}
		if ( $ids ) {
			$principal = array_shift( $ids );
			set_post_thumbnail( $novo_id, $principal );
			update_post_meta( $novo_id, '_product_image_gallery', implode( ',', $ids ) );
		}
	}

	return $novo_id;
}

/**
 * Cria todas as categorias do catálogo, na mesma ordem do site antigo, com a
 * frase de cada uma, a imagem do cartão em "Categorias" e as subcategorias.
 *
 * Categorias sem produto também entram: o site antigo mostrava as 22.
 *
 * @param array  $cat  Catálogo.
 * @param string $site Endereço do site antigo (opcional).
 */
function sgp_prepara_categorias( $cat, $site = '' ) {
	if ( empty( $cat['categorias_ordem'] ) ) {
		return;
	}
	$info  = isset( $cat['categorias_info'] ) ? $cat['categorias_info'] : array();
	$nomes = isset( $cat['categorias'] ) ? $cat['categorias'] : array();

	foreach ( $cat['categorias_ordem'] as $n => $slug ) {
		$nome  = isset( $info[ $slug ]['nome'] ) ? $info[ $slug ]['nome'] : ( isset( $nomes[ $slug ] ) ? $nomes[ $slug ] : $slug );
		$frase = isset( $info[ $slug ]['capa'] ) ? $info[ $slug ]['capa'] : '';

		$termo = get_term_by( 'slug', $slug, 'product_cat' );
		if ( ! $termo ) {
			$novo = wp_insert_term( $nome, 'product_cat', array( 'slug' => $slug, 'description' => $frase ) );
			if ( is_wp_error( $novo ) ) {
				continue;
			}
			$id = (int) $novo['term_id'];
		} else {
			$id = (int) $termo->term_id;
			wp_update_term( $id, 'product_cat', array( 'name' => $nome, 'description' => $frase ) );
		}

		update_term_meta( $id, 'order', $n );
		update_term_meta( $id, '_sg_icone', $slug );
		if ( ! empty( $info[ $slug ]['subs_pilulas'] ) ) {
			update_term_meta( $id, '_sg_subs_ordem', implode( '|', $info[ $slug ]['subs_pilulas'] ) );
		}
		if ( ! empty( $info[ $slug ]['subs'] ) ) {
			update_term_meta( $id, '_sg_subs', implode( ' · ', $info[ $slug ]['subs'] ) );
		}

		if ( ! empty( $info[ $slug ]['img'] ) && ! get_term_meta( $id, 'thumbnail_id', true ) ) {
			$g   = $info[ $slug ]['img'];
			$aid = sgp_sobe_imagem( ! empty( $g['local'] ) ? $g['local'] : 'remota', $nome, 0, $site, ! empty( $g['urls'] ) ? $g['urls'] : array() );
			if ( $aid ) {
				update_term_meta( $id, 'thumbnail_id', $aid );
			}
		}
	}

	if ( ! empty( $cat['loja_texto'] ) ) {
		update_option( 'sgp_loja_texto', $cat['loja_texto'], false );
	}
	if ( ! empty( $cat['categorias_texto'] ) ) {
		update_option( 'sgp_categorias_texto', $cat['categorias_texto'], false );
	}
}

/**
 * Converte "92 g" ou "1,2 kg" para a unidade da loja.
 *
 * @param string $txt Texto do peso.
 * @return string
 */
function sgp_converte_peso( $txt ) {
	$txt = strtolower( trim( $txt ) );
	if ( ! preg_match( '/([\d.,]+)\s*(kg|g)?/', $txt, $m ) ) {
		return '';
	}
	$n = (float) str_replace( ',', '.', str_replace( '.', '', $m[1] ) );
	$u = isset( $m[2] ) && $m[2] ? $m[2] : 'g';

	$gramas = 'kg' === $u ? $n * 1000 : $n;
	$loja   = get_option( 'woocommerce_weight_unit', 'kg' );

	switch ( $loja ) {
		case 'g':
			return (string) round( $gramas, 2 );
		case 'lbs':
			return (string) round( $gramas / 453.592, 3 );
		case 'oz':
			return (string) round( $gramas / 28.3495, 2 );
		case 'kg':
		default:
			return (string) round( $gramas / 1000, 3 );
	}
}

/**
 * Garante que os termos existam e devolve os IDs.
 *
 * @param array  $nomes Nomes.
 * @param string $tax   Taxonomia.
 * @return array
 */
function sgp_termo_ids( $nomes, $tax ) {
	$ids = array();
	foreach ( $nomes as $n ) {
		$n = trim( $n );
		if ( ! $n ) {
			continue;
		}
		$t = get_term_by( 'name', $n, $tax );
		if ( $t ) {
			$ids[] = $t->term_id;
			continue;
		}
		$novo = wp_insert_term( $n, $tax );
		if ( ! is_wp_error( $novo ) ) {
			$ids[] = $novo['term_id'];
		}
	}
	return $ids;
}

/**
 * Põe uma imagem na biblioteca de mídia.
 *
 * Procura primeiro na pasta de importação; se não achar e houver um endereço de
 * site antigo, baixa de lá. Imagem repetida é reaproveitada em vez de duplicada.
 *
 * @param string $rel  Caminho relativo dentro da pasta.
 * @param string $nome Nome do produto (vira o texto alternativo).
 * @param int    $post ID do produto.
 * @param string $site Endereço do site antigo (opcional).
 * @param array  $urls Endereços para baixar, se o arquivo não estiver no pacote (opcional).
 * @return int ID do anexo, ou 0.
 */
function sgp_sobe_imagem( $rel, $nome, $post, $site = '', $urls = array() ) {
	$rel = ltrim( str_replace( array( '..', "\0" ), '', $rel ), '/' );

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$temporario = false;
	$de         = sgp_pasta_import() . '/imagens/' . $rel;

	if ( ! file_exists( $de ) ) {
		// o catálogo guarda caminhos como assets/img/noa/x.jpg; tentamos só o nome
		$de = sgp_pasta_import() . '/imagens/' . basename( $rel );
	}

	$url_usada = '';
	if ( ! file_exists( $de ) ) {
		// Sem o arquivo no pacote: tenta baixar dos endereços informados no catálogo
		// (galeria do produto) ou, se não houver, do site antigo.
		$candidatas = array_filter( (array) $urls );
		if ( ! $candidatas && $site ) {
			$candidatas = array( trailingslashit( $site ) . implode( '/', array_map( 'rawurlencode', explode( '/', $rel ) ) ) );
		}
		if ( ! $candidatas ) {
			return 0;
		}

		$baixado = false;
		foreach ( $candidatas as $url ) {
			// já baixamos esta mesma URL antes?
			$achado = get_posts( array(
				'post_type'   => 'attachment',
				'numberposts' => 1,
				'fields'      => 'ids',
				'meta_key'    => '_sgp_origem_url', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'  => $url, // phpcs:ignore WordPress.DB.SlowDBQuery
			) );
			if ( $achado ) {
				return (int) $achado[0];
			}

			$tmp = download_url( $url, 25 );
			if ( is_wp_error( $tmp ) ) {
				continue;
			}
			if ( ! wp_getimagesize( $tmp ) ) {
				wp_delete_file( $tmp );
				continue;
			}
			$baixado   = $tmp;
			$url_usada = $url;
			break;
		}

		if ( ! $baixado ) {
			return 0;
		}

		$de         = $baixado;
		$temporario = true;
	}

	// já subimos esta mesma imagem antes?
	$chave  = md5_file( $de );
	$achado = get_posts( array(
		'post_type'   => 'attachment',
		'numberposts' => 1,
		'fields'      => 'ids',
		'meta_key'    => '_sgp_hash', // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_value'  => $chave, // phpcs:ignore WordPress.DB.SlowDBQuery
	) );
	if ( $achado ) {
		if ( $temporario ) {
			wp_delete_file( $de );
		}
		return (int) $achado[0];
	}

	$up   = wp_upload_dir();
	$base = sanitize_file_name( basename( $rel ) );
	$para = trailingslashit( $up['path'] ) . wp_unique_filename( $up['path'], $base );

	$copiou = copy( $de, $para );
	if ( $temporario ) {
		wp_delete_file( $de );
	}
	if ( ! $copiou ) {
		return 0;
	}

	$tipo = wp_check_filetype( $para );
	$id   = wp_insert_attachment( array(
		'post_mime_type' => $tipo['type'],
		'post_title'     => $nome,
		'post_content'   => '',
		'post_status'    => 'inherit',
	), $para, $post );

	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}

	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $para ) );
	update_post_meta( $id, '_wp_attachment_image_alt', $nome );
	update_post_meta( $id, '_sgp_hash', $chave );
	if ( $temporario ) {
		update_post_meta( $id, '_sgp_origem_url', $url_usada );
	}

	return (int) $id;
}

/* -------------------------------------------------------------------------
 * Redirecionar os endereços antigos
 * ---------------------------------------------------------------------- */

/**
 * Manda /produto/SKU/ do site antigo para a página nova, sem perder o Google.
 */
function sgp_redireciona_antigos() {
	if ( is_admin() || ! is_404() ) {
		return;
	}

	$caminho = wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '', PHP_URL_PATH );
	if ( ! $caminho || ! preg_match( '#^/produto/([^/]+)/?$#', $caminho, $m ) ) {
		return;
	}

	if ( ! function_exists( 'wc_get_product_id_by_sku' ) ) {
		return;
	}

	$id = wc_get_product_id_by_sku( rawurldecode( $m[1] ) );
	if ( $id ) {
		wp_safe_redirect( get_permalink( $id ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'sgp_redireciona_antigos', 1 );
