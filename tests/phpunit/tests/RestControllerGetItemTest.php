<?php

namespace McpWp\Tests;

use McpWp\RestController;
use McpWp\Tests_Includes\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use Spy_REST_Server;
use WP_REST_Request;
use WP_REST_Server;
use WP_UnitTest_Factory;

#[CoversClass( RestController::class )]
#[CoversMethod( RestController::class, 'get_item' )]
class RestControllerGetItemTest extends TestCase {
	private static int $admin = 0;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$admin = $factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	public function set_up(): void {
		parent::set_up();

		add_filter( 'rest_url', array( $this, 'filter_rest_url_for_leading_slash' ), 10, 2 );
		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$wp_rest_server = new Spy_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );
	}

	public function tear_down(): void {
		remove_filter( 'rest_url', array( $this, 'test_rest_url_for_leading_slash' ), 10, 2 );
		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$wp_rest_server = null;
		parent::tear_down();
	}

	public function test_disallows_get_requests(): void {
		$request = new WP_REST_Request( 'GET', '/mcp/v1/mcp' );
		$request->add_header( 'Content-Type', 'application/json' );
		$request->set_body(
			json_encode(
				[
					'jsonrpc' => '2.0',
					'id'      => '0',
					'method'  => 'initialize',
				],
				JSON_THROW_ON_ERROR
			)
		);

		$response = rest_get_server()->dispatch( $request );

		$error = $response->as_error();
		$this->assertWPError( $error );
		$this->assertSame( 'mcp_sse_not_supported', $error->get_error_code(), 'The expected error code does not match.' );
	}

	public function filter_rest_url_for_leading_slash( $url, $path ) {
		if ( is_multisite() || get_option( 'permalink_structure' ) ) {
			return $url;
		}

		// Make sure path for rest_url has a leading slash for proper resolution.
		if ( ! str_starts_with( $path, '/' ) ) {
			$this->fail(
				sprintf(
					'REST API URL "%s" should have a leading slash.',
					$path
				)
			);
		}

		return $url;
	}
}
