<?php
/**
 * Front page template
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
			<div class="hero-section">
				<h1 class="hero-title"><?php the_title(); ?></h1>
				<?php if ( has_post_thumbnail() ) : ?>
					<div class="hero-image">
						<?php the_post_thumbnail( 'large' ); ?>
					</div>
				<?php endif; ?>
				<div class="hero-content">
					<?php the_content(); ?>
				</div>
			</div>
			<?php
		}
	}

	// Display latest posts
	$args = array(
		'posts_per_page' => 6,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);
	$latest_posts = new WP_Query( $args );

	if ( $latest_posts->have_posts() ) {
		echo '<div class="latest-posts"><h2>Latest Posts</h2><div class="posts-grid">';
		while ( $latest_posts->have_posts() ) {
			$latest_posts->the_post();
			?>
			<article class="post-card">
				<?php if ( has_post_thumbnail() ) : ?>
					<div class="post-image">
						<a href="<?php the_permalink(); ?>">
							<?php the_post_thumbnail( 'medium' ); ?>
						</a>
					</div>
				<?php endif; ?>
				<h3 class="post-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
				<div class="post-meta">
					<span class="post-date"><?php echo get_the_date(); ?></span>
				</div>
				<p class="post-excerpt"><?php echo wp_trim_words( get_the_excerpt(), 20 ); ?></p>
				<a href="<?php the_permalink(); ?>" class="read-more">Read More</a>
			</article>
			<?php
		}
		echo '</div></div>';
		wp_reset_postdata();
	}
	?>
</div>

<?php get_footer();
