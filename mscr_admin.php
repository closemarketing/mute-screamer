<?php  if ( !defined('ABSPATH') ) exit;

/**
 * Mute Screamer admin class
 */
class Mscr_admin {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_init', array($this, 'admin_init') );
		add_action( 'admin_menu', array($this, 'admin_menu') );
		add_filter( 'screen_settings', array($this, 'screen_settings'), 10, 2 );
		add_filter( 'set-screen-option', array($this, 'set_screen_option'), 10, 3 );

		// Run update routines
		// TODO: Don't check for updates will on update-core.php
		$update = MSCR_Update::instance();
		$update->update_check();

		// Update core actions
		// add_action( 'load-update-core.php', array($update, 'load_update_core') );

		// Display Mute Screamer updates in the Wordpress update admin page
		add_action( 'core_upgrade_preamble', array( $update, 'list_mscr_updates' ) );

		// Update Mute Screamer action
		add_action( 'update-custom_mscr_upgrade_diff', array( $update, 'do_upgrade_diff' ) );
	}


	/**
	 * Admin init
	 *
	 * @return	void
	 */
	public function admin_init() {
		// Are we on Mute Screamer's intrusions page?
		if( $this->page() == 'mscr_intrusions' ) {
			// Handle bulk actions
			$this->do_action();

			// Reset new instrusions badge for admin menu
			// Must be called before register_setting
			Mute_screamer::instance()->set_option( 'new_intrusions_count', 0 );
			return;
		}

		// Add admin CSS
		wp_enqueue_style( 'mscr_styles', WP_PLUGIN_URL . '/mute-screamer/css/mscr.css', array(), Mute_screamer::VERSION );

		// Once a setting is registered adding/updating options
		// will run options_validate, which we may not want in all cases
		register_setting( 'mscr_options', 'mscr_options', array($this, 'options_validate') );
	}


	/**
	 * Perform an action based on the request
	 *
	 * @return	void
	 */
	private function do_action() {
		global $wpdb;

		// Handle bulk actions
		if ( isset( $_GET['doaction'] ) || isset( $_GET['doaction2'] ) ) {
			check_admin_referer('mscr_action_intrusions_bulk');
			$sendback = remove_query_arg( array('intrusions' ), wp_get_referer() );

			if ( ( $_GET['action'] != -1 || $_GET['action2'] != -1 ) && ( isset($_GET['page']) && isset($_GET['intrusions']) ) ) {
				$intrusion_ids = $_GET['intrusions'];
				$doaction = ($_GET['action'] != -1) ? $_GET['action'] : $_GET['action2'];
			} else {
				wp_redirect( admin_url("index.php?page=mscr_intrusions") );
				exit;
			}

			switch( $doaction ) {
				case 'bulk_delete':
					$deleted = 0;
					foreach( (array) $intrusion_ids as $intrusion_id ) {
						if( !current_user_can('activate_plugins') )
							wp_die( __('You are not allowed to delete this item.') );

						$sql = $wpdb->prepare( "DELETE FROM ".Mute_screamer::INTRUSIONS_TABLE." WHERE id = %d", $intrusion_id );
						$result = $wpdb->query( $sql );

						if( ! $result) {
							wp_die( __('Error in deleting...') );
						}
						$deleted++;
					}
					$sendback = add_query_arg( 'deleted', $deleted, $sendback );
					break;
			}

			if( isset($_GET['action']) )
				$sendback = remove_query_arg( array('action', 'action2', 'intrusions'), $sendback );

			wp_redirect($sendback);
			exit;
		} elseif( ! empty($_GET['_wp_http_referer']) ) {
			wp_redirect( remove_query_arg( array('_wp_http_referer', '_wpnonce'), stripslashes($_SERVER['REQUEST_URI']) ) );
			exit;
		}
	}


	/**
	 * Add custon screen options to a plugin page
	 *
	 * @param	string
	 * @param	object
	 * @return	string
	 */
	public function screen_settings( $action, $screen_object ) {
		global $current_user;

		if( $screen_object->id == 'dashboard_page_mscr_intrusions' ) {
			$per_page = MSCR_Utils::mscr_intrusions_per_page();

			$data['per_page'] = $per_page;
			$action = MSCR_Utils::view('admin_intrusions_screen_options', $data, TRUE);
		}

		return $action;
	}


	/**
	 * Update the current user's screen options
	 *
	 * @return	mixed
	 */
	public function set_screen_option( $flag, $option, $value ) {
		switch( $option ) {
			case 'mscr_intrusions_per_page':
				$value = absint($value);
				if( $value < 1 ) {
					return FALSE;
				}

				return $value;
		}

		return $flag;
	}


	/**
	 * Add admin menu items
	 *
	 * @return	void
	 */
	public function admin_menu() {
		$intrusion_count = (int) Mute_screamer::instance()->get_option( 'new_intrusions_count' );
		$intrusions_menu_title = sprintf( __('Intrusions %s'), "<span class='update-plugins count-$intrusion_count' title='$intrusion_count'><span class='update-count'>" . number_format_i18n($intrusion_count) . "</span></span>" );
		add_dashboard_page( __('Mute Screamer Intrusions'), $intrusions_menu_title, 'activate_plugins', 'mscr_intrusions', array($this, 'intrusions') );
		add_options_page( __('Mute Screamer Configuration'), __('Mute Screamer'), 'activate_plugins', 'mscr_options', array($this, 'options') );

		// Modify the Dashboard menu updates count
		$this->set_update_badge();
	}

	/**
	 * Change the updates badge in the Dashboard menu
	 * if there are updates available for Mute Screamer
	 *
	 * @return	void
	 */
	private function set_update_badge() {
		global $submenu;
		$updates = get_site_transient( 'mscr_update' );

		if( $updates === FALSE OR empty( $updates['updates'] ) )
			return;

		if( ! isset( $submenu['index.php'] ) )
			return;

		$update_count = count( $updates );
		$existing_count = 0;

		// Find the update-core submenu
		foreach( $submenu['index.php'] as &$item ) {
			if( isset( $item[2] ) && $item[2] == 'update-core.php' ) {
				// Is there already an update badge? Get existing update count
				if( strpos( $item[0], '<span' ) !== FALSE ) {
					$existing_count = preg_replace('/.+?<span\b[^>]*><span\b[^>]*>(\d+)<\/span><\/span>/', '$1', $item[0]);
				}

				$update_count += (int) $existing_count;
				$update_title = sprintf(_n('%d Update', '%d Updates', $update_count), $update_count);
				$item[0] = sprintf( __('Updates %s'), "<span class='update-plugins count-$update_count' title='$update_title'><span class='update-count'>" . number_format_i18n($update_count) . "</span></span>");
				break;
			}
		}
	}

	/**
	 * Display PHPIDS Intrusions
	 *
	 * @return	void
	 */
	public function intrusions()
	{
		global $wpdb;

		// Current page number, items per page
		$per_page = MSCR_Utils::mscr_intrusions_per_page();
		$pagenum = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 0;
		if ( empty($pagenum) )
			$pagenum = 1;

		// Offset, limit
		$limit = $per_page;
		$offset = ( $pagenum * $limit ) - $limit;
		$offset = ( $offset < 0 ) ? 0 : $offset;

		// Get results
		$search = isset( $_GET['intrusions_search'] ) ? esc_attr($_GET['intrusions_search']) : '';
		$search_title = '';
		if($search) {
			$search_title = sprintf( '<span class="subtitle">' . __('Search results for &#8220;%s&#8221;') . '</span>', $search );
			$token = '%'.$search.'%';
			$sql = $wpdb->prepare( "SELECT SQL_CALC_FOUND_ROWS * FROM " . Mute_screamer::INTRUSIONS_TABLE . " WHERE (name LIKE %s OR page LIKE %s OR tags LIKE %s OR ip LIKE %s OR impact LIKE %s) ORDER BY created DESC LIMIT %d, %d", $token, $token, $token, $token, $token, $offset, $limit );
		} else {
			$sql = $wpdb->prepare( "SELECT SQL_CALC_FOUND_ROWS * FROM " . Mute_screamer::INTRUSIONS_TABLE . " ORDER BY created DESC LIMIT %d, %d", $offset, $limit );
		}

		$intrusions = $wpdb->get_results($sql);
		$total_intrusions = $wpdb->get_var("SELECT FOUND_ROWS();");

		// Construct pagination links
		$num_pages = ceil($total_intrusions / $per_page);
		$pagination = MSCR_Utils::pagination($pagenum, $num_pages, $per_page, $total_intrusions);

		// Columns
		$columns = array(
			'name' => 'Name',
			'value' => 'Value',
			'page' => 'Page',
			'tags' => 'Tags',
			'ip' => 'IP',
			'impact' => 'Impact',
			'origin' => 'Origin',
			'date' => 'Date'
		);
		$columns = apply_filters('mscr_admin_intrusions_columns', $columns);

		// Was something deleted?
		$deleted = isset($_GET['deleted']) ? (int) $_GET['deleted'] : 0;

		$data['message'] = FALSE;
		$data['intrusions'] = $intrusions;
		$data['style'] = '';
		$data['columns'] = $columns;
		$data['page'] = $_GET['page'];
		$data['pagination'] = $pagination;
		$data['intrusions_search'] = $search;
		$data['search_title'] = $search_title;

		if( $deleted )
			$data['message'] = sprintf( _n( 'Item permanently deleted.', '%s items permanently deleted.', $deleted ), number_format_i18n( $deleted ) );

		MSCR_Utils::view('admin_intrusions', $data);
	}


	/**
	 * Validate options
	 *
	 * @return	array
	 */
	public function options_validate( $input = array() ) {
		$options = get_option( 'mscr_options' );

		foreach( array( 'email', 'email_threshold', 'exception_fields', 'html_fields', 'json_fields' ) as $key ) {
			if( ! isset($input[$key]) ) {
				continue;
			}

			$options[$key] = $input[$key];

			switch($key) {
				case 'email':
					if( !is_email($options[$key]) ) {
						$options[$key] = get_option('admin_email');
					}
					break;

				case 'email_threshold':
					$options[$key] = absint($options[$key]);
					break;

				case 'exception_fields':
				case 'html_fields':
				case 'json_fields':
					$options[$key] = str_replace( array( "\r\n", "\n", "\r" ), "\n", $options[$key] );
					$options[$key] = explode( "\n", $options[$key] );

					// Exception fields array must not contain an empty string
					// otherwise all fields will be excepted
					foreach( $options[$key] as $k => $v ) {
						if( strlen($options[$key][$k]) == 0 ) {
							unset($options[$key][$k]);
						}
					}
			}
		}

		// Warnings
		$options['warning_wp_admin'] = isset($input['warning_wp_admin']) ? 1 : 0;
		$options['warning_threshold'] = absint( $input['warning_threshold'] );

		// Checkboxes
		$options['email_notifications'] = isset($input['email_notifications']) ? 1 : 0;
		$options['enable_admin'] = isset($input['enable_admin']) ? 1 : 0;

		return $options;
	}


	/**
	 * Display options page
	 *
	 * @return	void
	 */
	public function options() {
		//$url = 'options-general.php?page=mscr_options&action=update_phpids';
		//request_filesystem_credentials($url, '', true, ABSPATH);
		//return;

		$options = get_option( 'mscr_options' );
		$options['exception_fields'] = implode("\r\n", $options['exception_fields']);
		$options['html_fields'] = implode("\r\n", $options['html_fields']);
		$options['json_fields'] = implode("\r\n", $options['json_fields']);

		MSCR_Utils::view('admin_options', $options);
	}


	/**
	 * Retrieve the admin page variable
	 *
	 * @return	string|bool
	 */
	private function page() {
		return isset( $_GET['page'] ) ? $_GET['page'] : FALSE;
	}
}
