<?php
add_action( 'update_option_acsp_policy', 'acsp_maybe_switch_to_custom', 10, 3 );
function acsp_maybe_switch_to_custom( $old, $new, $option ) {
    if ( ! $new || ! is_array( $new ) ) return;

    $preset_key = get_option( 'acsp_current_preset', '' );
    if ( ! $preset_key || 'custom' === $preset_key ) return;   // already custom

    $preset_pol = acsp_get_presets()[ $preset_key ]['policy'] ?? [];
    if ( ! $preset_pol ) return;

    // normalise order & spaces
    $norm = function( $a ) {
        foreach ( $a as &$v ) $v = implode( ' ', array_unique( preg_split( '/\s+/', trim( $v ) ) ) );
        ksort( $a ); return $a;
    };

    if ( $norm( $preset_pol ) !== $norm( $new ) )
        update_option( 'acsp_current_preset', 'custom' );
}