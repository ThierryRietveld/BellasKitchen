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

		$legacy_ingredients  = $row['ingredienten'] ?? '';
		$legacy_instructions = $row['instructies'] ?? '';

		$row['ingredienten'] = $this->getIngredients( $id );
		$row['instructies']  = $this->getInstructions( $id );

		if ( empty( $row['ingredienten'] ) && is_string( $legacy_ingredients ) && $legacy_ingredients !== '' ) {
			$row['ingredienten'] = $this->parseLegacyIngredients( $legacy_ingredients );
		}

		if ( empty( $row['instructies'] ) && is_string( $legacy_instructions ) && $legacy_instructions !== '' ) {
			$row['instructies'] = $this->parseLegacyInstructions( $legacy_instructions );
		}

		return $row;
	}

	public function create( array $data ): int {
		global $wpdb;

		$table_name = Installer::getTableName();
		$now        = current_time( 'mysql' );
		$insert_data = [
			'naam'            => $data['naam'],
			'beschrijving'    => $data['beschrijving'],
			'foto_id'         => $data['foto_id'],
			'aantal_personen' => $data['aantal_personen'],
			'bereidingstijd'  => $data['bereidingstijd'],
			'moeilijkheid'    => $data['moeilijkheid'],
			'soort_gerecht'   => $data['soort_gerecht'],
			'created_at'      => $now,
			'updated_at'      => $now,
		];
		$formats = [ '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s' ];

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

		$table_name = Installer::getTableName();
		$update_data = [
			'naam'            => $data['naam'],
			'beschrijving'    => $data['beschrijving'],
			'foto_id'         => $data['foto_id'],
			'aantal_personen' => $data['aantal_personen'],
			'bereidingstijd'  => $data['bereidingstijd'],
			'moeilijkheid'    => $data['moeilijkheid'],
			'soort_gerecht'   => $data['soort_gerecht'],
			'updated_at'      => current_time( 'mysql' ),
		];
		$formats = [ '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s' ];

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

	private function getIngredients( int $recept_id ): array {
		global $wpdb;

		$table_name = Installer::getIngredientsTableName();
		$results    = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT quantity, unit, item FROM {$table_name} WHERE recept_id = %d ORDER BY sort_order ASC, id ASC",
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

		$table_name = Installer::getIngredientsTableName();
		$deleted    = $wpdb->delete( $table_name, [ 'recept_id' => $recept_id ], [ '%d' ] );

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

			$now      = current_time( 'mysql' );
			$inserted = $wpdb->insert(
				$table_name,
				[
					'recept_id'  => $recept_id,
					'sort_order' => $index,
					'quantity'   => $this->sanitizeScalarText( $ingredient['quantity'] ?? '' ),
					'unit'       => $this->sanitizeScalarKey( $ingredient['unit'] ?? '' ),
					'item'       => $item,
					'created_at' => $now,
					'updated_at' => $now,
				],
				[ '%d', '%d', '%s', '%s', '%s', '%s', '%s' ]
			);

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
