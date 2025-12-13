import {useState,useEffect} from "react";
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import Sections, {Section, SectionDescription, SectionHeader, SectionTitle} from "../../components/Sections";
import Infobox from "../../components/Infobox";

export default function Account( { settings, updateSettings } ) {
	const [ showKeyValidator, setShowKeyValidator ] = useState( false );

	const [ imageshopMessage, setImageshopMessage ] = useState( '' );

	const testApiKey = () => {
		apiFetch( {
			path: '/imageshop/v1/settings/test-connection',
			method: 'POST',
			data: {
				token: settings.api_key
			}
		} )
			.then( function( response ) {
				setImageshopMessage( response.message );
			} );
	}

	useEffect( () => {
		// Reset the key validator when settings change
		setShowKeyValidator( settings.api_key !== settings.original_api_key );
	}, [ settings ] );

	const updateApiKey = ( newKey ) => {
		updateSettings( { api_key: newKey } );

		setShowKeyValidator( newKey !== settings.original_api_key );
	}

	const updateImageshopUploadPreference = ( newState ) => {
		updateSettings( { upload_to_imageshop: newState ? 'yes' : 'no' } );
	}

	return (
		<div>
			<Infobox className="grid grid-cols-1 gap-2">
				<p className="m-0!">
					{ __( 'The Imageshop plugin will automatically replace your Media Library with the Imageshop media bank, giving you direct access to your organizations entire media portfolio.', 'imageshop-dam-connector' ) }
				</p>

				<p className="m-0!">
					{ __( 'To make use of the Imageshop services, you will need to register for an account. Create a new Imageshop account, or view your account details.', 'imageshop-dam-connector' ) }
				</p>
			</Infobox>

			<Sections>
				<Section>
					<SectionHeader>
						<SectionTitle>
							{ __( 'Imageshop account', 'imageshop-dam-connector' ) }
						</SectionTitle>
						<SectionDescription>
							{ __( 'Your Imageshop key is used to authenticate your account and connect to the Imageshop services.', 'imageshop-dam-connector' ) }
						</SectionDescription>
					</SectionHeader>

					<div className="grid grid-cols-1 gap-2">
						<label htmlFor="api_key">{ __( 'Imageshop Key:', 'imageshop-dam-connector' ) }</label>
						<div className="grid grid-cols-1 gap-1">
							<input
								type="text"
								placeholder={ __( 'Enter your Imageshop key', 'imageshop-dam-connector' ) }
								className="w-full"
								id="api_key"
								value={ settings.api_key }
								onChange={ ( event ) => updateApiKey( event.target.value ) }
							/>
							{ showKeyValidator &&
								<>
									<button
										className="bg-primary text-white px-4 py-2 rounded-md hover:bg-accent/40 hover:text-black cursor-pointer transition-colors"
										onClick={ testApiKey }
									>
										{ __( 'Test the modified API key', 'imageshop-dam-connector' ) }
									</button>
									<span className="italic">
										You should always test a new API key before clicking through to store your changes.
									</span>
									{ imageshopMessage &&
										<div dangerouslySetInnerHTML={ { __html: imageshopMessage } } />
									}
								</>
							}
						</div>
					</div>
				</Section>

				<Section>
					<SectionHeader>
						<SectionTitle>
							{ __( 'Upload images to Imageshop (not recommended)', 'imageshop-dam-connector' ) }
						</SectionTitle>
						<SectionDescription>
							{ __( 'Even if you use the Imageshop integration on your site, you can continue to upload images directly to WordPress. You also have the option to have these images added to Imageshop. However, this is not something we recommend.', 'imageshop-dam-connector' ) }
						</SectionDescription>
						<SectionDescription className="font-semibold">
							{ __( 'To ensure your image archive maintains the highest quality possible, we recommend that only files that will be used across multiple channels are uploaded directly to Imageshop for distribution.', 'imageshop-dam-connector' ) }
						</SectionDescription>
						<SectionDescription>
							{ __( 'Files that will only be used on your WordPress site will then not clutter your Imageshop archive, and for this reason we recommend keeping this option unchecked.', 'imageshop-dam-connector' ) }
						</SectionDescription>

						<SectionDescription className="italic">
							{ __( 'This setting does not affect existing images, and is disabled by default.', 'imageshop-dam-connector' ) }
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

					{ settings.upload_to_imageshop === 'yes' &&
						<>
							<SectionHeader>
								<SectionTitle subtitle={true}>
									{ __( 'Imageshop interfaces', 'imageshop-dam-connector' ) }
								</SectionTitle>
								<SectionDescription>
									{ __( 'If you still choose to have your media files uploaded to Imageshop via WordPress, you need to choose which interface these files will be saved to by default. It is recommended to put in a request with Imageshop to have a dedicated interface created for this, usually with a name of "WordPress".', 'imageshop-dam-connector' ) }
								</SectionDescription>
							</SectionHeader>

							<div className="grid grid-cols-1 gap-2">
								<label className="sr-only" htmlFor="default_interface">{ __( 'Default interface:', 'imageshop-dam-connector' ) }</label>
								<select className="w-full" id="default_interface">
									<option value="">&mdash; { __( 'Select your default interface', 'imageshop-dam-connector' ) } &mdash;</option>
									{ settings?.interfaces && settings?.interfaces?.map( ( apiInterface ) => (
										<option
											key={ apiInterface.Id }
											value={ apiInterface.Id }
											selected={ parseInt( settings.default_interface ) === apiInterface.Id }
										>
											{ apiInterface.Name }
										</option>
									) ) }
								</select>
							</div>
						</>
					}
				</Section>
			</Sections>
		</div>
	)
}
