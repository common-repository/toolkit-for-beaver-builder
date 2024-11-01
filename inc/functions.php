<?php

/**
 * Wrapper function around cmb2_get_option
 * @since  1.0.0
 * @param  string $group   Options for option group
 * @param  string $key     Options array key
 * @param  mixed  $default Optional default value
 * @return mixed           Option value
 */
function bb_toolkit_get_option( $group = 'bb_toolkit_options', $key = '', $default = false ) {
	if ( function_exists( 'cmb2_get_option' ) ) {
		// Use cmb2_get_option as it passes through some key filters.
		return cmb2_get_option( $group, $key, $default );
	}

	// Fallback to get_option if CMB2 is not loaded yet.
	$opts = get_option( $group, $default );

	$val = $default;

	if ( 'all' == $key ) {
		$val = $opts;
	}

	elseif ( is_array( $opts ) && array_key_exists( $key, $opts ) && false !== $opts[$key] ) {
		$val = $opts[$key];
	}

	return $val;
}
