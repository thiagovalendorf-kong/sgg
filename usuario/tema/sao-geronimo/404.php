<?php
/**
 * Página não encontrada.
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<section class="sec"><div class="wrap" style="text-align:center;max-width:620px">
	<span class="eyebrow"><?php esc_html_e( 'Erro 404', 'sao-geronimo' ); ?></span>
	<h1 class="h-sec"><?php echo esc_html( sg_opt( 'texto_404_titulo', 'Esta página não existe mais.' ) ); ?></h1>
	<p class="texto-mudo" style="margin:16px 0 28px">
		<?php echo esc_html( sg_opt( 'texto_404', 'Pode ser que o endereço tenha mudado. Use a busca ou volte para a loja — o que você procura provavelmente está lá.' ) ); ?>
	</p>

	<?php get_search_form(); ?>

	<div class="hero__botoes" style="justify-content:center;margin-top:28px">
		<a class="btn btn--azul" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Voltar ao início', 'sao-geronimo' ); ?></a>
		<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
			<a class="btn btn--vazado" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Ver a loja', 'sao-geronimo' ); ?></a>
		<?php endif; ?>
	</div>
</div></section>
<?php
get_footer();
