<?php

declare(strict_types=1);

namespace Imageshop\WordPress;

use Imageshop\WordPress\Admin\Dashboard;
use Imageshop\WordPress\Admin\Onboarding;
use Imageshop\WordPress\Media\Attachment;
use Imageshop\WordPress\Compatibility\Polylang;
use Imageshop\WordPress\Media\Insert;
use Imageshop\WordPress\REST\Helpers;
use Imageshop\WordPress\Media\Library;
use Imageshop\WordPress\Media\MimeTypes;
use Imageshop\WordPress\Media\Search;
use Imageshop\WordPress\REST\Settings;
use Imageshop\WordPress\Sync\Sync;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Imageshop {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		new Upgrade();
		new Dashboard();

		$this->init();
		$this->compatibility();
	}

	/**
	 * Initiate all needed classes.
	 */
	public function init() {
		new Helpers();

		new MimeTypes();
		Attachment::get_instance();
		new Library();
		new Onboarding();
		new Search();
		new Insert();
		new Sync();

		new Settings();
	}

	public function compatibility() {
		new Polylang();
	}

	public static function available_locales(): array {
		return array(
			'dk' => array(
				'label'     => esc_html__( 'Danish', 'imageshop-dam-connector' ),
				'default'   => false,
				'iso_codes' => array(
					'da'    => 'string',
					'dk'    => 'string',
					'da_DK' => 'string',
				),
			),
			'en' => array(
				'label'     => esc_html__( 'English', 'imageshop-dam-connector' ),
				'default'   => false,
				'iso_codes' => array(
					'/en_*/' => 'regex',
				),
			),
			'no' => array(
				'label'     => esc_html__( 'Norwegian', 'imageshop-dam-connector' ),
				'default'   => true,
				'iso_codes' => array(
					'nb'    => 'string',
					'nn'    => 'string',
					'nb_NO' => 'string',
					'nn_NO' => 'string',
				),
			),
			'sv' => array(
				'label'     => esc_html__( 'Swedish', 'imageshop-dam-connector' ),
				'default'   => false,
				'iso_codes' => array(
					'se'    => 'string',
					'sv'    => 'string',
					'sv_SE' => 'string',
				),
			),
		);
	}

	/**
	 * Check the onboarding state, and if required settings are in place.
	 *
	 * @return bool
	 */
	public static function onboarding_completed(): bool {
		return ! empty( \get_option( 'imageshop_api_key', false ) );
	}
}
