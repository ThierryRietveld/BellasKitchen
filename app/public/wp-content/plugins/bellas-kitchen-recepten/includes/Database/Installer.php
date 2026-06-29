<?php
/**
 * Database installer for the recepten table.
 *
 * @package BellasKitchenRecepten
 */

namespace BellasKitchenRecepten\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Installer {

	public static function activate(): void {
		self::install();
	}

	public static function maybeUpgrade(): void {
		if ( get_option( 'bellas_kitchen_recepten_db_version' ) === BKR_RECEPTEN_DB_VERSION ) {
			return;
		}

		self::install();
	}

	public static function install(): void {
		global $wpdb;

		$recepten_table     = self::getTableName();
		$ingredienten_table = self::getIngredientsTableName();
		$instructies_table  = self::getInstructionsTableName();
		$charset_collate    = $wpdb->get_charset_collate();

		$recepten_sql = "CREATE TABLE {$recepten_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			naam varchar(255) NOT NULL,
			slug varchar(200) NOT NULL DEFAULT '',
			beschrijving longtext NOT NULL,
			foto_id bigint(20) unsigned NOT NULL DEFAULT 0,
			aantal_personen int(10) unsigned NOT NULL DEFAULT 0,
			aantal_personen_label varchar(100) NOT NULL DEFAULT 'personen',
			bereidingstijd int(10) unsigned NOT NULL DEFAULT 0,
			oven_temperatuur int(10) unsigned NOT NULL DEFAULT 0,
			moeilijkheid varchar(50) NOT NULL DEFAULT '',
			soort_gerecht varchar(50) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY naam (naam(191)),
			KEY slug (slug(191)),
			KEY moeilijkheid (moeilijkheid),
			KEY soort_gerecht (soort_gerecht)
		) {$charset_collate};";

		$ingredienten_sql = "CREATE TABLE {$ingredienten_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			recept_id bigint(20) unsigned NOT NULL,
			sort_order int(10) unsigned NOT NULL DEFAULT 0,
			category varchar(100) NOT NULL DEFAULT '',
			quantity varchar(50) NOT NULL DEFAULT '',
			unit varchar(50) NOT NULL DEFAULT '',
			item varchar(255) NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY recept_id (recept_id),
			KEY recept_sort_order (recept_id, sort_order)
		) {$charset_collate};";

		$instructies_sql = "CREATE TABLE {$instructies_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			recept_id bigint(20) unsigned NOT NULL,
			sort_order int(10) unsigned NOT NULL DEFAULT 0,
			text longtext NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY recept_id (recept_id),
			KEY recept_sort_order (recept_id, sort_order)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $recepten_sql );
		dbDelta( $ingredienten_sql );
		dbDelta( $instructies_sql );

		self::migrateLegacyJsonFields();
		self::migrateSlugs();

		update_option( 'bellas_kitchen_recepten_db_version', BKR_RECEPTEN_DB_VERSION );
	}

	public static function getTableName(): string {
		global $wpdb;

		return $wpdb->prefix . 'bellas_kitchen_recepten';
	}

	public static function getIngredientsTableName(): string {
		global $wpdb;

		return $wpdb->prefix . 'bellas_kitchen_recept_ingredienten';
	}

	public static function getInstructionsTableName(): string {
		global $wpdb;

		return $wpdb->prefix . 'bellas_kitchen_recept_instructies';
	}

	public static function hasLegacyIngredientsColumn(): bool {
		return self::columnExists( self::getTableName(), 'ingredienten' );
	}

	public static function hasLegacyInstructionsColumn(): bool {
		return self::columnExists( self::getTableName(), 'instructies' );
	}

	public static function hasIngredientCategoryColumn(): bool {
		return self::columnExists( self::getIngredientsTableName(), 'category' );
	}

	public static function hasServingsLabelColumn(): bool {
		return self::columnExists( self::getTableName(), 'aantal_personen_label' );
	}

	private static function migrateLegacyJsonFields(): void {
		global $wpdb;

		$recepten_table   = self::getTableName();
		$has_ingredients  = self::columnExists( $recepten_table, 'ingredienten' );
		$has_instructions = self::columnExists( $recepten_table, 'instructies' );

		if ( ! $has_ingredients && ! $has_instructions ) {
			return;
		}

		$columns = [ 'id' ];

		if ( $has_ingredients ) {
			$columns[] = 'ingredienten';
		}

		if ( $has_instructions ) {
			$columns[] = 'instructies';
		}

		$rows = $wpdb->get_results( 'SELECT ' . implode( ', ', $columns ) . " FROM {$recepten_table}", ARRAY_A );

		if ( ! is_array( $rows ) ) {
			return;
		}

		foreach ( $rows as $row ) {
			$recept_id = absint( $row['id'] ?? 0 );

			if ( $recept_id <= 0 ) {
				continue;
			}

			if ( $has_ingredients && isset( $row['ingredienten'] ) ) {
				self::migrateLegacyIngredients( $recept_id, (string) $row['ingredienten'] );
			}

			if ( $has_instructions && isset( $row['instructies'] ) ) {
				self::migrateLegacyInstructions( $recept_id, (string) $row['instructies'] );
			}
		}
	}

	private static function migrateSlugs(): void {
		global $wpdb;

		$recepten_table = self::getTableName();

		if ( ! self::columnExists( $recepten_table, 'slug' ) ) {
			return;
		}

		$rows = $wpdb->get_results(
			"SELECT id, naam, slug FROM {$recepten_table} WHERE slug = '' OR slug IS NULL ORDER BY id ASC",
			ARRAY_A
		);

		if ( ! is_array( $rows ) || empty( $rows ) ) {
			return;
		}

		foreach ( $rows as $row ) {
			$recept_id = absint( $row['id'] ?? 0 );

			if ( $recept_id <= 0 ) {
				continue;
			}

			$slug = self::generateUniqueSlug( (string) ( $row['naam'] ?? '' ), $recept_id );

			$wpdb->update(
				$recepten_table,
				[ 'slug' => $slug ],
				[ 'id' => $recept_id ],
				[ '%s' ],
				[ '%d' ]
			);
		}
	}

	public static function generateUniqueSlug( string $name, int $exclude_id = 0 ): string {
		global $wpdb;

		$table_name = self::getTableName();
		$base_slug  = sanitize_title( $name );

		if ( '' === $base_slug ) {
			$base_slug = 'recept';
		}

		$base_slug = substr( $base_slug, 0, 180 );
		$slug      = $base_slug;
		$suffix    = 2;

		while ( self::slugExists( $table_name, $slug, $exclude_id ) ) {
			$slug = substr( $base_slug, 0, max( 1, 180 - strlen( (string) $suffix ) - 1 ) ) . '-' . $suffix;
			$suffix++;
		}

		return $slug;
	}

	private static function slugExists( string $table_name, string $slug, int $exclude_id ): bool {
		global $wpdb;

		$query = "SELECT COUNT(*) FROM {$table_name} WHERE slug = %s";
		$args  = [ $slug ];

		if ( $exclude_id > 0 ) {
			$query .= ' AND id != %d';
			$args[] = $exclude_id;
		}

		$count = (int) $wpdb->get_var( $wpdb->prepare( $query, ...$args ) );

		return $count > 0;
	}

	private static function migrateLegacyIngredients( int $recept_id, string $raw_ingredients ): void {
		global $wpdb;

		$ingredients_table = self::getIngredientsTableName();
		$existing_count    = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$ingredients_table} WHERE recept_id = %d", $recept_id )
		);

		if ( $existing_count > 0 || trim( $raw_ingredients ) === '' ) {
			return;
		}

		$ingredients = self::parseLegacyIngredients( $raw_ingredients );

		foreach ( $ingredients as $index => $ingredient ) {
			$now         = current_time( 'mysql' );
			$insert_data = [
				'recept_id'  => $recept_id,
				'sort_order' => $index,
				'quantity'   => $ingredient['quantity'],
				'unit'       => $ingredient['unit'],
				'item'       => $ingredient['item'],
				'created_at' => $now,
				'updated_at' => $now,
			];
			$formats     = [ '%d', '%d', '%s', '%s', '%s', '%s', '%s' ];

			if ( self::hasIngredientCategoryColumn() ) {
				$insert_data['category'] = $ingredient['category'] ?? '';
				$formats[]               = '%s';
			}

			$wpdb->insert(
				$ingredients_table,
				$insert_data,
				$formats
			);
		}
	}

	private static function migrateLegacyInstructions( int $recept_id, string $raw_instructions ): void {
		global $wpdb;

		$instructions_table = self::getInstructionsTableName();
		$existing_count     = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$instructions_table} WHERE recept_id = %d", $recept_id )
		);

		if ( $existing_count > 0 || trim( $raw_instructions ) === '' ) {
			return;
		}

		$instructions = self::parseLegacyInstructions( $raw_instructions );

		foreach ( $instructions as $index => $instruction ) {
			$now = current_time( 'mysql' );

			$wpdb->insert(
				$instructions_table,
				[
					'recept_id'  => $recept_id,
					'sort_order' => $index,
					'text'       => $instruction['text'],
					'created_at' => $now,
					'updated_at' => $now,
				],
				[ '%d', '%d', '%s', '%s', '%s' ]
			);
		}
	}

	private static function parseLegacyIngredients( string $raw_ingredients ): array {
		$ingredients = [];
		$decoded     = json_decode( $raw_ingredients, true );

		if ( is_array( $decoded ) ) {
			foreach ( $decoded as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}

				$item = self::sanitizeScalarText( $row['item'] ?? '' );

				if ( $item === '' ) {
					continue;
				}

				$ingredients[] = [
					'category' => self::sanitizeScalarText( $row['category'] ?? '' ),
					'quantity' => self::sanitizeScalarText( $row['quantity'] ?? '' ),
					'unit'     => self::sanitizeScalarKey( $row['unit'] ?? '' ),
					'item'     => $item,
				];
			}

			return $ingredients;
		}

		$lines = preg_split( '/\r\n|\r|\n/', $raw_ingredients );

		foreach ( $lines as $line ) {
			$item = sanitize_text_field( trim( $line ) );

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

	private static function parseLegacyInstructions( string $raw_instructions ): array {
		$instructions = [];
		$decoded      = json_decode( $raw_instructions, true );

		if ( is_array( $decoded ) ) {
			foreach ( $decoded as $row ) {
				$text = is_array( $row ) ? ( $row['text'] ?? '' ) : $row;
				$text = self::sanitizeScalarTextarea( $text );

				if ( $text === '' ) {
					continue;
				}

				$instructions[] = [ 'text' => $text ];
			}

			return $instructions;
		}

		$lines = preg_split( '/\r\n|\r|\n/', $raw_instructions );

		foreach ( $lines as $line ) {
			$text = sanitize_textarea_field( trim( $line ) );

			if ( $text === '' ) {
				continue;
			}

			$instructions[] = [ 'text' => $text ];
		}

		return $instructions;
	}

	private static function columnExists( string $table_name, string $column_name ): bool {
		global $wpdb;

		$result = $wpdb->get_var(
			$wpdb->prepare( "SHOW COLUMNS FROM {$table_name} LIKE %s", $column_name )
		);

		return $result === $column_name;
	}

	private static function sanitizeScalarText( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return sanitize_text_field( (string) $value );
	}

	private static function sanitizeScalarKey( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return sanitize_key( (string) $value );
	}

	private static function sanitizeScalarTextarea( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return sanitize_textarea_field( (string) $value );
	}
}
