<?php

namespace McpWp\Tests\MCP\Servers\WordPress\Tools;

use McpWp\MCP\Servers\WordPress\Tools\RestApi;
use McpWp\Tests_Includes\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use WP_UnitTest_Factory;

#[CoversClass( RestApi::class )]
#[CoversMethod( RestApi::class, 'get_tools' )]
class RestApiTest extends TestCase {
	private static $admin      = 0;
	private static $subscriber = 0;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$admin = $factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		self::$subscriber = $factory->user->create(
			array(
				'role' => 'subscriber',
			)
		);
	}

	public function test_get_tools_no_user(): void {
		$instance = new RestApi();
		$tools    = $instance->get_tools();

		$tool_names = wp_list_pluck( $tools, 'name' );

		$this->assertNotEmpty( $tools );
		$this->assertNotContains( 'get_wp_v2_settings', $tool_names, 'get_wp_v2_settings tool should not be included due to lack of capabilities' );
		$this->assertContains( 'get_wp_v2_users_me', $tool_names, 'get_wp_v2_users_me tool should always be included due to required parameter' );
		$this->assertContains( 'get_wp_v2_posts_p_id', $tool_names, 'get_wp_v2_posts_p_id tool should always be included due to required parameter' );
	}

	public function test_get_tools_admin(): void {
		wp_set_current_user( self::$admin );

		$instance = new RestApi();
		$tools    = $instance->get_tools();

		$tool_names = wp_list_pluck( $tools, 'name' );

		$this->assertNotEmpty( $tools );
		$this->assertContains( 'get_wp_v2_settings', $tool_names, 'get_wp_v2_settings tool should be included due to matching capabilities' );
		$this->assertContains( 'post_wp_v2_users_me', $tool_names, 'post_wp_v2_users_me tool should be included due to required parameter' );
		$this->assertContains( 'get_wp_v2_posts_p_id', $tool_names, 'get_wp_v2_posts_p_id tool should always be included due to required parameter' );
	}
	public function test_get_tools_subscriber(): void {
		wp_set_current_user( self::$subscriber );

		$instance = new RestApi();
		$tools    = $instance->get_tools();

		$tool_names = wp_list_pluck( $tools, 'name' );

		$this->assertNotEmpty( $tools );
		$this->assertNotContains( 'get_wp_v2_settings', $tool_names, 'get_wp_v2_settings tool should not be included due to lack of capabilities' );
		$this->assertContains( 'post_wp_v2_users_me', $tool_names, 'post_wp_v2_users_me tool should be included due to required parameter' );
		$this->assertContains( 'get_wp_v2_posts_p_id', $tool_names, 'get_wp_v2_posts_p_id tool should always be included due to required parameter' );
	}
}
