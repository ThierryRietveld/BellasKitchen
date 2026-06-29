<?php
/**
 * Repository for custom recepten table access.
 *
 * @package BellasKitchenRecepten
 */

namespace BellasKitchenRecepten\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReceptRepository {

	public function getAll( string $search = '' ): array {
		global $wpdb;

		$table_name = Installer::getTableName();

		if ( $search !== '' ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';

			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table_name} WHERE naam LIKE %s OR soort_gerecht LIKE %s OR moeilijkheid LIKE %s ORDER BY updated_at DESC, id DESC",
					$like,
					$like,
					$like
				),
				ARRAY_A
			);

			return is_array( $results ) ? $results : [];
		}

		$results = $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY updated_at DESC, id DESC", ARRAY_A );

		return is_array( $results ) ? $results : [];
	}

	public function getLatest( int $limit = 3 ): array {
		global $wpdb;

		$table_name = Installer::getTableName();
		$limit      = max( 1, $limit );
		$results    = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} ORDER BY created_at DESC, id DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		return is_array( $results ) ? $results : [];
	}

	public function getPaginated( int $page = 1, int $per_page = 9 ): array {
		global $wpdb;

		$table_name  = Installer::getTableName();
		$page        = max( 1, $page );
		$per_page    = max( 1, $per_page );
		$offset      = ( $page - 1 ) * $per_page;
		$total_items = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
		$results     = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			),
			ARRAY_A
		);

		return [
			'items'        => is_array( $results ) ? $results : [],
			'total_items'  => $total_items,
			'per_page'     => $per_page,
			'current_page' => $page,
			'total_pages'  => max( 1, (int) ceil( $total_items / $per_page ) ),
		];
	}

	public function find( int $id ): ?array {
		global $wpdb;

		$table_name = Installer::getTableName();
		$row        = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $id ),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		return $this->hydrateRecipe( $row );
	}

	public function findBySlug( string $slug ): ?array {
		global $wpdb;

		$table_name = Installer::getTableName();
		$slug       = sanitize_title( $slug );

		if ( '' === $slug ) {
			return null;
		}

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table_name} WHERE slug = %s", $slug ),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		return $this->hydrateRecipe( $row );
	}

	public function create( array $data ): int {
		global $wpdb;

		$table_name         = Installer::getTableName();
		$has_servings_label = Installer::hasServingsLabelColumn();
		$now                = current_time( 'mysql' );
		$insert_data = [
			'naam'            => $data['naam'],
			'slug'            => Installer::generateUniqueSlug( $data['naam'] ),
			'beschrijving'    => $data['beschrijving'],
			'foto_id'         => $data['foto_id'],
			'aantal_personen' => $data['aantal_personen'],
		];
		$formats     = [ '%s', '%s', '%s', '%d', '%d' ];

		if ( $has_servings_label ) {
			$insert_data['aantal_personen_label'] = $this->normalizeServingsLabel( $data['aantal_personen_label'] ?? '' );
			$formats[]                            = '%s';
		}

		$insert_data['bereidingstijd']   = $data['bereidingstijd'];
		$insert_data['oven_temperatuur'] = absint( $data['oven_temperatuur'] ?? 0 );
		$insert_data['moeilijkheid']     = $data['moeilijkheid'];
		$insert_data['soort_gerecht']    = $data['soort_gerecht'];
		$insert_data['created_at']       = $now;
		$insert_data['updated_at']       = $now;
		$formats                         = array_merge( $formats, [ '%d', '%d', '%s', '%s', '%s', '%s' ] );

		$this->addLegacyEmptyFields( $insert_data, $formats );

		$wpdb->insert(
			$table_name,
			$insert_data,
			$formats
		);

		$recept_id = (int) $wpdb->insert_id;

		if ( $recept_id <= 0 ) {
			return 0;
		}

		if ( ! $this->saveIngredients( $recept_id, $data['ingredienten'] ) || ! $this->saveInstructions( $recept_id, $data['instructies'] ) ) {
			$this->delete( $recept_id );

			return 0;
		}

		return $recept_id;
	}

	public function update( int $id, array $data ): bool {
		global $wpdb;

		$table_name         = Installer::getTableName();
		$has_servings_label = Installer::hasServingsLabelColumn();
		$existing           = $this->find( $id );

		if ( ! $existing ) {
			return false;
		}

		$update_data = [
			'naam'            => $data['naam'],
		];
		$formats = [ '%s' ];

		if ( '' === (string) ( $existing['slug'] ?? '' ) ) {
			$update_data['slug'] = Installer::generateUniqueSlug( $data['naam'], $id );
			$formats[]          = '%s';
		}

		$update_data['beschrijving']     = $data['beschrijving'];
		$update_data['foto_id']          = $data['foto_id'];
		$update_data['aantal_personen']  = $data['aantal_personen'];
		if ( $has_servings_label ) {
			$update_data['aantal_personen_label'] = $this->normalizeServingsLabel( $data['aantal_personen_label'] ?? '' );
		}
		$update_data['bereidingstijd']   = $data['bereidingstijd'];
		$update_data['oven_temperatuur'] = absint( $data['oven_temperatuur'] ?? 0 );
		$update_data['moeilijkheid']     = $data['moeilijkheid'];
		$update_data['soort_gerecht']    = $data['soort_gerecht'];
		$update_data['updated_at']       = current_time( 'mysql' );
		$formats                         = array_merge(
			$formats,
			$has_servings_label
				? [ '%s', '%d', '%d', '%s', '%d', '%d', '%s', '%s', '%s' ]
				: [ '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s' ]
		);

		$this->addLegacyEmptyFields( $update_data, $formats );

		$updated = $wpdb->update(
			$table_name,
			$update_data,
			[ 'id' => $id ],
			$formats,
			[ '%d' ]
		);

		if ( $updated === false ) {
			return false;
		}

		return $this->saveIngredients( $id, $data['ingredienten'] ) && $this->saveInstructions( $id, $data['instructies'] );
	}

	private function addLegacyEmptyFields( array &$data, array &$formats ): void {
		if ( Installer::hasLegacyIngredientsColumn() ) {
			$data['ingredienten'] = '';
			$formats[]           = '%s';
		}

		if ( Installer::hasLegacyInstructionsColumn() ) {
			$data['instructies'] = '';
			$formats[]          = '%s';
		}
	}

	public function delete( int $id ): bool {
		global $wpdb;

		$table_name           = Installer::getTableName();
		$ingredients_table    = Installer::getIngredientsTableName();
		$instructions_table   = Installer::getInstructionsTableName();
		$deleted_ingredients  = $wpdb->delete( $ingredients_table, [ 'recept_id' => $id ], [ '%d' ] );
		$deleted_instructions = $wpdb->delete( $instructions_table, [ 'recept_id' => $id ], [ '%d' ] );
		$deleted              = $wpdb->delete( $table_name, [ 'id' => $id ], [ '%d' ] );

		return $deleted_ingredients !== false && $deleted_instructions !== false && $deleted !== false;
	}

	private function hydrateRecipe( array $row ): array {
		$recept_id = absint( $row['id'] ?? 0 );

		if ( $recept_id <= 0 ) {
			return $row;
		}

		$legacy_ingredients  = $row['ingredienten'] ?? '';
		$legacy_instructions = $row['instructies'] ?? '';

		$row['ingredienten'] = $this->getIngredients( $recept_id );
		$row['instructies']  = $this->getInstructions( $recept_id );

		$row['aantal_personen_label'] = $this->normalizeServingsLabel( $row['aantal_personen_label'] ?? '' );

		if ( empty( $row['ingredienten'] ) && is_string( $legacy_ingredients ) && $legacy_ingredients !== '' ) {
			$row['ingredienten'] = $this->parseLegacyIngredients( $legacy_ingredients );
		}

		if ( empty( $row['instructies'] ) && is_string( $legacy_instructions ) && $legacy_instructions !== '' ) {
			$row['instructies'] = $this->parseLegacyInstructions( $legacy_instructions );
		}

		return $row;
	}

	private function getIngredients( int $recept_id ): array {
		global $wpdb;

		$table_name      = Installer::getIngredientsTableName();
		$category_select = Installer::hasIngredientCategoryColumn() ? 'category' : "'' AS category";
		$results         = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT {$category_select}, quantity, unit, item FROM {$table_name} WHERE recept_id = %d ORDER BY sort_order ASC, id ASC",
				$recept_id
			),
			ARRAY_A
		);

		return is_array( $results ) ? $results : [];
	}

	private function getInstructions( int $recept_id ): array {
		global $wpdb;

		$table_name = Installer::getInstructionsTableName();
		$results    = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT text FROM {$table_name} WHERE recept_id = %d ORDER BY sort_order ASC, id ASC",
				$recept_id
			),
			ARRAY_A
		);

		return is_array( $results ) ? $results : [];
	}

	private function saveIngredients( int $recept_id, array $ingredients ): bool {
		global $wpdb;

		$table_name   = Installer::getIngredientsTableName();
		$has_category = Installer::hasIngredientCategoryColumn();
		$deleted      = $wpdb->delete( $table_name, [ 'recept_id' => $recept_id ], [ '%d' ] );

		if ( $deleted === false ) {
			return false;
		}

		foreach ( $ingredients as $index => $ingredient ) {
			if ( ! is_array( $ingredient ) ) {
				continue;
			}

			$item = $this->sanitizeScalarText( $ingredient['item'] ?? '' );

			if ( $item === '' ) {
				continue;
			}

			$now         = current_time( 'mysql' );
			$insert_data = [
				'recept_id'  => $recept_id,
				'sort_order' => $index,
				'quantity'   => $this->sanitizeScalarText( $ingredient['quantity'] ?? '' ),
				'unit'       => $this->sanitizeScalarKey( $ingredient['unit'] ?? '' ),
				'item'       => $item,
				'created_at' => $now,
				'updated_at' => $now,
			];
			$formats     = [ '%d', '%d', '%s', '%s', '%s', '%s', '%s' ];

			if ( $has_category ) {
				$insert_data['category'] = $this->sanitizeScalarText( $ingredient['category'] ?? '' );
				$formats[]               = '%s';
			}

			$inserted = $wpdb->insert( $table_name, $insert_data, $formats );

			if ( $inserted === false ) {
				return false;
			}
		}

		return true;
	}

	private function saveInstructions( int $recept_id, array $instructions ): bool {
		global $wpdb;

		$table_name = Installer::getInstructionsTableName();
		$deleted    = $wpdb->delete( $table_name, [ 'recept_id' => $recept_id ], [ '%d' ] );

		if ( $deleted === false ) {
			return false;
		}

		foreach ( $instructions as $index => $instruction ) {
			if ( ! is_array( $instruction ) ) {
				continue;
			}

			$text = $this->sanitizeScalarTextarea( $instruction['text'] ?? '' );

			if ( $text === '' ) {
				continue;
			}

			$now      = current_time( 'mysql' );
			$inserted = $wpdb->insert(
				$table_name,
				[
					'recept_id'  => $recept_id,
					'sort_order' => $index,
					'text'       => $text,
					'created_at' => $now,
					'updated_at' => $now,
				],
				[ '%d', '%d', '%s', '%s', '%s' ]
			);

			if ( $inserted === false ) {
				return false;
			}
		}

		return true;
	}

	private function parseLegacyIngredients( string $raw_ingredients ): array {
		$ingredients = [];
		$decoded     = json_decode( $raw_ingredients, true );

		if ( is_array( $decoded ) ) {
			foreach ( $decoded as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}

				$item = $this->sanitizeScalarText( $row['item'] ?? '' );

				if ( $item === '' ) {
					continue;
				}

				$ingredients[] = [
					'category' => $this->sanitizeScalarText( $row['category'] ?? '' ),
					'quantity' => $this->sanitizeScalarText( $row['quantity'] ?? '' ),
					'unit'     => $this->sanitizeScalarKey( $row['unit'] ?? '' ),
					'item'     => $item,
				];
			}

			return $ingredients;
		}

		$lines = preg_split( '/\r\n|\r|\n/', $raw_ingredients );

		foreach ( $lines as $line ) {
			$item = $this->sanitizeScalarText( trim( $line ) );

			if ( $item === '' ) {
				continue;
			}

			$ingredients[] = [
				'category' => '',
				'quantity' => '',
				'unit'     => '',
				'item'     => $item,
			];
		}

		return $ingredients;
	}

	private function parseLegacyInstructions( string $raw_instructions ): array {
		$instructions = [];
		$decoded      = json_decode( $raw_instructions, true );

		if ( is_array( $decoded ) ) {
			foreach ( $decoded as $row ) {
				$text = is_array( $row ) ? ( $row['text'] ?? '' ) : $row;
				$text = $this->sanitizeScalarTextarea( $text );

				if ( $text === '' ) {
					continue;
				}

				$instructions[] = [ 'text' => $text ];
			}

			return $instructions;
		}

		$lines = preg_split( '/\r\n|\r|\n/', $raw_instructions );

		foreach ( $lines as $line ) {
			$text = $this->sanitizeScalarTextarea( trim( $line ) );

			if ( $text === '' ) {
				continue;
			}

			$instructions[] = [ 'text' => $text ];
		}

		return $instructions;
	}

	private function normalizeServingsLabel( $label ): string {
		if ( ! is_scalar( $label ) ) {
			return 'personen';
		}

		$label = sanitize_text_field( (string) $label );
		$label = function_exists( 'mb_substr' ) ? mb_substr( $label, 0, 100 ) : substr( $label, 0, 100 );
		$label = trim( $label );

		return '' !== $label ? $label : 'personen';
	}

	private function sanitizeScalarText( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return sanitize_text_field( (string) $value );
	}

	private function sanitizeScalarKey( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return sanitize_key( (string) $value );
	}

	private function sanitizeScalarTextarea( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return sanitize_textarea_field( (string) $value );
	}
}
