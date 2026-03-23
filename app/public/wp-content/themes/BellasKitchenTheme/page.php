<?php
/**
 * Page template
 *
 * @package BellasKitchenTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<div class="container">
	<?php
	while ( have_posts() ) {
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<h1 class="entry-title"><?php the_title(); ?></h1>

			<?php
			if ( has_post_thumbnail() ) {
				?>
				<div class="post-thumbnail">
					<?php the_post_thumbnail( 'large' ); ?>
				</div>
				<?php
			}
			?>

			<div class="entry-content">
				<?php
				the_content();
				wp_link_pages( array(
					'before' => '<div class="page-links">',
					'after'  => '</div>',
				) );
				?>
			</div>
		</article>
		<?php
	}
	?>
</div>

<?php get_footer();
