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
		$this->assertInstanceOf('IDS_Init', $this->mute_screamer->init_ids());
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
}
