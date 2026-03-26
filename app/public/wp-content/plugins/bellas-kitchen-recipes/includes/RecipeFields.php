<?php
/**
 * Recipe meta boxes — native WordPress implementation (no ACF required).
 *
 * @package BellasKitchenRecipes
 */

namespace BellasKitchenRecipes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RecipeFields {

	public function register(): void {
		add_action( 'add_meta_boxes', [ $this, 'registerMetaBoxes' ] );
		add_action( 'save_post_recipe', [ $this, 'saveMetaBoxes' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAdminAssets' ] );
	}

	public function enqueueAdminAssets( string $hook ): void {
		global $post;

		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		if ( ! $post || $post->post_type !== 'recipe' ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'bkr-recipe-fields',
			BKR_PLUGIN_URL . 'assets/admin/recipe-fields.css',
			[],
			BKR_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'bkr-recipe-fields',
			BKR_PLUGIN_URL . 'assets/admin/recipe-fields.js',
			[ 'jquery' ],
			BKR_PLUGIN_VERSION,
			true
		);
	}

	public function registerMetaBoxes(): void {
		add_meta_box( 'recipe_details', 'Recipe Details', [ $this, 'renderDetailsBox' ], 'recipe', 'normal', 'high' );
		add_meta_box( 'recipe_ingredients', 'Ingredients', [ $this, 'renderIngredientsBox' ], 'recipe', 'normal', 'default' );
		add_meta_box( 'recipe_instructions', 'Instructions', [ $this, 'renderInstructionsBox' ], 'recipe', 'normal', 'default' );
		add_meta_box( 'recipe_info', 'Recipe Info', [ $this, 'renderInfoBox' ], 'recipe', 'side', 'default' );
	}

	public function renderDetailsBox( \WP_Post $post ): void {
		wp_nonce_field( 'recipe_save_meta', 'recipe_meta_nonce' );

		$description = get_post_meta( $post->ID, 'recipe_description', true );
		$servings    = get_post_meta( $post->ID, 'recipe_servings', true ) ?: 4;
		$main_id     = (int) get_post_meta( $post->ID, 'recipe_main_image_id', true );
		$extra_ids   = get_post_meta( $post->ID, 'recipe_extra_image_ids', true );

		if ( ! is_array( $extra_ids ) ) {
			$extra_ids = [];
		}

		$main_url = $main_id ? wp_get_attachment_image_url( $main_id, 'thumbnail' ) : '';
		?>
		<table class="bkr-table">
			<tr>
				<th><label for="recipe_description">Description</label></th>
				<td>
					<textarea id="recipe_description" name="recipe_description" rows="4" class="widefat"><?php echo esc_textarea( $description ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th><label for="recipe_servings">Default Servings</label></th>
				<td>
					<input type="number" id="recipe_servings" name="recipe_servings"
					       value="<?php echo esc_attr( $servings ); ?>" min="1" class="small-text">
				</td>
			</tr>
			<tr>
				<th><label>Main Image</label></th>
				<td>
					<input type="hidden" name="recipe_main_image_id" id="recipe_main_image_id"
					       value="<?php echo esc_attr( $main_id ?: '' ); ?>">
					<div id="bkr-main-image-preview" class="bkr-image-preview">
						<?php if ( $main_url ) : ?>
							<img src="<?php echo esc_url( $main_url ); ?>" alt="">
						<?php endif; ?>
					</div>
					<button type="button" class="button bkr-upload-main-image">
						<?php echo $main_id ? 'Change Image' : 'Select Image'; ?>
					</button>
					<?php if ( $main_id ) : ?>
						<button type="button" class="button bkr-remove-main-image">Remove</button>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><label>Extra Images</label></th>
				<td>
					<input type="hidden" name="recipe_extra_image_ids" id="recipe_extra_image_ids"
					       value="<?php echo esc_attr( implode( ',', array_filter( array_map( 'intval', $extra_ids ) ) ) ); ?>">
					<div id="bkr-gallery-preview" class="bkr-gallery-preview">
						<?php foreach ( $extra_ids as $img_id ) :
							if ( ! $img_id ) continue;
							$url = wp_get_attachment_image_url( $img_id, 'thumbnail' );
							if ( ! $url ) continue;
						?>
							<div class="bkr-gallery-item" data-id="<?php echo esc_attr( $img_id ); ?>">
								<img src="<?php echo esc_url( $url ); ?>" alt="">
								<button type="button" class="bkr-remove-gallery-image" data-id="<?php echo esc_attr( $img_id ); ?>">&#x2715;</button>
							</div>
						<?php endforeach; ?>
					</div>
					<button type="button" class="button bkr-upload-gallery-images">Add Photos</button>
				</td>
			</tr>
		</table>
		<?php
	}

	public function renderIngredientsBox( \WP_Post $post ): void {
		$ingredients = get_post_meta( $post->ID, 'recipe_ingredients', true );

		if ( ! is_array( $ingredients ) || empty( $ingredients ) ) {
			$ingredients = [ [ 'quantity' => '', 'unit' => '', 'item' => '' ] ];
		}

		$units = $this->getUnits();
		?>
		<div id="bkr-ingredients-list">
			<?php foreach ( $ingredients as $index => $ingredient ) : ?>
				<div class="bkr-repeater-row bkr-ingredient-row">
					<input type="text"
					       name="recipe_ingredients[<?php echo $index; ?>][quantity]"
					       value="<?php echo esc_attr( $ingredient['quantity'] ?? '' ); ?>"
					       placeholder="Qty" class="bkr-qty-input">
					<select name="recipe_ingredients[<?php echo $index; ?>][unit]">
						<?php foreach ( $units as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>"
								<?php selected( $ingredient['unit'] ?? '', $value ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<input type="text"
					       name="recipe_ingredients[<?php echo $index; ?>][item]"
					       value="<?php echo esc_attr( $ingredient['item'] ?? '' ); ?>"
					       placeholder="Ingredient" class="bkr-item-input">
					<button type="button" class="button bkr-remove-row">&#x2715;</button>
				</div>
			<?php endforeach; ?>
		</div>
		<button type="button" class="button bkr-add-ingredient">+ Add Ingredient</button>

		<script type="text/template" id="bkr-ingredient-template">
			<div class="bkr-repeater-row bkr-ingredient-row">
				<input type="text" name="recipe_ingredients[{{index}}][quantity]" value="" placeholder="Qty" class="bkr-qty-input">
				<select name="recipe_ingredients[{{index}}][unit]">
					<?php foreach ( $units as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<input type="text" name="recipe_ingredients[{{index}}][item]" value="" placeholder="Ingredient" class="bkr-item-input">
				<button type="button" class="button bkr-remove-row">&#x2715;</button>
			</div>
		</script>
		<?php
	}

	public function renderInstructionsBox( \WP_Post $post ): void {
		$instructions = get_post_meta( $post->ID, 'recipe_instructions', true );

		if ( ! is_array( $instructions ) || empty( $instructions ) ) {
			$instructions = [ [ 'text' => '' ] ];
		}
		?>
		<div id="bkr-instructions-list">
			<?php foreach ( $instructions as $index => $step ) : ?>
				<div class="bkr-repeater-row bkr-instruction-row">
					<span class="bkr-step-number"><?php echo $index + 1; ?></span>
					<textarea name="recipe_instructions[<?php echo $index; ?>][text]"
					          rows="3"
					          placeholder="Describe this step..."><?php echo esc_textarea( $step['text'] ?? '' ); ?></textarea>
					<button type="button" class="button bkr-remove-row">&#x2715;</button>
				</div>
			<?php endforeach; ?>
		</div>
		<button type="button" class="button bkr-add-instruction">+ Add Step</button>

		<script type="text/template" id="bkr-instruction-template">
			<div class="bkr-repeater-row bkr-instruction-row">
				<span class="bkr-step-number">{{step}}</span>
				<textarea name="recipe_instructions[{{index}}][text]" rows="3" placeholder="Describe this step..."></textarea>
				<button type="button" class="button bkr-remove-row">&#x2715;</button>
			</div>
		</script>
		<?php
	}

	public function renderInfoBox( \WP_Post $post ): void {
		$prep_time    = get_post_meta( $post->ID, 'recipe_prep_time', true );
		$cooking_time = get_post_meta( $post->ID, 'recipe_cooking_time', true );
		$difficulty   = get_post_meta( $post->ID, 'recipe_difficulty', true ) ?: 'easy';
		$meal_type    = get_post_meta( $post->ID, 'recipe_meal_type', true ) ?: 'dinner';
		?>
		<table class="bkr-table bkr-side-table">
			<tr>
				<th><label for="recipe_prep_time">Prep Time (min)</label></th>
				<td><input type="number" id="recipe_prep_time" name="recipe_prep_time"
				           value="<?php echo esc_attr( $prep_time ); ?>" min="0" class="small-text"></td>
			</tr>
			<tr>
				<th><label for="recipe_cooking_time">Cooking Time (min)</label></th>
				<td><input type="number" id="recipe_cooking_time" name="recipe_cooking_time"
				           value="<?php echo esc_attr( $cooking_time ); ?>" min="0" class="small-text"></td>
			</tr>
			<tr>
				<th><label for="recipe_difficulty">Difficulty</label></th>
				<td>
					<select id="recipe_difficulty" name="recipe_difficulty">
						<option value="easy" <?php selected( $difficulty, 'easy' ); ?>>Easy</option>
						<option value="medium" <?php selected( $difficulty, 'medium' ); ?>>Medium</option>
						<option value="hard" <?php selected( $difficulty, 'hard' ); ?>>Hard</option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="recipe_meal_type">Meal Type</label></th>
				<td>
					<select id="recipe_meal_type" name="recipe_meal_type">
						<option value="breakfast" <?php selected( $meal_type, 'breakfast' ); ?>>Breakfast</option>
						<option value="lunch" <?php selected( $meal_type, 'lunch' ); ?>>Lunch</option>
						<option value="dinner" <?php selected( $meal_type, 'dinner' ); ?>>Dinner</option>
						<option value="snack" <?php selected( $meal_type, 'snack' ); ?>>Snack</option>
						<option value="dessert" <?php selected( $meal_type, 'dessert' ); ?>>Dessert</option>
						<option value="drinks" <?php selected( $meal_type, 'drinks' ); ?>>Drinks</option>
					</select>
				</td>
			</tr>
		</table>
		<?php
	}

	public function saveMetaBoxes( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST['recipe_meta_nonce'] ) || ! wp_verify_nonce( $_POST['recipe_meta_nonce'], 'recipe_save_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['recipe_description'] ) ) {
			update_post_meta( $post_id, 'recipe_description', sanitize_textarea_field( wp_unslash( $_POST['recipe_description'] ) ) );
		}

		if ( isset( $_POST['recipe_servings'] ) ) {
			update_post_meta( $post_id, 'recipe_servings', absint( $_POST['recipe_servings'] ) );
		}

		if ( isset( $_POST['recipe_main_image_id'] ) ) {
			update_post_meta( $post_id, 'recipe_main_image_id', absint( $_POST['recipe_main_image_id'] ) );
		}

		if ( isset( $_POST['recipe_extra_image_ids'] ) ) {
			$raw_ids = sanitize_text_field( wp_unslash( $_POST['recipe_extra_image_ids'] ) );
			$ids     = array_values( array_filter( array_map( 'absint', explode( ',', $raw_ids ) ) ) );
			update_post_meta( $post_id, 'recipe_extra_image_ids', $ids );
		}

		if ( isset( $_POST['recipe_ingredients'] ) && is_array( $_POST['recipe_ingredients'] ) ) {
			$ingredients = [];
			foreach ( $_POST['recipe_ingredients'] as $row ) {
				$item = sanitize_text_field( wp_unslash( $row['item'] ?? '' ) );
				if ( $item === '' ) {
					continue;
				}
				$ingredients[] = [
					'quantity' => sanitize_text_field( wp_unslash( $row['quantity'] ?? '' ) ),
					'unit'     => sanitize_key( $row['unit'] ?? '' ),
					'item'     => $item,
				];
			}
			update_post_meta( $post_id, 'recipe_ingredients', $ingredients );
		}

		if ( isset( $_POST['recipe_instructions'] ) && is_array( $_POST['recipe_instructions'] ) ) {
			$instructions = [];
			foreach ( $_POST['recipe_instructions'] as $step ) {
				$text = sanitize_textarea_field( wp_unslash( $step['text'] ?? '' ) );
				if ( $text === '' ) {
					continue;
				}
				$instructions[] = [ 'text' => $text ];
			}
			update_post_meta( $post_id, 'recipe_instructions', $instructions );
		}

		if ( isset( $_POST['recipe_prep_time'] ) ) {
			update_post_meta( $post_id, 'recipe_prep_time', absint( $_POST['recipe_prep_time'] ) );
		}

		if ( isset( $_POST['recipe_cooking_time'] ) ) {
			update_post_meta( $post_id, 'recipe_cooking_time', absint( $_POST['recipe_cooking_time'] ) );
		}

		$valid_difficulties = [ 'easy', 'medium', 'hard' ];
		if ( isset( $_POST['recipe_difficulty'] ) && in_array( $_POST['recipe_difficulty'], $valid_difficulties, true ) ) {
			update_post_meta( $post_id, 'recipe_difficulty', $_POST['recipe_difficulty'] );
		}

		$valid_meal_types = [ 'breakfast', 'lunch', 'dinner', 'snack', 'dessert', 'drinks' ];
		if ( isset( $_POST['recipe_meal_type'] ) && in_array( $_POST['recipe_meal_type'], $valid_meal_types, true ) ) {
			update_post_meta( $post_id, 'recipe_meal_type', $_POST['recipe_meal_type'] );
		}
	}

	private function getUnits(): array {
		return [
			''          => '- none -',
			'ml'        => 'ml',
			'l'         => 'l',
			'g'         => 'g',
			'kg'        => 'kg',
			'stuks'     => 'stuks',
			'naar smaak'  => 'naar smaak',
		];
	}
}
