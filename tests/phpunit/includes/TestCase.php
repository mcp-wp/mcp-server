<?php

namespace McpWp\Tests_Includes;

use WP_UnitTestCase;

class TestCase extends WP_UnitTestCase {
	/**
	 * Temporary workaround to allow the tests to run on PHPUnit 10.
	 *
	 * @link https://core.trac.wordpress.org/ticket/59486
	 */
	public function expectDeprecated(): void {}
}
