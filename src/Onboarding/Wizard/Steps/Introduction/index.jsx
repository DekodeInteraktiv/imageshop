import React, { useState } from 'react';
import { __ } from '@wordpress/i18n';
import {ArrowTopRightOnSquareIcon} from "@heroicons/react/24/solid";

const Introduction = ( { setStep } ) => {


	return (
		<>
			<p className="p-4">
				{ __( 'After setup, Imageshop will become your one source of truth for all media on your site.', 'imageshop-dam-connector' ) }
			</p>

			<div className="bg-gray-50 p-4 rounded-b-lg">
				<div className="flex justify-end gap-4">
					<a href="https://imageshop.no" className="flex justify-center cursor-pointer bg-none py-2 px-4 text-black rounded-md hover:bg-accent/20 hover:underline" target="_blank" rel="noopener noreferrer">
						<ArrowTopRightOnSquareIcon className="inline-block w-4 h-4 mr-1" />
						{ __( 'Create an account', 'imageshop-dam-connector' )}
					</a>

					<button type="button" className="cursor-pointer bg-primary py-2 px-4 text-white rounded-md hover:bg-accent" onClick={ () => setStep( 2 ) }>
						{ __( 'Start setup', 'imageshop-dam-connector' ) }
					</button>
				</div>
			</div>
		</>
	)
}

export default Introduction;
