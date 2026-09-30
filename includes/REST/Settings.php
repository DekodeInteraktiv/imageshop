<?php

namespace Imageshop\WordPress\REST;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

use Imageshop\WordPress\API\REST_Controller;
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
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		\register_rest_route(
			'imageshop/v1',
			'/settings',
			[
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_settings' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => [
					'api_key'             => [
						'type'        => 'string',
						'description' => __( 'The API key for the Imageshop account.', 'imageshop-dam-connector' ),
						'required'    => false,
					],
					'default_interface'   => [
						'type'              => [ 'integer', 'null' ],
						'description'       => __( 'The default interface to use for uploads.', 'imageshop-dam-connector' ),
						'required'          => false,
						'validate_callback' => function ( $param, $request, $key ) {
							if ( empty( $param ) ) {
								return true;
							}
							if ( ! filter_var( $param, FILTER_VALIDATE_INT ) ) {
								return new \WP_Error(
									'imageshop_invalid_param',
									__( 'Default interface: An invalid value was used, please try choosing another interface.', 'imageshop-dam-connector' ),
									[ 'status' => 400 ]
								);
							}

							return true;
						},
						'sanitize_callback' => function ( $param, $request, $key ) {
							if ( empty( $param ) ) {
								return null;
							}
							return \absint( $param );
						},
					],
					'disable_srcset'      => [
						'type'              => [ 'string', 'null' ],
						'description'       => __( 'Whether to disable srcset generation.', 'imageshop-dam-connector' ),
						'required'          => false,
						'validate_callback' => function ( $param, $request, $key ) {
							if ( empty( $param ) ) {
								return true;
							}
							$valid_values = [ 'yes', 'no' ];
							if ( ! in_array( $param, $valid_values, true ) ) {
								return new \WP_Error(
									'imageshop_invalid_param',
									__( 'Disable srcset: An unrecognized value was used, please try again.', 'imageshop-dam-connector' ),
									[ 'status' => 400 ]
								);
							}

							return true;
						},
						'sanitize_callback' => function ( $param, $request, $key ) {
							if ( is_null( $param ) ) {
								$param = 'no';
							}
							return $param;
						},
					],
					'webp_support'        => [
						'type'              => [ 'string', 'null' ],
						'description'       => __( 'Whether to enable WebP support.', 'imageshop-dam-connector' ),
						'required'          => false,
						'validate_callback' => function ( $param, $request, $key ) {
							if ( empty( $param ) ) {
								return true;
							}
							$valid_values = [ 'yes', 'no' ];
							if ( ! in_array( $param, $valid_values, true ) ) {
								return new \WP_Error(
									'imageshop_invalid_param',
									__( 'WebP Images: An unrecognized value was used, please try again.', 'imageshop-dam-connector' ),
									[ 'status' => 400 ]
								);
							}

							return true;
						},
						'sanitize_callback' => function ( $param, $request, $key ) {
							if ( is_null( $param ) ) {
								$param = 'yes';
							}
							return $param;
						},
					],
					'upload_to_imageshop' => [
						'type'              => [ 'string', 'null' ],
						'description'       => __( 'Whether to upload images to Imageshop when added to WordPress.', 'imageshop-dam-connector' ),
						'required'          => false,
						'validate_callback' => function ( $param, $request, $key ) {
							if ( empty( $param ) ) {
								return true;
							}
							$valid_values = [ 'yes', 'no' ];
							if ( ! in_array( $param, $valid_values, true ) ) {
								return new \WP_Error(
									'imageshop_invalid_param',
									__( 'Upload images to Imageshop: An unrecognized value was used, please try again.', 'imageshop-dam-connector' ),
									[ 'status' => 400 ]
								);
							}

							return true;
						},
						'sanitize_callback' => function ( $param, $request, $key ) {
							if ( is_null( $param ) ) {
								$param = 'no';
							}
							return $param;
						},
					],
					'cdn_domain'          => [
						'type'              => 'string',
						'description'       => __( 'A custom CDN domain to use for serving images.', 'imageshop-dam-connector' ),
						'required'          => false,
						'validate_callback' => function ( $param, $request, $key ) {
							if ( empty( $param ) ) {
								return true;
							}
							if ( false === \filter_var( $param, FILTER_VALIDATE_DOMAIN ) ) {
								return new \WP_Error(
									'imageshop_invalid_param',
									__( 'Custom CDN Domain: The domain you entered was not recognized, please try again.', 'imageshop-dam-connector' ),
									[ 'status' => 400 ]
								);
							}

							return true;
						},
						'sanitize_callback' => function ( $param, $request, $key ) {
							if ( empty( $param ) ) {
								return $param;
							}

							// Ensure the scheme is part of the URL.
							if ( ! preg_match( '/^http(s)?:\/\//', $param ) ) {
								$param = 'https://' . $param;
							}
							return \filter_var( $param, FILTER_SANITIZE_URL );
						},
					],
				],
			]
		);
	}

	private function get_settings_api_response() {
		$imageshop = REST_Controller::get_instance();

		$api_key             = self::get( 'api_key' );
		$default_interface   = self::get( 'upload_interface' );
		$disable_srcset      = self::get( 'disable_srcset' );
		$webp_support        = self::get( 'webp_support' );
		$upload_to_imageshop = self::get( 'upload_to_imageshop' );
		$cdn_domain          = self::get( 'cdn_domain' );

		$valid_api = ! empty( $api_key ) && $imageshop->test_valid_token();

		$interfaces = [];
		if ( $valid_api ) {
			$interfaces = $imageshop->get_interfaces( true );
		}

		return [
			'api_key'             => $api_key,
			'original_api_key'    => $api_key,
			'default_interface'   => $default_interface,
			'disable_srcset'      => $disable_srcset,
			'webp_support'        => $webp_support,
			'upload_to_imageshop' => $upload_to_imageshop,
			'has_valid_api_key'   => $valid_api,
			'interfaces'          => $interfaces,
			'cdn_domain'          => $cdn_domain,
		];
	}

	public function get_settings() {
		return new WP_REST_Response( $this->get_settings_api_response(), 200 );
	}

	public function update_settings( WP_REST_Request $request ) {
		global $wpdb;

		$api_key             = $request->get_param( 'api_key' );
		$default_interface   = $request->get_param( 'default_interface' );
		$disable_srcset      = $request->get_param( 'disable_srcset' );
		$webp_support        = $request->get_param( 'webp_support' );
		$upload_to_imageshop = $request->get_param( 'upload_to_imageshop' );
		$cdn_domain          = $request->get_param( 'cdn_domain' );

		if ( ! empty( $api_key ) ) {
			\update_option( 'imageshop_api_key', $api_key );
		}

		if ( ! empty( $default_interface ) ) {
			\update_option( 'imageshop_upload_interface', $default_interface );
		}

		if ( ! is_null( $disable_srcset ) ) {
			\update_option( 'imageshop_disable_srcset', $disable_srcset );
		}

		if ( ! is_null( $webp_support ) && self::get( 'webp_support' ) !== $webp_support ) {
			\update_option( 'imageshop_webp_support', $webp_support );

			// Remove old and potentially invalid meta keys.
			$wpdb->query( 'DELETE FROM `' . $wpdb->postmeta . '` WHERE `meta_key` IN ( \'_imageshop_permalinks\', \'_imageshop_media_sizes\' )' );

			// Flush cache values to ensure no stale data is hanging around.
			if ( \wp_cache_supports( 'flush_group' ) ) {
				\wp_cache_flush_group( 'post_meta' );
			} else {
				\wp_cache_flush();
			}
		}

		if ( ! is_null( $upload_to_imageshop ) ) {
			\update_option( 'imageshop_upload_to_imageshop', $upload_to_imageshop );
		}

		if ( ! is_null( $cdn_domain ) ) {
			if ( empty( $cdn_domain ) ) {
				\delete_option( 'imageshop_cdn_domain' );
			} else {
				\update_option( 'imageshop_cdn_domain', \untrailingslashit( $cdn_domain ) );
			}
		}

		return new WP_REST_Response(
			[
				'success'  => true,
				'message'  => __( 'Your settings have been saved.', 'imageshop-dam-connector' ),
				'settings' => $this->get_settings_api_response(),
			],
			200
		);
	}

	/**
	 * @param $setting
	 *
	 * @return false|mixed|null
	 */
	public static function get( $setting ) {
		$defaults = [
			'imageshop_api_key'             => '',
			'imageshop_upload_interface'    => '',
			'imageshop_disable_srcset'      => 'no',
			'imageshop_webp_support'        => 'yes',
			'imageshop_upload_to_imageshop' => 'no',
			'imageshop_cdn_domain'          => 'https://v.imgi.no',
		];

		$setting = sprintf(
			'imageshop_%s',
			$setting
		);

		if ( ! isset( $defaults[ $setting ] ) ) {
			return null;
		}

		return \get_option( $setting, $defaults[ $setting ] );
	}
}
