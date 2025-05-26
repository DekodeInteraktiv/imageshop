import {useState,useEffect} from "react";
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

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
		<div className="imageshop-account">
			<p>
				{ __( 'The Imageshop plugin will automatically replace your Media Library with the Imageshop media bank, giving you direct access to your organizations entire media portfolio.', 'imageshop-dam-connector' ) }
			</p>

			<p>
				{ __( 'To make use of the Imageshop services, you will need to register for an account. Create a new Imageshop account, or view your account details.', 'imageshop-dam-connector' ) }
			</p>

			<h2>
				{ __( 'Connection settings', 'imageshop-dam-connector' ) }
			</h2>

			<div className="grid grid-cols-1 gap-4 w-full md:max-w-lg">
				<div className="grid grid-cols-1 gap-2">
					<label>{ __( 'Imageshop Key:', 'imageshop-dam-connector' ) }</label>
					<div className="grid grid-cols-1 gap-1">
						<input
							type="text"
							placeholder={ __( 'Enter your Imageshop key', 'imageshop-dam-connector' ) }
							className="w-full"
							value={ settings.api_key }
							onChange={ ( event ) => updateApiKey( event.target.value ) }
							/>
						{ showKeyValidator &&
							<>
								<button
									className="bg-primary text-white px-4 py-2 rounded-md hover:bg-accent cursor-pointer transition-colors"
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

					<span className="italic">
						{ __( 'Your Imageshop key is used to authenticate your account and connect to the Imageshop services.', 'imageshop-dam-connector' ) }
					</span>
				</div>

				<div className="grid grid-cols-1 gap-2">
					<label>{ __( 'Default interface:', 'imageshop-dam-connector' ) }</label>
					<select className="w-full">
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

					<span className="italic">
						{ __( 'The default interface is where any media uploaded directly via WordPress is stored in Imageshop.', 'imageshop-dam-connector' ) }
					</span>
				</div>
			</div>
		</div>
	)
}
