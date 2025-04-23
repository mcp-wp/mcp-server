<?php

namespace McpWp\MCP\Servers\WordPress\Tools;

use McpWp\MCP\Server;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST API tools class.
 *
 * @phpstan-import-type ToolDefinition from Server
 */
readonly class RestApi {
	private LoggerInterface $logger;

	/**
	 * Constructor.
	 *
	 * @param LoggerInterface|null $logger Logger.
	 */
	public function __construct( ?LoggerInterface $logger = null ) {
		$this->logger = $logger ?? new NullLogger();
	}

	/**
	 * Returns a list of tools for all REST API routes.
	 *
	 * @throws \Exception
	 *
	 * @return array<int, ToolDefinition> Tools.
	 */
	public function get_tools(): array {
		$server     = rest_get_server();
		$routes     = $server->get_routes();
		$namespaces = $server->get_namespaces();
		$tools      = [];

		foreach ( $routes as $route => $handlers ) {
			// Do not include namespace routes in the response.
			if ( in_array( ltrim( $route, '/' ), $namespaces, true ) ) {
				continue;
			}

			/**
			 * @param array{methods: array<string, mixed>, accept_json: bool, accept_raw: bool, show_in_index: bool, args: array, callback: array, permission_callback?: array} $handler
			 */
			foreach ( $handlers as $handler ) {

				/**
				 * Methods for this route, e.g. 'GET', 'POST', 'PUT', etc.
				 *
				 * @var string[] $methods
				 */
				$methods = array_keys( $handler['methods'] );

				// If WP_REST_Server::EDITABLE is used, keeo only POST but not PUT or PATCH.
				if ( in_array( 'POST', $methods, true ) ) {
					$methods = array_diff( $methods, [ 'PUT', 'PATCH' ] );
				}

				foreach ( $methods as $method ) {
					$title = '';

					if (
						is_array( $handler['callback'] ) &&
						isset( $handler['callback'][0] ) &&
						$handler['callback'][0] instanceof \WP_REST_Controller
					) {
						$controller = $handler['callback'][0];
						$schema     = $controller->get_public_item_schema();
						if ( isset( $schema['title'] ) ) {
							$title = $schema['title'];
						}
					}

					if ( isset( $handler['permission_callback'] ) && is_callable( $handler['permission_callback'] ) ) {
						$has_required_parameter = (bool) preg_match_all( '/\(?P<(\w+)>/', $route );
						/**
						 * Permission callback result.
						 *
						 * @var bool|\WP_Error $result
						 */
						$result = call_user_func( $handler['permission_callback'], new WP_REST_Request() );
						if ( ! $has_required_parameter && true !== $result ) {
							continue;
						}
					}

					$information = new RouteInformation(
						$route,
						$method,
						$title,
					);

					// Really no need to add a tool for this route.
					if (
						in_array(
							$information->get_name(),
							[
								'get_index',
								'post_batch_v1',
							],
							true
						)
					) {
						continue;
					}

					$tool = [
						'name'        => $information->get_name(),
						'description' => $information->get_description(),
						'inputSchema' => $this->args_to_schema( $handler['args'] ),
						'callable'    => function ( $params ) use ( $route, $method ) {
							return json_encode( $this->rest_callable( $route, $method, $params ), JSON_THROW_ON_ERROR );
						},
					];

					$tools[] = $tool;
				}
			}
		}

		return $tools;
	}

	/**
	 * REST route tool callback.
	 *
	 * @throws \JsonException
	 *
	 * @param string $route Route
	 * @param string $method HTTP method.
	 * @param array<string, mixed> $params Route params.
	 * @return array<string, mixed> REST response data.
	 */
	private function rest_callable( string $route, string $method, array $params ): array {
		$server = rest_get_server();

		preg_match_all( '/\(?P<(\w+)>/', $route, $matches );

		foreach ( $matches[1] as $match ) {
			if ( array_key_exists( $match, $params ) ) {
				$route = preg_replace( '/(\(\?P<' . $match . '>.*?\))/', $params[ $match ], $route, 1 );
				unset( $params[ $match ] );
			}
		}

		// Fix incorrect meta inputs.
		if ( isset( $params['meta'] ) ) {
			if ( false === $params['meta'] || '' === $params['meta'] || [] === $params['meta'] ) {
				unset( $params['meta'] );
			}
		}

		$this->logger->debug( "$method $route with input: " . json_encode( $params, JSON_THROW_ON_ERROR ) );

		$request = new WP_REST_Request( $method, $route );
		$request->set_body_params( $params );

		/**
		 * REST API response.
		 *
		 * @var WP_REST_Response $response
		 */
		$response = $server->dispatch( $request );

		/**
		 * Response data.
		 *
		 * @phpstan-var array<string, mixed> $data
		 */
		$data = $server->response_to_data( $response, false ); // @phpstan-ignore varTag.type

		// Reduce amount of data that is returned.
		unset( $data['_links'], $data['_embedded'] );

		foreach ( $data as &$item ) {
			if ( is_array( $item ) ) {
				unset( $item['_links'], $item['_embedded'] );
			}
		}

		return $data;
	}

	/**
	 * @throws \Exception
	 *
	 * @param array<string, mixed> $args REST API route arguments.
	 * @return array<string, mixed> Normalized schema.
	 */
	private function args_to_schema( array $args = [] ): array {
		$schema   = [];
		$required = [];

		if ( empty( $args ) ) {
			return [
				'type'       => 'object',
				'properties' => [],
				'required'   => [],
			];
		}

		foreach ( $args as $title => $arg ) {
			$description = $arg['description'] ?? $title;
			$type        = $this->sanitize_type( $arg['type'] ?? 'string' );

			$schema[ $title ] = [
				'type'        => $type,
				'description' => $description,
			];
			if ( isset( $arg['required'] ) && true === $arg['required'] ) {
				$required[] = $title;
			}
		}

		return [
			'type'       => 'object',
			'properties' => $schema,
			'required'   => $required,
		];
	}

	/**
	 * Normalize a type from REST API schema to MCP PHP SDK JSON schema.
	 *
	 * @param mixed $type Type.
	 * @return string Normalized type.
	 * @throws \Exception
	 */
	private function sanitize_type( $type ): string {

		$mapping = array(
			'string'  => 'string',
			'integer' => 'integer',
			'number'  => 'integer',
			'boolean' => 'boolean',
		);

		// Validated types:
		if ( ! \is_array( $type ) && isset( $mapping[ $type ] ) ) {
			return $mapping[ $type ];
		}

		if ( 'array' === $type || 'object' === $type ) {
			return 'string'; // TODO, better solution.
		}
		if ( empty( $type ) || 'null' === $type ) {
			return 'string';
		}

		if ( ! \is_array( $type ) ) {
			throw new \Exception( 'Invalid type: ' . $type );
		}

		// Find valid values in array.
		if ( \in_array( 'string', $type, true ) ) {
			return 'string';
		}
		if ( \in_array( 'integer', $type, true ) ) {
			return 'integer';
		}
		// TODO, better types handling.
		return 'string';
	}
}
