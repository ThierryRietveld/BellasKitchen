<?php
/**
 * Main template file
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
	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
				<div class="entry-meta">
					<span class="posted-on">Posted on <?php echo get_the_date(); ?></span>
					<span class="byline"> by <?php the_author(); ?></span>
				</div>
				<div class="entry-summary">
					<?php the_excerpt(); ?>
				</div>
			</article>
			<?php
		}

		// Pagination
		the_posts_pagination();
	} else {
		echo '<p>No posts found.</p>';
	}
	?>
</div>

<?php get_footer();
