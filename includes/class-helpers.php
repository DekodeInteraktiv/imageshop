<?php
declare(strict_types=1);

namespace Imageshop\WordPress;

/**
 * Helper class.
 */
class Helpers {
	private static $instance;

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function register_rest_routes() {
		register_rest_route(
			'imageshop/v1',
			'/settings/test-connection',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'test_connection' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 *
	 */
	public function test_connection( \WP_REST_Request $request ) {
		$api_key = $request->get_param( 'token' );

		$response = array(
			'message' => sprintf(
				'<div class="notice notice-success fade"><p>%s</p></div>',
				esc_html__( 'Connection is successfully established. Save the settings.', 'imageshop' )
			),
		);

		try {
			$isml_rest_controller = new REST_Controller( $api_key );
			$can_upload           = $isml_rest_controller->can_upload();

			if ( ! $can_upload ) {
				$response['message'] = sprintf(
					'<div class="notice notice-warning"><p>%s</p></div>',
					esc_html__( 'Could not establish a connection to Imageshop.', 'imageshop' )
				);
			}
		} catch ( \Exception $e ) {
			$response['message'] = sprintf(
				'<div class="notice notice-error"><p>%s</p></div>',
				sprintf(
					// translators: 1: Error message. 2: Error code.
					esc_html__( 'Could not establish a connection: %1$s (%2$d)', 'imageshop' ),
					$e->getMessage(),
					$e->getCode()
				)
			);
		}

		return new \WP_REST_Response( $response );
	}

}
