import React, { useState } from 'react';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import {ArrowPathIcon} from "@heroicons/react/24/solid";

const Tokens = ( { setStep } ) => {
	const [ token, setToken ] = useState( '' );
	const [ validToken, setValidToken ] = useState( false );
	const [ validationNotice, setValidationNotice ] = useState( '' );
	const [ isTestingToken, setIsTestingToken ] = useState( false );

	const testToken = () => {
		setIsTestingToken( true );
		setValidationNotice( '' );
		setValidToken( false );

		apiFetch( {
			path: '/imageshop/v1/onboarding/token',
			method: 'POST',
			data: {
				token
			}
		} )
			.then( ( response ) => {
				setValidToken( response.valid );
				setValidationNotice( response.message );
			} )
			.catch( ( response ) => {
				setValidToken( response.valid );
				setValidationNotice( response.message );
			} )
			.finally( () => {
				setIsTestingToken( false );
			} );
	}

	return (
		<>
			<div className="p-4">
				<p>
					{ __( 'Enter your API token here. You can find your API token in your ImageShop account.', 'imageshop-dam-connector' ) }
				</p>

				{ validationNotice &&
					<>
						{ validToken &&
							<p className="m-0!">
								{ __( '✔ Your API token is valid.', 'imageshop-dam-connector' ) }
							</p>
						}

						{ ! validToken &&
							<div className="mb-2">
								<p className="m-0!">
									{ __( '❌ Your API token is invalid', 'imageshop-dam-connector' ) }
								</p>
							</div>
						}
					</>
				}

				<div className="relative w-full">
					{ isTestingToken &&
						<div className="absolute top-0 left-0 flex justify-center items-center w-full h-full bg-gray-100">
							<ArrowPathIcon className="inline-block w-6 h-6 animate-spin text-primary" />
						</div>
					}
					<input type="text" className="w-full" value={ token } onChange={ ( e ) => setToken( e.target.value ) } />
					{ validationNotice && ! validToken &&
						<span className="italic text-red-500">
							{ validationNotice }
						</span>
					}
				</div>
			</div>

			<div className="bg-gray-50 p-4 rounded-b-lg">
				<div className="flex justify-end gap-4">
					{ ! validToken &&
						<button type="button" disabled={ isTestingToken } className="cursor-pointer bg-primary py-2 px-4 text-white rounded-md hover:bg-accent" onClick={ () => testToken() }>
							{ __( 'Test API token', 'imageshop-dam-connector' ) }
						</button>
					}

					{ validToken &&
						<button type="button" className="cursor-pointer bg-primary py-2 px-4 text-white rounded-md hover:bg-accent" onClick={ () => setStep( 3 ) }>
							{ __( 'Continue to upload settings', 'imageshop-dam-connector' ) }
						</button>
					}
				</div>
			</div>
		</>
	)
}

export default Tokens;
