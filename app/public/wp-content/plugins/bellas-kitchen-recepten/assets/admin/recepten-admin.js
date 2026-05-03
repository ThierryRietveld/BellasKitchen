/* global bkrReceptenAdmin, jQuery, wp */
jQuery( function ( $ ) {
	var imageFrame;

	$( document ).on( 'click', '.bkr-select-image', function () {
		if ( imageFrame ) {
			imageFrame.open();
			return;
		}

		imageFrame = wp.media( {
			title: bkrReceptenAdmin.mediaTitle,
			button: {
				text: bkrReceptenAdmin.mediaButton,
			},
			library: {
				type: 'image',
			},
			multiple: false,
		} );

		imageFrame.on( 'select', function () {
			var attachment = imageFrame.state().get( 'selection' ).first().toJSON();
			var imageUrl = attachment.url;

			if ( attachment.sizes && attachment.sizes.medium ) {
				imageUrl = attachment.sizes.medium.url;
			} else if ( attachment.sizes && attachment.sizes.thumbnail ) {
				imageUrl = attachment.sizes.thumbnail.url;
			}

			$( '#bkr-foto-id' ).val( attachment.id );
			$( '#bkr-foto-preview' ).empty().append(
				$( '<img>', {
					src: imageUrl,
					alt: '',
					class: 'bkr-recepten-thumb',
				} )
			);
			$( '.bkr-remove-image' ).show();
		} );

		imageFrame.open();
	} );

	$( document ).on( 'click', '.bkr-remove-image', function () {
		$( '#bkr-foto-id' ).val( '' );
		$( '#bkr-foto-preview' ).empty().append(
			$( '<span>', {
				class: 'bkr-no-image',
				text: bkrReceptenAdmin.noImageText,
			} )
		);
		$( this ).hide();
	} );

	var ingredientIndex = $( '#bkr-ingredienten .bkr-ingredient-row' ).length;

	$( document ).on( 'click', '.bkr-add-ingredient', function () {
		var template = $( '#bkr-ingredient-template' ).html();

		template = template.replace( /\{\{index\}\}/g, ingredientIndex );
		$( '#bkr-ingredienten' ).append( template );
		ingredientIndex++;
	} );

	$( document ).on( 'click', '.bkr-remove-ingredient', function () {
		var rows = $( '#bkr-ingredienten .bkr-ingredient-row' );

		if ( rows.length > 1 ) {
			$( this ).closest( '.bkr-ingredient-row' ).remove();
			return;
		}

		rows.find( 'input[type="text"]' ).val( '' );
		rows.find( 'select' ).val( '' );
	} );

	var instructionIndex = $( '#bkr-instructies .bkr-instruction-row' ).length;

	function renumberInstructions() {
		$( '#bkr-instructies .bkr-instruction-row' ).each( function ( index ) {
			$( this ).find( '.bkr-instruction-number' ).text( index + 1 );
			$( this ).find( 'textarea' ).attr( 'name', 'instructies[' + index + '][text]' );
		} );
	}

	$( document ).on( 'click', '.bkr-add-instruction', function () {
		var step = $( '#bkr-instructies .bkr-instruction-row' ).length + 1;
		var template = $( '#bkr-instruction-template' ).html();

		template = template
			.replace( /\{\{index\}\}/g, instructionIndex )
			.replace( /\{\{step\}\}/g, step );

		$( '#bkr-instructies' ).append( template );
		instructionIndex++;
	} );

	$( document ).on( 'click', '.bkr-remove-instruction', function () {
		var rows = $( '#bkr-instructies .bkr-instruction-row' );

		if ( rows.length > 1 ) {
			$( this ).closest( '.bkr-instruction-row' ).remove();
			renumberInstructions();
			return;
		}

		rows.find( 'textarea' ).val( '' );
	} );
} );
