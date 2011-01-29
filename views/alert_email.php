<?php if( ! defined('ABSPATH')) exit;
printf( __( "Mute Screamer has detected an attack on your site %s", 'mute-screamer' ), $blogname );
echo "\r\n\r\n";
printf( __( "Total impact: %s", 'mute-screamer' ), $result->getImpact() );
echo "\r\n";
printf( __( "Affected tags: %s", 'mute-screamer' ), join(', ', $result->getTags()) );
echo "\r\n";
printf( __( "Detected ip address: %s ", 'mute-screamer' ), $ip_address );
echo "\r\n\r\n";

foreach( $result->getIterator() as $event ) {
	printf( __( 'Variable: %s', 'mute-screamer' ), esc_html($event->getName()) );
	echo "\r\n";
	printf( __( 'Value: %s', 'mute-screamer' ), esc_html($event->getValue()) );
	echo "\r\n";
	printf( __( 'Impact: %s', 'mute-screamer' ), esc_html($event->getImpact()) );
	echo "\r\n";
	printf( __( 'Tags: %s', 'mute-screamer' ), esc_html($event->getTags()) );
	echo "\r\n\r\n";
}

if( $centrifuge = $result->getCentrifuge() ) {
	_e( 'Centrifuge detection data', 'mute-screamer' );
	echo "\r\n";
	printf( __( 'Threshold: %s', 'mute-screamer' ), ( isset($centrifuge['threshold']) && $centrifuge['threshold'] ) ? $centrifuge['threshold'] : '---' );
	echo "\r\n";
	printf( __( 'Ratio: %s', 'mute-screamer' ), ( isset($centrifuge['ratio']) && $centrifuge['ratio'] ) ? $centrifuge['ratio'] : '---' );
	echo "\r\n\r\n";

	if( isset($centrifuge['converted']) ) {
		printf( __( 'Converted: %s', 'mute-screamer' ), $centrifuge['converted'] );
		echo "\r\n\r\n";
	}
}
