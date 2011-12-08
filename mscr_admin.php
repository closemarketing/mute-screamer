<?php  if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Mute Screamer admin class
 */
class MSCR_Admin {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_filter( 'screen_settings', array( $this, 'screen_settings' ), 10, 2 );
		add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 10, 3 );
		add_filter( 'plugin_action_links_mute-screamer/mute-screamer.php', array( $this, 'plugin_action_links' ) );
		add_action( 'load-dashboard_page_mscr_intrusions', array( $this, 'dashboard_page_mscr_intrusions' ) );

		// Run update routines
		if ( Mute_Screamer::instance()->get_option( 'enable_automatic_updates' ) ) {
			$update = MSCR_Update::instance();
			$update->update_check();

			// Display Mute Screamer updates in the Wordpress update admin page
			add_action( 'core_upgrade_preamble', array( $update, 'list_mscr_updates' ) );

			// Update Mute Screamer actions
			add_action( 'update-custom_mscr_upgrade_diff', array( $update, 'do_upgrade_diff' ) );
			add_action( 'update-custom_mscr_upgrade', array( $update, 'do_upgrade' ) );
			add_action( 'update-custom_mscr_upgrade_run', array( $update, 'do_upgrade_run' ) );
		}
	}

	/**
	 * Intrusions page load action
	 *
	 * @return void
	 */
	public function dashboard_page_mscr_intrusions() {
		// WordPress 3.3
		if ( function_exists( 'wp_suspend_cache_addition' ) ) {
			$args = array(
				'title' => 'Help',
				'id' => 'mscr_help',
				'content' => $this->get_contextual_help(),
			);
			get_current_screen()->add_help_tab( $args );
		}
		// WordPress 3.1 and later
		else if ( function_exists( 'get_current_screen' ) ) {
			// Add help to the intrusions list page
			add_contextual_help( get_current_screen(), $this->get_contextual_help() );
		}
	}

	/**
	 * Get contextual help for the intrusions page
	 *
	 * @return string
	 */
	public function get_contextual_help() {
		return '<p>' . __( 'Hovering over a row in the intrusions list will display action links that allow you to manage the intrusion. You can perform the following actions:', 'mute-screamer' ) . '</p>' .
			'<ul>' .
			'<li>' . __( 'Exclude automatically adds the item to the Exception fields list.', 'mute-screamer' ) . '</li>' .
			'<li>' . __( 'Delete permanently deletes the intrusion.', 'mute-screamer' ) . '</li>' .
			'</ul>';
	}

	/**
	 * Admin init
	 *
	 * @return void
	 */
	public function admin_init() {
		// Are we on Mute Screamer's intrusions page?
		if ( MSCR_Utils::get( 'page' ) == 'mscr_intrusions' ) {
			// Handle bulk actions
			$this->do_action();

			// Reset new instrusions badge for admin menu
			// Must be called before register_setting, becuase it updates options
			Mute_Screamer::instance()->set_option( 'new_intrusions_count', 0 );
			return;
		}

		// Add admin CSS
		wp_enqueue_style( 'mscr_styles', Mute_Screamer::plugin_url() . 'css/mscr.css', array(), Mute_Screamer::VERSION );

		// Once a setting is registered updating options
		// will run options_validate on every call to update_option
		register_setting( 'mscr_options', 'mscr_options', array( $this, 'options_validate' ) );
	}

	/**
	 * Perform an action based on the request
	 *
	 * @return void
	 */
	private function do_action() {
		global $wpdb;
		$sendback = remove_query_arg( array( 'intrusions' ), wp_get_referer() );

		// Handle bulk actions
		if ( isset( $_GET['doaction'] ) || isset( $_GET['doaction2'] ) ) {
			check_admin_referer( 'mscr_action_intrusions_bulk' );

			if ( ( $_GET['action'] != '' || $_GET['action2'] != '' ) && ( isset( $_GET['page'] ) && isset( $_GET['intrusions'] ) ) ) {
				$intrusion_ids = $_GET['intrusions'];
				$doaction = ( $_GET['action'] != '' ) ? $_GET['action'] : $_GET['action2'];
			} else {
				wp_redirect( admin_url( 'index.php?page=mscr_intrusions' ) );
				exit;
			}

			switch ( $doaction ) {
				case 'bulk_delete':
					$deleted = 0;
					foreach ( (array) $intrusion_ids as $intrusion_id ) {
						if ( ! current_user_can( 'activate_plugins' ) )
							wp_die( __( 'You are not allowed to delete this item.', 'mute-screamer' ) );

						$sql    = $wpdb->prepare( 'DELETE FROM ' . $wpdb->mscr_intrusions . ' WHERE id = %d', $intrusion_id );
						$result = $wpdb->query( $sql );

						if ( ! $result ) {
							wp_die( __( 'Error in deleting...', 'mute-screamer' ) );
						}
						$deleted++;
					}
					$sendback = add_query_arg( 'deleted', $deleted, $sendback );
					break;

				case 'bulk_exclude':
					$excluded = 0;
					foreach ( (array) $intrusion_ids as $intrusion_id ) {
						if ( ! current_user_can( 'activate_plugins' ) ) {
							wp_die( __( 'You are not allowed to exclude this item.', 'mute-screamer' ) );
						}

						// Get the intrusion field to exclude
						$sql    = $wpdb->prepare( "SELECT name FROM {$wpdb->mscr_intrusions} WHERE id = %d", $intrusion_id );
						$result = $wpdb->get_row( $sql );

						if ( ! $result ) {
							wp_die( __( 'Error in excluding...', 'mute-screamer' ) );
						}

						$mscr = Mute_Screamer::instance();
						$exceptions = $mscr->get_option( 'exception_fields' );

						// Only add the field once
						if ( ! in_array( $result->name, $exceptions ) ) {
							$exceptions[] = $result->name;
						}

						$mscr->set_option( 'exception_fields', $exceptions );
						$excluded++;
					}
					$sendback = add_query_arg( 'excluded', $excluded, $sendback );
					break;
			}

			if ( isset( $_GET['action'] ) ) {
				$sendback = remove_query_arg( array( 'action', 'action2', 'intrusions' ), $sendback );
			}

			wp_redirect( $sendback );
			exit;
		} else if ( ! empty( $_GET['_wp_http_referer'] ) ) {
			wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), stripslashes( $_SERVER['REQUEST_URI'] ) ) );
			exit;
		}

		// Handle other actions
		$action = MSCR_Utils::get( 'action' );
		$id     = (int) MSCR_Utils::get( 'intrusion' );

		if ( ! $action )
			return;

		switch ( $action ) {
			case 'exclude':
				check_admin_referer( 'mscr_action_exclude_intrusion' );
				if ( ! current_user_can( 'activate_plugins' ) )
					wp_die( __( 'You are not allowed to exclude this item.', 'mute-screamer' ) );

				// Get the intrusion field to exclude
				$sql    = $wpdb->prepare( "SELECT name FROM {$wpdb->mscr_intrusions} WHERE id = %d", $id );
				$result = $wpdb->get_row( $sql );

				if ( ! $result ) {
					wp_die( __( 'Error in excluding...', 'mute-screamer' ) );
				}

				$mscr = Mute_Screamer::instance();
				$exceptions = $mscr->get_option( 'exception_fields' );

				// Only add the field once
				if ( ! in_array( $result->name, $exceptions ) ) {
					$exceptions[] = $result->name;
				}

				$mscr->set_option( 'exception_fields', $exceptions );
				$sendback = add_query_arg( 'excluded', $id, $sendback );
				break;

			case 'delete':
				check_admin_referer( 'mscr_action_delete_intrusion' );
				if ( ! current_user_can( 'activate_plugins' ) )
					wp_die( __( 'You are not allowed to delete this item.', 'mute-screamer' ) );

				$sql    = $wpdb->prepare( 'DELETE FROM ' . $wpdb->mscr_intrusions . ' WHERE id = %d', $id );
				$result = $wpdb->query( $sql );

				if ( ! $result ) {
					wp_die( __( 'Error in deleting...', 'mute-screamer' ) );
				}

				$sendback = add_query_arg( 'deleted', 1, $sendback );
				break;
		}

		wp_redirect( $sendback );
		exit;
	}

	/**
	 * Add custom screen options & help to a plugin page
	 *
	 * @param string
	 * @param object
	 * @return string
	 */
	public function screen_settings( $action, $screen_object ) {
		if ( $screen_object->id == 'dashboard_page_mscr_intrusions' ) {
			// Add screen options to the intrusions list page
			$per_page = MSCR_Utils::mscr_intrusions_per_page();
			$data['per_page'] = $per_page;
			$action = MSCR_Utils::view( 'admin_intrusions_screen_options', $data, true );

			// Are we on WordPress 3.1 or higher?
			if ( function_exists( 'get_current_screen' ) ) {
				return $action;
			}

			// Legacy support for contextual help on the intrusions page for WordPress 3.0
			add_contextual_help( $screen_object->id, $this->get_contextual_help() );
		}

		return $action;
	}

	/**
	 * Update the current user's screen options
	 *
	 * @return mixed
	 */
	public function set_screen_option( $flag, $option, $value ) {
		switch ( $option ) {
			case 'mscr_intrusions_per_page':
				$value = absint( $value );
				if ( $value < 1 ) {
					return false;
				}

				return $value;
		}

		return $flag;
	}

	/**
	 * Add admin menu items
	 *
	 * @return void
	 */
	public function admin_menu() {
		$intrusion_count = (int) Mute_Screamer::instance()->get_option( 'new_intrusions_count' );
		$intrusions_menu_title = sprintf( __( 'Intrusions %s', 'mute-screamer' ), "<span class='update-plugins count-$intrusion_count' title='$intrusion_count'><span class='update-count'>" . number_format_i18n( $intrusion_count ) . '</span></span>' );
		add_dashboard_page( __( 'Mute Screamer Intrusions', 'mute-screamer' ), $intrusions_menu_title, 'activate_plugins', 'mscr_intrusions', array( $this, 'intrusions' ) );
		add_options_page( __( 'Mute Screamer Configuration', 'mute-screamer' ), __( 'Mute Screamer', 'mute-screamer' ), 'activate_plugins', 'mscr_options', array( $this, 'options' ) );

		// Modify the Dashboard menu updates count
		$this->set_update_badge();
	}

	/**
	 * Change the updates badge in the Dashboard menu
	 * if there are updates available for Mute Screamer
	 *
	 * @return void
	 */
	private function set_update_badge() {
		global $submenu;
		$updates = get_site_transient( 'mscr_update' );

		if ( $updates === false OR empty( $updates['updates'] ) )
			return;

		if ( ! isset( $submenu['index.php'] ) )
			return;

		$update_count   = count( $updates['updates'] );
		$existing_count = 0;

		// Find the update-core submenu
		foreach ( $submenu['index.php'] as &$item ) {
			if ( isset( $item[2] ) && $item[2] == 'update-core.php' ) {
				// Is there already an update badge? Get existing update count
				if ( strpos( $item[0], '<span' ) !== false ) {
					$existing_count = preg_replace( '/.+?<span\b[^>]*><span\b[^>]*>(\d+)<\/span><\/span>/', '$1', $item[0] );
				}

				$update_count += (int) $existing_count;
				$update_title  = sprintf( _n( '%d Update', '%d Updates', $update_count, 'mute-screamer' ), $update_count );
				$item[0] = sprintf( __( 'Updates %s', 'mute-screamer' ), "<span class='update-plugins count-$update_count' title='$update_title'><span class='update-count'>" . number_format_i18n( $update_count ) . '</span></span>' );
				break;
			}
		}
	}

	/**
	 * Add link to settings on the plugins page
	 *
	 * @param array
	 * @return array
	 */
	public function plugin_action_links( $actions ) {
		$mscr_actions['settings'] = '<a href="'.admin_url( 'options-general.php?page=mscr_options' ).'">Settings</a>';

		foreach ( $actions as $key => $val ) {
			$mscr_actions[$key] = $val;
		}

		return $mscr_actions;
	}
	

	/**
	 * Display PHPIDS Intrusions
	 *
	 * @return void
	 */
	public function intrusions() {
		global $wpdb;

		// Current page number, items per page
		$per_page = MSCR_Utils::mscr_intrusions_per_page();
		$pagenum  = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 0;
		if ( empty( $pagenum ) )
			$pagenum = 1;

		// Offset, limit
		$limit  = $per_page;
		$offset = ( $pagenum * $limit ) - $limit;
		$offset = ( $offset < 0 ) ? 0 : $offset;

		// Get results
		$search = isset( $_GET['intrusions_search'] ) ? stripslashes( $_GET['intrusions_search'] ) : '';
		$search_title = '';
		if ( $search ) {
			$search_title = sprintf( '<span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;', 'mute-screamer' ) . '</span>', esc_html( $search ) );
			$token = '%'.$search.'%';
			$sql = $wpdb->prepare( 'SELECT SQL_CALC_FOUND_ROWS * FROM ' . $wpdb->mscr_intrusions . ' WHERE (name LIKE %s OR page LIKE %s OR tags LIKE %s OR ip LIKE %s OR impact LIKE %s) ORDER BY created DESC LIMIT %d, %d', $token, $token, $token, $token, $token, $offset, $limit );
		} else {
			$sql = $wpdb->prepare( 'SELECT SQL_CALC_FOUND_ROWS * FROM ' . $wpdb->mscr_intrusions . ' ORDER BY created DESC LIMIT %d, %d', $offset, $limit );
		}

		$intrusions = $wpdb->get_results( $sql );
		$total_intrusions = $wpdb->get_var( 'SELECT FOUND_ROWS();' );

		// Construct pagination links
		$num_pages  = ceil( $total_intrusions / $per_page );
		$pagination = MSCR_Utils::pagination( $pagenum, $num_pages, $per_page, $total_intrusions );

		// Columns
		$columns = array(
			'name' => __( 'Name', 'mute-screamer' ),
			'value' => __( 'Value', 'mute-screamer' ),
			'page' => __( 'Page', 'mute-screamer' ),
			'tags' => __( 'Tags', 'mute-screamer' ),
			'ip' => __( 'IP', 'mute-screamer' ),
			'impact' => __( 'Impact', 'mute-screamer' ),
			'date' => __( 'Date', 'mute-screamer' )
		);
		$columns = apply_filters( 'mscr_admin_intrusions_columns', $columns );

		// Was something deleted?
		$deleted = isset( $_GET['deleted'] ) ? (int) $_GET['deleted'] : 0;

		// Was something excluded?
		$excluded = isset( $_GET['excluded'] ) ? (int) $_GET['excluded'] : 0;

		$data['message'] = false;
		$data['intrusions'] = $intrusions;
		$data['style'] = '';
		$data['columns'] = $columns;
		$data['page'] = $_GET['page'];
		$data['pagination'] = $pagination;
		$data['intrusions_search'] = $search;
		$data['search_title'] = $search_title;

		$data['time_offset'] = get_option( 'gmt_offset' ) * 3600;
		$data['date_format'] = get_option( 'date_format' );
		$data['time_format'] = get_option( 'time_format' );

		if ( $deleted )
			$data['message'] = sprintf( _n( 'Item permanently deleted.', '%s items permanently deleted.', $deleted, 'mute-screamer' ), number_format_i18n( $deleted ) );

		if ( $excluded )
			$data['message'] = sprintf( _n( 'Item added to the exceptions list.', '%s items added to the exceptions list.', $excluded, 'mute-screamer' ), number_format_i18n( $excluded ) );

		MSCR_Utils::view( 'admin_intrusions', $data );
	}

	/**
	 * Validate options
	 *
	 * @return array
	 */
	public function options_validate( $input = array() ) {
		$options = get_option( 'mscr_options' );

		foreach ( array( 'email', 'email_threshold', 'exception_fields', 'html_fields', 'json_fields' ) as $key ) {
			if ( ! isset( $input[$key] ) ) {
				continue;
			}

			$options[$key] = $input[$key];

			switch ( $key ) {
				case 'email':
					if ( !is_email( $options[$key] ) ) {
						$options[$key] = get_option( 'admin_email' );
					}
					break;

				case 'email_threshold':
					$options[$key] = absint( $options[$key] );
					break;

				case 'exception_fields':
				case 'html_fields':
				case 'json_fields':
					if ( ! is_string( $options[$key] ) ) {
						continue;
					}

					$options[$key] = str_replace( array( "\r\n", "\n", "\r" ), "\n", $options[$key] );
					$options[$key] = explode( "\n", $options[$key] );

					// Exception fields array must not contain an empty string
					// otherwise all fields will be excepted
					foreach ( $options[$key] as $k => $v ) {
						if ( strlen( $options[$key][$k] ) == 0 ) {
							unset( $options[$key][$k] );
						}
					}
			}
		}

		// Warnings
		$options['warning_wp_admin']  = isset( $input['warning_wp_admin'] ) ? 1 : 0;
		$options['warning_threshold'] = absint( $input['warning_threshold'] );

		// Checkboxes
		$options['email_notifications']      = isset( $input['email_notifications'] ) ? 1 : 0;
		$options['enable_admin']             = isset( $input['enable_admin'] ) ? 1 : 0;
		$options['enable_intrusion_logs']    = isset( $input['enable_intrusion_logs'] ) ? 1 : 0;
		$options['enable_automatic_updates'] = isset( $input['enable_automatic_updates'] ) ? 1 : 0;

		// Clear the update cache
		if ( 0 == $options['enable_automatic_updates'] ) {
			delete_site_transient( 'mscr_update' );
		}

		// Banning
		$options['ban_enabled'] = isset( $input['ban_enabled'] ) ? 1 : 0;
		$options['ban_threshold'] = absint( $input['ban_threshold'] );
		$options['attack_repeat_limit'] = absint( $input['attack_repeat_limit'] );
		$options['ban_time'] = absint( $input['ban_time'] );

		return $options;
	}

	/**
	 * Display options page
	 *
	 * @return void
	 */
	public function options() {
		$options = get_option( 'mscr_options' );
		$default_options = Mute_Screamer::default_options();

		// Make sure we have all the options
		$options = array_merge( $default_options, $options );

		// Prep exception data
		$options['exception_fields'] = implode( "\r\n", $options['exception_fields'] );
		$options['html_fields'] = implode( "\r\n", $options['html_fields'] );
		$options['json_fields'] = implode( "\r\n", $options['json_fields'] );

		// Apply textarea escaping, backwards compat for WordPress 3.0
		if ( function_exists( 'esc_textarea' ) ) {
			$options['exception_fields'] = esc_textarea( $options['exception_fields'] );
			$options['html_fields'] = esc_textarea( $options['html_fields'] );
			$options['json_fields'] = esc_textarea( $options['json_fields'] );
		} else {
			$options['exception_fields'] = esc_html( $options['exception_fields'] );
			$options['html_fields'] = esc_html( $options['html_fields'] );
			$options['json_fields'] = esc_html( $options['json_fields'] );
		}

		MSCR_Utils::view( 'admin_options', $options );
	}
}
