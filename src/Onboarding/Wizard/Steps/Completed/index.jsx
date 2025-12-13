import React from 'react';
import { __ } from '@wordpress/i18n';

const Completed = ( { setShowWizard, setShowNotice } ) => {
	const closeOnboarding = () => {
		setShowNotice( false );
		setShowWizard( false );
	}

	return (
		<>
			<div className="p-4">
				<p>
					{ __( 'Your site has now been configured to retrieve media from Imageshop.', 'imageshop-dam-connector' ) }
				</p>
			</div>

			<div className="bg-gray-50 p-4 rounded-b-lg">
				<div className="flex justify-end gap-4">
					<button type="button" className="cursor-pointer bg-primary py-2 px-4 text-white rounded-md hover:bg-accent" onClick={ () => closeOnboarding() }>
						{ __( 'Finish setup', 'imageshop-dam-connector' ) }
					</button>
				</div>
			</div>
		</>
	)
}

export default Completed;
