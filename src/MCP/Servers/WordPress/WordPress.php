<?php

namespace McpWp\MCP\Servers\WordPress;

use Mcp\Types\Resource;
use Mcp\Types\ResourceTemplate;
use McpWp\MCP\Server;
use McpWp\MCP\Servers\WordPress\Tools\CommunityEvents;
use McpWp\MCP\Servers\WordPress\Tools\Dummy;
use McpWp\MCP\Servers\WordPress\Tools\RestApi;
use Psr\Log\LoggerInterface;

class WordPress extends Server {
	public function __construct( ?LoggerInterface $logger = null ) {
		parent::__construct( 'WordPress', $logger );

		$all_tools = [
			...( new RestApi( $this->logger ) )->get_tools(),
			...( new CommunityEvents() )->get_tools(),
			...( new Dummy() )->get_tools(),
		];

		/**
		 * Filters all the tools exposed by the WordPress MCP server.
		 *
		 * @param array $all_tools MCP tools.
		 */
		$all_tools = apply_filters( 'mcp_wp_wordpress_tools', $all_tools );

		foreach ( $all_tools as $tool ) {
			try {
				$this->register_tool( $tool );
			} catch ( \Exception $e ) {
				$this->logger->debug( $e->getMessage() );
			}
		}

		/**
		 * Fires after tools have been registered in the WordPress MCP server.
		 *
		 * Can be used to register additional tools.
		 *
		 * @param Server $server WordPress MCP server instance.
		 */
		do_action( 'mcp_wp_wordpress_tools_loaded', $this );

		$this->register_resource(
			new Resource(
				'Greeting Text',
				'example://greeting',
				'A simple greeting message',
				'text/plain'
			)
		);

		$this->register_resource_template(
			new ResourceTemplate(
				'Attachment',
				'media://{id}',
				'WordPress attachment',
				'application/octet-stream'
			)
		);
	}
}
