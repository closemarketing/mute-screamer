<?php  if ( !defined('ABSPATH') ) exit;

/*
 * Mute Screamer utils class
 */
class MSCR_Utils {
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


	/**
	 * Create pagination links
	 *
	 * @return	string
	 */
	public static function pagination($current_page = 1, $total_pages = 0, $per_page = 0, $count = 0)
	{
		$page_links = paginate_links( array(
			'base' => add_query_arg( 'paged', '%#%' ),
			'format' => '',
			'prev_text' => __('&laquo;'),
			'next_text' => __('&raquo;'),
			'total' => $total_pages,
			'current' => $current_page
		));

		if( !$page_links ) {
			return '';
		}

		$page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
			number_format_i18n( ( $current_page - 1 ) * $per_page + 1 ),
			number_format_i18n( min( $current_page * $per_page, $count ) ),
			number_format_i18n( $count ),
			$page_links
		);

		return "<div class='tablenav-pages'>{$page_links_text}</div>";
	}


	/**
	 * Get intrusions per page option
	 *
	 * @return	integer
	 */
	public static function mscr_intrusions_per_page() {
		$per_page = (int) get_user_option('mscr_intrusions_per_page');

		// Set default if user option does not exist
		if( !$per_page ) {
			$per_page = 20;
		}

		return $per_page;
	}


	/**
	 * Get the current upload path
	 *
	 * @return	string
	 */
	public static function upload_path() {
		$upload_path = get_option( 'upload_path' );

		if ( empty($upload_path) ) {
			$dir = WP_CONTENT_DIR . '/uploads';
		} else {
			$dir = $upload_path;
			if ( 'wp-content/uploads' == $upload_path ) {
				$dir = WP_CONTENT_DIR . '/uploads';
			} elseif ( 0 !== strpos($dir, ABSPATH) ) {
				// $dir is absolute, $upload_path is (maybe) relative to ABSPATH
				$dir = path_join( ABSPATH, $dir );
			}
		}

		return $dir;
	}
}
