<?php
/**
 * Admin pages for managing recepten.
 *
 * @package BellasKitchenRecepten
 */

namespace BellasKitchenRecepten\Admin;

use BellasKitchenRecepten\Database\ReceptRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReceptenAdminPage {

	private const MENU_SLUG = 'bellas-kitchen-recepten';
	private const ADD_SLUG  = 'bellas-kitchen-recepten-add';
	private const EDIT_SLUG = 'bellas-kitchen-recepten-edit';

	/**
	 * @var ReceptRepository
	 */
	private $repository;

	public function __construct( ReceptRepository $repository ) {
		$this->repository = $repository;
	}

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'registerMenu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAssets' ] );
		add_action( 'admin_post_bkr_save_recept', [ $this, 'handleSave' ] );
		add_action( 'admin_post_bkr_delete_recept', [ $this, 'handleDelete' ] );
	}

	public function registerMenu(): void {
		add_menu_page(
			__( 'Recepten', 'bellas-kitchen-recepten' ),
			__( 'Recepten', 'bellas-kitchen-recepten' ),
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'renderOverviewPage' ],
			'dashicons-food',
			26
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Alle recepten', 'bellas-kitchen-recepten' ),
			__( 'Alle recepten', 'bellas-kitchen-recepten' ),
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'renderOverviewPage' ]
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Recept toevoegen', 'bellas-kitchen-recepten' ),
			__( 'Nieuw recept', 'bellas-kitchen-recepten' ),
			'manage_options',
			self::ADD_SLUG,
			[ $this, 'renderAddPage' ]
		);

		add_submenu_page(
			'admin.php',
			__( 'Recept bewerken', 'bellas-kitchen-recepten' ),
			__( 'Recept bewerken', 'bellas-kitchen-recepten' ),
			'manage_options',
			self::EDIT_SLUG,
			[ $this, 'renderEditPage' ]
		);
	}

	public function enqueueAssets(): void {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		if ( ! in_array( $page, [ self::MENU_SLUG, self::ADD_SLUG, self::EDIT_SLUG ], true ) ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'bkr-recepten-admin',
			BKR_RECEPTEN_PLUGIN_URL . 'assets/admin/recepten-admin.css',
			[],
			BKR_RECEPTEN_VERSION
		);

		wp_enqueue_script(
			'bkr-recepten-admin',
			BKR_RECEPTEN_PLUGIN_URL . 'assets/admin/recepten-admin.js',
			[ 'jquery' ],
			BKR_RECEPTEN_VERSION,
			true
		);

		wp_localize_script(
			'bkr-recepten-admin',
			'bkrReceptenAdmin',
			[
				'mediaTitle'  => __( 'Kies een foto', 'bellas-kitchen-recepten' ),
				'mediaButton' => __( 'Gebruik deze foto', 'bellas-kitchen-recepten' ),
				'noImageText' => __( 'Geen foto', 'bellas-kitchen-recepten' ),
			]
		);
	}

	public function renderOverviewPage(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Je hebt geen rechten om deze pagina te bekijken.', 'bellas-kitchen-recepten' ) );
		}

		$search  = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$recepten = $this->repository->getAll( $search );
		?>
		<div class="wrap bkr-recepten-admin">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Recepten', 'bellas-kitchen-recepten' ); ?></h1>
			<a href="<?php echo esc_url( $this->getAddUrl() ); ?>" class="page-title-action">
				<?php esc_html_e( 'Nieuw recept', 'bellas-kitchen-recepten' ); ?>
			</a>

			<?php $this->renderNotice(); ?>

			<form method="get" class="bkr-recepten-search">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::MENU_SLUG ); ?>">
				<p class="search-box">
					<label class="screen-reader-text" for="bkr-recepten-search-input">
						<?php esc_html_e( 'Recepten zoeken', 'bellas-kitchen-recepten' ); ?>
					</label>
					<input type="search" id="bkr-recepten-search-input" name="s" value="<?php echo esc_attr( $search ); ?>">
					<input type="submit" class="button" value="<?php esc_attr_e( 'Zoeken', 'bellas-kitchen-recepten' ); ?>">
				</p>
			</form>

			<table class="widefat fixed striped bkr-recepten-table">
				<thead>
					<tr>
						<th scope="col" class="column-image"><?php esc_html_e( 'Foto', 'bellas-kitchen-recepten' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Naam', 'bellas-kitchen-recepten' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Soort gerecht', 'bellas-kitchen-recepten' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Aantal personen', 'bellas-kitchen-recepten' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Moeilijkheid', 'bellas-kitchen-recepten' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Bereidingstijd', 'bellas-kitchen-recepten' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Bijgewerkt', 'bellas-kitchen-recepten' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $recepten ) ) : ?>
						<tr>
							<td colspan="7"><?php esc_html_e( 'Er zijn nog geen recepten gevonden.', 'bellas-kitchen-recepten' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $recepten as $recept ) : ?>
							<?php $this->renderOverviewRow( $recept ); ?>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function renderAddPage(): void {
		$this->renderFormPage( $this->getEmptyRecept(), false );
	}

	public function renderEditPage(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Je hebt geen rechten om deze pagina te bekijken.', 'bellas-kitchen-recepten' ) );
		}

		$id     = isset( $_GET['recept_id'] ) ? absint( wp_unslash( $_GET['recept_id'] ) ) : 0;
		$recept = $id ? $this->repository->find( $id ) : null;

		if ( ! $recept ) {
			wp_die( esc_html__( 'Recept niet gevonden.', 'bellas-kitchen-recepten' ) );
		}

		$this->renderFormPage( $recept, true );
	}

	public function handleSave(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Je hebt geen rechten om recepten te beheren.', 'bellas-kitchen-recepten' ) );
		}

		check_admin_referer( 'bkr_save_recept', 'bkr_recept_nonce' );

		$id   = isset( $_POST['recept_id'] ) ? absint( wp_unslash( $_POST['recept_id'] ) ) : 0;
		$data = $this->sanitizePostData();

		if ( $data['naam'] === '' ) {
			$this->redirectToForm( $id, 'missing-name' );
		}

		if ( $id > 0 ) {
			$existing = $this->repository->find( $id );

			if ( ! $existing ) {
				wp_die( esc_html__( 'Recept niet gevonden.', 'bellas-kitchen-recepten' ) );
			}

			if ( ! $this->repository->update( $id, $data ) ) {
				$this->redirectToForm( $id, 'save-failed' );
			}

			$this->redirectToOverview( 'updated' );
		}

		if ( $this->repository->create( $data ) <= 0 ) {
			$this->redirectToForm( 0, 'save-failed' );
		}

		$this->redirectToOverview( 'created' );
	}

	public function handleDelete(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Je hebt geen rechten om recepten te verwijderen.', 'bellas-kitchen-recepten' ) );
		}

		$id = isset( $_GET['recept_id'] ) ? absint( wp_unslash( $_GET['recept_id'] ) ) : 0;

		check_admin_referer( 'bkr_delete_recept_' . $id );

		if ( $id > 0 && ! $this->repository->delete( $id ) ) {
			$this->redirectToOverview( 'delete-failed' );
		}

		$this->redirectToOverview( 'deleted' );
	}

	private function renderOverviewRow( array $recept ): void {
		$id         = absint( $recept['id'] );
		$edit_url   = $this->getEditUrl( $id );
		$delete_url = wp_nonce_url(
			add_query_arg(
				[
					'action'    => 'bkr_delete_recept',
					'recept_id' => $id,
				],
				admin_url( 'admin-post.php' )
			),
			'bkr_delete_recept_' . $id
		);
		?>
		<tr>
			<td class="column-image"><?php echo wp_kses_post( $this->getImageHtml( absint( $recept['foto_id'] ) ) ); ?></td>
			<td>
				<strong>
					<a href="<?php echo esc_url( $edit_url ); ?>">
						<?php echo esc_html( $recept['naam'] ); ?>
					</a>
				</strong>
				<div class="row-actions">
					<span class="edit">
						<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Bewerken', 'bellas-kitchen-recepten' ); ?></a>
					</span>
					<span class="trash">
						| <a class="submitdelete" href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Weet je zeker dat je dit recept wilt verwijderen?', 'bellas-kitchen-recepten' ) ); ?>');">
							<?php esc_html_e( 'Verwijderen', 'bellas-kitchen-recepten' ); ?>
						</a>
					</span>
				</div>
			</td>
			<td><?php echo esc_html( $this->getMealTypeLabel( $recept['soort_gerecht'] ) ); ?></td>
			<td><?php echo esc_html( $this->formatServings( absint( $recept['aantal_personen'] ?? 0 ) ) ); ?></td>
			<td><?php echo esc_html( $this->getDifficultyLabel( $recept['moeilijkheid'] ) ); ?></td>
			<td>
				<?php
				printf(
					/* translators: %d: amount of minutes. */
					esc_html__( '%d minuten', 'bellas-kitchen-recepten' ),
					absint( $recept['bereidingstijd'] )
				);
				?>
			</td>
			<td><?php echo esc_html( $this->formatDate( $recept['updated_at'] ) ); ?></td>
		</tr>
		<?php
	}

	private function renderFormPage( array $recept, bool $is_edit ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Je hebt geen rechten om deze pagina te bekijken.', 'bellas-kitchen-recepten' ) );
		}

		$title        = $is_edit ? __( 'Recept bewerken', 'bellas-kitchen-recepten' ) : __( 'Recept toevoegen', 'bellas-kitchen-recepten' );
		$foto_id      = absint( $recept['foto_id'] );
		$foto_html    = $this->getImageHtml( $foto_id, 'medium' );
		$ingredienten = $this->getIngredientsForForm( $recept['ingredienten'] );
		$instructies  = $this->getInstructionsForForm( $recept['instructies'] );
		$units        = $this->getUnits();
		?>
		<div class="wrap bkr-recepten-admin">
			<h1><?php echo esc_html( $title ); ?></h1>

			<?php $this->renderNotice(); ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bkr-recepten-form">
				<input type="hidden" name="action" value="bkr_save_recept">
				<input type="hidden" name="recept_id" value="<?php echo esc_attr( absint( $recept['id'] ) ); ?>">
				<?php wp_nonce_field( 'bkr_save_recept', 'bkr_recept_nonce' ); ?>

				<div class="bkr-form-layout">
					<div class="bkr-form-main">
						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row"><label for="bkr-naam"><?php esc_html_e( 'Naam', 'bellas-kitchen-recepten' ); ?></label></th>
									<td>
										<input type="text" id="bkr-naam" name="naam" class="regular-text" value="<?php echo esc_attr( $recept['naam'] ); ?>" required>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="bkr-beschrijving"><?php esc_html_e( 'Beschrijving', 'bellas-kitchen-recepten' ); ?></label></th>
									<td>
										<textarea id="bkr-beschrijving" name="beschrijving" rows="5" class="large-text"><?php echo esc_textarea( $recept['beschrijving'] ); ?></textarea>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="bkr-ingredienten"><?php esc_html_e( 'Ingrediënten', 'bellas-kitchen-recepten' ); ?></label></th>
									<td>
										<div id="bkr-ingredienten" class="bkr-ingredienten-list">
											<?php foreach ( $ingredienten as $index => $ingredient ) : ?>
												<div class="bkr-ingredient-row">
													<input type="text"
														name="ingredienten[<?php echo esc_attr( $index ); ?>][quantity]"
														value="<?php echo esc_attr( $ingredient['quantity'] ); ?>"
														placeholder="<?php esc_attr_e( 'Hoeveelheid', 'bellas-kitchen-recepten' ); ?>"
														class="bkr-ingredient-quantity">
													<select name="ingredienten[<?php echo esc_attr( $index ); ?>][unit]" class="bkr-ingredient-unit">
														<?php foreach ( $units as $value => $label ) : ?>
															<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $ingredient['unit'], $value ); ?>>
																<?php echo esc_html( $label ); ?>
															</option>
														<?php endforeach; ?>
													</select>
													<input type="text"
														name="ingredienten[<?php echo esc_attr( $index ); ?>][item]"
														value="<?php echo esc_attr( $ingredient['item'] ); ?>"
														placeholder="<?php esc_attr_e( 'Ingrediënt', 'bellas-kitchen-recepten' ); ?>"
														class="regular-text bkr-ingredient-item">
													<button type="button" class="button bkr-remove-ingredient"><?php esc_html_e( 'Verwijderen', 'bellas-kitchen-recepten' ); ?></button>
												</div>
											<?php endforeach; ?>
										</div>

										<button type="button" class="button bkr-add-ingredient">
											<?php esc_html_e( 'Ingrediënt toevoegen', 'bellas-kitchen-recepten' ); ?>
										</button>

										<script type="text/template" id="bkr-ingredient-template">
											<div class="bkr-ingredient-row">
												<input type="text" name="ingredienten[{{index}}][quantity]" value="" placeholder="<?php esc_attr_e( 'Hoeveelheid', 'bellas-kitchen-recepten' ); ?>" class="bkr-ingredient-quantity">
												<select name="ingredienten[{{index}}][unit]" class="bkr-ingredient-unit">
													<?php foreach ( $units as $value => $label ) : ?>
														<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
													<?php endforeach; ?>
												</select>
												<input type="text" name="ingredienten[{{index}}][item]" value="" placeholder="<?php esc_attr_e( 'Ingrediënt', 'bellas-kitchen-recepten' ); ?>" class="regular-text bkr-ingredient-item">
												<button type="button" class="button bkr-remove-ingredient"><?php esc_html_e( 'Verwijderen', 'bellas-kitchen-recepten' ); ?></button>
											</div>
										</script>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Instructies', 'bellas-kitchen-recepten' ); ?></th>
									<td>
										<div id="bkr-instructies" class="bkr-instructies-list">
											<?php foreach ( $instructies as $index => $instruction ) : ?>
												<div class="bkr-instruction-row">
													<span class="bkr-instruction-number"><?php echo esc_html( $index + 1 ); ?></span>
													<textarea
														name="instructies[<?php echo esc_attr( $index ); ?>][text]"
														rows="3"
														placeholder="<?php esc_attr_e( 'Beschrijf deze stap...', 'bellas-kitchen-recepten' ); ?>"
														class="large-text bkr-instruction-text"><?php echo esc_textarea( $instruction['text'] ); ?></textarea>
													<button type="button" class="button bkr-remove-instruction"><?php esc_html_e( 'Verwijderen', 'bellas-kitchen-recepten' ); ?></button>
												</div>
											<?php endforeach; ?>
										</div>

										<button type="button" class="button bkr-add-instruction">
											<?php esc_html_e( 'Stap toevoegen', 'bellas-kitchen-recepten' ); ?>
										</button>

										<script type="text/template" id="bkr-instruction-template">
											<div class="bkr-instruction-row">
												<span class="bkr-instruction-number">{{step}}</span>
												<textarea name="instructies[{{index}}][text]" rows="3" placeholder="<?php esc_attr_e( 'Beschrijf deze stap...', 'bellas-kitchen-recepten' ); ?>" class="large-text bkr-instruction-text"></textarea>
												<button type="button" class="button bkr-remove-instruction"><?php esc_html_e( 'Verwijderen', 'bellas-kitchen-recepten' ); ?></button>
											</div>
										</script>
									</td>
								</tr>
							</tbody>
						</table>
					</div>

					<div class="bkr-form-side">
						<div class="postbox">
							<h2 class="hndle"><?php esc_html_e( 'Receptgegevens', 'bellas-kitchen-recepten' ); ?></h2>
							<div class="inside">
								<p>
									<label for="bkr-aantal-personen"><?php esc_html_e( 'Aantal personen', 'bellas-kitchen-recepten' ); ?></label>
									<input type="number" id="bkr-aantal-personen" name="aantal_personen" min="1" step="1" class="small-text" value="<?php echo esc_attr( $this->getNumberInputValue( absint( $recept['aantal_personen'] ?? 0 ) ) ); ?>">
								</p>
								<p>
									<label for="bkr-bereidingstijd"><?php esc_html_e( 'Bereidingstijd (minuten)', 'bellas-kitchen-recepten' ); ?></label>
									<input type="number" id="bkr-bereidingstijd" name="bereidingstijd" min="0" step="1" class="small-text" value="<?php echo esc_attr( absint( $recept['bereidingstijd'] ) ); ?>">
								</p>
								<p>
									<label for="bkr-moeilijkheid"><?php esc_html_e( 'Moeilijkheid', 'bellas-kitchen-recepten' ); ?></label>
									<select id="bkr-moeilijkheid" name="moeilijkheid">
										<?php foreach ( $this->getDifficulties() as $value => $label ) : ?>
											<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $recept['moeilijkheid'], $value ); ?>>
												<?php echo esc_html( $label ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</p>
								<p>
									<label for="bkr-soort-gerecht"><?php esc_html_e( 'Soort gerecht', 'bellas-kitchen-recepten' ); ?></label>
									<select id="bkr-soort-gerecht" name="soort_gerecht">
										<?php foreach ( $this->getMealTypes() as $value => $label ) : ?>
											<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $recept['soort_gerecht'], $value ); ?>>
												<?php echo esc_html( $label ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</p>
							</div>
						</div>

						<div class="postbox">
							<h2 class="hndle"><?php esc_html_e( 'Foto', 'bellas-kitchen-recepten' ); ?></h2>
							<div class="inside">
								<input type="hidden" id="bkr-foto-id" name="foto_id" value="<?php echo esc_attr( $foto_id ); ?>">
								<div id="bkr-foto-preview" class="bkr-foto-preview">
									<?php echo wp_kses_post( $foto_html ); ?>
								</div>
								<p>
									<button type="button" class="button bkr-select-image">
										<?php esc_html_e( 'Foto kiezen', 'bellas-kitchen-recepten' ); ?>
									</button>
									<button type="button" class="button bkr-remove-image" <?php echo $foto_id ? '' : 'style="display:none;"'; ?>>
										<?php esc_html_e( 'Verwijderen', 'bellas-kitchen-recepten' ); ?>
									</button>
								</p>
							</div>
						</div>
					</div>
				</div>

				<p class="submit">
					<button type="submit" class="button button-primary">
						<?php echo esc_html( $is_edit ? __( 'Recept bijwerken', 'bellas-kitchen-recepten' ) : __( 'Recept opslaan', 'bellas-kitchen-recepten' ) ); ?>
					</button>
					<a href="<?php echo esc_url( $this->getOverviewUrl() ); ?>" class="button">
						<?php esc_html_e( 'Annuleren', 'bellas-kitchen-recepten' ); ?>
					</a>
				</p>
			</form>
		</div>
		<?php
	}

	private function sanitizePostData(): array {
		$difficulty = isset( $_POST['moeilijkheid'] ) ? sanitize_key( wp_unslash( $_POST['moeilijkheid'] ) ) : 'makkelijk';
		$meal_type  = isset( $_POST['soort_gerecht'] ) ? sanitize_key( wp_unslash( $_POST['soort_gerecht'] ) ) : 'diner';

		if ( ! array_key_exists( $difficulty, $this->getDifficulties() ) ) {
			$difficulty = 'makkelijk';
		}

		if ( ! array_key_exists( $meal_type, $this->getMealTypes() ) ) {
			$meal_type = 'diner';
		}

		return [
			'naam'            => isset( $_POST['naam'] ) ? sanitize_text_field( wp_unslash( $_POST['naam'] ) ) : '',
			'beschrijving'    => isset( $_POST['beschrijving'] ) ? sanitize_textarea_field( wp_unslash( $_POST['beschrijving'] ) ) : '',
			'foto_id'         => isset( $_POST['foto_id'] ) ? absint( wp_unslash( $_POST['foto_id'] ) ) : 0,
			'ingredienten'    => $this->sanitizeIngredients(),
			'instructies'     => $this->sanitizeInstructions(),
			'aantal_personen' => isset( $_POST['aantal_personen'] ) ? absint( wp_unslash( $_POST['aantal_personen'] ) ) : 0,
			'bereidingstijd'  => isset( $_POST['bereidingstijd'] ) ? absint( wp_unslash( $_POST['bereidingstijd'] ) ) : 0,
			'moeilijkheid'    => $difficulty,
			'soort_gerecht'   => $meal_type,
		];
	}

	private function sanitizeIngredients(): array {
		if ( ! isset( $_POST['ingredienten'] ) || ! is_array( $_POST['ingredienten'] ) ) {
			return [];
		}

		$raw_rows    = wp_unslash( $_POST['ingredienten'] );
		$units       = $this->getUnits();
		$ingredients = [];

		foreach ( $raw_rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$item = $this->sanitizeIngredientTextValue( $row['item'] ?? '' );

			if ( $item === '' ) {
				continue;
			}

			$unit = $this->sanitizeIngredientKeyValue( $row['unit'] ?? '' );

			if ( ! array_key_exists( $unit, $units ) ) {
				$unit = '';
			}

			$ingredients[] = [
				'quantity' => $this->sanitizeIngredientTextValue( $row['quantity'] ?? '' ),
				'unit'     => $unit,
				'item'     => $item,
			];
		}

		return $ingredients;
	}

	private function sanitizeInstructions(): array {
		if ( ! isset( $_POST['instructies'] ) || ! is_array( $_POST['instructies'] ) ) {
			return [];
		}

		$raw_rows     = wp_unslash( $_POST['instructies'] );
		$instructions = [];

		foreach ( $raw_rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$text = $this->sanitizeInstructionTextValue( $row['text'] ?? '' );

			if ( $text === '' ) {
				continue;
			}

			$instructions[] = [ 'text' => $text ];
		}

		return $instructions;
	}

	private function getIngredientsForForm( $raw_ingredients ): array {
		$ingredients = [];

		if ( is_array( $raw_ingredients ) ) {
			foreach ( $raw_ingredients as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}

				$ingredients[] = $this->normalizeIngredientRow( $row );
			}
		} elseif ( is_string( $raw_ingredients ) && trim( $raw_ingredients ) !== '' ) {
			$decoded = json_decode( $raw_ingredients, true );

			if ( is_array( $decoded ) ) {
				return $this->getIngredientsForForm( $decoded );
			}

			$lines = preg_split( '/\r\n|\r|\n/', $raw_ingredients );

			foreach ( $lines as $line ) {
				$item = trim( $line );

				if ( $item === '' ) {
					continue;
				}

				$ingredients[] = $this->normalizeIngredientRow( $this->parseIngredientLine( $item ) );
			}
		}

		return ! empty( $ingredients ) ? $ingredients : [ $this->getEmptyIngredient() ];
	}

	private function getInstructionsForForm( $raw_instructions ): array {
		$instructions = [];

		if ( is_array( $raw_instructions ) ) {
			foreach ( $raw_instructions as $row ) {
				$instructions[] = $this->normalizeInstructionRow( $row );
			}
		} elseif ( is_string( $raw_instructions ) && trim( $raw_instructions ) !== '' ) {
			$decoded = json_decode( $raw_instructions, true );

			if ( is_array( $decoded ) ) {
				return $this->getInstructionsForForm( $decoded );
			}

			$lines = preg_split( '/\r\n|\r|\n/', $raw_instructions );

			foreach ( $lines as $line ) {
				$text = trim( $line );

				if ( $text === '' ) {
					continue;
				}

				$instructions[] = $this->normalizeInstructionRow( [ 'text' => $text ] );
			}
		}

		return ! empty( $instructions ) ? $instructions : [ $this->getEmptyInstruction() ];
	}

	private function normalizeIngredientRow( array $row ): array {
		$unit = $this->sanitizeIngredientKeyValue( $row['unit'] ?? '' );

		if ( ! array_key_exists( $unit, $this->getUnits() ) ) {
			$unit = '';
		}

		return [
			'quantity' => $this->sanitizeIngredientTextValue( $row['quantity'] ?? '' ),
			'unit'     => $unit,
			'item'     => $this->sanitizeIngredientTextValue( $row['item'] ?? '' ),
		];
	}

	private function normalizeInstructionRow( $row ): array {
		if ( is_scalar( $row ) ) {
			return [ 'text' => $this->sanitizeInstructionTextValue( $row ) ];
		}

		if ( ! is_array( $row ) ) {
			return $this->getEmptyInstruction();
		}

		return [ 'text' => $this->sanitizeInstructionTextValue( $row['text'] ?? '' ) ];
	}

	private function parseIngredientLine( string $line ): array {
		$row = [
			'quantity' => '',
			'unit'     => '',
			'item'     => $line,
		];

		if ( ! preg_match( '/^(\S+)\s+([a-z_]+)\s+(.+)$/i', $line, $matches ) ) {
			return $row;
		}

		$unit = sanitize_key( $matches[2] );

		if ( ! array_key_exists( $unit, $this->getUnits() ) ) {
			return $row;
		}

		return [
			'quantity' => $matches[1],
			'unit'     => $unit,
			'item'     => $matches[3],
		];
	}

	private function sanitizeIngredientTextValue( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return sanitize_text_field( (string) $value );
	}

	private function sanitizeIngredientKeyValue( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return sanitize_key( (string) $value );
	}

	private function sanitizeInstructionTextValue( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return sanitize_textarea_field( (string) $value );
	}

	private function getEmptyIngredient(): array {
		return [
			'quantity' => '',
			'unit'     => '',
			'item'     => '',
		];
	}

	private function getEmptyInstruction(): array {
		return [ 'text' => '' ];
	}

	private function getEmptyRecept(): array {
		return [
			'id'              => 0,
			'naam'            => '',
			'beschrijving'    => '',
			'foto_id'         => 0,
			'ingredienten'    => [],
			'instructies'     => [],
			'aantal_personen' => 0,
			'bereidingstijd'  => 0,
			'moeilijkheid'    => 'makkelijk',
			'soort_gerecht'   => 'diner',
			'created_at'      => '',
			'updated_at'      => '',
		];
	}

	private function renderNotice(): void {
		$message = isset( $_GET['message'] ) ? sanitize_key( wp_unslash( $_GET['message'] ) ) : '';

		if ( $message === '' ) {
			return;
		}

		$messages = [
			'created'       => __( 'Recept opgeslagen.', 'bellas-kitchen-recepten' ),
			'updated'       => __( 'Recept bijgewerkt.', 'bellas-kitchen-recepten' ),
			'deleted'       => __( 'Recept verwijderd.', 'bellas-kitchen-recepten' ),
			'missing-name'  => __( 'Vul een naam in voor het recept.', 'bellas-kitchen-recepten' ),
			'save-failed'   => __( 'Het recept kon niet worden opgeslagen.', 'bellas-kitchen-recepten' ),
			'delete-failed' => __( 'Het recept kon niet worden verwijderd.', 'bellas-kitchen-recepten' ),
		];

		if ( ! isset( $messages[ $message ] ) ) {
			return;
		}

		$type = in_array( $message, [ 'missing-name', 'save-failed', 'delete-failed' ], true ) ? 'error' : 'success';
		?>
		<div class="notice notice-<?php echo esc_attr( $type ); ?> is-dismissible">
			<p><?php echo esc_html( $messages[ $message ] ); ?></p>
		</div>
		<?php
	}

	private function getImageHtml( int $foto_id, string $size = 'thumbnail' ): string {
		if ( $foto_id <= 0 ) {
			return '<span class="bkr-no-image">' . esc_html__( 'Geen foto', 'bellas-kitchen-recepten' ) . '</span>';
		}

		$image = wp_get_attachment_image(
			$foto_id,
			$size,
			false,
			[
				'class' => 'bkr-recepten-thumb',
				'alt'   => '',
			]
		);

		return $image ?: '<span class="bkr-no-image">' . esc_html__( 'Geen foto', 'bellas-kitchen-recepten' ) . '</span>';
	}

	private function getDifficulties(): array {
		return [
			'makkelijk' => __( 'Makkelijk', 'bellas-kitchen-recepten' ),
			'gemiddeld' => __( 'Gemiddeld', 'bellas-kitchen-recepten' ),
			'moeilijk'  => __( 'Moeilijk', 'bellas-kitchen-recepten' ),
		];
	}

	private function getMealTypes(): array {
		return [
			'ontbijt'       => __( 'Ontbijt', 'bellas-kitchen-recepten' ),
			'lunch'         => __( 'Lunch', 'bellas-kitchen-recepten' ),
			'diner'         => __( 'Diner', 'bellas-kitchen-recepten' ),
			'bijgerecht'    => __( 'Bijgerecht', 'bellas-kitchen-recepten' ),
			'tussendoortje' => __( 'Tussendoortje', 'bellas-kitchen-recepten' ),
			'dessert'       => __( 'Dessert', 'bellas-kitchen-recepten' ),
			'drankje'       => __( 'Drankje', 'bellas-kitchen-recepten' ),
		];
	}

	private function getUnits(): array {
		return [
			''            => __( '- geen -', 'bellas-kitchen-recepten' ),
			'ml'          => __( 'ml', 'bellas-kitchen-recepten' ),
			'l'           => __( 'l', 'bellas-kitchen-recepten' ),
			'g'           => __( 'g (gram)', 'bellas-kitchen-recepten' ),
			'kg'          => __( 'kg (kilogram)', 'bellas-kitchen-recepten' ),
			'tl'          => __( 'tl', 'bellas-kitchen-recepten' ),
			'el'          => __( 'el', 'bellas-kitchen-recepten' ),
			'snufje'      => __( 'snufje', 'bellas-kitchen-recepten' ),
			'stuks'       => __( 'stuks', 'bellas-kitchen-recepten' ),
			'naar_smaak' => __( 'naar smaak', 'bellas-kitchen-recepten' ),
		];
	}

	private function getDifficultyLabel( string $value ): string {
		$difficulties = $this->getDifficulties();

		return $difficulties[ $value ] ?? $value;
	}

	private function getMealTypeLabel( string $value ): string {
		$meal_types = $this->getMealTypes();

		return $meal_types[ $value ] ?? $value;
	}

	private function formatServings( int $servings ): string {
		if ( $servings <= 0 ) {
			return '-';
		}

		return sprintf(
			/* translators: %d: number of people. */
			_n( '%d persoon', '%d personen', $servings, 'bellas-kitchen-recepten' ),
			$servings
		);
	}

	private function getNumberInputValue( int $value ): string {
		return $value > 0 ? (string) $value : '';
	}

	private function formatDate( string $date ): string {
		if ( $date === '' ) {
			return '-';
		}

		return mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $date );
	}

	private function getOverviewUrl(): string {
		return admin_url( 'admin.php?page=' . self::MENU_SLUG );
	}

	private function getAddUrl(): string {
		return admin_url( 'admin.php?page=' . self::ADD_SLUG );
	}

	private function getEditUrl( int $id ): string {
		return add_query_arg(
			[
				'page'      => self::EDIT_SLUG,
				'recept_id' => $id,
			],
			admin_url( 'admin.php' )
		);
	}

	private function redirectToOverview( string $message ): void {
		wp_safe_redirect(
			add_query_arg(
				[ 'message' => $message ],
				$this->getOverviewUrl()
			)
		);
		exit;
	}

	private function redirectToForm( int $id, string $message ): void {
		$url = $id > 0 ? $this->getEditUrl( $id ) : $this->getAddUrl();

		wp_safe_redirect(
			add_query_arg(
				[ 'message' => $message ],
				$url
			)
		);
		exit;
	}
}
