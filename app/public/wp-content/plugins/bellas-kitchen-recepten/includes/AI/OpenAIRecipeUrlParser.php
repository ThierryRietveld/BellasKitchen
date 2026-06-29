<?php
/**
 * OpenAI integration for parsing online recipes into the template format.
 *
 * @package BellasKitchenRecepten
 */

namespace BellasKitchenRecepten\AI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OpenAIRecipeUrlParser {

	private const API_URL        = 'https://api.openai.com/v1/responses';
	private const OPTION_API_KEY = 'bellas_kitchen_recepten_openai_api_key';

	public function hasApiKey(): bool {
		return '' !== $this->getApiKey();
	}

	public function getApiKey(): string {
		$api_key = get_option( self::OPTION_API_KEY, '' );

		return is_string( $api_key ) ? trim( $api_key ) : '';
	}

	public function updateApiKey( string $api_key ): void {
		update_option( self::OPTION_API_KEY, trim( $api_key ), false );
	}

	public function clearApiKey(): void {
		delete_option( self::OPTION_API_KEY );
	}

	public function generateTemplateFromUrl( string $url ) {
		$api_key = $this->getApiKey();
		$url     = esc_url_raw( trim( $url ) );

		if ( '' === $api_key ) {
			return new \WP_Error(
				'openai-missing-api-key',
				__( 'Voeg eerst een OpenAI API-sleutel toe op de instellingenpagina.', 'bellas-kitchen-recepten' )
			);
		}

		if ( '' === $url || ! wp_http_validate_url( $url ) ) {
			return new \WP_Error(
				'openai-invalid-url',
				__( 'Voer een geldige URL in naar een online recept.', 'bellas-kitchen-recepten' )
			);
		}

		$response = wp_remote_post(
			self::API_URL,
			[
				'timeout' => 120,
				'headers' => [
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				],
				'body'    => wp_json_encode( $this->buildRequestPayload( $url ) ),
			]
		);

		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'openai-request-failed',
				__( 'De aanvraag naar OpenAI is mislukt. Controleer de serververbinding en probeer het opnieuw.', 'bellas-kitchen-recepten' )
			);
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		$body        = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status_code < 200 || $status_code >= 300 ) {
			return new \WP_Error(
				'openai-api-error',
				$this->extractErrorMessage( $body )
			);
		}

		if ( ! is_array( $body ) ) {
			return new \WP_Error(
				'openai-invalid-response',
				__( 'OpenAI gaf een onleesbare reactie terug.', 'bellas-kitchen-recepten' )
			);
		}

		$template = $this->extractTemplateText( $body );

		if ( '' === $template ) {
			return new \WP_Error(
				'openai-empty-template',
				__( 'OpenAI gaf geen bruikbare template terug voor deze URL.', 'bellas-kitchen-recepten' )
			);
		}

		return $template;
	}

	private function buildRequestPayload( string $url ): array {
		$payload = [
			'model'             => (string) apply_filters( 'bkr_openai_recipe_parser_model', 'gpt-5' ),
			'input'             => $this->buildPrompt( $url ),
			'store'             => false,
			'tool_choice'       => 'auto',
			'max_output_tokens' => 1800,
			'reasoning'         => [
				'effort' => 'low',
			],
			'tools'             => [
				[
					'type'                => 'web_search',
					'external_web_access' => true,
				],
			],
		];

		$allowed_domains = $this->getAllowedDomains( $url );

		if ( ! empty( $allowed_domains ) ) {
			$payload['tools'][0]['filters'] = [
				'allowed_domains' => $allowed_domains,
			];
		}

		return $payload;
	}

	private function buildPrompt( string $url ): string {
		$prompt = <<<'PROMPT'
You are a recipe parser.

I will give you a URL to a recipe page. Your task is to extract the recipe EXACTLY as written on the page and convert it into the template format below.

IMPORTANT RULES:
Do NOT invent, simplify, or approximate anything.
Only use information explicitly present on the page.
If something is missing on the page, leave it empty or omit it (do not guess).
Keep ingredient quantities, units, and names as written, but normalize units to the allowed list where possible.
If ingredients are grouped under headings like sauce, topping, dough, vulling, or garnering, keep that heading as the ingredient category.
Keep the number of steps and their meaning exactly the same (you may split or merge slightly only if needed for clarity, but do not change content).
Do not add extra explanations outside the template.

Allowed values: Moeilijkheid: makkelijk, gemiddeld, moeilijk SoortGerecht: ontbijt, lunch, diner, bijgerecht, tussendoortje, dessert, drankje Eenheden: ml, l, g, kg, tl, el, snufje, stuks, naar_smaak
Template: 

[Naam][/Naam]
[Beschrijving][/Beschrijving]
[AantalPersonen][/AantalPersonen]
[Bereidingstijd][/Bereidingstijd]
[Moeilijkheid][/Moeilijkheid]
[SoortGerecht][/SoortGerecht]
[Ingredienten] categorie | hoeveelheid | eenheid | ingrediënt [/Ingredienten]
[Stappen] stap [/Stappen]

Example template:
[Naam]Pasta met spinazie en room[/Naam]
[Beschrijving]Een snelle doordeweekse pasta met veel smaak.[/Beschrijving]
[AantalPersonen]4[/AantalPersonen]
[Bereidingstijd]25[/Bereidingstijd]
[Moeilijkheid]makkelijk[/Moeilijkheid]
[SoortGerecht]diner[/SoortGerecht]
[Ingredienten] 
Basis | 2 | el | olijfolie
Basis | 1 | | ui
Basis | 2 | stuks | knoflooktenen
Basis | 250 | g | pasta
Saus | 200 | ml | kookroom
Saus | 150 | g | spinazie naar smaak
Saus | | | peper en zout
[/Ingredienten]
[Stappen]
Fruit de ui en knoflook in de olie.
Kook de pasta gaar volgens de verpakking. 
Voeg de room en spinazie toe en laat kort slinken.
Meng alles met de pasta en breng op smaak. 
[/Stappen]

Additional rules:
Convert ranges like "1-2 tl" into a single line (keep original format if unclear).
If “naar smaak” is mentioned, use: categorie | | naar_smaak | ingrediënt (leave categorie empty if none is given).
If no ingredient category is given, leave the category field empty.
If no unit is given, leave the unit field empty.
Keep ordering exactly the same as on the page.
Strip unnecessary text like tips, ads, or story content.

Now process this URL: {{URL}}
PROMPT;

		return str_replace( '{{URL}}', $url, $prompt );
	}

	private function getAllowedDomains( string $url ): array {
		$host = wp_parse_url( $url, PHP_URL_HOST );

		if ( ! is_string( $host ) || '' === $host ) {
			return [];
		}

		return [ strtolower( $host ) ];
	}

	private function extractErrorMessage( $body ): string {
		if ( is_array( $body ) ) {
			$error = $body['error'] ?? null;

			if ( is_array( $error ) && ! empty( $error['message'] ) && is_string( $error['message'] ) ) {
				return $error['message'];
			}
		}

		return __( 'OpenAI gaf een fout terug bij het verwerken van deze URL.', 'bellas-kitchen-recepten' );
	}

	private function extractTemplateText( array $body ): string {
		if ( isset( $body['output'] ) && is_array( $body['output'] ) ) {
			foreach ( $body['output'] as $output_item ) {
				if ( ! is_array( $output_item ) || 'message' !== ( $output_item['type'] ?? '' ) ) {
					continue;
				}

				$content = $output_item['content'] ?? null;

				if ( ! is_array( $content ) ) {
					continue;
				}

				foreach ( $content as $content_item ) {
					if ( ! is_array( $content_item ) || empty( $content_item['text'] ) || ! is_string( $content_item['text'] ) ) {
						continue;
					}

					$text        = $content_item['text'];
					$annotations = $content_item['annotations'] ?? [];

					return $this->normalizeTemplateText( $this->removeCitationText( $text, $annotations ) );
				}
			}
		}

		if ( ! empty( $body['output_text'] ) && is_string( $body['output_text'] ) ) {
			return $this->normalizeTemplateText( $body['output_text'] );
		}

		return '';
	}

	private function removeCitationText( string $text, $annotations ): string {
		if ( is_array( $annotations ) ) {
			$ranges = [];

			foreach ( $annotations as $annotation ) {
				if (
					! is_array( $annotation ) ||
					'url_citation' !== ( $annotation['type'] ?? '' ) ||
					! isset( $annotation['start_index'], $annotation['end_index'] )
				) {
					continue;
				}

				$start = absint( $annotation['start_index'] );
				$end   = absint( $annotation['end_index'] );

				if ( $end <= $start ) {
					continue;
				}

				$ranges[] = [
					'start' => $start,
					'end'   => $end,
				];
			}

			usort(
				$ranges,
				static function ( array $left, array $right ): int {
					return $right['start'] <=> $left['start'];
				}
			);

			foreach ( $ranges as $range ) {
				$text = $this->removeTextRange( $text, $range['start'], $range['end'] );
			}
		}

		$text = preg_replace( '/\s*【[^】]+】/u', '', $text );

		return is_string( $text ) ? $text : '';
	}

	private function removeTextRange( string $text, int $start, int $end ): string {
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $text, 0, $start ) . mb_substr( $text, $end );
		}

		return substr( $text, 0, $start ) . substr( $text, $end );
	}

	private function normalizeTemplateText( string $text ): string {
		$text = trim( $text );

		if ( preg_match( '/^```[a-z0-9_-]*\s*(.*?)```$/is', $text, $matches ) ) {
			$text = trim( (string) $matches[1] );
		}

		$text = str_replace( [ "\r\n", "\r" ], "\n", $text );

		return trim( $text );
	}
}
