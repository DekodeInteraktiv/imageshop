import { __ } from '@wordpress/i18n';
import Sections, {Section, SectionDescription, SectionHeader, SectionTitle} from "../../components/Sections";

export default function Advanced( { settings, updateSettings } ) {

	const updateSrcsetSetting = ( newState ) => {
		updateSettings( { disable_srcset: newState ? 'yes' : 'no' } );
	}

	const updateWebpSupportSetting = ( newState ) => {
		updateSettings( { webp_support: newState ? 'yes' : 'no' } )
	}

	const updateImageshopUploadPreference = ( newState ) => {
		updateSettings( { upload_to_imageshop: newState ? 'yes' : 'no' } );
	}

	return (
		<div>
			<Sections>
				<Section>
					<SectionHeader>
						<SectionTitle>
							{ __( 'WebP image support', 'imageshop-dam-connector' ) }
						</SectionTitle>
						<SectionDescription>
							{ __( 'WebP is a modern image format that can significantly reduce file sizes while maintaining quality. This setting allows you to enable or disable WebP support in your media library.', 'imageshop-dam-connector' ) }
						</SectionDescription>
					</SectionHeader>

					<div>
						<label className="inline-block py-2">
							<input
								type="checkbox"
								checked={ settings.webp_support === 'yes' }
								onChange={ ( event ) => updateWebpSupportSetting( event.target.checked ) }
							/>
							<span>
								{ __( 'Enable WebP image support', 'imageshop-dam-connector' ) }
							</span>
						</label>
					</div>

				</Section>

				<Section>
					<SectionHeader>
						<SectionTitle>
							{ __( 'Extended srcset attributes', 'imageshop-dam-connector' ) }
						</SectionTitle>
						<SectionDescription>
							{ __( 'Some users may experience performance issues when working with pages containing many manually added images that have not been processed by WordPress or Imageshop. This option will disable the extra processing performed to guarantee srcset attributes are applied.', 'imageshop-dam-connector' ) }
						</SectionDescription>
						<SectionDescription className="italic">
							{ __( 'Note that this may cause SEO warnings where large images are being loaded on your site, and should only be used if you are experiencing explicit performance issues.', 'imageshop-dam-connector' ) }
						</SectionDescription>
					</SectionHeader>

					<div>
						<label className="inline-block py-2">
							<input
								type="checkbox"
								checked={ settings.disable_srcset === 'yes' }
								onChange={ ( event ) => updateSrcsetSetting( event.target.checked ) }
							/>
							<span>
								{ __( 'Disable extended srcset attributes', 'imageshop-dam-connector' ) }
							</span>
						</label>
					</div>
				</Section>

				<Section>
					<SectionHeader>
						<SectionTitle>
							{ __( 'Upload images to Imageshop', 'imageshop-dam-connector' ) }
						</SectionTitle>
						<SectionDescription>
							{ __( 'Choose if you would like any new images you upload in WordPress to also be uploaded and served by Imageshop.', 'imageshop-dam-connector' ) }
						</SectionDescription>
						<SectionDescription>
							{ __( 'Some organizations may prefer to keep their Imageshop DAM interfaces clear of any assets intended purely for website use, this setting allows them to disable the automated Imageshop uploads to achieve this.', 'imageshop-dam-connector' ) }
						</SectionDescription>

						<SectionDescription className="italic">
							{ __( 'This setting does not affect existing images, and is enabled by default.', 'imageshop-dam-connector' ) }
						</SectionDescription>

						<div>
							<label className="inline-block py-2">
								<input
									type="checkbox"
									checked={ settings.upload_to_imageshop === 'yes' }
									onChange={ ( event ) => updateImageshopUploadPreference( event.target.checked ) }
								/>
								<span>
								{ __( 'Upload a copy of all images to Imageshop', 'imageshop-dam-connector' ) }
							</span>
							</label>
						</div>
					</SectionHeader>
				</Section>
			</Sections>
		</div>
	)
}
