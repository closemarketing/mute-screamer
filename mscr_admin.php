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
			$per_page = (int) get_user_option( 'mscr_intrusions_per_page' );

			// Set default if user option does not exist
			if( !$per_page ) {
				$per_page = 20;
			}

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
		add_submenu_page( 'index.php', __('Mute Screamer Intrusions'), __('Intrusions'), 'activate_plugins', 'mscr_intrusions', array($this, 'intrusions') );
		add_submenu_page( 'plugins.php', __('Mute Screamer Configuration'), __('Mute Screamer'), 'activate_plugins', 'mscr_options', array($this, 'options') );
	}


	/**
	 * Display PHPIDS Intrusions
	 *
	 * @return	void
	 */
	public function intrusions()
	{
		global $wpdb;
		$intrusions = $wpdb->get_results("SELECT * FROM " . Mute_screamer::INTRUSIONS_TABLE . "  ORDER BY created DESC");

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

		Utils::view('admin_intrusions', $data);
	}


	/**
	 * Display options page
	 *
	 * @return	void
	 */
	public function options()
	{
	}
}
