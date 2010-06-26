<?php  if ( !defined('ABSPATH') ) exit;

/*
 * Mute Screamer utils class
 */
class Utils {
	/**
	 * Load a template file
	 *
	 * @return	void/string
	 */
	public static function view( $view, $vars = array(), $return = FALSE ) {
		$found = FALSE;

		// Look in Mute Screamer views and the current Wordpress theme directories
		for( $i = 1; $i < 3; $i++ ) {
			$path = ($i % 2) ? MSCR_PATH . "/views/" : TEMPLATEPATH . '/';
			$view_path = $path . $view . '.php';

			// Does the file exist?
			if( file_exists($view_path) ) {
				$found = TRUE;
				break;
			}
		}

		if( $found === TRUE ) {
			extract($vars);
			ob_start();

			include($view_path);

			// Return the data if requested
			if( $return === TRUE ) {
				$buffer = ob_get_contents();
				@ob_end_clean();
				return $buffer;
			}

			$output = ob_get_contents();
			@ob_end_clean();

			echo $output;
		} else if( defined('WP_DEBUG') && WP_DEBUG == TRUE ) {
			trigger_error('Unable to load the requested view.', E_USER_ERROR);
		}
	}
}
