/* global wp, jQuery */
jQuery( function ( $ ) {

	// ── Main Image ────────────────────────────────────────────────────────────

	var mainImageFrame;

	$( document ).on( 'click', '.bkr-upload-main-image', function () {
		if ( mainImageFrame ) {
			mainImageFrame.open();
			return;
		}

		mainImageFrame = wp.media( {
			title:    'Select Main Image',
			button:   { text: 'Use This Image' },
			multiple: false,
			library:  { type: 'image' },
		} );

		mainImageFrame.on( 'select', function () {
			var attachment = mainImageFrame.state().get( 'selection' ).first().toJSON();
			var thumb = ( attachment.sizes && attachment.sizes.thumbnail )
				? attachment.sizes.thumbnail.url
				: attachment.url;

			$( '#recipe_main_image_id' ).val( attachment.id );
			$( '#bkr-main-image-preview' ).html( '<img src="' + thumb + '" alt="">' );
			$( '.bkr-upload-main-image' ).text( 'Change Image' );

			if ( ! $( '.bkr-remove-main-image' ).length ) {
				$( '.bkr-upload-main-image' ).after(
					'<button type="button" class="button bkr-remove-main-image">Remove</button>'
				);
			}
		} );

		mainImageFrame.open();
	} );

	$( document ).on( 'click', '.bkr-remove-main-image', function () {
		$( '#recipe_main_image_id' ).val( '' );
		$( '#bkr-main-image-preview' ).empty();
		$( '.bkr-upload-main-image' ).text( 'Select Image' );
		$( this ).remove();
	} );

	// ── Extra Images / Gallery ────────────────────────────────────────────────

	var galleryFrame;

	$( document ).on( 'click', '.bkr-upload-gallery-images', function () {
		if ( galleryFrame ) {
			galleryFrame.open();
			return;
		}

		galleryFrame = wp.media( {
			title:   'Select Extra Images',
			button:  { text: 'Add to Recipe' },
			multiple: 'add',
			library: { type: 'image' },
		} );

		galleryFrame.on( 'select', function () {
			var currentIds = $( '#recipe_extra_image_ids' ).val().split( ',' ).filter( Boolean );

			galleryFrame.state().get( 'selection' ).each( function ( attachment ) {
				var data  = attachment.toJSON();
				var id    = String( data.id );
				var thumb = ( data.sizes && data.sizes.thumbnail )
					? data.sizes.thumbnail.url
					: data.url;

				if ( currentIds.indexOf( id ) === -1 ) {
					currentIds.push( id );
					$( '#bkr-gallery-preview' ).append(
						'<div class="bkr-gallery-item" data-id="' + id + '">' +
						'<img src="' + thumb + '" alt="">' +
						'<button type="button" class="bkr-remove-gallery-image" data-id="' + id + '">✕</button>' +
						'</div>'
					);
				}
			} );

			$( '#recipe_extra_image_ids' ).val( currentIds.join( ',' ) );
		} );

		galleryFrame.open();
	} );

	$( document ).on( 'click', '.bkr-remove-gallery-image', function () {
		var id         = String( $( this ).data( 'id' ) );
		var currentIds = $( '#recipe_extra_image_ids' ).val().split( ',' ).filter( function ( v ) {
			return v !== id;
		} );

		$( this ).closest( '.bkr-gallery-item' ).remove();
		$( '#recipe_extra_image_ids' ).val( currentIds.join( ',' ) );
	} );

	// ── Ingredients Repeater ──────────────────────────────────────────────────

	var ingredientIndex = $( '#bkr-ingredients-list .bkr-ingredient-row' ).length;

	$( document ).on( 'click', '.bkr-add-ingredient', function () {
		var template = $( '#bkr-ingredient-template' ).html();
		template = template.replace( /\{\{index\}\}/g, ingredientIndex );
		$( '#bkr-ingredients-list' ).append( template );
		ingredientIndex++;
	} );

	$( document ).on( 'click', '#bkr-ingredients-list .bkr-remove-row', function () {
		if ( $( '#bkr-ingredients-list .bkr-ingredient-row' ).length > 1 ) {
			$( this ).closest( '.bkr-ingredient-row' ).remove();
		}
	} );

	// ── Instructions Repeater ─────────────────────────────────────────────────

	var instructionIndex = $( '#bkr-instructions-list .bkr-instruction-row' ).length;

	function renumberSteps() {
		$( '#bkr-instructions-list .bkr-instruction-row' ).each( function ( i ) {
			$( this ).find( '.bkr-step-number' ).text( i + 1 );
			$( this ).find( 'textarea' ).attr( 'name', 'recipe_instructions[' + i + '][text]' );
		} );
	}

	$( document ).on( 'click', '.bkr-add-instruction', function () {
		var step     = $( '#bkr-instructions-list .bkr-instruction-row' ).length + 1;
		var template = $( '#bkr-instruction-template' ).html();
		template = template
			.replace( /\{\{index\}\}/g, instructionIndex )
			.replace( /\{\{step\}\}/g, step );

		$( '#bkr-instructions-list' ).append( template );
		instructionIndex++;
	} );

	$( document ).on( 'click', '#bkr-instructions-list .bkr-remove-row', function () {
		if ( $( '#bkr-instructions-list .bkr-instruction-row' ).length > 1 ) {
			$( this ).closest( '.bkr-instruction-row' ).remove();
			renumberSteps();
		}
	} );

} );
