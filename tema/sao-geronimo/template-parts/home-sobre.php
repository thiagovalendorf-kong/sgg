<?php
/**
 * Bloco "Sobre nós" com imagem, texto curto e botão que abre a história completa.
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sg_texto = sg_opt( 'sobre_texto', '' );
if ( ! $sg_texto ) {
	return;
}
$sg_completo = sg_opt( 'sobre_completo', '' );
?>
<section class="sec sobre" id="sobre" aria-labelledby="t-sobre"><div class="wrap sobre__grade">
	<?php if ( sg_opt( 'sobre_img' ) ) : ?>
		<div class="sobre__img">
			<img src="<?php echo esc_url( sg_opt( 'sobre_img' ) ); ?>" alt="<?php echo esc_attr( sg_opt( 'sobre_img_alt', 'São Gerônimo Religiosos' ) ); ?>" loading="lazy">
		</div>
	<?php endif; ?>

	<div class="sobre__texto">
		<span class="eyebrow"><?php echo esc_html( sg_opt( 'sobre_eyebrow', 'Quem somos' ) ); ?></span>
		<h2 class="h-sec" id="t-sobre"><?php echo esc_html( sg_opt( 'sobre_titulo', 'Sobre nós' ) ); ?></h2>

		<div class="sobre__corpo"><?php echo wp_kses_post( wpautop( $sg_texto ) ); ?></div>

		<?php if ( $sg_completo ) : ?>
			<button type="button" class="cta-mais" data-abrir-historia>
				<span aria-hidden="true">+</span> <?php echo esc_html( sg_opt( 'sobre_cta', 'LEIA MAIS' ) ); ?>
			</button>
		<?php endif; ?>
	</div>
</div></section>

<?php if ( $sg_completo ) : ?>
	<dialog class="historia" data-historia aria-label="<?php esc_attr_e( 'Nossa história', 'sao-geronimo' ); ?>">
		<button type="button" class="historia__x" data-fechar-historia aria-label="<?php esc_attr_e( 'Fechar', 'sao-geronimo' ); ?>"><?php echo sg_icone( 'x', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		<div class="historia__corpo">
			<span class="eyebrow"><?php echo esc_html( sg_opt( 'sobre_eyebrow', 'Quem somos' ) ); ?></span>
			<h2><?php echo esc_html( sg_opt( 'sobre_completo_titulo', 'Nossa história' ) ); ?></h2>
			<?php echo wp_kses_post( wpautop( $sg_completo ) ); ?>
		</div>
	</dialog>
<?php endif; ?>
