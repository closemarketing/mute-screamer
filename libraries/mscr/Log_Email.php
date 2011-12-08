<?php  if ( ! defined( 'ABSPATH' ) ) exit;
/*
 * Mute Screamer
 *
 * PHPIDS for Wordpress
 */

require_once 'IDS/Log/Email.php';

/**
 * Log Email
 *
 * Log reports via email
 */
class MSCR_Log_Email extends IDS_Log_Email {

	/**
	* Prepares data
	*
	* Converts given data into a format that can be read in an email.
	* You might edit this method to your requirements.
	*
	* @param mixed $data the report data
	* @return string
	*/
	protected function prepareData( $data ) {
		$format  = __( "The following attack has been detected by PHPIDS\n\n", 'mute-screamer' );
		$format .= __( "IP: %s \n", 'mute-screamer' );
		$format .= __( "Date: %s \n", 'mute-screamer' );
		$format .= __( "Impact: %d \n", 'mute-screamer' );
		$format .= __( "Affected tags: %s \n", 'mute-screamer' );

		$attackedParameters = '';
		foreach ( $data as $event ) {
			$attackedParameters .= $event->getName() . '=' .
				( ( ! isset( $this->urlencode ) || $this->urlencode )
				? urlencode( $event->getValue() )
				: $event->getValue() ) . ', ';
		}

		$format .= __( "Affected parameters: %s \n", 'mute-screamer' );
		$format .= __( "Request URI: %s \n", 'mute-screamer' );
		$format .= __( "Origin: %s \n", 'mute-screamer' );

		return sprintf( $format,
			$this->ip,
			date( 'c' ),
			$data->getImpact(),
			join( ' ', $data->getTags() ),
			trim( $attackedParameters ),
			htmlspecialchars( $_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8' ),
			$_SERVER['SERVER_ADDR']
		);
	}

	/**
	* Sends an email
	*
	* @param string $address  email address
	* @param string $data     the report data
	* @param string $headers  the mail headers
	* @param string $envelope the optional envelope string
	* @return boolean
	*/
	protected function send( $address, $data, $headers, $envelope = null ) {
		return wp_mail( $address, $this->subject, $data );
	}
}
