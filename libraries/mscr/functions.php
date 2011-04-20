<?php
/**
 * Mute Screamer API.
 *
 * @package Mute Screamer
 */

/**
 * Is the current request a banned request
 *
 * @return bool
 */
if( ! function_exists( 'mscr_is_ban' ) ) {
	function mscr_is_ban() {
		return Mute_Screamer::instance()->is_ban;
	}
}

/**
 * Filter for wp_title. Change the page title when displaying a 500 error template.
 *
 * @param string The current page title
 * @param string How to separate the various items within the page title.
 * @param string Direction to display title.
 * @return string
 */
if( ! function_exists( 'mscr_filter_wp_title' ) ) {
	function mscr_filter_wp_title( $title, $sep, $seplocation ) {
		if( mscr_is_ban() ) {
			return sprintf( __( 'Error %s ', 'mute-screamer' ), $sep );
		} else {
			return sprintf( __( 'An Error Was Encountered %s ', 'mute-screamer' ), $sep );
		}
	}
}
