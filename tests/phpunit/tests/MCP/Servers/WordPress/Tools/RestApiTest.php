<?php

namespace McpWp\Tests\MCP\Servers\WordPress\Tools;

use McpWp\MCP\Servers\WordPress\Tools\RestApi;
use McpWp\Tests_Includes\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversClass( RestApi::class )]
#[CoversMethod( RestApi::class, 'get_tools' )]
class RestApiTest extends TestCase {
	public function test_get_tools(): void {
		$instance = new RestApi();
		$tools    = $instance->get_tools();

		$this->assertNotEmpty( $tools );
	}
}
