<?php

declare(strict_types=1);

namespace Imageshop\WordPress\Media;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class MimeTypes {

	public function __construct() {
		\add_filter( 'mime_types', array( $this, 'add_imageshop_mime_types' ) );
	}

	/**
	 * Register additional mime types that the Imageshop integration may return and use within the WordPress media library.
	 *
	 * @param array $wp_get_mime_types
	 *
	 * @return array
	 */
	public function add_imageshop_mime_types( array $wp_get_mime_types ) {
		$imageshop_types = array(
			'svg' => 'image/svg+xml',
		);

		return array_merge( $wp_get_mime_types, $imageshop_types );
	}
}
