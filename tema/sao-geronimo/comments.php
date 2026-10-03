<?php
/**
 * Comentários do blog (lista + formulário). O visual está em assets/css/tema.css.
 *
 * @package sao-geronimo
 */

if ( ! defined( 'ABSPATH' ) || post_password_required() ) {
	return;
}
?>
<div id="comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="comments-title">
			<?php
			printf(
				/* translators: %s: número de comentários */
				esc_html( _n( '%s comentário', '%s comentários', get_comments_number(), 'sao-geronimo' ) ),
				esc_html( number_format_i18n( get_comments_number() ) )
			);
			?>
		</h2>
		<ol class="comment-list">
			<?php wp_list_comments( array( 'style' => 'ol', 'short_ping' => true, 'avatar_size' => 0 ) ); ?>
		</ol>
		<?php the_comments_navigation(); ?>
	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() ) : ?>
		<p class="texto-mudo"><?php esc_html_e( 'Os comentários estão encerrados.', 'sao-geronimo' ); ?></p>
	<?php endif; ?>

	<?php comment_form(); ?>
</div>
