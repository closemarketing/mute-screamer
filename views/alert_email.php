<?php if( ! defined('ABSPATH')) exit;
echo "Mute Screamer has detected an attack on your site {$blogname}.\r\n\r\n";
echo "Total impact: ".$result->getImpact()."\r\n";
echo "Affected tags: ".join(', ', $result->getTags())."\r\n\r\n";

foreach( $result->getIterator() as $event ) {
	echo "Variable: ".esc_html($event->getName())."\r\n";
	echo "Value: ".esc_html($event->getValue())."\r\n";
	echo "Impact: ".$event->getImpact()."\r\n";
	echo "Tags: ".join(', ', $event->getTags())."\r\n\r\n";
}

if( $centrifuge = $result->getCentrifuge() ) {
	echo "Centrifuge detection data\r\n";
	echo "Threshold: ".( isset($centrifuge['threshold']) && $centrifuge['threshold'] ) ? $centrifuge['threshold'] : '---'."\r\n";
	echo "Ratio: ".( isset($centrifuge['ratio']) && $centrifuge['ratio'] ) ? $centrifuge['ratio'] : '---'."\r\n\r\n";

	if( isset($centrifuge['converted']) ) {
		echo "Converted: ".$centrifuge['converted']."\r\n\r\n";
	}
}
