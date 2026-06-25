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
			<div className="imageshop:fixed imageshop:top-0 imageshop:left-0 imageshop:z-[10000] imageshop:inset-0 imageshop:bg-gray-500/75 imageshop:transition-opacity" />

			<div className="imageshop:fixed imageshop:inset-0 imageshop:z-[10010] imageshop:w-screen imageshop:overflow-y-auto">
				<div className="imageshop:flex imageshop:min-h-full imageshop:items-end imageshop:justify-center imageshop:p-4 imageshop:text-center imageshop:sm:items-center imageshop:sm:p-0">
					<dialog className="imageshop:relative imageshop:transform imageshop:rounded-lg imageshop:bg-white imageshop:text-left imageshop:sm:my-8 imageshop:sm:w-full imageshop:sm:max-w-lg" open>
						<button type="button" className="imageshop:absolute imageshop:-right-7 imageshop:-top-7 imageshop:bg-transparent imageshop:border-none imageshop:text-primary imageshop:hover:text-accent imageshop:z-[10012] imageshop:cursor-pointer" onClick={ () => setShowWizard( false ) }>
							<XCircleIcon className="imageshop:inline-block imageshop:w-8 imageshop:h-8" />
							<span className="screen-reader-text">{ __( 'Close Imageshop setup modal', 'imageshop-dam-connector' ) }</span>
						</button>

						<div className="imageshop:p-4 imageshop:border-b imageshop:border-gray-200">
							<img src={ Logo } alt="ImageShop Logo" className="imageshop:w-64 imageshop:mx-auto" />
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
