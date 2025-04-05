<?php
/**
 * Main REST API controller.
 *
 * @package McpWp
 */

declare(strict_types = 1);

namespace McpWp;

use Mcp\Types\InitializeResult;
use Mcp\Types\JSONRPCError;
use Mcp\Types\JsonRpcErrorObject;
use Mcp\Types\JsonRpcMessage;
use Mcp\Types\JSONRPCNotification;
use Mcp\Types\JSONRPCRequest;
use Mcp\Types\JSONRPCResponse;
use Mcp\Types\NotificationParams;
use Mcp\Types\RequestId;
use Mcp\Types\RequestParams;
use McpWp\MCP\Servers\WordPress\WordPress;
use WP_Error;
use WP_Post;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * MCP REST API controller.
 */
class RestController extends WP_REST_Controller {
	/**
	 * MCP session ID header name.
	 *
	 * @var string
	 */
	const SESSION_ID_HEADER = 'Mcp-Session-Id';
	/**
	 * The namespace of this controller's route.
	 *
	 * @var string
	 */
	protected $namespace = 'mcp/v1';

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @see register_rest_route()
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/mcp',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => [
						'jsonrpc' => [
							'type'        => 'string',
							'enum'        => [ '2.0' ],
							'description' => __( 'JSON-RPC protocol version.', 'mcp' ),
							'required'    => true,
						],
						'id'      => [
							'type'        => [ 'string', 'integer' ],
							'description' => __( 'Identifier established by the client.', 'mcp' ),
							// It should be required, but it's not sent for things like notifications.
							'required'    => false,
						],
						'method'  => [
							'type'        => 'string',
							'description' => __( 'Method to be invoked.', 'mcp' ),
							'required'    => true,
						],
						'params'  => [
							'type'        => 'object',
							'description' => __( 'Method to be invoked.', 'mcp' ),
						],
					],
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_item' ],
					'permission_callback' => [ $this, 'delete_item_permissions_check' ],
				],
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => [ $this, 'get_item_permissions_check' ],
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);
	}

	/**
	 * Checks if a given request has access to create items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access to create items, WP_Error object otherwise.
	 */
	public function create_item_permissions_check( $request ): true|WP_Error {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You are not currently logged in.', 'mcp' ),
				array( 'status' => 401 )
			);
		}

		if ( 'initialize' !== $request['method'] ) {
			$session_id = (string) $request->get_header( self::SESSION_ID_HEADER );

			if ( empty( $session_id ) ) {
				return new WP_Error(
					'missing-session',
					__( 'Missing session.', 'mcp' ),
					array( 'status' => 400 )
				);
			}

			$session = $this->get_session( $session_id );

			if ( ! $session ) {
				return new WP_Error(
					'not-found',
					__( 'Session not found, it may have been terminated.', 'mcp' ),
					array( 'status' => 404 )
				);
			}
		}

		return true;
	}

	/**
	 * Creates one item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ): WP_Error|WP_REST_Response {
		$message = new JsonRpcMessage(
			new JSONRPCError(
				'2.0',
				new RequestId( '0' ),
				new JsonRpcErrorObject(
					-32600,
					'Invalid JSON-RPC message structure.',
					null
				)
			)
		);

		if ( isset( $request['method'] ) ) {
			// It's a Request or Notification
			if ( isset( $request['id'] ) ) {
				$params = new RequestParams();

				if ( isset( $request['params'] ) ) {
					foreach ( $request['params'] as $key => $value ) {
						$params->{$key} = $value;
					}
				}

				$message = new JsonRpcMessage(
					new JSONRPCRequest(
						'2.0',
						new RequestId( (string) $request['id'] ),
						isset( $request['params'] ) ? $params : null,
						$request['method'],
					)
				);
			} else {
				$params = new NotificationParams();

				if ( isset( $request['params'] ) ) {
					foreach ( $request['params'] as $key => $value ) {
						$params->{$key} = $value;
					}
				}
				$message = new JsonRpcMessage(
					new JSONRPCNotification(
						'2.0',
						isset( $request['params'] ) ? $params : null,
						$request['method'],
					)
				);
			}
		} elseif ( isset( $request['result'] ) || isset( $request['error'] ) ) {
			// It's a Response or Error
			if ( isset( $request['error'] ) ) {
				// It's an Error
				$error_data = $request['error'];
				$message    = new JsonRpcMessage(
					new JSONRPCError(
						'2.0',
						isset( $request['id'] ) ? new RequestId( $request['id'] ) : null,
						new JsonRpcErrorObject(
							$error_data['code'],
							$error_data['message'],
							$error_data['data'] ?? null
						)
					)
				);
			} else {
				// It's a Response
				$message = new JsonRpcMessage(
					new JSONRPCResponse(
						'2.0',
						isset( $request['id'] ) ? new RequestId( $request['id'] ) : null,
						$request['result']
					)
				);
			}
		}

		$server       = new WordPress();
		$mcp_response = $server->handle_message( $message );
		$response     = new WP_REST_Response();

		if ( $mcp_response ) {
			$response->set_data( $mcp_response );
		} else {
			$response->set_status( 202 );
		}

		if ( isset( $mcp_response ) && $mcp_response->message->result instanceof InitializeResult ) {
			$uuid = wp_generate_uuid4();

			wp_insert_post(
				[
					'post_type'   => 'mcp_session',
					'post_status' => 'publish',
					'post_title'  => $uuid,
					'post_name'   => $uuid,
				]
			);

			$response->header( self::SESSION_ID_HEADER, $uuid );
		}

		// Quick workaround for MCP Inspector.
		$response->header( 'Access-Control-Allow-Origin', '*' );

		// TODO: send right status code.

		return $response;
	}

	/**
	 * Checks if a given request has access to terminate an MCP session.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access to delete the item, WP_Error object otherwise.
	 */
	public function delete_item_permissions_check( $request ): true|WP_Error {
		return true;
	}

	/**
	 * Terminates an MCP session.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ): WP_Error|WP_REST_Response {
		$session_id = (string) $request->get_header( self::SESSION_ID_HEADER );

		if ( empty( $session_id ) ) {
			return new WP_Error(
				'missing-session',
				__( 'Missing session.', 'mcp' ),
				array( 'status' => 400 )
			);
		}

		$session = $this->get_session( $session_id );

		if ( ! $session ) {
			return new WP_Error(
				'not-found',
				__( 'Session not found, it may have been terminated.', 'mcp' ),
				array( 'status' => 404 )
			);
		}
		wp_delete_post( $session->ID, true );

		return new WP_REST_Response( '' );
	}


	/**
	 * Checks if a given request has access to get a specific item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access for the item, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ): true|WP_Error {
		return new WP_Error(
			'not-supported',
			__( 'Server does not currently offer an SSE stream.', 'mcp' ),
			array( 'status' => 405 )
		);
	}

	/**
	 * Retrieves the post's schema, conforming to JSON Schema.
	 *
	 * @since 4.7.0
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$schema = [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => __( 'MCP Server', 'mcp' ),
			'type'       => 'object',
			// Base properties for every Post.
			'properties' => [
				'jsonrpc'  => [
					'description' => __( 'JSON-RPC protocol version.', 'mcp' ),
					'type'        => 'string',
					'context'     => [ 'view' ],
				],
				'id'       => [
					'description' => __( 'Identifier established by the client.', 'mcp' ),
					'type'        => [ 'string', 'integer' ],
					'context'     => [ 'view' ],
				],
				'result'   => [
					'description' => __( 'Result', 'mcp' ),
					'type'        => [ 'object' ],
					'context'     => [ 'view' ],
				],
				'date_gmt' => [
					'description' => __( 'The date the post was published, as GMT.' ),
					'type'        => [ 'string', 'null' ],
					'format'      => 'date-time',
					'context'     => [ 'view' ],
				],
			],
		];

		$this->schema = $schema;

		return $this->add_additional_fields_schema( $this->schema );
	}

	/**
	 * Gets a session by its ID.
	 *
	 * @param string $session_id MCP session ID.
	 * @return WP_Post|null Post object if ID is valid, null otherwise.
	 */
	protected function get_session( string $session_id ): ?WP_Post {
		$args = [
			'name'             => $session_id,
			'post_type'        => 'mcp_session',
			'post_status'      => 'publish',
			'numberposts'      => 1,
			'suppress_filters' => false,
		];

		$posts = get_posts( $args );

		if ( empty( $posts ) ) {
			return null;
		}

		return $posts[0];
	}
}
