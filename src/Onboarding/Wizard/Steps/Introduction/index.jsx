import React, { useState } from 'react';
import { __ } from '@wordpress/i18n';
import {ArrowTopRightOnSquareIcon} from "@heroicons/react/24/solid";

const Introduction = ( { setStep } ) => {


	return (
		<>
			<p className="imageshop:p-4">
				{ __( 'After setup, Imageshop will become your one source of truth for all media on your site.', 'imageshop-dam-connector' ) }
			</p>

			<div className="imageshop:bg-gray-50 imageshop:p-4 imageshop:rounded-b-lg">
				<div className="imageshop:flex imageshop:justify-end imageshop:gap-4">
					<a href="https://imageshop.no" className="imageshop:flex imageshop:justify-center imageshop:cursor-pointer imageshop:bg-none imageshop:py-2 imageshop:px-4 imageshop:text-black imageshop:rounded-md imageshop:hover:bg-accent/20 imageshop:hover:underline" target="_blank" rel="noopener noreferrer">
						<ArrowTopRightOnSquareIcon className="imageshop:inline-block imageshop:w-4 imageshop:h-4 imageshop:mr-1" />
						{ __( 'Create an account', 'imageshop-dam-connector' )}
					</a>

					<button type="button" className="imageshop:cursor-pointer imageshop:bg-primary imageshop:py-2 imageshop:px-4 imageshop:text-white imageshop:rounded-md imageshop:hover:bg-accent" onClick={ () => setStep( 2 ) }>
						{ __( 'Start setup', 'imageshop-dam-connector' ) }
					</button>
				</div>
			</div>
		</>
	)
}

export default Introduction;
