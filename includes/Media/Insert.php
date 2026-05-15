<?php

declare(strict_types=1);

namespace Imageshop\WordPress\Media;

use Imageshop\WordPress\API\REST_Controller;
use Imageshop\WordPress\Imageshop;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Search
 */
class Insert {
	private $attachment;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		if ( Imageshop::onboarding_completed() ) {
			$this->attachment = Attachment::get_instance();

			\add_filter( 'rest_prepare_attachment', array( $this, 'rest_image_override' ), 10, 2 );
		}
	}

	/**
	 * Modify the attachment post object response.
	 *
	 * @param \WP_REST_Response $response The response object.
	 * @param \WP_Post          $post     The original attachment post.
	 *
	 * @return mixed
	 */
	public function rest_image_override( $response, $post ) {
		if ( 'attachment' !== $post->post_type ) {
			return $response;
		}
		if ( ! $post->_imageshop_document_id ) {
			return $response;
		}

		$media_details = $post->_imageshop_media_sizes;

		if ( empty( $media_details ) ) {
			$media_details = $this->attachment->generate_imageshop_metadata( $post );
		}

		$response->data['media_details'] = $media_details;

		/*
		 * Trigger WP_Cron.
		 *
		 * Any new image sizes that may be needed should have been scheduled at this point
		 * so instructing WordPress to run the cron system should generate image sizes in
		 * the most timely manner for the editorial needs.
		 */
		\spawn_cron();

		return $response;
	}
}
