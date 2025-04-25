<?php

namespace McpWp\MCP\Servers\WordPress\Tools;

use Mcp\Types\TextContent;
use McpWp\MCP\Server;

/**
 * @phpstan-import-type ToolDefinition from Server
 */
readonly class Dummy {

	/**
	 * Returns a list of dummy tools for testing.
	 *
	 * @return array<int, ToolDefinition> Tools.
	 */
	public function get_tools(): array {
		$tools = [];

		$tools[] = [
			'name'        => 'greet-user',
			'description' => 'Greet a given user by their name',
			'inputSchema' => [
				'type'       => 'object',
				'properties' => [
					'name' => [
						'type'        => 'string',
						'description' => 'Name',
					],
				],
				'required'   => [ 'name' ],
			],
			'callback'    => static function ( $arguments ) {
				$name = $arguments['name'];

				return new TextContent(
					"Hello my friend, $name"
				);
			},
		];

		return $tools;
	}
}
