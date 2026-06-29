<?php
/**
 * Admin pages for managing recepten.
 *
 * @package BellasKitchenRecepten
 */

namespace BellasKitchenRecepten\Admin;

use BellasKitchenRecepten\AI\OpenAIRecipeUrlParser;
use BellasKitchenRecepten\Database\ReceptRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReceptenAdminPage {

	private const MENU_SLUG = 'bellas-kitchen-recepten';
	private const ADD_SLUG      = 'bellas-kitchen-recepten-add';
	private const TEMPLATE_SLUG = 'bellas-kitchen-recepten-template';
	private const SETTINGS_SLUG = 'bellas-kitchen-recepten-settings';
	private const EDIT_SLUG     = 'bellas-kitchen-recepten-edit';

	/**
	 * @var ReceptRepository
	 */
	private $repository;

	/**
	 * @var OpenAIRecipeUrlParser
	 */
	private $recipe_url_parser;

	public function __construct( ReceptRepository $repository, OpenAIRecipeUrlParser $recipe_url_parser ) {
		$this->repository        = $repository;
		$this->recipe_url_parser = $recipe_url_parser;
	}

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'registerMenu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAssets' ] );
		add_action( 'admin_post_bkr_save_recept', [ $this, 'handleSave' ] );
		add_action( 'admin_post_bkr_import_recept_template', [ $this, 'handleTemplateImport' ] );
		add_action( 'admin_post_bkr_save_openai_settings', [ $this, 'handleSaveSettings' ] );
		add_action( 'admin_post_bkr_delete_recept', [ $this, 'handleDelete' ] );
		add_action( 'wp_ajax_bkr_generate_template_from_url', [ $this, 'handleGenerateTemplateFromUrl' ] );
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
			self::MENU_SLUG,
			__( 'Voeg toe via template', 'bellas-kitchen-recepten' ),
			__( 'Voeg toe via template', 'bellas-kitchen-recepten' ),
			'manage_options',
			self::TEMPLATE_SLUG,
			[ $this, 'renderTemplatePage' ]
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Instellingen', 'bellas-kitchen-recepten' ),
			__( 'Instellingen', 'bellas-kitchen-recepten' ),
			'manage_options',
			self::SETTINGS_SLUG,
			[ $this, 'renderSettingsPage' ]
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

		if ( ! in_array( $page, [ self::MENU_SLUG, self::ADD_SLUG, self::TEMPLATE_SLUG, self::SETTINGS_SLUG, self::EDIT_SLUG ], true ) ) {
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
				'ajaxUrl'               => admin_url( 'admin-ajax.php' ),
				'generateTemplateNonce' => wp_create_nonce( 'bkr_generate_template_from_url' ),
				'mediaTitle'            => __( 'Kies een foto', 'bellas-kitchen-recepten' ),
				'mediaButton'           => __( 'Gebruik deze foto', 'bellas-kitchen-recepten' ),
				'noImageText'           => __( 'Geen foto', 'bellas-kitchen-recepten' ),
				'confirmReplaceText'    => __( 'De huidige template-invoer wordt vervangen. Wil je doorgaan?', 'bellas-kitchen-recepten' ),
				'missingUrlText'        => __( 'Vul eerst een URL in naar een online recept.', 'bellas-kitchen-recepten' ),
				'generatingText'        => __( 'De receptpagina wordt via ChatGPT verwerkt...', 'bellas-kitchen-recepten' ),
				'generatedText'         => __( 'De template is toegevoegd aan het invoerveld.', 'bellas-kitchen-recepten' ),
				'requestFailedText'     => __( 'De template kon niet worden opgehaald. Probeer het opnieuw.', 'bellas-kitchen-recepten' ),
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
			<a href="<?php echo esc_url( $this->getTemplateAddUrl() ); ?>" class="page-title-action">
				<?php esc_html_e( 'Voeg toe via template', 'bellas-kitchen-recepten' ); ?>
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

	public function renderTemplatePage(): void {
		$flash_state = $this->consumeTemplateImportState();
		$template    = is_array( $flash_state ) ? (string) ( $flash_state['template_input'] ?? '' ) : '';
		$error       = is_array( $flash_state ) ? (string) ( $flash_state['error_message'] ?? '' ) : '';

		$this->renderTemplateImportPage( $template, $error );
	}

	public function renderSettingsPage(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Je hebt geen rechten om deze pagina te bekijken.', 'bellas-kitchen-recepten' ) );
		}

		$api_key = $this->recipe_url_parser->getApiKey();
		?>
		<div class="wrap bkr-recepten-admin">
			<h1><?php esc_html_e( 'OpenAI instellingen', 'bellas-kitchen-recepten' ); ?></h1>
			<p><?php esc_html_e( 'Voeg hier je OpenAI API-sleutel toe voor de URL-naar-template import op de receptenpagina.', 'bellas-kitchen-recepten' ); ?></p>

			<?php $this->renderNotice(); ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bkr-settings-form">
				<input type="hidden" name="action" value="bkr_save_openai_settings">
				<?php wp_nonce_field( 'bkr_save_openai_settings', 'bkr_openai_settings_nonce' ); ?>

				<div class="postbox bkr-settings-box">
					<h2 class="hndle"><?php esc_html_e( 'API sleutel', 'bellas-kitchen-recepten' ); ?></h2>
					<div class="inside">
						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row"><label for="bkr-openai-api-key"><?php esc_html_e( 'OpenAI API key', 'bellas-kitchen-recepten' ); ?></label></th>
									<td>
										<input type="password" id="bkr-openai-api-key" name="openai_api_key" class="regular-text code" value="" autocomplete="new-password" spellcheck="false">
										<?php if ( '' !== $api_key ) : ?>
											<p class="description">
												<?php
												printf(
													/* translators: %s: masked API key. */
													esc_html__( 'Er is al een sleutel opgeslagen (%s). Laat dit veld leeg om die te behouden, of vul een nieuwe sleutel in om die te vervangen.', 'bellas-kitchen-recepten' ),
													esc_html( $this->maskApiKey( $api_key ) )
												);
												?>
											</p>
											<p>
												<label for="bkr-openai-api-key-clear">
													<input type="checkbox" id="bkr-openai-api-key-clear" name="openai_api_key_clear" value="1">
													<?php esc_html_e( 'Verwijder de opgeslagen API-sleutel', 'bellas-kitchen-recepten' ); ?>
												</label>
											</p>
										<?php else : ?>
											<p class="description"><?php esc_html_e( 'Plak hier je OpenAI API-sleutel. Deze wordt alleen gebruikt voor de template-import via URL.', 'bellas-kitchen-recepten' ); ?></p>
										<?php endif; ?>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>

				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Instellingen opslaan', 'bellas-kitchen-recepten' ); ?></button>
				</p>
			</form>
		</div>
		<?php
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

	public function handleTemplateImport(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Je hebt geen rechten om recepten te beheren.', 'bellas-kitchen-recepten' ) );
		}

		check_admin_referer( 'bkr_import_recept_template', 'bkr_template_nonce' );

		$template_input = isset( $_POST['template_input'] ) ? (string) wp_unslash( $_POST['template_input'] ) : '';
		$data           = $this->parseTemplateInput( $template_input );

		if ( is_wp_error( $data ) ) {
			$this->storeTemplateImportState( $template_input, $data->get_error_message() );
			$this->redirectToTemplateAdd();
		}

		$recept_id = $this->repository->create( $data );

		if ( $recept_id <= 0 ) {
			$this->storeTemplateImportState(
				$template_input,
				__( 'Het recept kon niet worden opgeslagen op basis van deze template.', 'bellas-kitchen-recepten' )
			);
			$this->redirectToTemplateAdd();
		}

		$this->redirectToEdit( $recept_id, 'created' );
	}

	public function handleSaveSettings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Je hebt geen rechten om deze instellingen op te slaan.', 'bellas-kitchen-recepten' ) );
		}

		check_admin_referer( 'bkr_save_openai_settings', 'bkr_openai_settings_nonce' );

		$clear_api_key = isset( $_POST['openai_api_key_clear'] ) && '1' === wp_unslash( $_POST['openai_api_key_clear'] );
		$api_key       = isset( $_POST['openai_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['openai_api_key'] ) ) : '';

		if ( $clear_api_key ) {
			$this->recipe_url_parser->clearApiKey();
			$this->redirectToSettings( 'settings-saved' );
		}

		if ( '' !== $api_key ) {
			$this->recipe_url_parser->updateApiKey( $api_key );
		}

		$this->redirectToSettings( 'settings-saved' );
	}

	public function handleGenerateTemplateFromUrl(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Je hebt geen rechten om deze actie uit te voeren.', 'bellas-kitchen-recepten' ),
				],
				403
			);
		}

		check_ajax_referer( 'bkr_generate_template_from_url', 'nonce' );

		$source_url = isset( $_POST['source_url'] ) ? esc_url_raw( trim( (string) wp_unslash( $_POST['source_url'] ) ) ) : '';

		if ( '' === $source_url || ! wp_http_validate_url( $source_url ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Voer een geldige URL in naar een online recept.', 'bellas-kitchen-recepten' ),
				],
				400
			);
		}

		$template = $this->recipe_url_parser->generateTemplateFromUrl( $source_url );

		if ( is_wp_error( $template ) ) {
			wp_send_json_error(
				[
					'message' => $template->get_error_message(),
				],
				500
			);
		}

		wp_send_json_success(
			[
				'template' => $template,
			]
		);
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
														name="ingredienten[<?php echo esc_attr( $index ); ?>][category]"
														value="<?php echo esc_attr( $ingredient['category'] ); ?>"
														placeholder="<?php esc_attr_e( 'Categorie', 'bellas-kitchen-recepten' ); ?>"
														maxlength="100"
														class="regular-text bkr-ingredient-category">
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
												<input type="text" name="ingredienten[{{index}}][category]" value="" placeholder="<?php esc_attr_e( 'Categorie', 'bellas-kitchen-recepten' ); ?>" maxlength="100" class="regular-text bkr-ingredient-category">
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
									<label for="bkr-oven-temperatuur"><?php esc_html_e( 'Oventemperatuur (°C)', 'bellas-kitchen-recepten' ); ?></label>
									<input type="number" id="bkr-oven-temperatuur" name="oven_temperatuur" min="0" step="1" class="small-text" value="<?php echo esc_attr( $this->getNumberInputValue( absint( $recept['oven_temperatuur'] ?? 0 ) ) ); ?>">
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

	private function renderTemplateImportPage( string $template_input = '', string $error_message = '' ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Je hebt geen rechten om deze pagina te bekijken.', 'bellas-kitchen-recepten' ) );
		}

		$template_example = $this->getTemplateExample();
		$has_api_key      = $this->recipe_url_parser->hasApiKey();
		?>
		<div class="wrap bkr-recepten-admin">
			<h1><?php esc_html_e( 'Voeg toe via template', 'bellas-kitchen-recepten' ); ?></h1>
			<p><?php esc_html_e( 'Plak hieronder een volledig recept als tekst. De foto wordt genegeerd, de rest van het recept wordt direct aangemaakt.', 'bellas-kitchen-recepten' ); ?></p>

			<?php if ( '' !== $error_message ) : ?>
				<div class="notice notice-error">
					<p><?php echo esc_html( $error_message ); ?></p>
				</div>
			<?php endif; ?>

			<div class="bkr-template-layout">
				<div class="bkr-template-editor">
					<div class="postbox">
						<h2 class="hndle"><?php esc_html_e( 'Haal op via URL', 'bellas-kitchen-recepten' ); ?></h2>
						<div class="inside">
							<div class="bkr-template-url-bar">
								<input type="url" id="bkr-template-source-url" class="regular-text code" value="" placeholder="https://voorbeeld.nl/recept/..." inputmode="url" spellcheck="false">
								<button type="button" id="bkr-generate-template" class="button" <?php disabled( ! $has_api_key ); ?>>
									<?php esc_html_e( 'Ophalen met ChatGPT', 'bellas-kitchen-recepten' ); ?>
								</button>
								<span id="bkr-template-generate-spinner" class="spinner" aria-hidden="true"></span>
							</div>
							<p class="description"><?php esc_html_e( 'Plak een URL van een online recept en laat ChatGPT de template hieronder invullen. Daarna kun je de tekst nog controleren voordat je het recept opslaat.', 'bellas-kitchen-recepten' ); ?></p>
							<?php if ( ! $has_api_key ) : ?>
								<p class="bkr-template-inline-notice">
									<?php
									printf(
										wp_kses(
											__( 'Voeg eerst een OpenAI API-sleutel toe op de <a href="%s">instellingenpagina</a>.', 'bellas-kitchen-recepten' ),
											[
												'a' => [
													'href' => [],
												],
											]
										),
										esc_url( $this->getSettingsUrl() )
									);
									?>
								</p>
							<?php endif; ?>
							<div id="bkr-template-generation-status" class="notice inline" hidden>
								<p></p>
							</div>
						</div>
					</div>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="bkr_import_recept_template">
						<?php wp_nonce_field( 'bkr_import_recept_template', 'bkr_template_nonce' ); ?>

						<div class="postbox">
							<h2 class="hndle"><?php esc_html_e( 'Template invoer', 'bellas-kitchen-recepten' ); ?></h2>
							<div class="inside">
								<textarea id="bkr-template-input" name="template_input" rows="26" class="large-text code bkr-template-input" spellcheck="false"><?php echo esc_textarea( $template_input ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Gebruik de blokken uit het voorbeeld. Je kunt de waardes gewoon vervangen door je eigen recept.', 'bellas-kitchen-recepten' ); ?></p>
							</div>
						</div>

						<p class="submit">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Recept aanmaken', 'bellas-kitchen-recepten' ); ?></button>
							<a href="<?php echo esc_url( $this->getOverviewUrl() ); ?>" class="button"><?php esc_html_e( 'Annuleren', 'bellas-kitchen-recepten' ); ?></a>
						</p>
					</form>
				</div>

				<div class="bkr-template-sidebar">
					<div class="postbox">
						<h2 class="hndle"><?php esc_html_e( 'Template voorbeeld', 'bellas-kitchen-recepten' ); ?></h2>
						<div class="inside">
							<pre class="bkr-template-example"><code><?php echo esc_html( $template_example ); ?></code></pre>
						</div>
					</div>

					<div class="postbox">
						<h2 class="hndle"><?php esc_html_e( 'Geldige waardes', 'bellas-kitchen-recepten' ); ?></h2>
						<div class="inside">
							<p><strong><?php esc_html_e( 'Moeilijkheid:', 'bellas-kitchen-recepten' ); ?></strong> <?php echo esc_html( implode( ', ', array_keys( $this->getDifficulties() ) ) ); ?></p>
							<p><strong><?php esc_html_e( 'Soort gerecht:', 'bellas-kitchen-recepten' ); ?></strong> <?php echo esc_html( implode( ', ', array_keys( $this->getMealTypes() ) ) ); ?></p>
							<p><strong><?php esc_html_e( 'Eenheden:', 'bellas-kitchen-recepten' ); ?></strong> <?php echo esc_html( implode( ', ', array_filter( array_keys( $this->getUnits() ) ) ) ); ?></p>
						</div>
					</div>
				</div>
			</div>
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
			'naam'             => isset( $_POST['naam'] ) ? sanitize_text_field( wp_unslash( $_POST['naam'] ) ) : '',
			'beschrijving'     => isset( $_POST['beschrijving'] ) ? sanitize_textarea_field( wp_unslash( $_POST['beschrijving'] ) ) : '',
			'foto_id'          => isset( $_POST['foto_id'] ) ? absint( wp_unslash( $_POST['foto_id'] ) ) : 0,
			'ingredienten'     => $this->sanitizeIngredients(),
			'instructies'      => $this->sanitizeInstructions(),
			'aantal_personen'  => isset( $_POST['aantal_personen'] ) ? absint( wp_unslash( $_POST['aantal_personen'] ) ) : 0,
			'bereidingstijd'   => isset( $_POST['bereidingstijd'] ) ? absint( wp_unslash( $_POST['bereidingstijd'] ) ) : 0,
			'oven_temperatuur' => isset( $_POST['oven_temperatuur'] ) ? absint( wp_unslash( $_POST['oven_temperatuur'] ) ) : 0,
			'moeilijkheid'     => $difficulty,
			'soort_gerecht'    => $meal_type,
		];
	}

	private function parseTemplateInput( string $template_input ) {
		$template_input = trim( $template_input );

		if ( '' === $template_input ) {
			return new \WP_Error(
				'template-empty',
				__( 'Plak eerst een template voordat je het recept aanmaakt.', 'bellas-kitchen-recepten' )
			);
		}

		$naam              = $this->extractTemplateTagValue( $template_input, [ 'Naam', 'Name', 'Title' ] );
		$beschrijving      = $this->extractTemplateTagValue( $template_input, [ 'Beschrijving', 'Description' ] );
		$aantal_personen   = $this->extractTemplateTagValue( $template_input, [ 'AantalPersonen', 'Servings', 'Personen' ] );
		$bereidingstijd    = $this->extractTemplateTagValue( $template_input, [ 'Bereidingstijd', 'Time', 'PreparationTime' ] );
		$oven_temperatuur  = $this->extractTemplateTagValue( $template_input, [ 'Oventemperatuur', 'OvenTemperatuur', 'OvenTemperature' ] );
		$moeilijkheid_raw  = $this->extractTemplateTagValue( $template_input, [ 'Moeilijkheid', 'Difficulty' ] );
		$soort_gerecht_raw = $this->extractTemplateTagValue( $template_input, [ 'SoortGerecht', 'MealType' ] );
		$ingredienten      = $this->parseTemplateIngredients( $template_input );
		$instructies       = $this->parseTemplateInstructions( $template_input );

		if ( '' === $naam ) {
			return new \WP_Error(
				'template-missing-name',
				__( 'De template moet minstens een [Naam] of [Name] blok bevatten.', 'bellas-kitchen-recepten' )
			);
		}

		if ( is_wp_error( $ingredienten ) ) {
			return $ingredienten;
		}

		if ( is_wp_error( $instructies ) ) {
			return $instructies;
		}

		$moeilijkheid = $this->normalizeTemplateDifficulty( $moeilijkheid_raw );

		if ( is_wp_error( $moeilijkheid ) ) {
			return $moeilijkheid;
		}

		$soort_gerecht = $this->normalizeTemplateMealType( $soort_gerecht_raw );

		if ( is_wp_error( $soort_gerecht ) ) {
			return $soort_gerecht;
		}

		return [
			'naam'             => sanitize_text_field( $naam ),
			'beschrijving'     => sanitize_textarea_field( $beschrijving ),
			'foto_id'          => 0,
			'ingredienten'     => $ingredienten,
			'instructies'      => $instructies,
			'aantal_personen'  => absint( $aantal_personen ),
			'bereidingstijd'   => absint( $bereidingstijd ),
			'oven_temperatuur' => absint( $oven_temperatuur ),
			'moeilijkheid'     => $moeilijkheid,
			'soort_gerecht'    => $soort_gerecht,
		];
	}

	private function parseTemplateIngredients( string $template_input ) {
		$ingredienten_block = $this->extractTemplateTagValue( $template_input, [ 'Ingredienten', 'Ingredients' ] );

		if ( '' === $ingredienten_block ) {
			return [];
		}

		$ingredient_lines = $this->extractTemplateTagValues( $ingredienten_block, [ 'Ingredient' ] );

		if ( empty( $ingredient_lines ) ) {
			$ingredient_lines = preg_split( '/\r\n|\r|\n/', $ingredienten_block );
		}

		$ingredienten     = [];
		$current_category = '';

		foreach ( $ingredient_lines as $ingredient_line ) {
			$category_group = $this->parseTemplateIngredientCategoryGroup( $ingredient_line );

			if ( null !== $category_group ) {
				$ingredienten = array_merge( $ingredienten, $category_group );
				continue;
			}

			$category_heading = $this->parseTemplateIngredientCategoryHeading( $ingredient_line );

			if ( null !== $category_heading ) {
				$current_category = $category_heading;
				continue;
			}

			$ingredient = $this->parseTemplateIngredientEntry( $ingredient_line, $current_category );

			if ( null === $ingredient ) {
				continue;
			}

			$ingredienten[] = $ingredient;
		}

		return $ingredienten;
	}

	private function parseTemplateInstructions( string $template_input ) {
		$stappen_block = $this->extractTemplateTagValue( $template_input, [ 'Stappen', 'Steps', 'Instructions' ] );

		if ( '' === $stappen_block ) {
			return [];
		}

		$step_lines = $this->extractTemplateTagValues( $stappen_block, [ 'Stap', 'Step' ] );

		if ( empty( $step_lines ) ) {
			$step_lines = preg_split( '/\r\n|\r|\n/', $stappen_block );
		}

		$instructies = [];

		foreach ( $step_lines as $step_line ) {
			$text = $this->sanitizeInstructionTextValue( trim( $step_line ) );

			if ( '' === $text ) {
				continue;
			}

			$instructies[] = [ 'text' => $text ];
		}

		return $instructies;
	}

	private function parseTemplateIngredientCategoryGroup( string $ingredient_line ): ?array {
		$ingredient_line = trim( $ingredient_line );

		if ( '' === $ingredient_line || false !== strpos( $ingredient_line, '|' ) ) {
			return null;
		}

		$ingredient_line = trim( $ingredient_line, "[] \t\n\r\0\x0B" );

		if ( ! preg_match( '/^([^:]+):\s*(.+)$/u', $ingredient_line, $matches ) ) {
			return null;
		}

		$category = $this->sanitizeIngredientCategoryValue( $matches[1] );
		$items    = array_map( 'trim', explode( ',', $matches[2] ) );

		if ( '' === $category || empty( $items ) ) {
			return null;
		}

		$ingredients = [];

		foreach ( $items as $item ) {
			$item = $this->sanitizeIngredientTextValue( $item );

			if ( '' === $item ) {
				continue;
			}

			$ingredients[] = [
				'category' => $category,
				'quantity' => '',
				'unit'     => '',
				'item'     => $item,
			];
		}

		return ! empty( $ingredients ) ? $ingredients : null;
	}

	private function parseTemplateIngredientCategoryHeading( string $ingredient_line ): ?string {
		$ingredient_line = trim( $ingredient_line );

		if ( '' === $ingredient_line || false !== strpos( $ingredient_line, '|' ) ) {
			return null;
		}

		if ( preg_match( '/^\[?([^:\[\]]+):\]?$/u', $ingredient_line, $matches ) || preg_match( '/^\[([^\[\]]+)\]$/u', $ingredient_line, $matches ) ) {
			$category = $this->sanitizeIngredientCategoryValue( $matches[1] );

			return '' !== $category ? $category : null;
		}

		return null;
	}

	private function parseTemplateIngredientEntry( string $ingredient_line, string $category = '' ): ?array {
		$ingredient_line = trim( $ingredient_line );
		$category        = $this->sanitizeIngredientCategoryValue( $category );

		if ( '' === $ingredient_line ) {
			return null;
		}

		if ( false !== strpos( $ingredient_line, '|' ) ) {
			$parts = array_map( 'trim', explode( '|', $ingredient_line, 4 ) );

			if ( 4 === count( $parts ) ) {
				$entry_category = $this->sanitizeIngredientCategoryValue( $parts[0] );
				$quantity       = $this->sanitizeIngredientTextValue( $parts[1] );
				$unit           = $this->sanitizeIngredientKeyValue( $parts[2] );
				$item           = $this->sanitizeIngredientTextValue( $parts[3] );

				if ( ! array_key_exists( $unit, $this->getUnits() ) ) {
					$item = $this->sanitizeIngredientTextValue( trim( $parts[2] . ' ' . $parts[3] ) );
					$unit = '';
				}

				if ( '' === $item ) {
					return null;
				}

				return [
					'category' => '' !== $entry_category ? $entry_category : $category,
					'quantity' => $quantity,
					'unit'     => $unit,
					'item'     => $item,
				];
			}

			if ( 3 === count( $parts ) ) {
				$quantity = $this->sanitizeIngredientTextValue( $parts[0] );
				$unit     = $this->sanitizeIngredientKeyValue( $parts[1] );
				$item     = $this->sanitizeIngredientTextValue( $parts[2] );

				if ( ! array_key_exists( $unit, $this->getUnits() ) ) {
					$item = $this->sanitizeIngredientTextValue( trim( $parts[1] . ' ' . $parts[2] ) );
					$unit = '';
				}

				if ( '' === $item ) {
					return null;
				}

				return [
					'category' => $category,
					'quantity' => $quantity,
					'unit'     => $unit,
					'item'     => $item,
				];
			}

			if ( 2 === count( $parts ) ) {
				$item = $this->sanitizeIngredientTextValue( $parts[1] );

				if ( '' === $item ) {
					return null;
				}

				return [
					'category' => $category,
					'quantity' => $this->sanitizeIngredientTextValue( $parts[0] ),
					'unit'     => '',
					'item'     => $item,
				];
			}
		}

		$parsed = $this->parseIngredientLine( $ingredient_line );

		if ( '' !== $parsed['quantity'] || '' !== $parsed['unit'] || $parsed['item'] !== $ingredient_line ) {
			$parsed['category'] = $category;

			return $parsed;
		}

		if ( preg_match( '/^(\S+)\s+(.+)$/', $ingredient_line, $matches ) ) {
			return [
				'category' => $category,
				'quantity' => $this->sanitizeIngredientTextValue( $matches[1] ),
				'unit'     => '',
				'item'     => $this->sanitizeIngredientTextValue( $matches[2] ),
			];
		}

		return [
			'category' => $category,
			'quantity' => '',
			'unit'     => '',
			'item'     => $this->sanitizeIngredientTextValue( $ingredient_line ),
		];
	}

	private function normalizeTemplateDifficulty( string $value ) {
		$value = sanitize_key( $value );

		if ( '' === $value ) {
			return 'makkelijk';
		}

		$map = [
			'makkelijk' => 'makkelijk',
			'easy'      => 'makkelijk',
			'gemiddeld' => 'gemiddeld',
			'medium'    => 'gemiddeld',
			'moeilijk'  => 'moeilijk',
			'hard'      => 'moeilijk',
		];

		if ( isset( $map[ $value ] ) ) {
			return $map[ $value ];
		}

		return new \WP_Error(
			'template-invalid-difficulty',
			__( 'Onbekende moeilijkheid in de template. Gebruik makkelijk, gemiddeld of moeilijk.', 'bellas-kitchen-recepten' )
		);
	}

	private function normalizeTemplateMealType( string $value ) {
		$value = sanitize_key( $value );

		if ( '' === $value ) {
			return 'diner';
		}

		$map = [
			'ontbijt'       => 'ontbijt',
			'breakfast'     => 'ontbijt',
			'lunch'         => 'lunch',
			'diner'         => 'diner',
			'dinner'        => 'diner',
			'bijgerecht'    => 'bijgerecht',
			'side'          => 'bijgerecht',
			'side_dish'     => 'bijgerecht',
			'tussendoortje' => 'tussendoortje',
			'snack'         => 'tussendoortje',
			'dessert'       => 'dessert',
			'drankje'       => 'drankje',
			'drink'         => 'drankje',
			'drinks'        => 'drankje',
		];

		if ( isset( $map[ $value ] ) ) {
			return $map[ $value ];
		}

		return new \WP_Error(
			'template-invalid-meal-type',
			__( 'Onbekend soort gerecht in de template. Gebruik bijvoorbeeld ontbijt, lunch, diner, bijgerecht, tussendoortje, dessert of drankje.', 'bellas-kitchen-recepten' )
		);
	}

	private function extractTemplateTagValue( string $template_input, array $tag_names ): string {
		foreach ( $tag_names as $tag_name ) {
			if ( preg_match( '/\[' . preg_quote( $tag_name, '/' ) . '\](.*?)\[\/' . preg_quote( $tag_name, '/' ) . '\]/is', $template_input, $matches ) ) {
				return trim( (string) $matches[1] );
			}
		}

		return '';
	}

	private function extractTemplateTagValues( string $template_input, array $tag_names ): array {
		foreach ( $tag_names as $tag_name ) {
			if ( preg_match_all( '/\[' . preg_quote( $tag_name, '/' ) . '\](.*?)\[\/' . preg_quote( $tag_name, '/' ) . '\]/is', $template_input, $matches ) ) {
				return array_map(
					static function ( $value ) {
						return trim( (string) $value );
					},
					$matches[1]
				);
			}
		}

		return [];
	}

	private function getTemplateExample(): string {
		return implode(
			"\n",
			[
'You are a recipe parser.',
'I will give you a URL to a recipe page. Your task is to extract the recipe EXACTLY as written on the page and convert it into the template format below.',
'IMPORTANT RULES:',
'Do NOT invent, simplify, or approximate anything.',
'Only use information explicitly present on the page.',
'If something is missing on the page, leave it empty or omit it (do not guess).',
'Keep ingredient quantities, units, and names as written, but normalize units to the allowed list where possible.',
'If ingredients are grouped under headings like sauce, topping, dough, vulling, or garnering, keep that heading as the ingredient category.',
'Keep the number of steps and their meaning exactly the same (you may split or merge slightly only if needed for clarity, but do not change content).',
'Only set oven temperature if the page explicitly mentions one. Use Celsius as a number only.',
'Do not add extra explanations outside the template.',

'Allowed values: Moeilijkheid: makkelijk, gemiddeld, moeilijk SoortGerecht: ontbijt, lunch, diner, bijgerecht, tussendoortje, dessert, drankje Eenheden: ml, l, g, kg, tl, el, snufje, stuks, naar_smaak',
'Template: ',

'[Naam][/Naam]',
'[Beschrijving][/Beschrijving]',
'[AantalPersonen][/AantalPersonen]',
'[Bereidingstijd][/Bereidingstijd]',
'[Oventemperatuur][/Oventemperatuur]',
'[Moeilijkheid][/Moeilijkheid]',
'[SoortGerecht][/SoortGerecht]',
'[Ingredienten] categorie | hoeveelheid | eenheid | ingrediënt [/Ingredienten]',
'[Stappen] stap [/Stappen]',

'Example template:',
'[Naam]Pasta met spinazie en room[/Naam]',
'[Beschrijving]Een snelle doordeweekse pasta met veel smaak.[/Beschrijving]',
'[AantalPersonen]4[/AantalPersonen]',
'[Bereidingstijd]25[/Bereidingstijd]',
'[Oventemperatuur][/Oventemperatuur]',
'[Moeilijkheid]makkelijk[/Moeilijkheid]',
'[SoortGerecht]diner[/SoortGerecht]',
'[Ingredienten]',
'Basis | 2 | el | olijfolie',
'Basis | 1 | | ui',
'Basis | 2 | stuks | knoflooktenen',
'Basis | 250 | g | pasta',
'Saus | 200 | ml | kookroom',
'Saus | 150 | g | spinazie naar smaak',
'Saus | | | peper en zout',
'[/Ingredienten]',
'[Stappen]',
'Fruit de ui en knoflook in de olie.',
'Kook de pasta gaar volgens de verpakking.',
'Voeg de room en spinazie toe en laat kort slinken.',
'Meng alles met de pasta en breng op smaak.',
'[/Stappen]',

'Additional rules:',
'Convert ranges like "1-2 tl" into a single line (keep original format if unclear).',
'If “naar smaak” is mentioned, use: categorie | | naar_smaak | ingrediënt (leave categorie empty if none is given).',
'If no ingredient category is given, leave the category field empty.',
'If no unit is given, leave the unit field empty.',
'Keep ordering exactly the same as on the page.',
'Strip unnecessary text like tips, ads, or story content.',

'Now process this URL: {{URL}}'			]
		);
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
				'category' => $this->sanitizeIngredientCategoryValue( $row['category'] ?? '' ),
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
			'category' => $this->sanitizeIngredientCategoryValue( $row['category'] ?? '' ),
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

	private function sanitizeIngredientCategoryValue( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = sanitize_text_field( (string) $value );

		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, 100 ) : substr( $value, 0, 100 );
	}

	private function sanitizeInstructionTextValue( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return sanitize_textarea_field( (string) $value );
	}

	private function getEmptyIngredient(): array {
		return [
			'category' => '',
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
			'id'               => 0,
			'naam'             => '',
			'beschrijving'     => '',
			'foto_id'          => 0,
			'ingredienten'     => [],
			'instructies'      => [],
			'aantal_personen'  => 0,
			'bereidingstijd'   => 0,
			'oven_temperatuur' => 0,
			'moeilijkheid'     => 'makkelijk',
			'soort_gerecht'    => 'diner',
			'created_at'       => '',
			'updated_at'       => '',
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
			'settings-saved' => __( 'Instellingen opgeslagen.', 'bellas-kitchen-recepten' ),
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

	private function getTemplateAddUrl(): string {
		return admin_url( 'admin.php?page=' . self::TEMPLATE_SLUG );
	}

	private function getSettingsUrl(): string {
		return admin_url( 'admin.php?page=' . self::SETTINGS_SLUG );
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

	private function redirectToEdit( int $id, string $message ): void {
		wp_safe_redirect(
			add_query_arg(
				[ 'message' => $message ],
				$this->getEditUrl( $id )
			)
		);
		exit;
	}

	private function redirectToSettings( string $message ): void {
		wp_safe_redirect(
			add_query_arg(
				[ 'message' => $message ],
				$this->getSettingsUrl()
			)
		);
		exit;
	}

	private function redirectToTemplateAdd(): void {
		wp_safe_redirect( $this->getTemplateAddUrl() );
		exit;
	}

	private function storeTemplateImportState( string $template_input, string $error_message ): void {
		set_transient(
			$this->getTemplateImportStateKey(),
			[
				'template_input' => $template_input,
				'error_message'  => $error_message,
			],
			5 * MINUTE_IN_SECONDS
		);
	}

	private function consumeTemplateImportState(): array {
		$state = get_transient( $this->getTemplateImportStateKey() );

		delete_transient( $this->getTemplateImportStateKey() );

		return is_array( $state ) ? $state : [];
	}

	private function getTemplateImportStateKey(): string {
		return 'bkr_template_import_state_' . get_current_user_id();
	}

	private function maskApiKey( string $api_key ): string {
		$api_key = trim( $api_key );

		if ( '' === $api_key ) {
			return '';
		}

		if ( strlen( $api_key ) <= 4 ) {
			return '****';
		}

		$prefix = 0 === strpos( $api_key, 'sk-' ) ? 'sk-' : '';

		return $prefix . '****' . substr( $api_key, -4 );
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
