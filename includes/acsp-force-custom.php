<?php
/**
 * Force policy to "custom" when user edits anything that diverges from a preset.
 *
 * @package aCSP-Builder
 */

add_action( 'update_option_acsp_policy', 'acsp_maybe_switch_to_custom', 10, 3 );

/**
 * Switches the active preset to "custom" whenever the saved policy no longer
 * matches the preset that was previously active.
 *
 * @param mixed  $old_value  Previous option value.
 * @param mixed  $value      New option value.
 * @param string $option     Option name (unused).
 */
function acsp_maybe_switch_to_custom( $old_value, $value, $option ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Required by hook signature.
	if ( ! $value || ! is_array( $value ) ) {
		return;
	}

	$preset_key = get_option( 'acsp_current_preset', '' );
	if ( ! $preset_key || 'custom' === $preset_key ) {
		return; // Already custom.
	}

	$preset_pol = acsp_get_presets()[ $preset_key ]['policy'] ?? array();
	if ( ! $preset_pol ) {
		return;
	}

	// Normalise order & spaces.
	$norm = function ( $a ) {
		foreach ( $a as &$v ) {
			$v = implode( ' ', array_unique( preg_split( '/\s+/', trim( $v ) ) ) );
		}
		ksort( $a );
		return $a;
	};

	if ( $norm( $preset_pol ) !== $norm( $value ) ) {
		update_option( 'acsp_current_preset', 'custom' );
	}
}
