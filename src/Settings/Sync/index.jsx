import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { useEffect, useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import {ExclamationTriangleIcon, InformationCircleIcon} from "@heroicons/react/24/solid";
import Sections, {Section, SectionDescription, SectionHeader, SectionTitle} from "../../components/Sections";
import Infobox from "../../components/Infobox";

const buttonClassName = 'imageshop:bg-primary imageshop:text-white imageshop:px-4 imageshop:py-2 imageshop:rounded-md imageshop:hover:bg-accent/40 imageshop:hover:text-black imageshop:cursor-pointer imageshop:transition-colors imageshop:disabled:opacity-50 imageshop:disabled:cursor-not-allowed';

const outcomeLabels = {
	working: __( 'Working', 'imageshop-dam-connector' ),
	repaired: __( 'Repaired', 'imageshop-dam-connector' ),
	registered: __( 'Registered', 'imageshop-dam-connector' ),
	retrying: __( 'Will be retried', 'imageshop-dam-connector' ),
	failed: __( 'Failed', 'imageshop-dam-connector' ),
};

const linkStatusLabels = {
	confirmed: __( 'Verified image links', 'imageshop-dam-connector' ),
	legacy: __( 'Not checked', 'imageshop-dam-connector' ),
	pending: __( 'Waiting to be registered', 'imageshop-dam-connector' ),
	unverified: __( 'Waiting to be checked again', 'imageshop-dam-connector' ),
	failed: __( 'Could not be repaired', 'imageshop-dam-connector' ),
};

function ToolGroup( { title, description, children } ) {
	return (
		<section className="imageshop:grid imageshop:grid-cols-1 imageshop:gap-8 imageshop:border-1 imageshop:border-accent/20 imageshop:rounded-md imageshop:p-6">
			<header className="imageshop:grid imageshop:grid-cols-1 imageshop:gap-1">
				<h2 className="imageshop:text-xl! imageshop:font-semibold imageshop:m-0!">{ title }</h2>
				<p className="imageshop:text-sm imageshop:text-gray-600 imageshop:m-0!">{ description }</p>
			</header>
			{ children }
		</section>
	);
}

const progressLabels = {
	/* translators: 1: Number of items processed. 2: Total number of items. */
	running: __( 'In progress, %1$d of %2$d processed.', 'imageshop-dam-connector' ),
	/* translators: 1: Number of items processed. 2: Total number of items. */
	done: __( 'Completed, %1$d of %2$d processed.', 'imageshop-dam-connector' ),
	/* translators: 1: Number of items processed. 2: Total number of items. */
	stopped: __( 'Stopped, %1$d of %2$d processed.', 'imageshop-dam-connector' ),
};

function TaskProgress( { progress, onStop } ) {
	if ( ! progress ) {
		return null;
	}

	const counts = Object.entries( progress.counts || {} )
		.filter( ( [ , count ] ) => count > 0 )
		.map( ( [ outcome, count ] ) => `${ outcomeLabels[ outcome ] || outcome }: ${ count }` )
		.join( ', ' );

	return (
		<div className="imageshop:grid imageshop:grid-cols-1 imageshop:gap-1">
			<progress className="imageshop:w-full" max={ progress.total } value={ progress.processed } />
			<p className="imageshop:text-sm imageshop:m-0!">
				{ sprintf( progressLabels[ progress.status ] || progressLabels.running, progress.processed, progress.total ) }
				{ ' ' }
				{ 'cli' === progress.source && __( 'Started from WP-CLI.', 'imageshop-dam-connector' ) }
				{ counts && ` ${ counts }` }
			</p>
			{ 'running' === progress.status && (
				<div>
					<button className="imageshop:px-4 imageshop:py-2 imageshop:rounded-md imageshop:cursor-pointer imageshop:border-1 imageshop:border-error imageshop:text-error imageshop:hover:bg-error imageshop:hover:text-white imageshop:transition-colors" onClick={ onStop }>
						{ __( 'Stop', 'imageshop-dam-connector' ) }
					</button>
				</div>
			) }
		</div>
	);
}

function ConfirmButton( { label, warning, onConfirm, disabled } ) {
	const [ confirming, setConfirming ] = useState( false );

	if ( ! confirming ) {
		return (
			<div>
				<button className={ buttonClassName } disabled={ disabled } onClick={ () => setConfirming( true ) }>
					{ label }
				</button>
			</div>
		);
	}

	return (
		<div className="imageshop:grid imageshop:grid-cols-1 imageshop:gap-3 imageshop:border-1 imageshop:border-error imageshop:rounded-md imageshop:p-4">
			<p className="imageshop:flex imageshop:items-start imageshop:gap-2 imageshop:m-0!">
				<ExclamationTriangleIcon className="imageshop:w-6 imageshop:h-6 imageshop:shrink-0 imageshop:text-error" />
				{ warning }
			</p>
			<div className="imageshop:flex imageshop:gap-2">
				<button className={ buttonClassName } onClick={ () => { setConfirming( false ); onConfirm(); } }>
					{ __( 'Yes, I am sure', 'imageshop-dam-connector' ) }
				</button>
				<button className="imageshop:px-4 imageshop:py-2 imageshop:rounded-md imageshop:cursor-pointer imageshop:hover:bg-accent/40" onClick={ () => setConfirming( false ) }>
					{ __( 'Cancel', 'imageshop-dam-connector' ) }
				</button>
			</div>
		</div>
	);
}

export default function Sync() {
	const [ syncMessage, setSyncMessage ] = useState( null );
	const [ status, setStatus ] = useState( null );

	const tasks = status?.tasks || {};
	const linkCounts = status?.permalinks || {};
	const isRunning = Object.values( tasks ).some( ( task ) => 'running' === task?.status );

	const refreshStatus = () => {
		apiFetch( { path: '/imageshop/v1/tools/status' } ).then( setStatus );
	};

	const runTask = ( path, data = {} ) => {
		setSyncMessage( null );

		apiFetch( { path, method: 'POST', data } )
			.then( ( response ) => setSyncMessage( response?.message ) )
			.catch( ( error ) => setSyncMessage( error?.message ) )
			.finally( refreshStatus );
	};

	useEffect( refreshStatus, [] );

	// Keep the progress up to date while a task runs, also when it was started from WP-CLI.
	useEffect( () => {
		if ( ! isRunning ) {
			return;
		}

		const interval = setInterval( refreshStatus, 10000 );

		return () => clearInterval( interval );
	}, [ isRunning ] );

	const heavyTaskWarning = __( 'This is a long-running task that may add some stress to your website. Are you sure you want to start it?', 'imageshop-dam-connector' );

	return (
		<div>
			<Infobox className="imageshop:grid imageshop:grid-cols-1 imageshop:gap-2">
				<p className="imageshop:m-0!">
					{ createInterpolateElement(
						__( 'These tools are meant for troubleshooting. They start potentially long-running tasks, which run in small batches in the background, and can take a long time to complete depending on the number of images. Running them with WP-CLI is faster, and does not depend on visitors to your site. <a>What is WP-CLI?</a>', 'imageshop-dam-connector' ),
						{
							// eslint-disable-next-line jsx-a11y/anchor-has-content -- The content is provided by the translatable string.
							a: <a href="https://wp-cli.org/" target="_blank" rel="noopener noreferrer" />,
						}
					) }
				</p>
			</Infobox>

			{ syncMessage && (
				<p className="imageshop:shadow-lg imageshop:border-1 imageshop:border-primary imageshop:bg-white imageshop:p-4 imageshop:rounded-md imageshop:text-primary imageshop:flex imageshop:items-center">
					<InformationCircleIcon className="imageshop:inline-block imageshop:w-6 imageshop:h-6 imageshop:mr-2 imageshop:text-primary" />
					{ syncMessage }
				</p>
			) }

			<Sections>
				<ToolGroup
					title={ __( 'Recovery', 'imageshop-dam-connector' ) }
					description={ __( 'Find and repair problems with images already in use on your site.', 'imageshop-dam-connector' ) }
				>
					<Section>
						<SectionHeader>
							<SectionTitle>
								{ __( 'Check image links', 'imageshop-dam-connector' ) }
							</SectionTitle>
							<SectionDescription>
								{ __( 'If you are experiencing issues with unresolved images, such as them showing as broken images. This check visits each of those image links, and only asks Imageshop to repair the ones that are broken, without changing your content.', 'imageshop-dam-connector' ) }
							</SectionDescription>
						</SectionHeader>

						{ Object.keys( linkCounts ).length > 0 && (
							<ul className="imageshop:m-0!">
								{ Object.entries( linkCounts ).map( ( [ linkStatus, count ] ) => (
									<li key={ linkStatus }>
										{ linkStatusLabels[ linkStatus ] || linkStatus }: { count }
									</li>
								) ) }
							</ul>
						) }

						{ status && ! linkCounts.legacy && (
							<p className="imageshop:text-sm imageshop:m-0!">
								{ __( 'There are no unverified image links. Images added with this version of the plugin are confirmed by Imageshop when they are inserted, and only need to be checked again if you still see broken images.', 'imageshop-dam-connector' ) }
							</p>
						) }

						<TaskProgress progress={ tasks.permalinks } onStop={ () => runTask( '/imageshop/v1/tools/stop', { task: 'permalinks' } ) } />
					</Section>
					<Section>
						<div className="imageshop:flex imageshop:gap-2">
							<ConfirmButton
								label={ __( 'Check image links', 'imageshop-dam-connector' ) }
								warning={ heavyTaskWarning }
								disabled={ ! linkCounts.legacy || 'running' === tasks.permalinks?.status }
								onConfirm={ () => runTask( '/imageshop/v1/tools/check-links' ) }
							/>

							{ linkCounts.confirmed > 0 && (
								<ConfirmButton
									label={ __( 'Check all image links again', 'imageshop-dam-connector' ) }
									warning={ __( 'This also checks the image links that Imageshop has already confirmed, and is only needed if you still see broken images. Are you sure you want to start it?', 'imageshop-dam-connector' ) }
									disabled={ 'running' === tasks.permalinks?.status }
									onConfirm={ () => runTask( '/imageshop/v1/tools/check-links', { recheck: true } ) }
								/>
							) }
						</div>
					</Section>
				</ToolGroup>

				<ToolGroup
					title={ __( 'Synchronization', 'imageshop-dam-connector' ) }
					description={ __( 'Copy all media between WordPress and Imageshop in bulk.', 'imageshop-dam-connector' ) }
				>

					<Section>
						<SectionHeader>
							<SectionTitle>
								{ __( 'Sync WordPress to Imageshop', 'imageshop-dam-connector' ) }
							</SectionTitle>
							<SectionDescription>
								{ __( 'This will upload all images in your WordPress media library to the Imageshop cloud, ensuring that all your media is available in the Imageshop platform.', 'imageshop-dam-connector' ) }
							</SectionDescription>
						</SectionHeader>

						<TaskProgress progress={ tasks.export } onStop={ () => runTask( '/imageshop/v1/tools/stop', { task: 'export' } ) } />

						<ConfirmButton
							label={ __( 'Sync WordPress images to the Imageshop cloud', 'imageshop-dam-connector' ) }
							warning={ heavyTaskWarning }
							disabled={ 'running' === tasks.export?.status }
							onConfirm={ () => runTask( '/imageshop/v1/sync/remote' ) }
						/>
					</Section>

					<Section>
						<SectionHeader>
							<SectionTitle>
								{ __( 'Sync Imageshop to WordPress', 'imageshop-dam-connector' ) }
							</SectionTitle>
							<SectionDescription>
								{ __( 'This will download all images used in a post or page in WordPress from the Imageshop cloud to your local media library, ensuring that all your media is available if the plugin is removed.', 'imageshop-dam-connector' ) }
							</SectionDescription>
						</SectionHeader>

						<TaskProgress progress={ tasks.import } onStop={ () => runTask( '/imageshop/v1/tools/stop', { task: 'import' } ) } />

						<ConfirmButton
							label={ __( 'Sync Imageshop images to the WordPress media library', 'imageshop-dam-connector' ) }
							warning={ heavyTaskWarning }
							disabled={ 'running' === tasks.import?.status }
							onConfirm={ () => runTask( '/imageshop/v1/sync/local' ) }
						/>
					</Section>

				</ToolGroup>
			</Sections>
		</div>
	)
}
