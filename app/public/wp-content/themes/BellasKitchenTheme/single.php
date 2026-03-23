<?php
/**
 * Single post template
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
			<div class="entry-meta">
				<span class="posted-on">Posted on <?php echo get_the_date(); ?></span>
				<span class="byline"> by <?php the_author(); ?></span>
				<?php
				$categories = get_the_category();
				if ( ! empty( $categories ) ) {
					echo '<span class="categories"> in ' . implode( ', ', array_map( function( $cat ) {
						return '<a href="' . get_category_link( $cat ) . '">' . esc_html( $cat->name ) . '</a>';
					}, $categories ) ) . '</span>';
				}
				?>
			</div>

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
				<?php the_content(); ?>
			</div>

			<?php
			the_post_navigation();
	}
	?>
</div>

<?php get_footer();
