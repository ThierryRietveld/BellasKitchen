/**
 * Bellas Kitchen Theme - Main JavaScript
 *
 * @package BellasKitchenTheme
 */

( function() {
	'use strict';

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

} )();
