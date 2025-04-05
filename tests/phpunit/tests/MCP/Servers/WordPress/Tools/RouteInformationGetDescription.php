<?php

namespace McpWp\Tests\MCP\Servers\WordPress\Tools;

use McpWp\MCP\Servers\WordPress\Tools\RestApi;
use McpWp\MCP\Servers\WordPress\Tools\RouteInformation;
use McpWp\Tests_Includes\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass( RouteInformation::class )]
#[CoversMethod( RouteInformation::class, 'get_description' )]
class RouteInformationGetDescription extends TestCase {
	#[DataProvider( 'data_get_description' )]
	public function test_get_description( $route, $method, $title, $expected ): void {
		$instance = new RouteInformation( $route, $method, $title );
		$actual   = $instance->get_description();

		$this->assertSame( $expected, $actual );
	}

	public static function data_get_description(): array {
		return [
			'GET /wp/v2/posts/'                 => [
				'/wp/v2/posts/',
				'GET',
				'post',
				'Get a list of post items',
			],
			'POST /wp/v2/posts/'                => [
				'/wp/v2/posts/',
				'POST',
				'post',
				'Create a single post item',
			],
			'PUT /wp/v2/posts/'                 => [
				'/wp/v2/posts/',
				'PUT',
				'post',
				'Update a list of post items',
			],
			'DELETE /wp/v2/posts/'              => [
				'/wp/v2/posts/',
				'DELETE',
				'post',
				'Delete a list of post items',
			],
			'GET /wp/v2/posts/(?P<id>[\d]+)'    => [
				'/wp/v2/posts/(?P<id>[\d]+)',
				'GET',
				'post',
				'Get a single post item',
			],
			'POST /wp/v2/posts/(?P<id>[\d]+)'   => [
				'/wp/v2/posts/(?P<id>[\d]+)',
				'POST',
				'post',
				'Create a single post item',
			],
			'PATCH /wp/v2/posts/(?P<id>[\d]+)'  => [
				'/wp/v2/posts/(?P<id>[\d]+)',
				'PATCH',
				'post',
				'Update a single post item',
			],
			'DELETE /wp/v2/posts/(?P<id>[\d]+)' => [
				'/wp/v2/posts/(?P<id>[\d]+)',
				'DELETE',
				'post',
				'Delete a single post item',
			],
		];
	}
}
