<?php

namespace McpWp\Tests;

use WP_UnitTestCase;

class PluginTest extends WP_UnitTestCase {
	public function test_plugin(): void {
		$this->assertTrue( true );
	}

	/**
	 * Temporary workaround to allow the tests to run on PHPUnit 10.
	 *
	 * @link https://core.trac.wordpress.org/ticket/59486
	 */
	public function expectDeprecated(): void {}
}
