<?php
/**
 * Mute Screamer
 */
class MuteScreamerTest extends WP_UnitTestCase {
	public $plugin_slug = 'mute-screamer';

	public function setUP() {
		parent::setUp();
		$this->mute_screamer = new Mute_Screamer();
	}

	public function testInitIDS() {
        $r = new ReflectionObject($this->mute_screamer);
        $m = $r->getMethod('init_ids');
        $m->setAccessible(true);

		$this->assertInstanceOf('IDS_Init', $m->invoke($this->mute_screamer), '->init_ids() returns Init_IDS instance');
	}

	public function testSendAlertEmail() {
		$r = new ReflectionObject($this->mute_screamer);
		$m = $r->getMethod('send_alert_email');
		$m->setAccessible(true);

		$this->mute_screamer->set_option('email_notifications', false);

		$this->assertFalse($m->invoke($this->mute_screamer), '->send_alert_email() returns false when email notifications are disabled');

		$this->mute_screamer->set_option('email_notifications', true);
		$this->mute_screamer->set_option('email_threshold', 1);

		$this->assertFalse($m->invoke($this->mute_screamer), '->send_alert_email() returns false when email notifications are enabled and result impact is less than email threshold');

		$this->mute_screamer->set_option('email_notifications', true);
		$this->mute_screamer->set_option('email_threshold', -1);

		$this->assertTrue($m->invoke($this->mute_screamer), '->send_alert_email() returns true when email notifications are enabled and result impact is greater than email threshold');
	}

	public function testInstance() {
		$this->assertInstanceOf('Mute_Screamer', Mute_Screamer::instance());
	}

	public function testGetOption() {
		$this->assertEquals(20, $this->mute_screamer->get_option('email_threshold'), 'Retrieve an option');
		$this->assertFalse($this->mute_screamer->get_option('foobar'), 'Returns false for options that do not exist');
	}

	public function testSetOption() {
		$this->mute_screamer->set_option('foo', 'bar');
		$this->assertFalse($this->mute_screamer->get_option('foo'), '->set_option does not set non-existent option');

		$this->mute_screamer->set_option('ban_time', 1000);
		$this->assertEquals(1000, $this->mute_screamer->get_option('ban_time'), '->set_option sets whitelisted option');
	}

	public function testDBTable() {
		$this->mute_screamer->db_table();
		$this->assertEquals('wp_mscr_intrusions', $GLOBALS['wpdb']->mscr_intrusions);
	}

	public function testPluinURL() {
		$file = ABSPATH . '/wp-content/plugins/mute-screamer/mute-screamer.php';
		$expected = WP_PLUGIN_URL . '/mute-screamer/';
		$this->assertEquals($expected, plugin_dir_url($file));
	}
}
