import React, { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';

import Wizard from '.././Wizard';

const Notice = () => {
	const [ showNotice, setShowNotice ] = useState( true );
	const [ showWizard, setShowWizard ] = useState( false );

	useEffect( () => {
		window.addEventListener( 'keydown', ( event ) => {
			if ( event.key === 'Escape' ) {
				setShowWizard( false );
			}
		} );
	}, [] );

	useEffect( () => {
		const bodyTag = document.querySelector( 'body' );

		if ( showWizard ) {
			bodyTag.classList.add( 'disable-scroll' );
		} else {
			bodyTag.classList.remove( 'disable-scroll' );
		}
	}, [ showWizard ] );

	if ( ! showNotice ) {
		return (
			<></>
		)
	}

	return (
		<>
			<div className="notice notice-warning update-nag inline">
				<h2>
					{ __( 'Imageshop setup', 'imageshop-dam-connector' ) }
				</h2>

				<p>
					{ __( 'The Imageshop integration is almost ready to use, please complete the setup steps to start using Imageshop directly from your media library.', 'imageshop-dam-connector' ) }
				</p>

				<p className="font-semibold">
					{ __( 'Note that the use of additional 3rd party plugins that manage or modify the WordPress media library in any way may lead to unexpected behavior. We recommend disabling any such plugin before completing the Imageshop setup.', 'imageshop-dam-connector' ) }
				</p>

				<p>
					<button type="button" className="button button-primary" onClick={ () => setShowWizard( true ) }>
						{ __( 'Complete setup', 'imageshop-dam-connector' ) }
					</button>
				</p>
			</div>

			{ showWizard && (
				<Wizard setShowWizard={ setShowWizard } setShowNotice={ setShowNotice } />
			) }
		</>
	)
}

export default Notice;
