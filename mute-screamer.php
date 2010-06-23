<?php  if ( !defined('ABSPATH') ) exit('No direct script access allowed');
/*
Plugin Name: Mute Screamer
Plugin URI: http://github.com/ampt/mute-screamer/
Description: <a href="http://phpids.org/">PHPIDS</a> for Wordpress.
Author: ampt
Version: 0.1
Author URI: http://notfornoone.com/
*/

if( !version_compare(PHP_VERSION, '5.1.6', '>=') ) {
	exit('Mute Screamer requires PHP 5.1.6 or higher to run. You are currently running PHP ' . PHP_VERSION . '.');
}

global $wp_version;
if( !version_compare($wp_version, '2.9', '>=') ) {
	exit('Mute Screamer requires Wordpress 2.9.x or higher to run. You are currently running Wordpress ' . $wp_version . '.');
}

/**
 * Mute Screamer
 */
if( !class_exists('Mute_screamer')) {
	define( 'MSCR_PATH', dirname(__FILE__) );
	set_include_path( get_include_path() . PATH_SEPARATOR . MSCR_PATH . '/lib' );
	require_once 'IDS/Init.php';
	require_once 'IDS/Log/Composite.php';
	require_once 'IDS/Log/Database.php';

	class Mute_screamer {
		const INTRUSIONS_TABLE = 'mscr_intrusions';
		private $options = array();


		/**
		 * Constructor
		 *
		 * Initialise Mute Screamer and run PHPIDS
		 *
		 * @return	object
		 */
		public function __construct() {
			$this->options = get_option( 'mscr_options' );
			$this->run();
		}


		/**
		 * Initialise PHPIDS
		 *
		 * @return	object
		 */
		public function init_ids() {
			$ids = IDS_Init::init( MSCR_PATH . '/lib/IDS/Config/Config.ini.php' );

			$ids->config['General']['use_base_path'] = FALSE;
			$ids->config['General']['filter_path'] = MSCR_PATH . '/lib/IDS/default_filter.xml';
			$ids->config['General']['tmp_path'] = $this->upload_path();

			$ids->config['Caching']['caching'] = 'none';

			$ids->config['Logging']['wrapper'] = 'mysql:host=' . DB_HOST . ';port=3306;dbname=' . DB_NAME;
			$ids->config['Logging']['user'] = DB_USER;
			$ids->config['Logging']['password'] = DB_PASSWORD;
			$ids->config['Logging']['table'] = Mute_screamer::INTRUSIONS_TABLE;

			return $ids;
		}


		/**
		 * Run PHPIDS
		 */
		public function run() {
		    $request = array(
		        'REQUEST' => $_REQUEST,
		        'GET' => $_GET,
		        'POST' => $_POST,
		        'COOKIE' => $_COOKIE
		    );

			// Initialise IDS
			$init = $this->init_ids();

			// Run IDS
			$ids = new IDS_Monitor($request, $init);
			$result = $ids->run();

			if( !$result->isEmpty() ) {
				$compositeLog = new IDS_Log_Composite();
				$compositeLog->addLogger(IDS_Log_Database::getInstance($init));
				$compositeLog->execute($result);
			}
		}


		/**
		 * Get the current upload path
		 *
		 * @return	string
		 */
		private function upload_path() {
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


		/**
		 * Get the Mute Screamer instance
		 *
		 * @return	object
		 */
		public static function self() {
			return $this;
		}


		/**
		 * Setup options, database table on activation
		 *
		 * @return	void
		 */
		public static function activate() {
			global $wpdb;

			// Default options
			$options = array(
				'notifications' => FALSE,
				'email' => get_option('admin_email'),
				'mode' => 'production',
				'ban_time' => 1800
			);

			// Attack attempts database table
			$wpdb->query("
				CREATE TABLE IF NOT EXISTS `" . Mute_screamer::INTRUSIONS_TABLE . "` (
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

			add_option( 'mscr_options', $options );
		}


		/**
		 * Remove options on deactivation
		 *
		 * @return	void
		 */
		public static function deactivate() {
			delete_option( 'mscr_options' );
		}


		/**
		 * Remove database tables on uninstall
		 *
		 * @return	void
		 */
		public static function uninstall() {
			global $wpdb;
			$wpdb->query( "DROP TABLE IF EXISTS `" . Mute_screamer::INTRUSIONS_TABLE . "`" );
		}
	}
}

if( !defined('WP_UNINSTALL_PLUGIN') ) {
	register_activation_hook( __FILE__, 'Mute_screamer::activate' );
	register_deactivation_hook( __FILE__, 'Mute_screamer::deactivate' );
	register_uninstall_hook( __FILE__, 'Mute_screamer::uninstall' );

	add_action( 'plugins_loaded', create_function('','new Mute_screamer();') );
}
