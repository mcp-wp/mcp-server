<?php

namespace McpWp\Tests\MCP\Servers\WordPress\Tools;

use Mcp\Types\TextContent;
use McpWp\MCP\Servers\WordPress\Tools\CommunityEvents;
use McpWp\Tests_Includes\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversClass( CommunityEvents::class )]
#[CoversMethod( CommunityEvents::class, 'get_tools' )]
class CommunityEventsTest extends TestCase {
	public function test_get_tools(): void {
		$instance = new CommunityEvents();
		$tools    = $instance->get_tools();

		add_filter( 'pre_http_request', [ $this, 'mock_http_request' ], 10, 3 );

		$actual = call_user_func( $tools[0]['callback'], [ 'location' => 'Zurich' ] );

		remove_filter( 'pre_http_request', [ $this, 'mock_http_request' ] );

		$this->assertNotEmpty( $tools );
		$this->assertInstanceOf( TextContent::class, $actual );
	}

	/**
	 * Intercept and mock HTTP request responses.
	 *
	 * @param mixed  $preempt Whether to preempt an HTTP request's return value. Default false.
	 * @param mixed  $r       HTTP request arguments.
	 * @param string $url     The request URL.
	 * @return mixed|WP_Error Response data.
	 */
	public function mock_http_request( $preempt, $r, string $url ) {
		if ( 'https://api.wordpress.org/events/1.0/' === $url ) {
			return [
				'headers'  => [
					'content-type'   => 'application/json',
					'content-length' => 100,
				],
				'response' => [
					'code' => 200,
				],
				'body'     => json_encode(
					[
						'location' => 'Zurich',
						'events'   => [],
					],
					JSON_THROW_ON_ERROR
				),
			];
		}

		return $preempt;
	}
}
