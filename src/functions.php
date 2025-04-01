<?php
/**
 * Collection of functions.
 *
 * @package McpWp
 */

declare(strict_types = 1);

namespace McpWp;
use function add_action;

function boot(): void {
	add_action( 'init', __NAMESPACE__ . '\register_session_post_type' );
	add_action( 'rest_api_init', __NAMESPACE__ . '\register_rest_routes' );

	add_action( 'mcp_sessions_cleanup', __NAMESPACE__ . '\delete_old_sessions' );
}

/**
 * Plugin activation hook.
 *
 * @codeCoverageIgnore
 *
 * @return void
 */
function activate_plugin(): void {
	register_session_post_type();

	if ( false === wp_next_scheduled( 'mcp_sessions_cleanup' ) ) {
		wp_schedule_event( time(), 'hourly', 'mcp_sessions_cleanup' );
	}
}

/**
 * Plugin deactivation hook.
 *
 * @codeCoverageIgnore
 *
 * @return void
 */
function deactivate_plugin(): void {
	unregister_post_type( 'mcp_session' );

	$timestamp = wp_next_scheduled( 'mcp_sessions_cleanup' );
	if ( false !== $timestamp ) {
		wp_unschedule_event( $timestamp, 'mcp_sessions_cleanup' );
	}
}

function register_session_post_type(): void {
	register_post_type(
		'mcp_session',
		[
			'public'  => false,
			'show_ui' => true, // For debugging.
		]
	);
}



function register_rest_routes(): void {
	$controller = new RestController();
	$controller->register_routes();
}

/**
 * Delete unresolved upload requests that are older than 1 day.
 *
 * @return void
 */
function delete_old_sessions(): void {
	$args = [
		'post_type'        => 'mcp_session',
		'post_status'      => 'publish',
		'numberposts'      => -1,
		'date_query'       => [
			[
				'before'    => '1 day ago',
				'inclusive' => true,
			],
		],
		'suppress_filters' => false,
	];

	$posts = get_posts( $args );

	foreach ( $posts as $post ) {
		wp_delete_post( $post->ID, true );
	}
}
