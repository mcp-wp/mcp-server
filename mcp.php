<?php
/**
 * Plugin Name:       MCP Server for WordPress
 * Plugin URI:        https://github.com/swissspidy/mcp
 * Description:       MCP server implementation using the WordPress REST API.
 * Version:           0.1.0
 * Author:            Pascal Birchler
 * Author URI:        https://pascalbirchler.com
 * License:           Apache-2.0
 * License URI:       https://www.apache.org/licenses/LICENSE-2.0
 * Text Domain:       mcp
 * Requires at least: 6.7
 * Requires PHP:      8.2
 *
 * @package McpWp
 */

require_once __DIR__ . '/vendor/autoload.php';

register_activation_hook( __FILE__, 'McpWp\\activate_plugin' );
register_deactivation_hook( __FILE__, 'McpWp\\deactivate_plugin' );

\McpWp\boot();
