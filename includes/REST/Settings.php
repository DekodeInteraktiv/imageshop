<?php

namespace Imageshop\WordPress\REST;

use Imageshop\WordPress\REST_Controller;
use WP_REST_Request;
use WP_REST_Response;

class Settings {

	public function __construct() {
		\add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
	}

	public function register_endpoints() {
		\register_rest_route(
			'imageshop/v1',
			'/settings',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_settings' ],
				'permission_callback' => '__return_true',
			]
		);

		\register_rest_route(
			'imageshop/v1',
			'/settings',
			[
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_settings' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	private function get_settings_api_response() {
		$imageshop = REST_Controller::get_instance();

		$api_key           = \get_option( 'imageshop_api_key', '' );
		$default_interface = \get_option( 'imageshop_upload_interface', '' );
		$disable_srcset    = \get_option( 'imageshop_disable_srcset', 'no' );
		$webp_support      = \get_option( 'imageshop_webp_support', 'no' );

		$valid_api = ! empty( $api_key ) && $imageshop->test_valid_token();

		$interfaces = [];
		if ( $valid_api ) {
			$interfaces = $imageshop->get_interfaces( true );
		}

		return [
			'api_key'           => $api_key,
			'original_api_key'  => $api_key,
			'default_interface' => $default_interface,
			'disable_srcset'    => $disable_srcset,
			'webp_support'      => $webp_support,
			'has_valid_api_key' => $valid_api,
			'interfaces'        => $interfaces,
		];
	}

	public function get_settings() {
		return new WP_REST_Response( $this->get_settings_api_response(), 200 );
	}

	public function update_settings( WP_REST_Request $request ) {
		$api_key           = $request->get_param( 'api_key' );
		$default_interface = $request->get_param( 'default_interface' );
		$disable_srcset    = $request->get_param( 'disable_srcset' );
		$webp_support      = $request->get_param( 'webp_support' );

		if ( ! empty( $api_key ) ) {
			\update_option( 'imageshop_api_key', $api_key );
		}

		if ( ! empty( $default_interface ) ) {
			\update_option( 'imageshop_upload_interface', $default_interface );
		}

		if ( ! is_null( $disable_srcset ) ) {
			\update_option( 'imageshop_disable_srcset', $disable_srcset );
		}

		if ( ! is_null( $webp_support ) ) {
			\update_option( 'imageshop_webp_support', $webp_support );
		}

		return new WP_REST_Response( [
			'success'  => true,
			'message'  => __( 'Your settings have been saved.', 'imageshop-dam-connector' ),
			'settings' => $this->get_settings_api_response(),
		], 200 );
	}

}
