import {useState,useEffect} from "react";
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import Sections, {Section, SectionDescription, SectionHeader, SectionTitle} from "../../components/Sections";
import Infobox from "../../components/Infobox";

export default function Account( { settings, updateSettings } ) {
	const [ showKeyValidator, setShowKeyValidator ] = useState( false );

	const testApiKey = () => {
		apiFetch( {
			path: '/imageshop/v1/settings/test-connection',
			method: 'POST',
			data: {
				token: settings.api_key
			}
		} )
			.then( function( response ) {
				imageshopMessage.innerHTML = response.message;
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
								</>
							}
						</div>
					</div>
				</Section>

				<Section>
					<SectionHeader>
						<SectionTitle>
							{ __( 'Imageshop interfaces', 'imageshop-dam-connector' ) }
						</SectionTitle>
						<SectionDescription>
							{ __( 'When uploading files to WordPress, the files are automatically stored in your preferred interface on the Imageshop platform. If you wish to keep your website media separate, you can always reach out to have a dedicated WordPress interface created for you.', 'imageshop-dam-connector' ) }
						</SectionDescription>
					</SectionHeader>

					<div className="grid grid-cols-1 gap-2">
						<label htmlFor="default_interface">{ __( 'Default interface:', 'imageshop-dam-connector' ) }</label>
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
				</Section>
			</Sections>
		</div>
	)
}
