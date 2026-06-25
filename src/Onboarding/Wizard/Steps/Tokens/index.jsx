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
			<div className="imageshop:p-4">
				<p>
					{ __( 'Enter your API token here. You can find your API token in your ImageShop account.', 'imageshop-dam-connector' ) }
				</p>

				{ validationNotice &&
					<>
						{ validToken &&
							<p className="imageshop:m-0!">
								{ __( '✔ Your API token is valid.', 'imageshop-dam-connector' ) }
							</p>
						}

						{ ! validToken &&
							<div className="imageshop:mb-2">
								<p className="imageshop:m-0!">
									{ __( '❌ Your API token is invalid', 'imageshop-dam-connector' ) }
								</p>
							</div>
						}
					</>
				}

				<div className="imageshop:relative imageshop:w-full">
					{ isTestingToken &&
						<div className="imageshop:absolute imageshop:top-0 imageshop:left-0 imageshop:flex imageshop:justify-center imageshop:items-center imageshop:w-full imageshop:h-full imageshop:bg-gray-100">
							<ArrowPathIcon className="imageshop:inline-block imageshop:w-6 imageshop:h-6 imageshop:animate-spin imageshop:text-primary" />
						</div>
					}
					<input type="text" className="imageshop:w-full" value={ token } onChange={ ( e ) => setToken( e.target.value ) } />
					{ validationNotice && ! validToken &&
						<span className="imageshop:italic imageshop:text-red-500">
							{ validationNotice }
						</span>
					}
				</div>
			</div>

			<div className="imageshop:bg-gray-50 imageshop:p-4 imageshop:rounded-b-lg">
				<div className="imageshop:flex imageshop:justify-end imageshop:gap-4">
					{ ! validToken &&
						<button type="button" disabled={ isTestingToken } className="imageshop:cursor-pointer imageshop:bg-primary imageshop:py-2 imageshop:px-4 imageshop:text-white imageshop:rounded-md imageshop:hover:bg-accent" onClick={ () => testToken() }>
							{ __( 'Test API token', 'imageshop-dam-connector' ) }
						</button>
					}

					{ validToken &&
						<button type="button" className="imageshop:cursor-pointer imageshop:bg-primary imageshop:py-2 imageshop:px-4 imageshop:text-white imageshop:rounded-md imageshop:hover:bg-accent" onClick={ () => setStep( 3 ) }>
							{ __( 'Save and continue', 'imageshop-dam-connector' ) }
						</button>
					}
				</div>
			</div>
		</>
	)
}

export default Tokens;
