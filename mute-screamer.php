<?php  if ( ! defined( 'ABSPATH' ) ) exit;
/*
Plugin Name: Mute Screamer
Plugin URI: https://github.com/ampt/mute-screamer
Description: <a href="http://phpids.org/">PHPIDS</a> for Wordpress.
Author: ampt
Version: 1.0.6
Author URI: http://notfornoone.com/
*/

/*
 * Mute Screamer
 *
 * PHPIDS for Wordpress
 *
 * Copyright (c) 2011 Luke Gallagher
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

if ( ! class_exists( 'Mute_Screamer' ) AND version_compare( PHP_VERSION, '5.2', '>=' ) ) :

define( 'MSCR_PATH', dirname( __FILE__ ) );
set_include_path( get_include_path() . PATH_SEPARATOR . MSCR_PATH . '/libraries' );

require_once 'mscr/Utils.php';
require_once 'mscr/Log_Database.php';
require_once 'mscr/functions.php';
require_once 'IDS/Init.php';
require_once 'IDS/Log/Composite.php';

/**
 * Mute Screamer
 */
class Mute_Screamer {

	const INTRUSIONS_TABLE	= 'mscr_intrusions';
	const VERSION			= '1.0.6';
	const DB_VERSION		= 2;
	const POST_TYPE			= 'mscr_ban';

	/**
	 * An instance of this class
	 *
	 * @var object
	 */
	private static $instance = null;

	/**
	 * Email address to send alerts to
	 *
	 * @var string
	 */
	private $email = '';

	/**
	 * Email notifications flag
	 *
	 * @var boolean
	 */
	private $email_notifications = false;

	/**
	 * Email notifications threshold
	 *
	 * @var int
	 */
	private $email_threshold = 0;

	/**
	 * Input fields to be exlcuded from PHPIDS
	 *
	 * @var array
	 */
	private $exception_fields = array();

	/**
	 * Input fields to be treated as HTML
	 *
	 * @var array
	 */
	private $html_fields = array();

	/**
	 * Input fields to be treated as JSON data
	 *
	 * @var array
	 */
	private $json_fields = array();

	/**
	 * New intrusion count
	 *
	 * @var int
	 */
	private $new_intrusions_count = 0;

	/**
	 * Enable PHPIDS in the WordPress admin
	 *
	 * @var int
	 */
	private $enable_admin = 1;

	/**
	 * Impact for a warning page to be shown
	 *
	 * @var int
	 */
	private $warning_threshold = 40;

	/**
	 * Log user out of WordPress admin as a warning
	 *
	 * @var int
	 */
	private $warning_wp_admin = 0;

	/**
	 * Ban clients
	 *
	 * @var int
	 */
	private $ban_enabled = 0;

	/**
	 * Impact for a ban to be applied
	 *
	 * @var int
	 */
	private $ban_threshold = 70;

	/**
	 * Attack repeat limit
	 *
	 * @var int
	 */
	private $attack_repeat_limit = 5;

	/**
	 * Time in seconds a user is banned for.
	 *
	 * @var int
	 */
	private $ban_time = 300;

	/**
	 * Enable logging of intrusion attempts
	 *
	 * @var int
	 */
	private $enable_intrusion_logs = 1;

	/**
	 * PHPIDS result
	 *
	 * @var object
	 */
	private $result = null;

	/**
	 * Is the current request a banned request?
	 *
	 * @var boolean
	 */
	public $is_ban = false;

	/**
	 * Constructor
	 *
	 * Initialise Mute Screamer and run PHPIDS
	 *
	 * @return object
	 */
	public function __construct() {
		// Require 3.0.
		if ( ! function_exists( '__return_false' ) )
			return;

		if ( is_multisite() ) {
			add_action( 'network_admin_notices', 'MSCR_Utils::ms_notice' );
			return;
		}

		// PHPIDS requires a writable folder
		if ( ! is_writable( MSCR_Utils::upload_path() ) ) {
			add_action( 'admin_notices', 'MSCR_Utils::writable_notice' );
			return;
		}

		// Display updates in admin bar, run after wp_admin_bar_updates_menu
		add_action( 'admin_bar_menu', array( $this, 'action_admin_bar_menu' ), 100 );

		self::$instance = $this;
		$this->init();
		$this->run();

		// Process wp-login.php requests
		if ( MSCR_Utils::is_wp_login() ) {
			do_action( 'mscr_wp_login' );
		}
	}

	/**
	 * Initialise Mute Screamer
	 *
	 * @return void
	 */
	private function init() {
		self::db_table();
		$this->init_options();

		// Update db table reference when switching blogs
		add_action( 'switch_blog', 'Mute_Screamer::db_table' );

		// Load textdomain
		load_plugin_textdomain( 'mute-screamer', false, dirname( plugin_basename( __FILE__ ) ).'/languages' );

		// Add ban post type, to track banned users
		$args = array(
			'public' => false,
		);
		register_post_type( self::POST_TYPE, $args );

		// Remove expired user bans
		$this->delete_expired_bans();

		// Is this a banned user?
		$this->banned_user();

		// Are we in the WP Admin?
		if ( is_admin() ) {
			if ( $this->db_version < self::DB_VERSION )
				$this->upgrade();

			require_once 'mscr/Update.php';
			require_once 'mscr_admin.php';
			new MSCR_Admin();
		}
	}

	/**
	 * Initialise PHPIDS
	 *
	 * @return object
	 */
	private function init_ids() {
		$config['General']['filter_type']   = 'xml';
		$config['General']['base_path']     = MSCR_PATH . '/libraries/IDS/';
		$config['General']['use_base_path'] = false;
		$config['General']['filter_path']   = MSCR_PATH . '/libraries/IDS/default_filter.xml';
		$config['General']['tmp_path']      = MSCR_Utils::upload_path();
		$config['General']['scan_keys']     = false;

		$config['General']['HTML_Purifier_Path']  = 'vendors/htmlpurifier/HTMLPurifier.auto.php';
		$config['General']['HTML_Purifier_Cache'] = MSCR_Utils::upload_path();

		$config['Caching']['caching'] = 'none';

		// Mark fields that shouldn't be monitored
		$config['General']['exceptions'] = $this->exception_fields ? $this->exception_fields : false;

		// Mark fields that contain HTML
		$config['General']['html'] = $this->html_fields ? $this->html_fields : false;

		// Mark fields that have JSON data
		$config['General']['json'] = $this->json_fields ? $this->json_fields : false;

		// Email logging
		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$subject = sprintf( __( '[%s] Mute Screamer IDS Alert', 'mute-screamer' ), $blogname );
		$config['Logging']['recipients']   = $this->email;
		$config['Logging']['subject']      = $subject;
		$config['Logging']['header']       = '';
		$config['Logging']['envelope']     = '';
		$config['Logging']['safemode']     = true;
		$config['Logging']['urlencode']    = true;
		$config['Logging']['allowed_rate'] = 15;

		$ids = IDS_Init::init();
		$ids->setConfig( $config, true );

		return $ids;
	}

	/**
	 * Run PHPIDS
	 *
	 * @return void
	 */
	public function run() {
		// Are we running in the WordPress admin?
		if ( is_admin() AND $this->enable_admin == false ) {
			return;
		}

	    $request = array(
	        'REQUEST' => $_REQUEST,
	        'GET' => $_GET,
	        'POST' => $_POST,
	        'COOKIE' => $_COOKIE,
	    );

		$init = $this->init_ids();
		$ids = new IDS_Monitor( $request, $init );
		$this->result = $ids->run();

		// Nothing more to do
		if ( $this->result->isEmpty() ) {
			return;
		}

		$compositeLog = new IDS_Log_Composite();

		// Are we logging the attempt?
		if ( $this->enable_intrusion_logs ) {
			$compositeLog->addLogger( new MSCR_Log_Database() );

			// Update new intrusion count, log the event
			$this->update_intrusion_count();
		}

		// Send alert email
		if ( $this->send_alert_email() ) {
			require_once 'mscr/Log_Email.php';
			$compositeLog->addLogger( MSCR_Log_Email::getInstance( $init, 'MSCR_Log_Email' ) );
		}

		$compositeLog->execute( $this->result );
		$this->ban_user();

		// Warning page runs last to allow for ban processing
		$this->warning_page();
	}

	/**
	 * We are sending alert emails if email notifications
	 * are turned on and the result impact is greater than the
	 * email threshold.
	 *
	 * @return boolean
	 */
	private function send_alert_email() {
		if ( ! $this->email_notifications ) {
			return false;
		}

		if ( $this->result->getImpact() < $this->email_threshold ) {
			return false;
		}

		return true;
	}

	/**
	 * Display a warning page if the impact is over the warning threshold
	 * If the request was in WP Admin logout the current user.
	 *
	 * @return void
	 */
	private function warning_page() {
		if ( $this->result->getImpact() < $this->warning_threshold ) {
			return;
		}

		// End user's session if they are in the wp admin
		if ( is_admin() AND $this->warning_wp_admin == true ) {
			wp_logout();
			wp_safe_redirect( '/wp-login.php?loggedout=true' );
			exit;
		}

		// Load custom error page
		add_action( 'template_redirect', array( $this, 'load_template' ) );

		// Catch wp-login.php requests
		add_action( 'mscr_wp_login', array( $this, 'load_template' ) );
	}

	/**
	 * Show a 500 error page if the template exists.
	 * Otherwise show a 404 error or redirect to homepage.
	 *
	 * @return void
	 */
	public function load_template( $template = '' ) {
		global $wp_query;

		if ( did_action( 'mscr_wp_login' ) ) {
			$this->admin_message();
		}

		$templates[] = '500.php';
		$templates[] = '404.php';
		$templates[] = 'index.php';

		$template = locate_template( $templates );

		// Did we find a template? If not fail silently...
		if ( '' == $template )
			exit;

		if ( '404.php' == basename( $template ) ) {
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
		} else if ( '500.php' == basename( $template ) ) {
			status_header( 500 );
			nocache_headers();
		} else if ( ! is_front_page() ) {
			wp_redirect( get_bloginfo( 'url' ) );
			exit;
		}

		load_template( $template );
		exit;
	}

	/**
	 * Ban user if the impact is over the ban threshold,
	 * if it is under the ban threshold record the attack
	 * for the repeat attack limit.
	 *
	 * @return void
	 */
	private function ban_user() {
		$data = array();

		// If the attack is under the ban threshold mark this
		// post as a repeat attack
		if ( $this->result->getImpact() < $this->ban_threshold ) {
			$data['post_excerpt'] = 'repeat_attack';
		}

		$data['post_type']    = self::POST_TYPE;
		$data['post_status']  = 'publish';
		$data['post_content'] = MSCR_Utils::ip_address();
		$data['post_title']   = MSCR_Utils::server( 'HTTP_USER_AGENT' );
		wp_insert_post( $data );
	}

	/**
	 * Remove user bans that have expired.
	 *
	 * @return void
	 */
	private function delete_expired_bans() {
		global $wpdb;

		$date = date( 'Y-m-d H:i:s', time() - $this->ban_time );
		$sql  = $wpdb->prepare( "DELETE FROM {$wpdb->posts} WHERE post_type = '".self::POST_TYPE."' AND post_date_gmt < '%s'", $date );
		$wpdb->query( $sql );
	}

	/**
	 * Number of attacks the user has made
	 *
	 * @return integer
	 */
	private function attack_count() {
		global $wpdb;

		$sql    = $wpdb->prepare( "SELECT COUNT(*) AS count FROM {$wpdb->posts} WHERE post_content = '%s' AND post_excerpt = 'repeat_attack'", MSCR_Utils::ip_address() );
		$result = $wpdb->get_row( $sql );
		return (int) $result->count;
	}

	/**
	 * Display an error page for banned users
	 *
	 * @return void
	 */
	private function banned_user() {
		global $wpdb;

		// Is banning enabled?
		if ( ! $this->ban_enabled ) {
			return;
		}

		$sql    = $wpdb->prepare( "SELECT post_type, post_content, post_title, post_excerpt FROM {$wpdb->posts} WHERE post_type = '".self::POST_TYPE."' AND post_content = '%s' AND post_excerpt <> 'repeat_attack'", MSCR_Utils::ip_address() );
		$result = $wpdb->get_row( $sql );

		// If there is no result and the user is under the repeat limit, we're good
		if ( ! $result AND $this->attack_count() < $this->attack_repeat_limit ) {
			return;
		}

		// This is a ban request
		$this->is_ban = true;

		// Admin notice
		if ( is_admin() ) {
			$this->admin_message();
		}

		// Load warning template
		add_action( 'template_redirect', array( $this, 'load_template' ) );

		// Catch wp-login.php requests
		add_action( 'mscr_wp_login', array( $this, 'load_template' ) );
	}

	/**
	 * Display admin warning message for a ban in the wp-admin
	 * and for warning on the wp-login page.
	 *
	 * @return void
	 */
	private function admin_message() {
		$filter  = 'mscr_admin_warn_message';
		$message = __( 'There was an error with the page you requested.', 'mute-screamer' );

		if ( $this->is_ban ) {
			$filter  = 'mscr_admin_ban_message';
			$message = __( 'There was a problem processing your request.', 'mute-screamer' );
		}

		$message = apply_filters( $filter, $message );
		wp_die( $message );
	}

	/**
	 * Get the Mute Screamer instance
	 *
	 * @return object
	 */
	public static function instance() {
		return self::$instance;
	}

	/**
	 * Retrieve options
	 *
	 * @param string
	 * @return mixed
	 */
	public function get_option( $key = '' ) {
		return isset( $this->$key ) ? $this->$key : false;
	}

	/**
	 * Update options
	 *
	 * @param string
	 * @param mixed
	 * @return void
	 */
	public function set_option( $key = '', $val = '' ) {
		// Bail if the key to be set does not exist in defaults
		if ( ! array_key_exists( $key, self::default_options() ) )
			return;

		$options = get_option( 'mscr_options' );
		$options[$key] = $val;
		update_option( 'mscr_options', $options );
		$this->$key = $val;
	}

	/**
	 * Initialse options
	 *
	 * @return void
	 */
	private function init_options() {
		$options = get_option( 'mscr_options' );
		$options['db_version'] = isset( $options['db_version'] ) ? $options['db_version'] : 0;
		$default_options = self::default_options();

		// Fallback to default options if the options don't exist in
		// the database (kind of like a soft upgrade).
		// Automatic plugin updates don't call register_activation_hook.
		foreach ( $default_options as $key => $val ) {
			$this->$key = isset( $options[$key] ) ? $options[$key] : $val;
		}
	}

	/**
	 * Update intrusion count for menu
	 *
	 * @return void
	 */
	private function update_intrusion_count() {
		$new_count = $this->new_intrusions_count + count( $this->result->getIterator() );
		$this->set_option( 'new_intrusions_count', $new_count );
	}

	/**
	 * Modify admin bar update count when there are Mute Screamer updates available
	 *
	 * @return void
	 */
	public function action_admin_bar_menu()	{
		global $wp_admin_bar;

		$updates = get_site_transient( 'mscr_update' );
		if ( $updates === false OR empty( $updates['updates'] ) ) {
			return;
		}

		$mscr_count = count( $updates['updates'] );
		$mscr_title = sprintf( _n( '%d Mute Screamer Update', '%d Mute Screamer Updates', $mscr_count, 'mute-screamer' ), $mscr_count );

		// WordPress 3.3
		if ( function_exists( 'wp_allowed_protocols' ) ) {
			$this->wp_admin_bar_updates_menu( $wp_admin_bar, $mscr_count, $mscr_title );
			return;
		}

		// WordPress 3.1, 3.2
		// Other WP updates, modify existing menu
		if ( isset( $wp_admin_bar->menu->updates ) ) {
			// <span title='1 Plugin Update'>Updates <span id='ab-updates' class='update-count'>1</span></span>
			$title = $wp_admin_bar->menu->updates['title'];

			// Get the existing title attribute
			preg_match( "/title='(.+?)'/", $title, $matches );
			$link_title  = isset( $matches[1] ) ? $matches[1] : '';
			$link_title .= ', '.esc_attr( $mscr_title );

			// Get the existing update count
			preg_match( '/<span\b[^>]*>(\d+)<\/span>/', $title, $matches );
			$update_count = isset( $matches[1] ) ? $matches[1] : 0;

			$update_count += $mscr_count;

			$update_title  = "<span title='$link_title'>";
			$update_title .= sprintf( __( 'Updates %s', 'mute-screamer' ), "<span id='ab-updates' class='update-count'>" . number_format_i18n( $update_count ) . '</span>' );
			$update_title .= '</span>';

			$wp_admin_bar->menu->updates['title'] = $update_title;
			return;
		}

		// Add update menu
		$update_title  = "<span title='".esc_attr( $mscr_title )."'>";
		$update_title .= sprintf( __( 'Updates %s', 'mute-screamer' ), "<span id='ab-updates' class='update-count'>" . number_format_i18n( $mscr_count ) . '</span>' );
		$update_title .= '</span>';
		$wp_admin_bar->add_menu( array( 'id' => 'updates', 'title' => $update_title, 'href' => network_admin_url( 'update-core.php' ) ) );
	}

	/**
	 * Display admin bar updates for WordPress 3.3
	 *
	 * @param WP_Admin_Bar instance
	 * @param integer $count
	 * @param string $title
	 * @return void
	 */
	private function wp_admin_bar_updates_menu( $wp_admin_bar, $count = 0, $title = '' ) {
		if ( ! $count OR ! $title )
			return;

		$update_data = wp_get_update_data();

		$update_data['counts']['total'] += $count;

		if ( ! $update_data['title'] ) {
			$update_data['title'] = $title;
		} else {
			$update_data['title'] .= ", {$title}";
		}

		$update_title = '<span class="ab-icon"></span><span class="ab-label">' . number_format_i18n( $update_data['counts']['total'] ) . '</span>';

		$update_node = $wp_admin_bar->get_node( 'updates' );

		// Does the update menu already exist?
		if ( ! $update_node ) {
			$wp_admin_bar->add_menu( array(
				'id'    => 'updates',
				'title' => $update_title,
				'href'  => network_admin_url( 'update-core.php' ),
				'meta'  => array(
					'title' => $update_data['title'],
				),
			) );

			return;
		}

		// Update existing menu
		$update_node->title = $update_title;
		$update_node->meta['title'] = $update_data['title'];

		$wp_admin_bar->add_menu( $update_node );
	}

	/**
	 * Default options
	 *
	 * @return array
	 */
	public static function default_options() {
		$default_exceptions = array(
			'REQUEST.comment',
			'POST.comment',
			'REQUEST.permalink_structure',
			'POST.permalink_structure',
			'REQUEST.selection',
			'POST.selection',
			'REQUEST.content',
			'POST.content',
			'REQUEST.__utmz',
			'COOKIE.__utmz',
			'REQUEST.s_pers',
			'COOKIE.s_pers',
			'REQUEST.user_pass',
			'POST.user_pass',
			'REQUEST.pass1',
			'POST.pass1',
			'REQUEST.pass2',
			'POST.pass2',
			'REQUEST.password',
			'POST.password',
		);

		return array(
			'db_version' => self::DB_VERSION,
			'email_threshold' => 20,
			'email_notifications' => false,
			'email' => get_option( 'admin_email' ),
			'exception_fields' => $default_exceptions,
			'html_fields' => array(),
			'json_fields' => array(),
			'new_intrusions_count' => 0,
			'enable_admin' => 1,
			'warning_threshold' => 40,
			'warning_wp_admin' => 0,
			'ban_enabled' => 0,
			'ban_threshold' => 70,
			'attack_repeat_limit' => 5,
			'ban_time' => 300,
			'enable_intrusion_logs' => 1,
			'enable_automatic_updates' => 1,
		);
	}

	/**
	 * Upgrade database
	 *
	 * @return void
	 */
	private function upgrade() {
		global $wpdb;

		if ( $this->db_version < 2 ) {
			// Prefix intrusions table
			$wpdb->query( 'DROP TABLE IF EXISTS `' . $wpdb->mscr_intrusions . '`' );
			$wpdb->query( 'ALTER TABLE ' . self::INTRUSIONS_TABLE . " RENAME TO {$wpdb->mscr_intrusions}" );

			// Take a punt and change the intrusion dates to what we *think* GMT time is
			$time_difference = get_option( 'gmt_offset' );

			$server_time = time() + date( 'Z' );
			$blog_time   = $server_time + $time_difference * 3600;
			$gmt_time    = time();

			$diff_gmt_server  = ($gmt_time - $server_time) / 3600;
			$diff_blog_server = ($blog_time - $server_time) / 3600;
			$diff_gmt_blog    = $diff_gmt_server - $diff_blog_server;
			$gmt_offset       = -$diff_gmt_blog;

			// Add or substract time to all dates, to get GMT dates
			$add_hours   = intval( $diff_gmt_blog );
			$add_minutes = intval( 60 * ($diff_gmt_blog - $add_hours) );
			$wpdb->query( "UPDATE $wpdb->mscr_intrusions SET created = DATE_ADD(created, INTERVAL '$add_hours:$add_minutes' HOUR_MINUTE)" );
		}

		// Update db version
		$this->set_option( 'db_version', self::DB_VERSION );
	}

	/**
	 * Setup options, database table on activation
	 *
	 * @return void
	 */
	public static function activate() {
		if ( is_multisite() ) {
			return;
		}

		global $wpdb;
		self::db_table();

		// Default options
		$options = self::default_options();

		// Attack attempts database table
		$wpdb->query(
			"CREATE TABLE IF NOT EXISTS `" . $wpdb->mscr_intrusions . "` (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `name` varchar(128) NOT NULL,
			  `value` text NOT NULL,
			  `page` varchar(255) NOT NULL,
			  `tags` varchar(50) NOT NULL,
			  `ip` varchar(16) NOT NULL DEFAULT '0',
			  `impact` int(11) unsigned NOT NULL,
		      `origin` varchar(16) NOT null,
			  `created` datetime NOT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;"
		);

		$prev_options = get_option( 'mscr_options' );

		// If we didn't previously have a db_version don't add one,
		// we want the upgrade routine to handle this
		if ( is_array( $prev_options ) AND ! isset( $prev_options['db_version'] ) ) {
			unset( $options['db_version'] );
		}

		// Do previous options exist? Merge them, this way we keep existing options
		// and if an update adds new options they get added too.
		if ( is_array( $prev_options ) ) {
			$options = array_merge( $options, $prev_options );
		}

		update_option( 'mscr_options', $options );
	}

	/**
	 * Clean up on deactivation
	 *
	 * @return void
	 */
	public static function deactivate() {
		global $wpdb;
		$wpdb->query( "DELETE FROM `{$wpdb->usermeta}` WHERE meta_key = 'mscr_intrusions_per_page'" );
		$wpdb->query( "DELETE FROM `{$wpdb->posts}` WHERE post_type = '".self::POST_TYPE."'" );
	}

	/**
	 * Clean up database on uninstall
	 *
	 * @return void
	 */
	public static function uninstall() {
		global $wpdb;
		self::db_table();

		// Remove Mute Screamer options
		delete_option( 'mscr_options' );

		// Remove intrusions table
		$wpdb->query( 'DROP TABLE IF EXISTS `' . $wpdb->mscr_intrusions . '`' );
	}

	/**
	 * Add database table references to wpdb
	 *
	 * @param string
	 * @return void
	 */
	public static function db_table() {
		global $wpdb;

		$table_name = self::INTRUSIONS_TABLE;
		$table = $wpdb->get_blog_prefix().$table_name;
		$wpdb->$table_name = $table;
	}

	/**
	 * Get URL path to the plugin directory
	 *
	 * @return string
	 */
	public static function plugin_url() {
		return plugin_dir_url( __FILE__ );
	}
}

// Register activation, deactivation and uninstall hooks,
// run Mute Screamer on init
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	register_activation_hook( __FILE__, 'Mute_Screamer::activate' );
	register_deactivation_hook( __FILE__, 'Mute_Screamer::deactivate' );
	register_uninstall_hook( __FILE__, 'Mute_Screamer::uninstall' );

	add_action( 'init', create_function( '','new Mute_Screamer();' ) );
}

endif;
