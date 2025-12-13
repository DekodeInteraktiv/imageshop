import React, { useState } from 'react';
import { __ } from '@wordpress/i18n';

import Introduction from "./Steps/Introduction";
import Tokens from "./Steps/Tokens";
import Completed from "./Steps/Completed";
import {XCircleIcon} from "@heroicons/react/24/outline";

import Logo from '../../../assets/images/logo.svg';

const Wizard = ( { setShowWizard, setShowNotice } ) => {
	const [ step, setStep ] = useState( 1 );

	return (
		<>
			<div className="fixed top-0 left-0 z-[10000] inset-0 bg-gray-500/75 transition-opacity" />

			<div className="fixed inset-0 z-[10010] w-screen overflow-y-auto">
				<div className="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
					<dialog className="relative transform rounded-lg bg-white text-left sm:my-8 sm:w-full sm:max-w-lg" open>
						<button type="button" className="absolute -right-7 -top-7 bg-transparent border-none text-primary hover:text-accent z-[10012] cursor-pointer" onClick={ () => setShowWizard( false ) }>
							<XCircleIcon className="inline-block w-8 h-8" />
							<span className="screen-reader-text">{ __( 'Close Imageshop setup modal', 'imageshop-dam-connector' ) }</span>
						</button>

						<div className="p-4 border-b border-gray-200">
							<img src={ Logo } alt="ImageShop Logo" className="w-64 mx-auto" />
						</div>

						<div className="imageshop-modal-body">
							{ 1 === step &&
								<Introduction setStep={ setStep } />
							}
							{ 2 === step &&
								<Tokens setStep={ setStep } />
							}
							{ 3 === step &&
								<Completed setShowNotice={ setShowNotice } setShowWizard={ setShowWizard } />
							}
						</div>
					</dialog>
				</div>
			</div>
		</>
	)
}

export default Wizard;
