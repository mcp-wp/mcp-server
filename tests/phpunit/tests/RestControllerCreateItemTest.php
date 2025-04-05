<?php

namespace McpWp\Tests;

use McpWp\RestController;
use McpWp\Tests_Includes\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use Spy_REST_Server;
use WP_REST_Request;
use WP_REST_Server;

#[CoversClass( RestController::class )]
#[CoversMethod( RestController::class, 'create_item' )]
class RestControllerCreateItemTest extends TestCase {
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

	public function test_creates_new_session(): void {
		$request = new WP_REST_Request( 'POST', '/mcp/v1/mcp' );
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
		$headers  = $response->get_headers();

		$this->assertArrayHasKey( 'Mcp-Session-Id', $headers );

		$session_post = get_page_by_path( $headers['Mcp-Session-Id'], OBJECT, 'mcp_session' );

		$this->assertNotNull( $session_post );
	}

	public function test_requires_a_session(): void {
		$request = new WP_REST_Request( 'POST', '/mcp/v1/mcp' );
		$request->add_header( 'Content-Type', 'application/json' );
		$request->set_body(
			json_encode(
				[
					'jsonrpc' => '2.0',
					'id'      => '0',
					'method'  => 'tools/list',
				],
				JSON_THROW_ON_ERROR
			)
		);
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );

		$error = $response->as_error();

		$this->assertWPError( $error, 'Missing session.' );
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
