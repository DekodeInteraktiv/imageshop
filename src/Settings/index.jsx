import React, {useState, useEffect} from 'react';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

import './styles.pcss';

import Tabs from "../components/Tabs";
import Account from "./Account";
import Advanced from "./Advanced";
import Sync from "./Sync";
import {
	ArrowPathIcon,
	CpuChipIcon,
	InformationCircleIcon,
	WifiIcon
} from "@heroicons/react/24/solid";

import Logo from '../../assets/images/logo.svg';

const tabs = {
	settings: {
		label: <><WifiIcon className="imageshop:inline-block imageshop:w-4 imageshop:h-4" /> <span>{ __( 'Connection settings', 'imageshop-dam-conenctor' ) }</span></>,
		hasSharedFooter: true,
	},
	advanced: {
		label: <><CpuChipIcon className="imageshop:inline-block imageshop:w-4 imageshop:h-4" /> <span>{ __( 'Advanced', 'imageshop-dam-connector' ) }</span></>,
		hasSharedFooter: true,
	},
	sync: {
		label: <><ArrowPathIcon className="imageshop:inline-block imageshop:w-4 imageshop:h-4" /> <span>{ __( 'Full sync commands', 'imageshop-dam-connector' ) }</span></>,
		hasSharedFooter: false,
	},
};

export default function Settings() {
	const [ activeTab, setActiveTab ] = useState( 'settings' );
	const [ settings, setSettings ] = useState( {} );
	const [ saveNotice, setSaveNotice ] = useState( null );
	const [ showSharedFooter, setShowSharedFooter ] = useState( true );

	const updateSettings = ( newSettings ) => {
		setSettings(
			( prevSettings ) => ( {
				...prevSettings,
				...newSettings
			} )
		)
	}

	const saveSettings = () => {
		apiFetch(
			{
				path: 'imageshop/v1/settings',
				method: 'POST',
				data: {
					api_key: settings.api_key,
					default_interface: settings.default_interface,
					disable_srcset: settings.disable_srcset,
					webp_support: settings.webp_support,
					upload_to_imageshop: settings.upload_to_imageshop,
				}
			}
		).then( ( response ) => {
			updateSettings( response.settings );
			setSaveNotice( response.message );

			setTimeout( () => {
				setSaveNotice( null );
			}, 5000 );
		} );
	}

	useEffect( () => {
		apiFetch(
			{
				path: 'imageshop/v1/settings',
				method: 'GET'
			}
			).then( ( response ) => {
				setSettings( response );
			} );
	}, [] );

	useEffect( () => {
		setShowSharedFooter( tabs[ activeTab ].hasSharedFooter );
	}, [ activeTab ] );

	return (
		<div className="imageshop:w-full imageshop:pr-4">
			<img src={ Logo } alt="ImageShop Logo" className="imageshop:w-64 imageshop:my-8" />

			<div className="imageshop:w-full imageshop:grid imageshop:grid-cols-1 imageshop:gap-4 imageshop:bg-white imageshop:rounded-md imageshop:p-8 imageshop:mr-8 imageshop:mt-4 imageshop:mb-8 imageshop:shadow-md">
				<div className="imageshop:flex imageshop:flex-cols imageshop:gap-16">
					<header>
						<Tabs tabs={ tabs } setActiveTab={ setActiveTab } activeTab={ activeTab } />
					</header>

					<div className="imageshop:grid imageshop:grid-cols-1 imageshop:gap-3">
						<div>
							{ activeTab === 'settings' && <Account settings={ settings } updateSettings={ updateSettings } /> }
							{ activeTab === 'advanced' && <Advanced settings={ settings } updateSettings={ updateSettings } /> }
							{ activeTab === 'sync' && <Sync settings={ settings } updateSettings={ updateSettings } /> }
						</div>

						{ showSharedFooter &&
							<>
								<hr />

								<div>
									{ saveNotice && (
										<p className="imageshop:shadow-lg imageshop:border-1 imageshop:border-primary imageshop:bg-white imageshop:p-4 imageshop:rounded-md imageshop:text-primary imageshop:flex imageshop:items-center">
											<InformationCircleIcon className="imageshop:inline-block imageshop:w-6 imageshop:h-6 imageshop:mr-2 imageshop:text-primary" />
											{ saveNotice }
										</p>
									) }

									<button
										className="imageshop:bg-primary imageshop:text-white imageshop:px-4 imageshop:py-2 imageshop:rounded-md imageshop:hover:bg-accent imageshop:cursor-pointer imageshop:transition-colors"
										onClick={ saveSettings }
									>
										Save settings
									</button>
								</div>
							</>
						}
					</div>
				</div>
			</div>
		</div>
	)
}
