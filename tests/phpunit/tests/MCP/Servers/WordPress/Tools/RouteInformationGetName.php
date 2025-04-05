<?php

namespace McpWp\Tests\MCP\Servers\WordPress\Tools;

use McpWp\MCP\Servers\WordPress\Tools\RestApi;
use McpWp\MCP\Servers\WordPress\Tools\RouteInformation;
use McpWp\Tests_Includes\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass( RouteInformation::class )]
#[CoversMethod( RouteInformation::class, 'get_name' )]
class RouteInformationGetName extends TestCase {
	#[DataProvider( 'data_get_name' )]
	public function test_get_name( $route, $method, $title, $expected ): void {
		$instance = new RouteInformation( $route, $method, $title );
		$actual   = $instance->get_name();

		$this->assertSame( $expected, $actual );
	}

	public static function data_get_name(): array {
		return [
			'GET /wp/v2/posts/'                 => [
				'/wp/v2/posts/',
				'GET',
				'post',
				'get_wp_v2_posts',
			],
			'DELETE /wp/v2/posts/(?P<id>[\d]+)' => [
				'/wp/v2/posts/(?P<id>[\d]+)',
				'DELETE',
				'post',
				'delete_wp_v2_posts_p_id',
			],
			'GET /'                             => [
				'/',
				'GET',
				'',
				'get_index',
			],
		];
	}
}
