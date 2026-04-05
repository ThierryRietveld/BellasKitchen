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

<div class="container px-5 py-10 md:px-8">

	<header class="archive-header mb-6">
		<h1 class="archive-title font-display text-4xl font-semibold text-slate-900 dark:text-slate-100">Recipes</h1>
	</header>

	<?php if ( have_posts() ) : ?>

		<div class="recipes-grid grid gap-6 md:grid-cols-2 lg:grid-cols-3">
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

				<article id="recipe-<?php the_ID(); ?>" <?php post_class( 'recipe-card overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 dark:border-slate-800 dark:bg-slate-900' ); ?>>
					<a href="<?php the_permalink(); ?>" class="recipe-card-link block">

						<div class="recipe-card-image aspect-[4/3] overflow-hidden bg-slate-100 dark:bg-slate-800">
							<?php if ( $main_url ) : ?>
								<img src="<?php echo esc_url( $main_url ); ?>"
								     alt="<?php echo esc_attr( $main_alt ?: get_the_title() ); ?>"
								     class="h-full w-full object-cover">
							<?php elseif ( has_post_thumbnail() ) : ?>
								<?php the_post_thumbnail( 'medium', array( 'class' => 'h-full w-full object-cover' ) ); ?>
							<?php else : ?>
								<div class="recipe-card-placeholder flex h-full items-center justify-center text-5xl">🍽</div>
							<?php endif; ?>
						</div>

						<div class="recipe-card-body space-y-4 p-5">
							<h2 class="recipe-card-title font-display text-2xl font-semibold text-slate-900 dark:text-slate-100"><?php the_title(); ?></h2>

							<?php if ( $description ) : ?>
								<p class="recipe-card-description text-slate-700 dark:text-slate-300"><?php echo esc_html( wp_trim_words( $description, 15 ) ); ?></p>
							<?php endif; ?>

							<div class="recipe-card-meta flex flex-wrap gap-2 text-sm">
								<?php if ( $total_time > 0 ) : ?>
									<span class="meta-item rounded-full bg-amber-100 px-3 py-1 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"><?php echo esc_html( $total_time ); ?> min</span>
								<?php endif; ?>
								<?php if ( $difficulty ) : ?>
									<span class="meta-item rounded-full bg-rose-100 px-3 py-1 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300"><?php echo esc_html( ucfirst( $difficulty ) ); ?></span>
								<?php endif; ?>
								<?php if ( $meal_type ) : ?>
									<span class="meta-item rounded-full bg-sky-100 px-3 py-1 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300"><?php echo esc_html( ucfirst( $meal_type ) ); ?></span>
								<?php endif; ?>
							</div>
						</div>

					</a>
				</article>

			<?php endwhile; ?>
		</div>

		<div class="pagination mt-8 text-slate-700 dark:text-slate-300">
			<?php the_posts_pagination( [
				'prev_text' => '&larr; Newer',
				'next_text' => 'Older &rarr;',
			] ); ?>
		</div>

	<?php else : ?>

		<p class="no-recipes text-slate-700 dark:text-slate-300">No recipes found. Check back soon!</p>

	<?php endif; ?>

</div>

<?php get_footer();
