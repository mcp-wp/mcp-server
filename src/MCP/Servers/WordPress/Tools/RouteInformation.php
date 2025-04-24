<?php

declare(strict_types=1);

namespace McpWp\MCP\Servers\WordPress\Tools;

use BadMethodCallException;
use WP_REST_Controller;
use WP_REST_Posts_Controller;
use WP_REST_Taxonomies_Controller;
use WP_REST_Users_Controller;

/**
 * RouteInformation helper class.
 */
readonly class RouteInformation {
	/**
	 * Class constructor.
	 *
	 * @param string $route Route name.
	 * @param string $method Method.
	 * @param string $title Schema title.
	 */
	public function __construct(
		private string $route,
		private string $method,
		private string $title,
	) {}

	/**
	 * Returns a tool name based on the route and method.
	 *
	 * Example: DELETE wp/v2/users/me -> delete_wp_v2_users_me
	 *
	 * @return string Tool name.
	 */
	public function get_name(): string {
		$route = $this->route;

		preg_match_all( '/\(?P<(\w+)>/', $this->route, $matches );

		foreach ( $matches[1] as $match ) {
			$route = (string) preg_replace(
				'/(\(\?P<' . $match . '>.*\))/',
				'p_' . $match,
				$route,
				1
			);
		}

		$suffix = sanitize_title( $route );

		if ( '' === $suffix ) {
			$suffix = 'index';
		}

		return strtolower( str_replace( '-', '_', $this->method . '_' . $suffix ) );
	}

	/**
	 * Returns a description based on the route and method.
	 *
	 * Examples:
	 *
	 * GET /wp/v2/posts               -> Get a list of post items
	 * GET /wp/v2/posts/(?P<id>[\d]+) -> Get a single post item
	 */
	public function get_description(): string {
		$verb = match ( $this->method ) {
			'POST' => 'Create',
			'PUT', 'PATCH'  => 'Update',
			'DELETE' => 'Delete',
			default => 'Get',
		};

		$is_singular = str_ends_with( $this->route, '(?P<id>[\d]+)' ) || 'POST' === $this->method;

		$determiner = $is_singular ? 'a single' : 'a list of';

		$title = '' !== $this->title ? "{$this->title} item" : 'item';
		$title = $is_singular ? $title : $title . 's';

		return $verb . ' ' . $determiner . ' ' . $title;
	}
}
