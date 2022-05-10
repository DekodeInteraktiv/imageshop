<?php
/**
 * REST Controller class.
 */

declare( strict_types = 1 );

namespace Imageshop\WordPress;

/**
 * Class REST_Controller
 */
class REST_Controller {
	private const IMAGESHOP_API_BASE_URL        = 'https://api.imageshop.no';
	private const IMAGESHOP_API_CAN_UPLOAD      = '/Login/CanUpload';
	private const IMAGESHOP_API_WHOAMI          = '/Login/WhoAmI';
	private const IMAGESHOP_API_CREATE_DOCUMENT = '/Document/CreateDocument';
	private const IMAGESHOP_API_GET_DOCUMENT    = '/Document/GetDocumentById';
	private const IMAGESHOP_API_DOWNLOAD        = '/Download';
	private const IMAGESHOP_API_GET_PERMALINK   = '/Permalink/GetPermalink';
	private const IMAGESHOP_API_GET_INTERFACE   = '/Interface/GetInterfaces';
	private const IMAGESHOP_API_GET_SEARCH      = '/Search2';
	private const IMAGESHOP_API_GET_CATEGORIES  = '/Category/GetCategoriesTree';

	/**
	 * @var REST_Controller
	 */
	private static $instance;

	/**
	 * @var string
	 */
	private string $api_token;

	/**
	 * @var string
	 */
	private string $language = 'en';

	public $interfaces;


	/**
	 * Class constructor.
	 *
	 * @param string $api_token Optional. An Imageshop API token.
	 */
	public function __construct( $token = null ) {
		if ( null !== $token ) {
			$this->api_token = $token;
		} else {
			$this->api_token = \get_option( 'imageshop_api_key' );
		}

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register WordPress REST API endpoints.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'imageshop/v1',
			'/categories/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_get_categories' ),
				'args'                => array(
					'id' => array(
						'validate_callback' => function( $param ) {
							return is_numeric( $param ) || 'all' === $param;
						},
					),
				),
				'permission_callback' => function() {
					return current_user_can( 'upload_files' );
				},
			)
		);
	}

	/**
	 * WordPress REST API endpoint for getting available Imageshop categories.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_get_categories( \WP_REST_Request $request ) {
		$interface = $request->get_param( 'id' );

		$categories = $this->get_categories( $interface );

		return new \WP_REST_Response( $categories, 200 );
	}

	/**
	 * A collection of headers to be used with Imageshop API requests.
	 *
	 * @return array
	 */
	public function get_headers(): array {
		return array(
			'Accept'       => 'application/json',
			'token'        => $this->api_token,
			'Content-Type' => 'application/json',
		);
	}

	/**
	 * Perform a request against the Imageshop API.
	 *
	 * @param string $url  The API endpoint to request.
	 * @param array  $args A JSON encoded string of arguments for the API call.
	 *
	 * @return array|mixed
	 */
	public function execute_request( string $url, array $args ) {
		try {
			$response      = wp_remote_request( $url, $args );
			$response_code = wp_remote_retrieve_response_code( $response );

			if ( ! in_array( $response_code, array( 200, 201 ), true ) ) {
				return array(
					'code'    => wp_remote_retrieve_response_code( $response ),
					'message' => wp_remote_retrieve_response_message( $response ),
				);
			}

			return json_decode( wp_remote_retrieve_body( $response ) );
		} catch ( \Exception $e ) {
			return array(
				'code'    => $e->getCode(),
				'message' => $e->getMessage(),
			);
		}
	}

	/**
	 * Validate that the current API user can upload files to Imageshop.
	 *
	 * @return mixed|void
	 */
	public function can_upload() {
		$args = array(
			'method'  => 'GET',
			'headers' => $this->get_headers(),
		);

		return $this->execute_request( self::IMAGESHOP_API_BASE_URL . self::IMAGESHOP_API_CAN_UPLOAD, $args );
	}

	/**
	 * Create a new document with Imageshop.
	 *
	 * Creating a document is the same as uploading a file, and pushes a base64 encoded
	 * version of the file to the Imageshop services for processing.
	 *
	 * @param string $b64_file_content Base64 encoded file content.
	 * @param string $name             Name of the file.
	 *
	 * @return array|mixed
	 */
	public function create_document( $b64_file_content, $name ) {
		$pyload = array(
			'bFile'         => $b64_file_content,
			'fileName'      => str_replace( '/', '_', $name ),
			'interfaceName' => get_option( 'imageshop_upload_interface' ),
			'doc'           => array(
				'Active' => true,
			),
		);

		$args = array(
			'method'  => 'POST',
			'headers' => $this->get_headers(),
			'body'    => json_encode( $pyload ),
		);

		return $this->execute_request( self::IMAGESHOP_API_BASE_URL . self::IMAGESHOP_API_CREATE_DOCUMENT, $args );
	}

	/**
	 * Get the permalink for an image on the Imageshop CDN.
	 *
	 * @param int $document_id The Imageshop document ID.
	 * @param int $width       The width of the image.
	 * @param int $height      The height of the image.
	 *
	 * @return mixed
	 */
	public function get_permalink( $document_id, $width, $height ) {
		$payload = array(
			'language'        => $this->language,
			'documentid'      => $document_id,
			'cropmode'        => 'ZOOM',
			'width'           => $width,
			'height'          => $height,
			'x1'              => 0,
			'y1'              => 0,
			'x2'              => 100,
			'y2'              => 100,
			'previewwidth'    => 100,
			'previewheight'   => 100,
			'optionalurlhint' => site_url( '/' ),
		);

		$args = array(
			'method'  => 'POST',
			'headers' => $this->get_headers(),
			'body'    => json_encode( $payload ),
		);
		$ret  = $this->execute_request( self::IMAGESHOP_API_BASE_URL . self::IMAGESHOP_API_GET_PERMALINK, $args );
		return $ret->permalinktoken;
	}

	/**
	 * Return a list of interfaces available to the given API user.
	 *
	 * @return array
	 */
	public function get_interfaces() {
		if ( empty( $this->interfaces ) ) {
			$interfaces = \get_transient( 'imageshop_interfaces' );

			if ( false === $interfaces ) {
				$args    = array(
					'method'  => 'GET',
					'headers' => $this->get_headers(),
				);
				$request = $this->execute_request( self::IMAGESHOP_API_BASE_URL . self::IMAGESHOP_API_GET_INTERFACE, $args );

				if ( \is_wp_error( $request ) ) {
					return $this->interfaces;
				}

				$interfaces = $request;

				\set_transient( 'imageshop_interfaces', $interfaces, HOUR_IN_SECONDS );
			}

			$this->interfaces = $interfaces;
		}

		return $this->interfaces;
	}

	/**
	 * Get a list of available categories for the given interface and language.
	 *
	 * @param int|null $interface The interface to return categories from.
	 * @param string   $lang      The language to return categories for.
	 *
	 * @return array
	 */
	public function get_categories( $interface = null, $lang = 'no' ) {
		if ( null === $interface ) {
			$interface = \get_option( 'imageshop_upload_interface' );
		}

		$transient_key = 'imageshop_categories_' . $interface . '_' . $lang;

		$categories = \get_transient( $transient_key );

		if ( false === $categories ) {
			$args    = array(
				'method'  => 'GET',
				'headers' => $this->get_headers(),
			);
			$request = $this->execute_request(
				\add_query_arg(
					array(
						'interfacename' => $interface,
						'language'      => $lang,
					),
					self::IMAGESHOP_API_BASE_URL . self::IMAGESHOP_API_GET_CATEGORIES
				),
				$args
			);

			if ( \is_wp_error( $request ) ) {
				return array();
			}

			$categories = $request;

			\set_transient( $transient_key, $categories, HOUR_IN_SECONDS );
		}

		return $categories->Root->Children; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$categories->Root->Children` is provided by the SaaS API.
	}

	/**
	 * Perform a document search on the Imageshop service.
	 *
	 * @param array $attributes An array of search criteria.
	 *
	 * @return array
	 */
	public function search( array $attributes ) {
		$interface_ids  = array();
		$interface_list = $this->get_interfaces();

		foreach ( $interface_list as $interface ) {
			$interface_ids[] = $interface->Id; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$interface->Id` is defined by the third party SaaS API.
		}

		// set default search attributes
		$attributes = \array_merge(
			array(
				'InterfaceIds'  => $interface_ids,
				'Language'      => 'no',
				'Querystring'   => '',
				'Page'          => 0,
				'Pagesize'      => 80,
				'DocumentType'  => 'IMAGE',
				'SortBy'        => 'DEFAULT',
				'SortDirection' => 'DESC',
			),
			$attributes
		);

		$args    = array(
			'method'  => 'POST',
			'headers' => $this->get_headers(),
			'body'    => json_encode( $attributes ),
		);
		$results = $this->execute_request( self::IMAGESHOP_API_BASE_URL . self::IMAGESHOP_API_GET_SEARCH, $args );

		if ( \is_wp_error( $results ) ) {
			return array();
		}

		return $results;
	}

	/**
	 * Get the details of a document from Imageshop by its ID.
	 *
	 * @param int $id The Imageshop document ID.
	 *
	 * @return array|mixed|object
	 */
	public function get_document( $id ) {
		$url  = \add_query_arg(
			array(
				'language'   => 'no',
				'DocumentID' => $id,
			),
			self::IMAGESHOP_API_BASE_URL . self::IMAGESHOP_API_GET_DOCUMENT
		);
		$args = array(
			'method'  => 'GET',
			'headers' => $this->get_headers(),
		);

		$ret = $this->execute_request( $url, $args );

		if ( \is_wp_error( $ret ) ) {
			return (object) array();
		}

		return $ret;
	}

	/**
	 * Test if the active API token is valid.
	 *
	 * @return bool
	 */
	public function test_valid_token() {

		$args = array(
			'method'  => 'GET',
			'headers' => $this->get_headers(),
		);

		$ret = $this->execute_request( self::IMAGESHOP_API_BASE_URL . self::IMAGESHOP_API_WHOAMI, $args );

		return ( ! \is_wp_error( $ret ) && ! isset( $ret['code'] ) );
	}

	/**
	 * Return a singleton instance of this class.
	 *
	 * @return self
	 */
	public static function get_instance(): REST_Controller {
		if ( ! self::$instance ) {
			self::$instance = new REST_Controller();
		}

		return self::$instance;
	}

	/**
	 * Request a download source for an Imageshop document.
	 *
	 * @param int $document_id The ID of the document to be downloaded.
	 *
	 * @return array|mixed
	 */
	public function download( $document_id ) {
		$payload = array(
			'DocumentId'           => $document_id,
			'Quality'              => 'OriginalFile',
			'DownloadAsAttachment' => false,
		);
		$args    = array(
			'method'  => 'POST',
			'headers' => $this->get_headers(),
			'body'    => json_encode( $payload ),
		);

		return $this->execute_request( self::IMAGESHOP_API_BASE_URL . self::IMAGESHOP_API_DOWNLOAD, $args );
	}
}
