<?php
/**
 * AMP functionality
 *
 * @package      RBStarter
 * @author       Red Bridge Internet
 * @since        1.0.0
 * @license      GPL-2.0+
 **/

/**
 * Is AMP?
 * Conditional tag
 */
function rb_is_amp() {
	return function_exists( 'is_amp_endpoint' ) && is_amp_endpoint();
}

/**
 * WPForms AMP Support
 *
 */
function rb_wpforms_amp_support( $is_pro ) {
	if ( rb_is_amp() ) {
		$is_pro = false;
	}
	
	return $is_pro;
}

add_filter( 'wpforms_amp_pro', 'rb_wpforms_amp_support' );

/**
 * Generate a class attribute and an AMP class attribute binding.
 *
 * @param string $default Default class value.
 * @param string $active  Value when the state enabled.
 * @param string $state   State variable to toggle based on.
 *
 * @return string HTML attributes.
 */
function rb_amp_class( $default, $active, $state ) {
	$output = '';
	if ( rb_is_amp() ) {
		$output .= sprintf(
			' [class]="%s"',
			esc_attr(
				sprintf(
					'%s ? \'%s\' : \'%s\'',
					$state,
					$default . ' ' . $active,
					$default
				)
			)
		);
	}
	$output .= sprintf( ' class="%s"', esc_attr( $default ) );
	
	return $output;
}

/**
 * Add the AMP toggle 'on' attribute.
 *
 * @param string $state   State to toggle.
 * @param array  $disable , list of states to disable
 *
 * @return string The 'on' attribute.
 */
function rb_amp_toggle( $state = '', $disable = array() ) {
	if ( ! rb_is_amp() ) {
		return;
	}
	
	$settings = sprintf(
		'%1$s: ! %1$s',
		esc_js( $state )
	);
	
	if ( ! empty( $disable ) ) {
		foreach ( $disable as $disableState ) {
			$settings .= sprintf(
				', %s: false',
				esc_js( $disableState )
			);
		}
	}
	
	return sprintf(
		' on="tap:AMP.setState({%s})"',
		$settings
	);
	
}

/**
 * AMP Nav Dropdown toggle and class attributes.
 *
 * @param bool|string $theme_location Theme location.
 * @param int    $depth          Depth.
 *
 * @return string The class and on attributes.
 */
function rb_amp_nav_dropdown( $theme_location = false, $depth = 0 ) {
	
	$key = 'nav';
	if ( ! empty( $theme_location ) ) {
		$key .= ucwords( $theme_location );
	}
	
	global $submenu_index;
	$submenu_index ++;
	$key .= 'SubmenuExpanded' . $submenu_index;
	
	if ( 1 < $depth ) {
		$key .= 'Depth' . $depth;
	}
	
	return rb_amp_toggle( $key ) . rb_amp_class( 'submenu-expand', 'expanded', $key );
}
