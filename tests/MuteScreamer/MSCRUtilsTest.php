<?php
/**
 * MSCR Utilities
 */
class MSCRUtilsTest extends WP_UnitTestCase {
	public $plugin_slug = 'mute-screamer';

	public function setUP() {
		parent::setUp();
	}

	public function testIntrusionsPerPage()	{
		delete_option('mscr_intrusions_per_page');
		$this->assertEquals(20, MSCR_Utils::mscr_intrusions_per_page(), '->mscr_intrusions_per_page default is 20');
	}
}
