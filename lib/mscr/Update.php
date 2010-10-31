<?php  if ( ! defined('ABSPATH') ) exit;

/*
 * Mute Screamer update class
 *
 * Updates PHPIDS with the latest default_filter.xml
 * and Converter.php
 */
class MSCR_Update {
	public static $instance = NULL;
	public $rules_file = 'default_filter.xml';
	public $converter_file = 'Coverter.php';
	public $rules_rss_url = 'https://trac.php-ids.org/index.fcgi/log/trunk/lib/IDS/default_filter.xml?limit=5&format=rss';
	public $converter_rss_url = 'https://trac.php-ids.org/index.fcgi/log/trunk/lib/IDS/Converter.php?limit=5&format=rss';
	private $updates = array(); // TODO: rename to update_cache
	private $file = '';
	private $timeout = 3600;

	/**
	 * Constructor
	 *
	 * @return	void
	 */
	protected function __construct() {
		$this->updates = get_site_transient( 'mscr_update' );
	}

	/**
	 * Get the MSCR Update instance
	 *
	 * @return	object
	 */
	public static function instance() {
		if( ! self::$instance )
			self::$instance = new MSCR_Update;

		return self::$instance;
	}

	/**
	 * Check for updates to Converter.php and default_filter.xml
	 *
	 * @return	bool
	 */
	public function update_check() {
		/*
		 * Reference
		 * wp_version_check() in wp-includes
		 * wp-admin/update-core.php
		 * wp-admin/update.php
		 * wp-includes/update.php
		 * class-http.php
		 * http.php
		 */

		/*
		 1. does the sha1 differ from the local version?
		 2. fetch the latest rss entry, get revision number, get file revision
		 3. does the sha1 match the revision version of the file from rss?
		 4. display update notice, with link to changeset
		 */

		// Is it time to check for updates?
		if( $this->updates !== FALSE )
			return FALSE;

		// Initialise the update cache
		$this->updates['updates'] = array();

		// Suppress libxml parsing errors
		$libxml_use_errors = libxml_use_internal_errors( TRUE );

		foreach( array( 'default_filter.xml', 'Converter.php' ) as $file ) {
			$this->file = $file;

			// Fetch remote sha1
			$this->sha1_fetch();

			// Fetch RSS for latest revision
			$this->rss_fetch();

			// Did any remote requests fail?
			$responses = $this->updates['updates'][$file]->responses;
			if( $responses['sha1'] == '' OR $responses['rss'] == '' ) {
				$this->abort();
				return FALSE;
			}

			// Does the sha1 differ?
			if( ! $this->sha1_check() ) {
				// File doesn't need updating remove from update array
				unset( $this->updates['updates'][$file] );
				continue;
			}

			// Simple XML elements can't be serialized so cast them to strings
			$details = $this->updates['updates'][$this->file];
			$rss = simplexml_load_string($details->responses['rss']);
			$details->title = (string) $rss->channel->item->title;
			$details->revision = preg_replace('/Revision (\d+).+/si', '$1', $rss->channel->item->title);
			$details->date = (string) $rss->channel->item->pubDate;
			$details->revision_url = (string) $rss->channel->item->guid;
			$details->revision_file_url = "https://trac.php-ids.org/index.fcgi/export/{$details->revision}/trunk/lib/IDS/{$this->file}";

			// Did we parse the revision number correctly?
			if( ! ctype_digit( $details->revision ) ) {
				$this->abort();
				// wp_die( new WP_Error( 'revision_parse_failed', 'Mute Screamer could not parse the revision number.' ) );
				return FALSE;
			}
		}

		// Clear libxml errors
		libxml_clear_errors();

		// Restore libxml errors
		libxml_use_internal_errors( $libxml_use_errors );

		// TODO: Extra validation step
		// TODO: Check revision_file_url sha1 and compare to remote sha1
		// TODO: If the sha1's are the same then we can run the update

		set_site_transient( 'mscr_update', $this->updates, $this->timeout );
	}

	/**
	 * Fetch the remote sha1 and cache the result
	 *
	 * @return	void
	 */
	private function sha1_fetch() {
		// Fetch remote sha1
		$url = 'https://php-ids.org/hash.php?f='.$this->file;
		$response = $this->remote_get( $url );
		$this->updates['updates'][$this->file] = new stdClass;
		$this->updates['updates'][$this->file]->responses['sha1'] = $response['body'];
	}

	/**
	 * Fetch the latest rss revision and cache the result
	 *
	 * @return	void
	 */
	private function rss_fetch() {
		$url = "https://trac.php-ids.org/index.fcgi/log/trunk/lib/IDS/{$this->file}?limit=1&format=rss";
		$response = $this->remote_get( $url );
		$this->updates['updates'][$this->file]->responses['rss'] = $response['body'];
	}

	/**
	 * Check the sha1 to see if we need to update
	 *
	 * @return	bool	true if the sha1's are different
	 */
	private function sha1_check() {
		// Get the current sha1
		$local_file = MSCR_PATH."/lib/IDS/{$this->file}";

		if( ! file_exists( $local_file ) )
			return FALSE;

		$local_sha1 = sha1_file( $local_file );
		$remote_sha1 = $this->updates['updates'][$this->file]->responses['sha1'];

		if( $local_sha1 == $remote_sha1 )
			return FALSE;

		return TRUE;
	}

	/**
	 * A wrapper function to wp_remote_get. On error return
	 * an empty body so we can fail gracefully.
	 *
	 * @param	string
	 * @return	array
	 */
	private function remote_get( $url = '', $options = array() ) {
		$cache = get_site_transient( 'mscr_requests_cache' );

		// Is it in the cache?
		$hash = md5( $url );
		if( isset( $cache[$hash] ) )
			return $cache[$hash];

		// Default options
		if( empty( $options ) ) {
			$options = array( 'sslverify' => FALSE );
		}

		$response = wp_remote_get( $url, $options );

		if( is_wp_error( $response ) )
			return array( 'body' => '' );

		if( 200 != $response['response']['code'] )
			return array( 'body' => '' );

		if( ! is_array( $cache ) )
			$cache = array();

		$cache[$hash] = $response;
		set_site_transient( 'mscr_requests_cache', $cache, $this->timeout );

		return $response;
	}

	/**
	 * Abort the update process.
	 *
	 * @return	void
	 */
	private function abort() {
		// Set error flag and try again when the transient expires next
		$this->updates = array();
		$this->updates['updates'] = array();
		$this->updates['status'] = 'Failed';
		set_site_transient( 'mscr_update', $this->updates, $this->timeout );
	}

	/**
	 * A hook to use
	 */
	public function load_update_core() {}

	/**
	 * Display update notices on the update page
	 */
	public function list_mscr_updates() {
		if( empty( $this->updates['updates'] ) ) {
			echo '<h3>' . __( 'Mute Screamer' ) . '</h3>';
			echo '<p>' . __( 'Is up to date.' ) . '</p>';
			return;
		}

		// TODO: Fix revision number
		MSCR_Utils::view( 'admin_update', array( 'files' => $this->updates['updates'] ) );
	}

	/**
	 * Display diff of files to be upgraded
	 */
	public function do_upgrade_diff() {
		$diff_files = array();

		if ( ! current_user_can('update_plugins') )
			wp_die(__('You do not have sufficient permissions to update Mute Screamer for this site.'));

		check_admin_referer('upgrade-core');

		// TODO: Utils POST, GET, COOKIE
		// $files = MSRC_Utils::post( 'checked' );

		$files = array();
		if( isset( $_POST['checked'] ) ) {
			$files = (array) $_POST['checked'];
		}

		// Valid files to upgrade?
		foreach( $files as $file ) {
			if( ! isset( $this->updates['updates'][$file]))
				continue;

			// Get local file
			$local = MSCR_PATH.'/lib/IDS/'.$file;

			if( ! file_exists( $local ) ) {
				wp_die( new WP_Error( 'mscr_upgrade_file_missing', esc_html($file).' does not exist.' ) );
			}

			if( ! @is_readable( $local ) ) {
				wp_die( new WP_Error( 'mscr_upgrade_file_read_error', 'Can not read file '.esc_html($file).'.' ) );
			}

			$local = file_get_contents( $local );

			// Fetch remote file
			$remote = $this->remote_get( $this->updates['updates'][$file]->revision_file_url );

			if( $remote['body'] == '' )
				wp_die( new WP_Error( 'mscr_upgrade_error', 'Could not connect to phpids.org, please try again later.' ) );

			$remote = $remote['body'];

			$diff_files[$file] = new stdClass;
			$diff_files[$file]->name = $file;
			$diff_files[$file]->diff = MSCR_Utils::text_diff( $local, $remote );
		}

		if( empty( $diff_files ) )
			wp_die( new WP_Error( 'mscr_upgrade_error', 'No files to update.' ) );

		$url = 'update.php?action=mscr_upgrade_run&files=' . urlencode(implode(',', $files));
		$url = wp_nonce_url($url, 'bulk-update-mscr');

		$this->admin_header( __('Update Mute Screamer') );

		$data['url'] = $url;
		$data['diff_files'] = $diff_files;

		MSCR_Utils::view( 'admin_update_diff', $data );
	}

	/**
	 * Admin header, because we are firing our own action
	 * in /wp-admin/update.php which does not set this up
	 * for us.
	 *
	 * @param string title
	 * @return void
	 */
	private function admin_header( $title ) {
		// Admin header requires these variables to be in scope
		// TODO: Test for multisite variables that need to be in scope
		global $hook_suffix, $pagenow, $is_iphone, $current_screen, $user_identity, $wp_locale;
		require_once(ABSPATH . 'wp-admin/admin-header.php');
	}
}
