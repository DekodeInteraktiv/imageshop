import React, {useState, useEffect} from 'react';
import apiFetch from '@wordpress/api-fetch';

import './styles.pcss';

import Tabs from "../components/Tabs";
import Account from "./Account";
import Advanced from "./Advanced";
import Sync from "./Sync";
import {InformationCircleIcon} from "@heroicons/react/24/solid";

const tabs = {
	settings: {
		label: 'Account settings',
	},
	advanced: {
		label: 'Advanced',
	},
	sync: {
		label: 'Full sync commands',
	},
};

export default function Settings() {
	const [ activeTab, setActiveTab ] = useState( 'settings' );
	const [ settings, setSettings ] = useState( {} );
	const [ saveNotice, setSaveNotice ] = useState( null );

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

	return (
		<div className="max-w-3xl w-full mx-auto">
			<header>
				<Tabs tabs={ tabs } setActiveTab={ setActiveTab } activeTab={ activeTab } />
			</header>

			<div className="grid grid-cols-1 gap-3 bg-white p-4 rounded-md shadow-md">
				<div>
					{ activeTab === 'settings' && <Account settings={ settings } updateSettings={ updateSettings } /> }
					{ activeTab === 'advanced' && <Advanced settings={ settings } updateSettings={ updateSettings } /> }
					{ activeTab === 'sync' && <Sync settings={ settings } updateSettings={ updateSettings } /> }
				</div>

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
			</div>
		</div>
	)
}
