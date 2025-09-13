/**
 * The aCSP Builder admin scripts
 *
 * @package aCSP
 */

( function ( $ ) {
	/* -------------------------------------------------------------
	 * 1. Builder tab – Add/Remove custom URLs
	 * ----------------------------------------------------------- */
	$( document ).on(
		'click',
		'.acsp-add-url',
		function () {
			const dir  = $( this ).data( 'dir' );
			const html =
			'<div style="margin-top:4px;">' +
			'<input type="text" name="acsp_policy[' + dir + '][]" value="" ' +
			'placeholder="https://example.com" class="regular-text code" /> ' +
			'<button type="button" class="button acsp-remove-url">Remove</button>' +
			'</div>';
			$( this ).prev( '.acsp-custom-urls' ).append( html );
		}
	);

	$( document ).on(
		'click',
		'.acsp-remove-url',
		function () {
			$( this ).parent().remove();
		}
	);

	/* -------------------------------------------------------------
	 * 2. Settings tab – Hash Allow-List
	 * ----------------------------------------------------------- */
	$(
		function () {
			const box = $( '#acsp-hash-list' );
			$( '#acsp-add-hash' ).on(
				'click',
				function () {
					box.append(
						'<div class="acsp-hash-item">' +
						'<input type="text" name="acsp_hash_values[]" value="" placeholder="sha256-…" class="regular-text code"/>' +
						'<button type="button" class="button button-small acsp-remove-hash">Remove</button>' +
						'</div>'
					);
				}
			);
			box.on(
				'click',
				'.acsp-remove-hash',
				function () {
					$( this ).closest( '.acsp-hash-item' ).remove();
				}
			);
			$( '#acsp_enable_hashes' ).on(
				'change',
				function () {
					$( '.acsp-hash-row' ).toggle( $( this ).prop( 'checked' ) );
				}
			);
		}
	);

	/* -------------------------------------------------------------
	* 3. Settings tab – Report Endpoint Testing
	* ----------------------------------------------------------- */
	$( document ).ready(
		function () {
			$( '#acsp_test_endpoint' ).on(
				'click',
				function () {
					const endpoint      = $( '#acsp_report_endpoint' ).val().trim();
					const resultElement = $( '#acsp_test_result' );

					if ( ! endpoint ) {
							resultElement.html( '<span style="color: #dc3232;">Please enter an endpoint URL first</span>' ).show();
							return;
					}

					// Show loading state.
					$( this ).prop( 'disabled', true ).text( 'Testing...' );
					resultElement.html( '<span style="color: #666;">Testing endpoint...</span>' ).show();

					// Send AJAX request.
					$.ajax(
						{
							url: acsp_ajax.ajaxurl,
							type: 'POST',
							data: {
								action: 'acsp_test_report_endpoint',
								url: endpoint,
								nonce: acsp_ajax.nonce
							},
							success: function ( response ) {
								if ( response.success ) {
									resultElement.html( '<span style="color: #46b450;">✓ Endpoint available and responding</span>' );
								} else {
									resultElement.html( '<span style="color: #dc3232;">✗ Endpoint error: ' + response.data + '</span>' );
								}
							},
							error: function ( xhr, status, error ) {
								resultElement.html( '<span style="color: #dc3232;">✗ AJAX error: ' + error + '</span>' );
							},
							complete: function () {
								$( '#acsp_test_endpoint' ).prop( 'disabled', false ).text( 'Test Endpoint' );

								// Hide result after 5 seconds.
								setTimeout(
									function () {
										resultElement.fadeOut();
									},
									5000
								);
							}
						}
					);
				}
			);
		}
	);
} )( jQuery );