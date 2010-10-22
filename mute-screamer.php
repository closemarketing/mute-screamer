<?php  if ( !defined('ABSPATH') ) exit;
/*
Plugin Name: Mute Screamer
Plugin URI: http://wordpress.org/extend/plugins/mute-screamer/
Description: <a href="http://phpids.org/">PHPIDS</a> for Wordpress.
Author: ampt
Version: 0.32
Author URI: http://notfornoone.com/
*/

/*
 * Mute Screamer
 *
 * PHPIDS for Wordpress
 *
 * Copyright (c) 2010 Luke Gallagher
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

if( !version_compare(PHP_VERSION, '5.2', '>=') ) {
	exit('Mute Screamer requires PHP 5.2 or higher to run. You are currently running PHP ' . PHP_VERSION . '.');
}

global $wp_version;
if( !version_compare($wp_version, '3.0', '>=') ) {
	exit('Mute Screamer requires Wordpress 3.0 or higher to run. You are currently running Wordpress ' . $wp_version . '.');
}

if( !class_exists('Mute_screamer')) {
	define( 'MSCR_PATH', dirname(__FILE__) );
	set_include_path( get_include_path() . PATH_SEPARATOR . MSCR_PATH . '/lib' );
	require_once 'mscr/utils.php';
	require_once 'mscr/log_database.php';
	require_once 'IDS/Init.php';
	require_once 'IDS/Log/Composite.php';

	// PHPIDS requires a writable folder
	if( !is_writable(MSCR_Utils::upload_path()) ) {
		exit("Mute Screamer requires that your uploads folder ".MSCR_Utils::upload_path()." is writable.");
	}

	/**
	 * Mute Screamer
	 */
	class Mute_screamer {
		const INTRUSIONS_TABLE 			= 'mscr_intrusions';
		private static $instance 		= NULL;
		private $email 					= '';
		private $email_notifications 	= '';
		private $email_threshold 		= '';
		private $exception_fields 		= array();
		private $html_fields 			= array();
		private $json_fields 			= array();
		private $new_intrusions_count 	= 0;
		private $result 				= FALSE;

		/**
		 * Constructor
		 *
		 * Initialise Mute Screamer and run PHPIDS
		 *
		 * @return	object
		 */
		public function __construct() {
			self::$instance = $this;

			$this->init();
			$this->run();
		}


		/**
		 * Initialise Mute Screamer
		 *
		 * @return	void
		 */
		private function init() {
			$this->init_options();

			// Are we in the WP Admin?
			if( is_admin() ) {
				require_once 'mscr/Update.php';
				require_once 'mscr_admin.php';
				new Mscr_admin();
			}
		}


		/**
		 * Initialise PHPIDS
		 *
		 * @return	object
		 */
		public function init_ids() {
			$config['General']['filter_type'] = 'xml';
			$config['General']['base_path'] = MSCR_PATH . '/lib/IDS/';
			$config['General']['use_base_path'] = FALSE;
			$config['General']['filter_path'] = MSCR_PATH . '/lib/IDS/default_filter.xml';
			$config['General']['tmp_path'] = MSCR_Utils::upload_path();
			$config['General']['scan_keys'] = FALSE;

			$config['General']['HTML_Purifier_Path'] = 'vendors/htmlpurifier/HTMLPurifier.auto.php';
			$config['General']['HTML_Purifier_Cache'] = MSCR_Utils::upload_path();

			$config['Caching']['caching'] = 'none';

			// Mark fields that shouldn't be monitored
			$config['General']['exceptions'] = $this->exception_fields ? $this->exception_fields : FALSE;

			// Mark fields that contain HTML
			$config['General']['html'] = $this->html_fields ? $this->html_fields : FALSE;

			// Mark fields that have JSON data
			$config['General']['json'] = $this->json_fields ? $this->json_fields : FALSE;

			$ids = IDS_Init::init();
			$ids->setConfig( $config, TRUE );

			return $ids;
		}


		/**
		 * Run PHPIDS
		 */
		public function run() {
			// Are we running in the WordPress admin?
			if( is_admin() AND $this->enable_admin == FALSE ) {
				return;
			}

		    $request = array(
		        'REQUEST' => $_REQUEST,
		        'GET' => $_GET,
		        'POST' => $_POST,
		        'COOKIE' => $_COOKIE
		    );

			$init = $this->init_ids();
			$ids = new IDS_Monitor($request, $init);
			$this->result = $ids->run();

			if( !$this->result->isEmpty() ) {
				$this->update_intrusion_count();
				$compositeLog = new IDS_Log_Composite();
				$compositeLog->addLogger( new mscr_log_database() );
				$compositeLog->execute($this->result);

				if( $this->email_notifications ) {
					$this->email();
				}

				$this->warning_page();
			}
		}


		/**
		 * Send an alert email if the impact is over the email threshold
		 *
		 * @return	void
		 */
		private function email() {
			if( $this->result->getImpact() < $this->email_threshold ) {
				return;
			}

			$data['blogname'] = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
			$data['result'] = $this->result;
			$data['ip_address'] = MSCR_Utils::ip_address();

			$message = MSCR_Utils::view('alert_email', $data, TRUE);
			$subject = sprintf(__('[%s] Mute Screamer IDS Alert'), $data['blogname']);

			wp_mail( $this->email, $subject, $message );
		}


		/**
		 * Display a warning page if the impact is over the warning threshold
		 * If the request was in WP Admin logout the current user.
		 *
		 * @return	void
		 */
		private function warning_page() {
			if( $this->result->getImpact() < $this->warning_threshold ) {
				return;
			}

			// End user's session if they are in the wp admin
			if( is_admin() AND $this->warning_wp_admin == TRUE ) {
				wp_logout();
				wp_safe_redirect( '/wp-login.php?loggedout=true' );
				exit;
			}

			// Load custom error page
			add_action( 'template_redirect', array($this, 'load_template') );
		}


		/**
		 * Show a 500 error page if the template exists.
		 * Otherwise show a 404 error or redirect to homepage.
		 *
		 * @return	void
		 */
		public function load_template( $template = '' ) {
			$templates[] = "500.php";
			$templates[] = "404.php";
			$templates[] = "index.php";

			$template = locate_template( $templates );

			// Did we find a template? If not fail silently...
			if( '' == $template )
				exit;

			if( '404.php' == basename($template) ) {
				status_header( 404 );
				nocache_headers();
			} else if( '500.php' == basename($template) ) {
				status_header( 500 );
				nocache_headers();
			} else {
				wp_redirect( get_bloginfo('url') );
				exit;
			}

			load_template( $template );
			exit;
		}


		/**
		 * Get the Mute Screamer instance
		 *
		 * @return	object
		 */
		public static function instance() {
			return self::$instance;
		}


		/**
		 * Retrieve options
		 *
		 * @param	string
		 * @return	mixed
		 */
		public function get_option( $key = '' ) {
			return isset( $this->$key ) ? $this->$key : FALSE;
		}


		/**
		 * Update options
		 *
		 * @param	string
		 * @param	mixed
		 * @return	void
		 */
		public function set_option( $key = '', $val = '' ) {
			$options = get_option( 'mscr_options' );
			if( ! isset( $options[$key] ))
				return;

			$options[$key] = $val;
			update_option( 'mscr_options', $options );
			$this->init_options();
		}


		/**
		 * Initialse options
		 *
		 * @return	void
		 */
		private function init_options() {
			$options = get_option( 'mscr_options' );
			$default_options = array(
				'db_version',
				'email',
				'email_notifications',
				'email_threshold',
				'exception_fields',
				'html_fields',
				'json_fields',
				'new_intrusions_count',
				'enable_admin',
				'warning_threshold',
				'warning_wp_admin'
			);

			foreach( $default_options as $key ) {
				$this->$key = isset( $options[$key] ) ? $options[$key] : FALSE;
			}
		}


		/**
		 * Update intrusion count for menu
		 *
		 * @return	void
		 */
		private function update_intrusion_count() {
			$new_count = $this->new_intrusions_count + count($this->result->getIterator());
			$this->set_option( 'new_intrusions_count', $new_count );
		}


		/**
		 * Setup options, database table on activation
		 *
		 * @return	void
		 */
		public static function activate() {
			global $wpdb;

			$default_exceptions = array(
				'REQUEST.permalink_structure',
				'POST.permalink_structure',
				'REQUEST.selection',
				'POST.selection',
				'REQUEST.content',
				'POST.content',
				'REQUEST.__utmz',
				'COOKIE.__utmz',
				'REQUEST.s_pers',
				'COOKIE.s_pers'
			);

			// Default options
			$options = array(
				'db_version' => 1,
				'email_threshold' => 10,
				'email_notifications' => FALSE,
				'email' => get_option('admin_email'),
				'exception_fields' => $default_exceptions,
				'html_fields' => array(),
				'json_fields' => array(),
				'new_intrusions_count' => 0,
				'enable_admin' => 1,
				'warning_threshold' => 40,
				'warning_wp_admin' => 0
			);

			// Attack attempts database table
			$wpdb->query("
				CREATE TABLE IF NOT EXISTS `" . self::INTRUSIONS_TABLE . "` (
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

			// Do previous options exist? Merge them, this way we keep existing options
			// and if an update adds new options they get added too.
			$prev_options = get_option( 'mscr_options' );
			if( is_array($prev_options) ) {
				$options = array_merge( $options, $prev_options );
			}

			update_option( 'mscr_options', $options );
		}


		/**
		 * Clean up on deactivation
		 *
		 * @return	void
		 */
		public static function deactivate() {
			global $wpdb;
			$wpdb->query( "DELETE FROM `{$wpdb->usermeta}` WHERE meta_key = 'mscr_intrusions_per_page'" );
		}


		/**
		 * Clean up database on uninstall
		 *
		 * @return	void
		 */
		public static function uninstall() {
			global $wpdb;

			// Remove Mute Screamer options
			delete_option( 'mscr_options' );

			// Remove intrustions table
			$wpdb->query( "DROP TABLE IF EXISTS `" . self::INTRUSIONS_TABLE . "`" );
		}
	}

	// Register activation, deactivation and uninstall hooks,
	// run Mute Screamer on init
	if( !defined('WP_UNINSTALL_PLUGIN') ) {
		register_activation_hook( __FILE__, 'Mute_screamer::activate' );
		register_deactivation_hook( __FILE__, 'Mute_screamer::deactivate' );
		register_uninstall_hook( __FILE__, 'Mute_screamer::uninstall' );

		add_action( 'init', create_function('','new Mute_screamer();') );
	}
}
