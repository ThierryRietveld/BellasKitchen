/**
 * Bellas Kitchen Theme - Main JavaScript
 *
 * @package BellasKitchenTheme
 */

( function() {
	'use strict';

	var decimalFormatter = new Intl.NumberFormat(
		'nl-NL',
		{
			maximumFractionDigits: 2
		}
	);

	function applyTheme( theme ) {
		var isDark = 'dark' === theme;
		var root = document.documentElement;
		root.classList.toggle( 'dark', isDark );
		localStorage.setItem( 'bellas-theme', theme );

		var icon = document.getElementById( 'theme-toggle-icon' );
		var text = document.getElementById( 'theme-toggle-text' );
		var button = document.getElementById( 'theme-toggle' );

		if ( icon ) {
			icon.textContent = isDark ? '☀️' : '🌙';
		}

		if ( text ) {
			text.textContent = isDark ? 'Licht' : 'Donker';
		}

		if ( button ) {
			button.setAttribute( 'aria-pressed', isDark ? 'true' : 'false' );
		}
	}

	function roundNumber( value, decimals ) {
		var factor = Math.pow( 10, decimals );

		return Math.round( value * factor ) / factor;
	}

	function normalizeQuantityText( value ) {
		return String( value || '' )
			.replace( /,/g, '.' )
			.replace( /½/g, ' 1/2' )
			.replace( /¼/g, ' 1/4' )
			.replace( /¾/g, ' 3/4' )
			.replace( /⅓/g, ' 1/3' )
			.replace( /⅔/g, ' 2/3' )
			.replace( /⅛/g, ' 1/8' )
			.replace( /⅜/g, ' 3/8' )
			.replace( /⅝/g, ' 5/8' )
			.replace( /⅞/g, ' 7/8' )
			.replace( /\s+/g, ' ' )
			.trim();
	}

	function parseSingleQuantity( value ) {
		var normalized = normalizeQuantityText( value );
		var match = normalized.match( /^(\d+)\s+(\d+)\/(\d+)$/ );
		var numerator;
		var denominator;

		if ( match ) {
			numerator = parseInt( match[2], 10 );
			denominator = parseInt( match[3], 10 );

			if ( denominator > 0 ) {
				return parseInt( match[1], 10 ) + ( numerator / denominator );
			}

			return null;
		}

		match = normalized.match( /^(\d+)\/(\d+)$/ );

		if ( match ) {
			numerator = parseInt( match[1], 10 );
			denominator = parseInt( match[2], 10 );

			if ( denominator > 0 ) {
				return numerator / denominator;
			}

			return null;
		}

		if ( /^\d+(?:\.\d+)?$/.test( normalized ) ) {
			return parseFloat( normalized );
		}

		return null;
	}

	function formatFractionNumber( value ) {
		var denominators = [ 2, 3, 4, 8 ];
		var whole = Math.floor( value );
		var fraction = value - whole;
		var bestMatch = null;
		var i;
		var denominator;
		var numerator;
		var candidate;
		var difference;

		if ( fraction < 0.01 ) {
			return String( whole );
		}

		for ( i = 0; i < denominators.length; i++ ) {
			denominator = denominators[i];
			numerator = Math.round( fraction * denominator );
			candidate = numerator / denominator;
			difference = Math.abs( candidate - fraction );

			if ( null === bestMatch || difference < bestMatch.difference ) {
				bestMatch = {
					difference: difference,
					numerator: numerator,
					denominator: denominator
				};
			}
		}

		if ( ! bestMatch || bestMatch.difference > 0.03 || 0 === bestMatch.numerator ) {
			return null;
		}

		if ( bestMatch.numerator === bestMatch.denominator ) {
			return String( whole + 1 );
		}

		if ( whole > 0 ) {
			return whole + ' ' + bestMatch.numerator + '/' + bestMatch.denominator;
		}

		return bestMatch.numerator + '/' + bestMatch.denominator;
	}

	function formatScaledQuantity( value, unitKey ) {
		var decimalUnits = [ 'ml', 'l', 'g', 'kg' ];
		var roundedValue = roundNumber( value, 2 );
		var fractionText;

		if ( roundedValue <= 0 ) {
			return '0';
		}

		if ( -1 !== decimalUnits.indexOf( unitKey ) ) {
			return decimalFormatter.format( roundedValue );
		}

		fractionText = formatFractionNumber( roundedValue );

		if ( fractionText ) {
			return fractionText;
		}

		return decimalFormatter.format( roundedValue );
	}

	function scaleQuantityText( rawQuantity, ratio, unitKey, unitLabel ) {
		var normalized = normalizeQuantityText( rawQuantity );
		var rangeMatch = normalized.match( /^(.+?)\s*[-–]\s*(.+)$/ );
		var firstValue;
		var secondValue;
		var singleValue;
		var scaledParts;

		if ( ! normalized ) {
			return null;
		}

		if ( rangeMatch ) {
			firstValue = parseSingleQuantity( rangeMatch[1] );
			secondValue = parseSingleQuantity( rangeMatch[2] );

			if ( null === firstValue || null === secondValue ) {
				return null;
			}

			scaledParts = [
				formatScaledQuantity( firstValue * ratio, unitKey ),
				formatScaledQuantity( secondValue * ratio, unitKey )
			];

			return unitLabel ? scaledParts.join( ' - ' ) + ' ' + unitLabel : scaledParts.join( ' - ' );
		}

		singleValue = parseSingleQuantity( normalized );

		if ( null === singleValue ) {
			return null;
		}

		return unitLabel ? formatScaledQuantity( singleValue * ratio, unitKey ) + ' ' + unitLabel : formatScaledQuantity( singleValue * ratio, unitKey );
	}

	function formatServingsLabel( servings, label ) {
		label = String( label || '' ).trim();

		if ( label ) {
			if ( 1 === servings && 'personen' === label.toLowerCase() ) {
				return 'persoon';
			}

			return label;
		}

		return 1 === servings ? 'persoon' : 'personen';
	}

	function updateIngredientAmount( ingredientRow, ratio ) {
		var baseQuantity = ingredientRow.getAttribute( 'data-base-quantity' ) || '';
		var unitKey = ingredientRow.getAttribute( 'data-base-unit-key' ) || '';
		var unitLabel = ingredientRow.getAttribute( 'data-base-unit' ) || '';
		var baseAmount = ingredientRow.getAttribute( 'data-base-amount' ) || '';
		var amountElement = ingredientRow.querySelector( '[data-ingredient-amount]' );
		var scaledAmount = scaleQuantityText( baseQuantity, ratio, unitKey, unitLabel );
		var amountText = null === scaledAmount ? baseAmount : scaledAmount;

		if ( ! amountElement ) {
			return;
		}

		amountElement.textContent = amountText;
		amountElement.classList.toggle( 'hidden', '' === amountText );
	}

	function initRecipeServings() {
		var servingsRoot = document.querySelector( '[data-recipe-servings]' );
		var baseServings;
		var currentServings;
		var decreaseButton;
		var increaseButton;
		var displayElement;
		var countElement;
		var labelElement;
		var summaryElement;
		var servingsLabel;
		var ingredientRows;

		if ( ! servingsRoot ) {
			return;
		}

		baseServings = parseInt( servingsRoot.getAttribute( 'data-base-servings' ), 10 );

		if ( ! baseServings || baseServings < 1 ) {
			return;
		}

		currentServings = baseServings;
		servingsLabel = servingsRoot.getAttribute( 'data-servings-label' ) || '';
		decreaseButton = servingsRoot.querySelector( '[data-servings-decrease]' );
		increaseButton = servingsRoot.querySelector( '[data-servings-increase]' );
		displayElement = servingsRoot.querySelector( '[data-servings-display]' );
		countElement = servingsRoot.querySelector( '[data-servings-count]' );
		labelElement = servingsRoot.querySelector( '[data-servings-unit-label]' );
		summaryElement = document.querySelector( '[data-servings-summary]' );
		ingredientRows = document.querySelectorAll( '[data-ingredient]' );

		function render() {
			var ratio = currentServings / baseServings;

			if ( displayElement ) {
				displayElement.textContent = String( currentServings );
			}

			if ( countElement ) {
				countElement.textContent = String( currentServings );
			}

			if ( labelElement ) {
				labelElement.textContent = formatServingsLabel( currentServings, servingsLabel );
			}

			if ( summaryElement ) {
				summaryElement.textContent = currentServings + ' ' + formatServingsLabel( currentServings, servingsLabel );
			}

			if ( decreaseButton ) {
				decreaseButton.disabled = currentServings <= 1;
			}

			ingredientRows.forEach( function( ingredientRow ) {
				updateIngredientAmount( ingredientRow, ratio );
			} );
		}

		if ( decreaseButton ) {
			decreaseButton.addEventListener( 'click', function() {
				if ( currentServings <= 1 ) {
					return;
				}

				currentServings--;
				render();
			} );
		}

		if ( increaseButton ) {
			increaseButton.addEventListener( 'click', function() {
				currentServings++;
				render();
			} );
		}

		render();
	}

	function normalizeSearchText( value ) {
		var text = String( value || '' ).toLowerCase();

		if ( 'function' === typeof text.normalize ) {
			text = text.normalize( 'NFD' ).replace( /[\u0300-\u036f]/g, '' );
		}

		return text.replace( /\s+/g, ' ' ).trim();
	}

	function formatRecipeSearchCount( count, hasQuery ) {
		return 1 === count ? '1 recept' : count + ' recepten';
	}

	function initRecipeArchiveSearch() {
		var archiveRoot = document.querySelector( '[data-recipe-archive]' );
		var input;
		var cards;
		var indexedCards;
		var emptyElement;
		var countElement;

		if ( ! archiveRoot ) {
			return;
		}

		input = archiveRoot.querySelector( '[data-recipe-search-input]' );
		cards = Array.prototype.slice.call( archiveRoot.querySelectorAll( '[data-recipe-card]' ) );
		emptyElement = archiveRoot.querySelector( '[data-recipe-search-empty]' );
		countElement = archiveRoot.querySelector( '[data-recipe-search-count]' );

		if ( ! input || ! cards.length ) {
			return;
		}

		indexedCards = cards.map( function( card ) {
			return {
				element: card,
				searchText: normalizeSearchText( card.getAttribute( 'data-recipe-search' ) || card.textContent )
			};
		} );

		function renderResults() {
			var query = normalizeSearchText( input.value );
			var queryParts = query ? query.split( ' ' ) : [];
			var visibleCount = 0;

			indexedCards.forEach( function( item ) {
				var isVisible = ! queryParts.length || queryParts.every( function( queryPart ) {
					return -1 !== item.searchText.indexOf( queryPart );
				} );

				item.element.classList.toggle( 'hidden', ! isVisible );

				if ( isVisible ) {
					item.element.removeAttribute( 'aria-hidden' );
					visibleCount++;
				} else {
					item.element.setAttribute( 'aria-hidden', 'true' );
				}
			} );

			if ( countElement ) {
				countElement.textContent = formatRecipeSearchCount( visibleCount, queryParts.length > 0 );
			}

			if ( emptyElement ) {
				emptyElement.classList.toggle( 'hidden', ! queryParts.length || visibleCount > 0 );
			}
		}

		input.addEventListener( 'input', renderResults );
		input.addEventListener( 'search', renderResults );
		renderResults();
	}

	document.addEventListener( 'DOMContentLoaded', function() {
		var toggleButton = document.getElementById( 'theme-toggle' );
		var isDark = document.documentElement.classList.contains( 'dark' );
		applyTheme( isDark ? 'dark' : 'light' );

		if ( ! toggleButton ) {
			return;
		}

		toggleButton.addEventListener( 'click', function() {
			var useDark = ! document.documentElement.classList.contains( 'dark' );
			applyTheme( useDark ? 'dark' : 'light' );
		} );
	} );

	document.addEventListener( 'DOMContentLoaded', function() {
		initRecipeServings();
		initRecipeArchiveSearch();
	} );

} )();
