<?php
/**
 * Functions
 */
class FunctionsTest extends WP_UnitTestCase {
	public $plugin_slug = 'mute-screamer';

	public function testMSCRBodyClass()	{
		$this->assertEquals(array('error404', 'error500'), mscr_body_class(array()));
	}
}
