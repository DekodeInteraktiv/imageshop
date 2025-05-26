import { __ } from '@wordpress/i18n';

export default function Advanced( { settings, updateSettings } ) {

	const updateSrcsetSetting = ( newState ) => {
		updateSettings( { disable_srcset: newState ? 'yes' : 'no' } );
	}

	const updateWebpSupportSetting = ( newState ) => {
		updateSettings( { webp_support: newState ? 'yes' : 'no' } )
	}

	return (
		<div className="imageshop-advanced">

			<div className="grid grid-cols-1 gap-2 w-full md:max-w-lg">
				<div className="grid grid-cols-1 gap-2">
					<label>
						<input
							type="checkbox"
							checked={ settings.webp_support === 'yes' }
							onChange={ ( event ) => updateWebpSupportSetting( event.target.checked ) }
						/>
						<span>
							{ __( 'Enable WebP image support', 'imageshop-dam-connector' ) }
						</span>
					</label>

					<p>
						{ __( 'WebP is a modern image format optimized for websites, not all themes support WebP images, but when possible you may gain some performance improvement by enabling this feature.', 'imageshop-dam-connector' ) }
					</p>
				</div>

				<hr />

				<div className="grid grid-cols-1 gap-2">
					<label>
						<input
							type="checkbox"
							checked={ settings.disable_srcset === 'yes' }
							onChange={ ( event ) => updateSrcsetSetting( event.target.checked ) }
						/>
						<span>
							{ __( 'Disable extended srcset attributes', 'imageshop-dam-connector' ) }
						</span>
					</label>

					<p>
						{ __( 'Some users may experience performance issues when working with pages containing many manually added images that have not been processed by WordPress or Imageshop. This option will disable the extra processing performed to guarantee srcset attributes are applied.', 'imageshop-dam-connector' ) }
					</p>
					<p className="italic">
						{ __( 'Note that this may cause SEO warnings where large images are being loaded on your site, and should only be used if you are experiencing explicit performance issues.', 'imageshop-dam-connector' ) }
					</p>
				</div>
			</div>

		</div>
	)
}
