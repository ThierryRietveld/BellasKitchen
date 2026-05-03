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

	function setTemplateGenerationStatus( type, message ) {
		var $status = $( '#bkr-template-generation-status' );

		if ( ! $status.length ) {
			return;
		}

		$status
			.removeClass( 'notice-success notice-error notice-info' )
			.addClass( 'notice-' + type )
			.prop( 'hidden', false )
			.find( 'p' )
			.text( message );
	}

	function clearTemplateGenerationStatus() {
		var $status = $( '#bkr-template-generation-status' );

		if ( ! $status.length ) {
			return;
		}

		$status
			.removeClass( 'notice-success notice-error notice-info' )
			.prop( 'hidden', true )
			.find( 'p' )
			.text( '' );
	}

	$( document ).on( 'click', '#bkr-generate-template', function () {
		var $button = $( this );
		var $urlInput = $( '#bkr-template-source-url' );
		var $textarea = $( '#bkr-template-input' );
		var $spinner = $( '#bkr-template-generate-spinner' );
		var url = $.trim( $urlInput.val() );
		var existingValue = $.trim( $textarea.val() );

		if ( ! url ) {
			setTemplateGenerationStatus( 'error', bkrReceptenAdmin.missingUrlText );
			$urlInput.trigger( 'focus' );
			return;
		}

		if ( existingValue && ! window.confirm( bkrReceptenAdmin.confirmReplaceText ) ) {
			return;
		}

		clearTemplateGenerationStatus();
		setTemplateGenerationStatus( 'info', bkrReceptenAdmin.generatingText );

		$button.prop( 'disabled', true );
		$urlInput.prop( 'disabled', true );
		$spinner.addClass( 'is-active' );

		$.ajax( {
			url: bkrReceptenAdmin.ajaxUrl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'bkr_generate_template_from_url',
				nonce: bkrReceptenAdmin.generateTemplateNonce,
				source_url: url,
			},
		} )
			.done( function ( response ) {
				if ( response && response.success && response.data && response.data.template ) {
					$textarea.val( response.data.template ).trigger( 'focus' );
					setTemplateGenerationStatus( 'success', bkrReceptenAdmin.generatedText );
					return;
				}

				if ( response && response.data && response.data.message ) {
					setTemplateGenerationStatus( 'error', response.data.message );
					return;
				}

				setTemplateGenerationStatus( 'error', bkrReceptenAdmin.requestFailedText );
			} )
			.fail( function ( jqXHR ) {
				var response = jqXHR.responseJSON;

				if ( response && response.data && response.data.message ) {
					setTemplateGenerationStatus( 'error', response.data.message );
					return;
				}

				setTemplateGenerationStatus( 'error', bkrReceptenAdmin.requestFailedText );
			} )
			.always( function () {
				$button.prop( 'disabled', false );
				$urlInput.prop( 'disabled', false );
				$spinner.removeClass( 'is-active' );
			} );
	} );
} );
