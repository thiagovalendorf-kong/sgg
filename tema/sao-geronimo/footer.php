<?php
/**
 * Rodapé do site — mesma estrutura do site original.
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sg_colado  = is_front_page() ? ' rodape--colado' : '';
$sg_insta   = sg_opt( 'url_instagram' );
$sg_arroba  = sg_opt( 'instagram_arroba' );
// Número que aparece escrito: o digitado no painel ou, na falta dele, o do WhatsApp já formatado.
$sg_zap_num = sg_opt( 'whatsapp_exibido' );
if ( ! $sg_zap_num ) {
	$sg_zap_num = sg_opt( 'telefone' );
}
$sg_zap_num = sg_formata_fone( $sg_zap_num ? $sg_zap_num : sg_opt( 'whatsapp' ) );
$sg_zap_url = sg_whatsapp_link( sg_opt( 'whatsapp_msg', 'Olá! Vim pelo site da São Gerônimo.' ) );
?>
</main>

<footer class="rodape<?php echo esc_attr( $sg_colado ); ?>">

	<?php if ( 'sim' === sg_opt( 'faixa_social_ativa', 'sim' ) && ( $sg_zap_url || $sg_insta ) ) : ?>
		<div class="faixa-social">
			<div class="wrap faixa-social__int">
				<p class="faixa-social__titulo"><?php echo esc_html( sg_opt( 'faixa_social_titulo', 'Fale com a gente ou acompanhe as novidades' ) ); ?></p>
				<p class="faixa-social__sub"><?php echo esc_html( sg_opt( 'faixa_social_sub', 'Atendimento rápido no WhatsApp e lançamentos em primeira mão no Instagram.' ) ); ?></p>
				<div class="faixa-social__btns">
					<?php if ( $sg_zap_url ) : ?>
						<a class="btn-social btn-social--wa" href="<?php echo esc_url( $sg_zap_url ); ?>" target="_blank" rel="noopener">
							<?php echo sg_icone( 'whatsapp', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<span class="btn-social__txt">
								<small><?php esc_html_e( 'Chamar no WhatsApp', 'sao-geronimo' ); ?></small>
								<b><?php echo esc_html( $sg_zap_num ); ?></b>
							</span>
						</a>
					<?php endif; ?>
					<?php if ( $sg_insta ) : ?>
						<a class="btn-social btn-social--ig" href="<?php echo esc_url( $sg_insta ); ?>" target="_blank" rel="noopener">
							<?php echo sg_icone( 'instagram', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<span class="btn-social__txt">
								<small><?php esc_html_e( 'Seguir no Instagram', 'sao-geronimo' ); ?></small>
								<b><?php echo esc_html( $sg_arroba ? $sg_arroba : '@' . sanitize_title( get_bloginfo( 'name' ) ) ); ?></b>
							</span>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<div class="wrap rodape__g">
		<div>
			<div class="rodape__marca"><?php echo esc_html( sg_opt( 'logo_texto', get_bloginfo( 'name' ) ) ); ?></div>
			<p class="rodape__sobre"><?php echo esc_html( sg_opt( 'rodape_sobre', 'Há mais de 15 anos trazendo para a sua vida a espiritualidade em cada detalhe.' ) ); ?></p>
			<?php sg_redes(); ?>
			<?php if ( $sg_insta ) : ?>
				<a href="<?php echo esc_url( $sg_insta ); ?>" target="_blank" rel="noopener" class="link-sub rodape__insta"><?php esc_html_e( 'Seguir no Instagram', 'sao-geronimo' ); ?></a>
			<?php endif; ?>
		</div>

		<div>
			<h4><?php echo esc_html( sg_opt( 'rodape_col1', 'Navegar' ) ); ?></h4>
			<?php
			// items_wrap sem classe: o <ul> do rodapé não pode herdar o estilo
			// do menu do topo, que é horizontal.
			wp_nav_menu( array(
				'theme_location' => 'rodape',
				'container'      => false,
				'items_wrap'     => '<ul>%3$s</ul>',
				'depth'          => 1,
				'fallback_cb'    => 'sg_rodape_padrao',
			) );
			?>
		</div>

		<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
			<div>
				<h4><?php echo esc_html( sg_opt( 'rodape_col2', 'Conta' ) ); ?></h4>
				<ul>
					<li><a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'Minha conta', 'sao-geronimo' ); ?></a></li>
					<li><a href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'Sacola', 'sao-geronimo' ); ?></a></li>
					<li><a href="<?php echo esc_url( wc_get_checkout_url() ); ?>"><?php esc_html_e( 'Finalizar compra', 'sao-geronimo' ); ?></a></li>
					<li><a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Loja', 'sao-geronimo' ); ?></a></li>
				</ul>
			</div>
		<?php endif; ?>

		<div>
			<h4><?php echo esc_html( sg_opt( 'rodape_col3', 'Atendimento' ) ); ?></h4>
			<ul>
				<?php if ( $sg_zap_url ) : ?>
					<li><a href="<?php echo esc_url( $sg_zap_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'WhatsApp', 'sao-geronimo' ); ?> <?php echo esc_html( $sg_zap_num ); ?></a></li>
				<?php endif; ?>
				<?php if ( sg_opt( 'telefone' ) ) : ?>
					<li><a href="tel:<?php echo esc_attr( preg_replace( '/\D/', '', sg_opt( 'telefone' ) ) ); ?>"><?php esc_html_e( 'Telefone', 'sao-geronimo' ); ?> <?php echo esc_html( sg_opt( 'telefone' ) ); ?></a></li>
				<?php endif; ?>
				<?php if ( sg_opt( 'email_contato' ) ) : ?>
					<li><a href="mailto:<?php echo esc_attr( sg_opt( 'email_contato' ) ); ?>"><?php echo esc_html( sg_opt( 'email_contato' ) ); ?></a></li>
				<?php endif; ?>
				<?php
				wp_nav_menu( array(
					'theme_location' => 'legal',
					'container'      => false,
					'items_wrap'     => '%3$s',
					'depth'          => 1,
					'fallback_cb'    => '__return_empty_string',
				) );
				?>
				<?php if ( sg_opt( 'endereco' ) ) : ?>
					<li><?php echo esc_html( sg_opt( 'endereco' ) ); ?></li>
				<?php endif; ?>
			</ul>
		</div>
	</div>

	<div class="rodape__base"><div class="wrap">
		<span>
			&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( sg_opt( 'razao_social', get_bloginfo( 'name' ) ) ); ?>
			<?php if ( sg_opt( 'rodape_frase', 'Sua fé, sua energia, seu caminho.' ) ) : ?>
				&mdash; <?php echo esc_html( sg_opt( 'rodape_frase', 'Sua fé, sua energia, seu caminho.' ) ); ?>
			<?php endif; ?>
		</span>
		<?php if ( sg_opt( 'rodape_assinatura', 'Desenvolvido por Kong Media' ) ) : ?>
			<span><?php echo esc_html( sg_opt( 'rodape_assinatura', 'Desenvolvido por Kong Media' ) ); ?></span>
		<?php endif; ?>
	</div></div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
