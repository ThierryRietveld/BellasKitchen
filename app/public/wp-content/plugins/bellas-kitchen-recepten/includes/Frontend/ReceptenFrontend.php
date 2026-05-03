<?php
/**
 * Frontend routing and URLs for recepten.
 *
 * @package BellasKitchenRecepten
 */

namespace BellasKitchenRecepten\Frontend;

use BellasKitchenRecepten\Database\ReceptRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReceptenFrontend {

	private const ARCHIVE_QUERY_VAR = 'bkr_recepten_archive';
	private const PAGED_QUERY_VAR   = 'bkr_recepten_paged';
	private const SLUG_QUERY_VAR    = 'bkr_recept_slug';
	private const REWRITE_OPTION    = 'bellas_kitchen_recepten_rewrite_version';
	private const ARCHIVE_SLUG      = 'recepten';

	/**
	 * @var ReceptRepository
	 */
	private $repository;

	public function __construct( ReceptRepository $repository ) {
		$this->repository = $repository;
	}

	public function register(): void {
		add_action( 'init', [ $this, 'registerRewriteRules' ] );
		add_filter( 'query_vars', [ $this, 'registerQueryVars' ] );
		add_filter( 'template_include', [ $this, 'includeThemeTemplate' ] );
		add_filter( 'redirect_canonical', [ $this, 'disableCanonicalRedirect' ], 10, 2 );
		add_filter( 'document_title_parts', [ $this, 'filterDocumentTitle' ] );
		add_action( 'wp', [ $this, 'prepareResponseStatus' ] );
		add_action( 'init', [ $this, 'maybeFlushRewriteRules' ], 20 );
	}

	public function registerRewriteRules(): void {
		add_rewrite_rule(
			'^' . self::ARCHIVE_SLUG . '/?$',
			'index.php?' . self::ARCHIVE_QUERY_VAR . '=1',
			'top'
		);

		add_rewrite_rule(
			'^' . self::ARCHIVE_SLUG . '/pagina/([0-9]+)/?$',
			'index.php?' . self::ARCHIVE_QUERY_VAR . '=1&' . self::PAGED_QUERY_VAR . '=$matches[1]',
			'top'
		);

		add_rewrite_rule(
			'^' . self::ARCHIVE_SLUG . '/([^/]+)/?$',
			'index.php?' . self::SLUG_QUERY_VAR . '=$matches[1]',
			'top'
		);
	}

	public function registerQueryVars( array $query_vars ): array {
		$query_vars[] = self::ARCHIVE_QUERY_VAR;
		$query_vars[] = self::PAGED_QUERY_VAR;
		$query_vars[] = self::SLUG_QUERY_VAR;

		return $query_vars;
	}

	public function includeThemeTemplate( string $template ): string {
		if ( self::isArchiveRequest() ) {
			$archive_template = locate_template( 'archive-recipe.php' );

			return $archive_template ?: $template;
		}

		if ( self::isSingleRequest() ) {
			$single_template = locate_template( 'single-recipe.php' );

			return $single_template ?: $template;
		}

		return $template;
	}

	public function disableCanonicalRedirect( $redirect_url, string $requested_url ) {
		if ( self::isArchiveRequest() || self::isSingleRequest() ) {
			return false;
		}

		return $redirect_url;
	}

	public function filterDocumentTitle( array $title_parts ): array {
		if ( self::isArchiveRequest() ) {
			$title_parts['title'] = __( 'Recepten', 'bellas-kitchen-recepten' );

			return $title_parts;
		}

		if ( ! self::isSingleRequest() ) {
			return $title_parts;
		}

		$recipe = $this->repository->findBySlug( self::getCurrentRecipeSlug() );

		if ( $recipe ) {
			$title_parts['title'] = $recipe['naam'];
		}

		return $title_parts;
	}

	public function prepareResponseStatus(): void {
		global $wp_query;

		if ( self::isArchiveRequest() ) {
			status_header( 200 );
			$wp_query->is_404 = false;

			return;
		}

		if ( ! self::isSingleRequest() ) {
			return;
		}

		$recipe = $this->repository->findBySlug( self::getCurrentRecipeSlug() );

		if ( $recipe ) {
			status_header( 200 );
			$wp_query->is_404 = false;

			return;
		}

		$wp_query->set_404();
		status_header( 404 );
	}

	public function maybeFlushRewriteRules(): void {
		if ( get_option( self::REWRITE_OPTION ) === BKR_RECEPTEN_VERSION ) {
			return;
		}

		flush_rewrite_rules( false );
		update_option( self::REWRITE_OPTION, BKR_RECEPTEN_VERSION );
	}

	public static function getArchiveUrl(): string {
		return home_url( '/' . self::ARCHIVE_SLUG . '/' );
	}

	public static function getRecipeUrl( array $recipe ): string {
		$slug = sanitize_title( $recipe['slug'] ?? '' );

		if ( '' === $slug ) {
			return self::getArchiveUrl();
		}

		return home_url( '/' . self::ARCHIVE_SLUG . '/' . $slug . '/' );
	}

	public static function isArchiveRequest(): bool {
		return '1' === (string) get_query_var( self::ARCHIVE_QUERY_VAR );
	}

	public static function isSingleRequest(): bool {
		return '' !== self::getCurrentRecipeSlug();
	}

	public static function getCurrentArchivePage(): int {
		$page = absint( get_query_var( self::PAGED_QUERY_VAR ) );

		return $page > 0 ? $page : 1;
	}

	public static function getCurrentRecipeSlug(): string {
		return sanitize_title( (string) get_query_var( self::SLUG_QUERY_VAR ) );
	}
}
