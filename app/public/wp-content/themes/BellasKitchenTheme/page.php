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

<div class="container px-5 py-10 md:px-8">
	<?php
	while ( have_posts() ) {
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900' ); ?>>
			<h1 class="entry-title font-display text-4xl font-semibold text-slate-900 dark:text-slate-100"><?php the_title(); ?></h1>

			<?php
			if ( has_post_thumbnail() ) {
				?>
				<div class="post-thumbnail">
					<?php the_post_thumbnail( 'large' ); ?>
				</div>
				<?php
			}
			?>

			<div class="entry-content mt-6 text-slate-700 dark:text-slate-300">
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
