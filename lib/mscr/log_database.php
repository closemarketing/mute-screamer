<?php  if ( !defined('ABSPATH') ) exit;
/*
 * Mute Screamer
 *
 * PHPIDS for Wordpress
 */

require_once 'IDS/Log/Interface.php';

/**
 * Log Database
 *
 * Log reports using the wpdb class
 */
class mscr_log_database implements IDS_Log_Interface {
    /**
     * Holds current remote address
     *
     * @var string
     */
    private $ip = 'local/unknown';


	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct() {
		// Determine correct IP address
		if( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$this->ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$this->ip = $_SERVER['REMOTE_ADDR'];
		}
	}


	/**
	* Inserts detected attacks into the database
	*
	* @param object
	* @return boolean
	*/
	public function execute( IDS_Report $report_data ) {
		global $wpdb;

		if( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			$_SERVER['REQUEST_URI'] = substr( $_SERVER['PHP_SELF'], 1 );
			if( isset( $_SERVER['QUERY_STRING'] ) && $_SERVER['QUERY_STRING'] ) {
				$_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
			}
		}

		foreach( $report_data as $event ) {
			$data['name'] = $event->getName();
			$data['value'] = stripslashes( $event->getValue() );
			$data['page'] = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
			$data['tags'] = implode( ', ', $event->getTags() );
			$data['ip'] = $this->ip;
			$data['impact'] = $event->getImpact();
			$data['origin'] = $_SERVER['SERVER_ADDR'];
			$data['created'] = date( 'Y-m-d H:i:s', current_time('timestamp') );

			if( FALSE === $wpdb->insert( Mute_screamer::INTRUSIONS_TABLE, $data ) ) {
				return FALSE;
			}
		}

		return TRUE;
	}
}
