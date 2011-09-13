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

	public function testGetOption() {
		$this->assertEquals(20, $this->mute_screamer->get_option('email_threshold'), 'Retrieve an option');
		$this->assertFalse($this->mute_screamer->get_option('foobar'), 'Returns false for options that do not exist');
	}
}
