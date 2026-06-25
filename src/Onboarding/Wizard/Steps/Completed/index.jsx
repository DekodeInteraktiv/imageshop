import React from 'react';
import { __ } from '@wordpress/i18n';

const Completed = ( { setShowWizard, setShowNotice } ) => {
	const closeOnboarding = () => {
		setShowNotice( false );
		setShowWizard( false );
	}

	return (
		<>
			<div className="imageshop:p-4">
				<p>
					{ __( 'Your site has now been configured to retrieve media from Imageshop.', 'imageshop-dam-connector' ) }
				</p>
			</div>

			<div className="imageshop:bg-gray-50 imageshop:p-4 imageshop:rounded-b-lg">
				<div className="imageshop:flex imageshop:justify-end imageshop:gap-4">
					<button type="button" className="imageshop:cursor-pointer imageshop:bg-primary imageshop:py-2 imageshop:px-4 imageshop:text-white imageshop:rounded-md imageshop:hover:bg-accent" onClick={ () => closeOnboarding() }>
						{ __( 'Finish setup', 'imageshop-dam-connector' ) }
					</button>
				</div>
			</div>
		</>
	)
}

export default Completed;
