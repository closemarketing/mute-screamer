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

	public function testIPAddress() {
		$this->assertEquals('0.0.0.0', MSCR_Utils::ip_address(), '->ip_address returns ip address');
	}

	public function testGet() {
		$_GET = array();

		$this->assertFalse(MSCR_Utils::get('foo'), '->get returns false for non-existent value');

		$_GET['foo'] = 'bar';
		$this->assertEquals('bar', MSCR_Utils::get('foo'));
	}

	public function testPost() {
		$_POST = array();

		$this->assertFalse(MSCR_Utils::post('foo'), '->post returns false for non-existent value');

		$_POST['foo'] = 'bar';
		$this->assertEquals('bar', MSCR_Utils::post('foo'));
	}

	public function testServer() {
		$this->assertFalse(MSCR_Utils::server('foo'), '->server returns false for non-existent value');

		$_SERVER['foo'] = 'bar';
		$this->assertEquals('bar', MSCR_Utils::server('foo'));
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
