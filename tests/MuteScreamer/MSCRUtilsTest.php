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
		$this->assertEquals(20, MSCR_Utils::mscr_intrusions_per_page(), '->mscr_intrusions_per_page default is 20');
	}

	public function testIsWPLogin() {
		$_SERVER['REQUEST_URI'] = '/wp-login.php';
		$this->assertTrue(MSCR_Utils::is_wp_login(), '->wp_is_login is true without query args');

		$_SERVER['REQUEST_URI'] = '/wp-login.php?redirect_to=http%3A%2F%2Fexample.org%2Fwp-admin%2F&reauth=1';
		$this->assertTrue(MSCR_Utils::is_wp_login(), '->wp_is_login is true with query args');

		$_SERVER['REQUEST_URI'] = '/index.php';
		$this->assertFalse(MSCR_Utils::is_wp_login(), '->wp_is_login is false other files');

		$_SERVER['REQUEST_URI'] = '/2011/05/hey-there/';
		$this->assertFalse(MSCR_Utils::is_wp_login(), '->wp_is_login is false for permalinks');
	}
}
