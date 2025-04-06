<?php

namespace McpWp\MCP\Servers\WordPress\Tools;

use Mcp\Types\TextContent;
use McpWp\MCP\Server;
use WP_Community_Events;

/**
 * CommunityEvents tool class.
 *
 * Demonstrates how an additional tool can be added to
 * provide some other information from WordPress beyond
 * the REST API routes.
 *
 * @phpstan-import-type ToolDefinition from Server
 */
readonly class CommunityEvents {
	/**
	 * Returns a list of tools.
	 *
	 * @return array<int, ToolDefinition> Tools.
	 */
	public function get_tools(): array {
		$tools = [];

		$tools[] = [
			'name'        => 'fetch_wp_community_events',
			'description' => __( 'Fetches upcoming WordPress community events near a specified city or the user\'s current location. If no events are found in the exact location, nearby events within a specific radius will be considered.', 'mcp' ),
			'inputSchema' => [
				'type'       => 'object',
				'properties' => [
					'location' => [
						'type'        => 'string',
						'description' => __( 'City name or "near me" for auto-detected location. If no events are found in the exact location, the tool will also consider nearby events within a specified radius (default: 100 km).', 'mcp' ),
					],
				],
				'required'   => [ 'location' ],
			],
			'callable'    => static function ( $params ) {
				$location_input = strtolower( trim( $params['location'] ) );

				if ( ! class_exists( 'WP_Community_Events' ) ) {
					// @phpstan-ignore requireOnce.fileNotFound
					require_once ABSPATH . 'wp-admin/includes/class-wp-community-events.php';
				}

				$location = [
					'description' => $location_input,
				];

				$events_instance = new WP_Community_Events( 0, $location );

				// Get events from WP_Community_Events
				$events = $events_instance->get_events( $location_input );

				// Check for WP_Error
				if ( is_wp_error( $events ) ) {
					return $events;
				}

				return new TextContent(
					json_encode( $events['events'], JSON_THROW_ON_ERROR )
				);
			},
		];

		return $tools;
	}
}
