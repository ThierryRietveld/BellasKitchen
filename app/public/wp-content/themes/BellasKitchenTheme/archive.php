<?php
/**
 * Archive template
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
	the_archive_title( '<h1 class="archive-title font-display text-4xl font-semibold text-slate-900 dark:text-night-text">', '</h1>' );
	the_archive_description( '<div class="archive-description mt-3 text-slate-700 dark:text-night-muted">', '</div>' );

	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-night-borderMuted dark:bg-night-surface' ); ?>>
				<h2 class="entry-title text-2xl font-display font-semibold text-slate-900 dark:text-night-text"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
				<div class="entry-meta mt-2 text-sm text-slate-600 dark:text-night-subtle">
					<span class="posted-on">Posted on <?php echo get_the_date(); ?></span>
					<span class="byline"> by <?php the_author(); ?></span>
				</div>
				<div class="entry-summary mt-4 text-slate-700 dark:text-night-muted">
					<?php the_excerpt(); ?>
				</div>
			</article>
			<?php
		}

		// Pagination
		the_posts_pagination();
	} else {
		echo '<p class="text-slate-700 dark:text-night-muted">No posts found.</p>';
	}
	?>
</div>

<?php get_footer();
