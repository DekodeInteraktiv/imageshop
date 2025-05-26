import { __ } from '@wordpress/i18n';
import { useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import {ExclamationCircleIcon, InformationCircleIcon} from "@heroicons/react/24/solid";

export default function Sync( { settings, refreshSettings } ) {
	const [ syncMessage, setSyncMessage ] = useState( null );

	const handleSyncToImageshop = () => {
		setSyncMessage( null );

		apiFetch( {
			path: '/imageshop/v1/sync/remote',
			method: 'POST'
		} )
			.then( function( response ) {
				setSyncMessage( response.message );
			} );
	}

	const handleSyncToLocal = () => {
		setSyncMessage( null );

		apiFetch( {
			path: '/imageshop/v1/sync/local',
			method: 'POST'
		} )
			.then( function( response ) {
				setSyncMessage( response.message );
			} );
	}

	return (
		<div className="grid grid-cols-1 gap-2 w-full">
			<p>
				{ __( 'The Imageshop plugin will automatically replace your Media Library with the Imageshop media bank, giving you direct access to your organizations entire media portfolio.', 'imageshop-dam-connector' ) }
			</p>

			<p>
				{ __( 'Note that all synchronization actions can take some time to complete, depending on the number of images in your media library or Imageshop account.', 'imageshop-dam-connector' ) }
			</p>

			{ syncMessage && (
				<p className="shadow-lg border-1 border-primary bg-white p-4 rounded-md text-primary flex items-center">
					<InformationCircleIcon className="inline-block w-6 h-6 mr-2 text-primary" />
					{ syncMessage }
				</p>
			) }

			<div>
				<button className="bg-primary text-white px-4 py-2 rounded-md hover:bg-accent cursor-pointer transition-colors">
					{ __( 'Sync WordPress images to the Imageshop cloud', 'imageshop-dam-connector' ) }
				</button>

				<p>
					{ __( 'This will upload all images in your WordPress media library to the Imageshop cloud, ensuring that all your media is available in the Imageshop platform.', 'imageshop-dam-connector' ) }
				</p>
			</div>

			<hr />

			<div>
				<button className="bg-primary text-white px-4 py-2 rounded-md hover:bg-accent cursor-pointer transition-colors">
					{ __( 'Sync Imageshop images to the WordPress media library', 'imageshop-dam-connector' ) }
				</button>

				<p>
					{ __( 'This will download all images used in a post or page in WordPress from the Imageshop cloud to your local media library, ensuring that all your media is available if the plugin is removed.', 'imageshop-dam-connector' ) }
				</p>
			</div>
		</div>
	)
}
