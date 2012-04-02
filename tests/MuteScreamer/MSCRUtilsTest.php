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

    public function testPagination() {
        $this->assertEquals('', MSCR_Utils::pagination(1, 1, 20, 15), '->pagination returns empty string');

        $expected =<<<EOS
<div class='tablenav-pages'><span class="displaying-num">Displaying 1&#8211;20 of 25</span><span class='page-numbers current'>1</span>
<a class='page-numbers' href='?paged=2'>2</a>
<a class="next page-numbers" href="?paged=2">&raquo;</a></div>
EOS;
        $this->assertEquals($expected, MSCR_Utils::pagination(1, 2, 20, 25), '->pagination returns pagination markup');
    }

    public function testMSCRIntrusionsPerPage() {
        $this->assertEquals(20, MSCR_Utils::mscr_intrusions_per_page());
    }

    public function testUploadPath() {
        $dir = WP_CONTENT_DIR.'/uploads';
        $this->assertEquals($dir, MSCR_Utils::upload_path());
    }

    public function testWritableNotice() {
        $dir = WP_CONTENT_DIR.'/uploads';
        $this->expectOutputString(sprintf('<div class="update-nag">Mute Screamer requires that your uploads folder %s is writable.</div>', $dir));
        MSCR_Utils::writable_notice();
    }

    public function testMSNotice() {
        $this->expectOutputString('<div class="update-nag">Mute Screamer multisite install currently not supported.</div>');
        MSCR_Utils::ms_notice();
    }

	public function testIPAddress() {
		$this->assertEquals('0.0.0.0', MSCR_Utils::ip_address(), '->ip_address returns ip address');
	}

    public function testTextDiff() {
        $this->assertEquals('', MSCR_Utils::text_diff('foo', 'foo'), '->text_diff returns empty string when identical');

        $expected =<<<DIFF
<table class='diff'>
<col class='ltype' /><col class='content' /><col class='ltype' /><col class='content' /><tbody>
<tr><td colspan="2" class="start-block">&nbsp;1c1</td></tr>
<tr><td class='diff-deletedline first'>-</td><td class='diff-deletedline'>foo</td></tr>
<tr><td class='diff-addedline first'>+</td><td class='diff-addedline'>bar</td></tr>

</tbody>
</table>
DIFF;
        $this->assertEquals($expected, MSCR_Utils::text_diff('foo', 'bar'), '->text_diff returns empty string when identical');
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
