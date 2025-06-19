<?php
/**
 * Collection of functions.
 *
 * @package McpWp
 */

declare(strict_types = 1);

namespace McpWp;
use WP_User;
use function add_action;

/**
 * Bootstrap function.
 *
 * @return void
 */
function boot(): void {
	add_action( 'init', __NAMESPACE__ . '\register_session_post_type' );
	add_action( 'rest_api_init', __NAMESPACE__ . '\register_rest_routes' );

	add_action( 'mcp_sessions_cleanup', __NAMESPACE__ . '\delete_old_sessions' );

	add_filter( 'update_plugins_mcp-wp.github.io', __NAMESPACE__ . '\filter_update_plugins', 10, 2 );

	add_filter( 'determine_current_user', __NAMESPACE__ . '\validate_bearer_token', 30 );
}

/**
 * Filters the update response for this plugin.
 *
 * Allows downloading updates from GitHub.
 *
 * @codeCoverageIgnore
 *
 * @param array<string,mixed>|false $update      The plugin update data with the latest details. Default false.
 * @param array<string,string>      $plugin_data Plugin headers.
 *
 * @return array<string,mixed>|false Filtered update data.
 */
function filter_update_plugins( $update, $plugin_data ) {
	// @phpstan-ignore requireOnce.fileNotFound
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	$updater = new \WP_Automatic_Updater();

	if ( $updater->is_vcs_checkout( dirname( __DIR__ ) ) ) {
		return $update;
	}

	$response = wp_remote_get( $plugin_data['UpdateURI'] );
	$response = wp_remote_retrieve_body( $response );

	if ( '' === $response ) {
		return $update;
	}

	/**
	 * Encoded update data.
	 *
	 * @var array<string,mixed> $result
	 */
	$result = json_decode( $response, true );

	return $result;
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

/**
 * Registers a new post type for MCP sessions.
 *
 * @return void
 */
function register_session_post_type(): void {
	register_post_type(
		'mcp_session',
		[
			'label'   => __( 'MCP Sessions', 'mcp-wp' ),
			'public'  => false,
			'show_ui' => defined( 'WP_DEBUG' ) && !! WP_DEBUG, // For debugging.
		]
	);
}

/**
 * Registers the MCP server REST API routes.
 *
 * @return void
 */
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


/**
 * Validates the application password credentials passed via `Authorization` header.
 *
 * @param int|false $input_user User ID if one has been determined, false otherwise.
 * @return int|false The authenticated user ID if successful, false otherwise.
 */
function validate_bearer_token( $input_user ) {
	// Don't authenticate twice.
	if ( ! empty( $input_user ) ) {
		return $input_user;
	}

	if ( ! wp_is_application_passwords_available() ) {
		return $input_user;
	}

	if ( ! isset( $_SERVER['HTTP_AUTHORIZATION'] ) || ! is_string( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
		return $input_user;
	}

	$matches = [];
	$match   = preg_match( '/^Bearer (?<user>.*):(?<password>.*)$/', $_SERVER['HTTP_AUTHORIZATION'], $matches );

	if ( 1 !== $match ) {
		return $input_user;
	}

	$authenticated = wp_authenticate_application_password( null, $matches['user'], $matches['password'] );

	if ( $authenticated instanceof WP_User ) {
		return $authenticated->ID;
	}

	// If it wasn't a user what got returned, just pass on what we had received originally.
	return $input_user;
}
