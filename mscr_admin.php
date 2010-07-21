<?php  if ( !defined('ABSPATH') ) exit;

/**
 * Mute Screamer admin class
 */
class Mscr_admin {
	private $page = FALSE;
	private $controller = FALSE;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_init', array($this, 'admin_init') );
		add_action( 'admin_menu', array($this, 'admin_menu') );
		add_filter( 'screen_settings', array($this, 'screen_settings'), 10, 2 );
		add_filter( 'set-screen-option', array($this, 'set_screen_option'), 10, 3 );
	}


	/**
	 * Admin init
	 *
	 * @return	void
	 */
	public function admin_init() {
		register_setting( 'mscr_options', 'mscr_options', array($this, 'options_validate') );
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
			$per_page = Utils::mscr_intrusions_per_page();

			$data['per_page'] = $per_page;
			$action = Utils::view('admin_intrusions_screen_options', $data, TRUE);
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
		add_dashboard_page( __('Mute Screamer Intrusions'), __('Intrusions'), 'activate_plugins', 'mscr_intrusions', array($this, 'intrusions') );
		add_options_page( __('Mute Screamer Configuration'), __('Mute Screamer'), 'activate_plugins', 'mscr_options', array($this, 'options') );
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
		$per_page = Utils::mscr_intrusions_per_page();
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
		$pagination = Utils::pagination($pagenum, $num_pages, $per_page, $total_intrusions);

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

		$data['intrusions'] = $intrusions;
		$data['style'] = '';
		$data['columns'] = $columns;
		$data['page'] = $_GET['page'];
		$data['pagination'] = $pagination;
		$data['intrusions_search'] = $search;
		$data['search_title'] = $search_title;

		Utils::view('admin_intrusions', $data);
	}


	/**
	 * Validate options
	 *
	 * @return	array
	 */
	public function options_validate( $input ) {
		$options = get_option( 'mscr_options' );

		foreach( array('email', 'email_threshold') as $key ) {
			if( isset($input[$key]) ) {
				$options[$key] = $input[$key];
			}

			switch($key) {
				case 'email':
					if( !is_email($options[$key]) ) {
						$options[$key] = get_option('admin_email');
					}
					break;

				case 'email_threshold':
					$options[$key] = absint($options[$key]);
			}
		}

		$options['email_notifications'] = isset($input['email_notifications']) ? 1 : 0;
		return $options;
	}


	/**
	 * Display options page
	 *
	 * @return	void
	 */
	public function options()
	{
		$options = get_option( 'mscr_options' );
		Utils::view('admin_options', $options);
	}
}