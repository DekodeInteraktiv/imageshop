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
		label: <><WifiIcon className="inline-block w-4 h-4" /> <span>{ __( 'Connection settings', 'imageshop-dam-conenctor' ) }</span></>,
		hasSharedFooter: true,
	},
	advanced: {
		label: <><CpuChipIcon className="inline-block w-4 h-4" /> <span>{ __( 'Advanced', 'imageshop-dam-connector' ) }</span></>,
		hasSharedFooter: true,
	},
	sync: {
		label: <><ArrowPathIcon className="inline-block w-4 h-4" /> <span>{ __( 'Full sync commands', 'imageshop-dam-connector' ) }</span></>,
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
		<div className="w-full pr-4">
			<img src={ Logo } alt="ImageShop Logo" className="w-64 my-8" />

			<div className="w-full grid grid-cols-1 gap-4 bg-white rounded-md p-8 mr-8 mt-4 mb-8 shadow-md">
				<div className="flex flex-cols gap-16">
					<header>
						<Tabs tabs={ tabs } setActiveTab={ setActiveTab } activeTab={ activeTab } />
					</header>

					<div className="grid grid-cols-1 gap-3">
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
										<p className="shadow-lg border-1 border-primary bg-white p-4 rounded-md text-primary flex items-center">
											<InformationCircleIcon className="inline-block w-6 h-6 mr-2 text-primary" />
											{ saveNotice }
										</p>
									) }

									<button
										className="bg-primary text-white px-4 py-2 rounded-md hover:bg-accent cursor-pointer transition-colors"
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
