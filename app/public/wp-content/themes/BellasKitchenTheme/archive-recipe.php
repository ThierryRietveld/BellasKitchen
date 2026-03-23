<?php
/**
 * Recipe archive template
 *
 * @package BellasKitchenTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<div class="container">

	<header class="archive-header">
		<h1 class="archive-title">Recipes</h1>
	</header>

	<?php if ( have_posts() ) : ?>

		<div class="recipes-grid">
			<?php while ( have_posts() ) : the_post(); ?>

				<?php
				$post_id      = get_the_ID();
				$main_id      = (int) get_post_meta( $post_id, 'recipe_main_image_id', true );
				$cooking_time = (int) get_post_meta( $post_id, 'recipe_cooking_time', true );
				$prep_time    = (int) get_post_meta( $post_id, 'recipe_prep_time', true );
				$difficulty   = get_post_meta( $post_id, 'recipe_difficulty', true );
				$meal_type    = get_post_meta( $post_id, 'recipe_meal_type', true );
				$description  = get_post_meta( $post_id, 'recipe_description', true );
				$main_url     = $main_id ? wp_get_attachment_image_url( $main_id, 'medium' ) : '';
				$main_alt     = $main_id ? get_post_meta( $main_id, '_wp_attachment_image_alt', true ) : '';
				$total_time   = $prep_time + $cooking_time;
				?>

				<article id="recipe-<?php the_ID(); ?>" <?php post_class( 'recipe-card' ); ?>>
					<a href="<?php the_permalink(); ?>" class="recipe-card-link">

						<div class="recipe-card-image">
							<?php if ( $main_url ) : ?>
								<img src="<?php echo esc_url( $main_url ); ?>"
								     alt="<?php echo esc_attr( $main_alt ?: get_the_title() ); ?>">
							<?php elseif ( has_post_thumbnail() ) : ?>
								<?php the_post_thumbnail( 'medium' ); ?>
							<?php else : ?>
								<div class="recipe-card-placeholder">🍽</div>
							<?php endif; ?>
						</div>

						<div class="recipe-card-body">
							<h2 class="recipe-card-title"><?php the_title(); ?></h2>

							<?php if ( $description ) : ?>
								<p class="recipe-card-description"><?php echo esc_html( wp_trim_words( $description, 15 ) ); ?></p>
							<?php endif; ?>

							<div class="recipe-card-meta">
								<?php if ( $total_time > 0 ) : ?>
									<span class="meta-item"><?php echo esc_html( $total_time ); ?> min</span>
								<?php endif; ?>
								<?php if ( $difficulty ) : ?>
									<span class="meta-item"><?php echo esc_html( ucfirst( $difficulty ) ); ?></span>
								<?php endif; ?>
								<?php if ( $meal_type ) : ?>
									<span class="meta-item"><?php echo esc_html( ucfirst( $meal_type ) ); ?></span>
								<?php endif; ?>
							</div>
						</div>

					</a>
				</article>

			<?php endwhile; ?>
		</div>

		<div class="pagination">
			<?php the_posts_pagination( [
				'prev_text' => '&larr; Newer',
				'next_text' => 'Older &rarr;',
			] ); ?>
		</div>

	<?php else : ?>

		<p class="no-recipes">No recipes found. Check back soon!</p>

	<?php endif; ?>

</div>

<?php get_footer();
