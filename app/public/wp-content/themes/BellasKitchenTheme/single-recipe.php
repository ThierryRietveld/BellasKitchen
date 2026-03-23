<?php
/**
 * Single Recipe template
 *
 * @package BellasKitchenTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<div class="container">
	<?php while ( have_posts() ) : the_post(); ?>

		<article id="recipe-<?php the_ID(); ?>" <?php post_class( 'recipe-single' ); ?>>

			<?php
		$post_id      = get_the_ID();
		$description  = get_post_meta( $post_id, 'recipe_description', true );
		$servings     = get_post_meta( $post_id, 'recipe_servings', true );
		$prep_time    = (int) get_post_meta( $post_id, 'recipe_prep_time', true );
		$cooking_time = (int) get_post_meta( $post_id, 'recipe_cooking_time', true );
		$difficulty   = get_post_meta( $post_id, 'recipe_difficulty', true );
		$meal_type    = get_post_meta( $post_id, 'recipe_meal_type', true );
		$ingredients  = get_post_meta( $post_id, 'recipe_ingredients', true );
		$instructions = get_post_meta( $post_id, 'recipe_instructions', true );
		$main_id      = (int) get_post_meta( $post_id, 'recipe_main_image_id', true );
		$extra_ids    = get_post_meta( $post_id, 'recipe_extra_image_ids', true );
		$main_url     = $main_id ? wp_get_attachment_image_url( $main_id, 'large' ) : '';
		$main_alt     = $main_id ? get_post_meta( $main_id, '_wp_attachment_image_alt', true ) : '';

			<!-- Hero Image -->
			<?php if ( $main_url ) : ?>
				<div class="recipe-hero">
					<img src="<?php echo esc_url( $main_url ); ?>"
					     alt="<?php echo esc_attr( $main_alt ?: get_the_title() ); ?>"
					     class="recipe-hero-image">
				</div>
			<?php elseif ( has_post_thumbnail() ) : ?>
				<div class="recipe-hero">
					<?php the_post_thumbnail( 'large', [ 'class' => 'recipe-hero-image' ] ); ?>
				</div>
			<?php endif; ?>

			<!-- Title & Meta -->
			<header class="recipe-header">
				<h1 class="recipe-title"><?php the_title(); ?></h1>

				<?php if ( $description ) : ?>
					<p class="recipe-description"><?php echo esc_html( $description ); ?></p>
				<?php endif; ?>

				<div class="recipe-meta">
					<?php if ( $prep_time ) : ?>
						<span class="recipe-meta-item">
							<strong>Prep:</strong> <?php echo esc_html( $prep_time ); ?> min
						</span>
					<?php endif; ?>

					<?php if ( $cooking_time ) : ?>
						<span class="recipe-meta-item">
							<strong>Cook:</strong> <?php echo esc_html( $cooking_time ); ?> min
						</span>
					<?php endif; ?>

					<?php if ( $prep_time || $cooking_time ) : ?>
						<span class="recipe-meta-item">
						<strong>Total:</strong> <?php echo esc_html( $prep_time + $cooking_time ); ?> min

					<?php if ( $meal_type ) : ?>
						<span class="recipe-meta-item">
							<strong>Meal:</strong> <?php echo esc_html( ucfirst( $meal_type ) ); ?>
						</span>
					<?php endif; ?>
				</div>
			</header>

			<div class="recipe-body">

				<!-- Ingredients -->
				<?php if ( is_array( $ingredients ) && ! empty( $ingredients ) ) : ?>
					<section class="recipe-ingredients">
						<h2>Ingredients</h2>
						<ul class="ingredients-list">
							<?php foreach ( $ingredients as $ingredient ) : ?>
								<li class="ingredient-item">
									<?php if ( ! empty( $ingredient['quantity'] ) ) : ?>
										<span class="ingredient-quantity"><?php echo esc_html( $ingredient['quantity'] ); ?></span>
									<?php endif; ?>
									<?php if ( ! empty( $ingredient['unit'] ) ) : ?>
										<span class="ingredient-unit"><?php echo esc_html( $ingredient['unit'] ); ?></span>
									<?php endif; ?>
									<span class="ingredient-item-name"><?php echo esc_html( $ingredient['item'] ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endif; ?>

				<!-- Instructions -->
				<?php if ( is_array( $instructions ) && ! empty( $instructions ) ) : ?>
					<section class="recipe-instructions">
						<h2>Instructions</h2>
						<ol class="instructions-list">
							<?php foreach ( $instructions as $step ) : ?>
								<li class="instruction-step">
									<?php echo wp_kses_post( nl2br( $step['text'] ) ); ?>
								</li>
							<?php endforeach; ?>
						</ol>
					</section>
				<?php endif; ?>

				<!-- WordPress editor content (optional extra notes) -->
				<?php
				$content = get_the_content();
				if ( $content ) :
				?>
					<section class="recipe-notes">
						<h2>Notes</h2>
						<div class="entry-content">
							<?php the_content(); ?>
						</div>
					</section>
				<?php endif; ?>

			</div><!-- .recipe-body -->

			<!-- Extra Images Gallery -->
			<?php if ( is_array( $extra_ids ) && ! empty( $extra_ids ) ) : ?>
				<section class="recipe-gallery">
					<h2>Photos</h2>
					<div class="gallery-grid">
						<?php foreach ( $extra_ids as $img_id ) :
							if ( ! $img_id ) continue;
							$img_url = wp_get_attachment_image_url( $img_id, 'large' );
							$img_alt = get_post_meta( $img_id, '_wp_attachment_image_alt', true );
							if ( ! $img_url ) continue;
						?>
							<figure class="gallery-item">
								<img src="<?php echo esc_url( $img_url ); ?>"
								     alt="<?php echo esc_attr( $img_alt ?: get_the_title() ); ?>">
							</figure>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

		</article>

		<div class="recipe-navigation">
			<?php the_post_navigation( [
				'prev_text' => '&larr; Previous Recipe',
				'next_text' => 'Next Recipe &rarr;',
				'in_same_term' => false,
			] ); ?>
		</div>

	<?php endwhile; ?>
</div>

<?php get_footer();
