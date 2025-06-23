import React, { useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';
import { SelectControl } from '@wordpress/components';

const Interfaces = ( { setStep } ) => {
	const [ apiInterface, setApiInterface ] = useState();
	const [ availableInterfaces, setAvailableInterfaces ] = useState( [] );

	const getInterfaces = () => {
		apiFetch( { path: '/imageshop/v1/onboarding/interfaces' } )
			.then( ( response ) => {
				const interfaces = [];

				// Add non-valued interface to the option dropdown.
				interfaces.push( {
					value: '',
					label: __( 'Select an interface', 'imageshop-dam-connector' ),
				} );

				response.interfaces.map( ( single ) => {
					const singleInterface = {
						value: single.Id,
						label: single.Name
					}

					interfaces.push( singleInterface );
				} );

				setAvailableInterfaces( interfaces );
			} )
	}

	useEffect( () => {
		getInterfaces()
	}, [] );

	useEffect( () => {
		if ( ! apiInterface ) {
			return;
		}

		apiFetch(
			{
				path: '/imageshop/v1/onboarding/interfaces',
				method: 'POST',
				data: {
					interface: apiInterface
				}
			}
		);
	}, [ apiInterface ] );

	return (
		<>
			<div className="p-4">
				<p>
					{ __( 'When uploading files to WordPress, the files are automatically stored in your preferred interface on the Imageshop platform. If you wish to keep your website media separate, you can always reach out to have a dedicated WordPress interface created for you.', 'imageshop-dam-connector' ) }
				</p>

				<SelectControl
					label={ __( 'Select which interface is used for uploaded media files', 'imageshop-dam-connector' ) }
					options={ availableInterfaces }
					onChange={ ( selection ) => setApiInterface( selection ) }
					className="w-full"
				/>
			</div>

			<div className="bg-gray-50 p-4 rounded-b-lg">
				<div className="flex justify-end gap-4">
					{ ! apiInterface &&
						<span className="italic">
							{ __( 'Please select an interface before proceeding.', 'imageshop-dam-connector' ) }
						</span>
					}
					<button type="button" disabled={ ! apiInterface } className="cursor-pointer bg-primary py-2 px-4 text-white rounded-md hover:bg-accent" onClick={ () => setStep( 4 ) }>
						{ __( 'Next step', 'imageshop-dam-connector' ) }
					</button>
				</div>
			</div>
		</>
	)
}

export default Interfaces;
