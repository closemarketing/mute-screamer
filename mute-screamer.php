<?php  if ( !defined('ABSPATH') ) exit;
/*
Plugin Name: Mute Screamer
Plugin URI: http://github.com/ampt/mute-screamer/
Description: <a href="http://phpids.org/">PHPIDS</a> for Wordpress.
Author: ampt
Version: 0.1
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

if( !version_compare(PHP_VERSION, '5.1.6', '>=') ) {
	exit('Mute Screamer requires PHP 5.1.6 or higher to run. You are currently running PHP ' . PHP_VERSION . '.');
}

global $wp_version;
if( !version_compare($wp_version, '3.0', '>=') ) {
	exit('Mute Screamer requires Wordpress 3.0 or higher to run. You are currently running Wordpress ' . $wp_version . '.');
}

if( !class_exists('Mute_screamer')) {
	define( 'MSCR_PATH', dirname(__FILE__) );
	set_include_path( get_include_path() . PATH_SEPARATOR . MSCR_PATH . '/lib' );
	require_once 'mscr/utils.php';
	require_once 'IDS/Init.php';
	require_once 'IDS/Log/Composite.php';
	require_once 'IDS/Log/Database.php';

	// PHPIDS requires a writable folder even through we don't use it
	if( !is_writable(Utils::upload_path()) ) {
		exit("Mute Screamer requires that your uploads folder ".Utils::upload_path()." is writable.");
	}

	/**
	 * Mute Screamer
	 */
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
			$this->init();
			$this->run();
		}


		/**
		 * Initialise Mute Screamer
		 *
		 * @return	void
		 */
		private function init() {
			if( is_admin() ) {
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
			$ids = IDS_Init::init( MSCR_PATH . '/lib/IDS/Config/Config.ini.php' );

			$ids->config['General']['use_base_path'] = FALSE;
			$ids->config['General']['filter_path'] = MSCR_PATH . '/lib/IDS/default_filter.xml';
			$ids->config['General']['tmp_path'] = Utils::upload_path();

			$ids->config['Caching']['caching'] = 'none';

			$ids->config['Logging']['wrapper'] = 'mysql:host=' . DB_HOST . ';port=3306;dbname=' . DB_NAME;
			$ids->config['Logging']['user'] = DB_USER;
			$ids->config['Logging']['password'] = DB_PASSWORD;
			$ids->config['Logging']['table'] = self::INTRUSIONS_TABLE;

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

			add_option( 'mscr_options', $options );
		}


		/**
		 * Remove user meta on deactivation
		 *
		 * @return	void
		 */
		public static function deactivate() {
			global $wpdb;
			$wpdb->query("DELETE FROM `{$wpdb->usermeta}` WHERE meta_key = 'mscr_intrusions_per_page'");
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
}

if( !defined('WP_UNINSTALL_PLUGIN') ) {
	register_activation_hook( __FILE__, 'Mute_screamer::activate' );
	register_deactivation_hook( __FILE__, 'Mute_screamer::deactivate' );
	register_uninstall_hook( __FILE__, 'Mute_screamer::uninstall' );

	add_action( 'plugins_loaded', create_function('','new Mute_screamer();') );
}
