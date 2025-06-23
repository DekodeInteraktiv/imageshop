import React from 'react';
import { __ } from '@wordpress/i18n';
import clsx from "clsx";

function Tab( { label, isActive, onClick } ) {
	const classNames = clsx(
		'py-2 px-4 cursor-pointer whitespace-nowrap flex justify-start items-center gap-4 rounded-md transition-colors',
		'hover:bg-accent/40 hover:text-black',
		isActive && 'bg-primary text-white'
	)

	return (
		<button
			className={ classNames }
			onClick={onClick}
		>
			{label}
		</button>
	);
}

export default function Tabs({ tabs, activeTab, setActiveTab }) {
	return (
		<nav aria-label={__('Tabs', 'imageshop-dam-connector')} className="grid grid-cols-1 gap-2">
			{Object.entries(tabs).map(([key, { label }]) => (
				<Tab
					key={key}
					label={label}
					isActive={activeTab === key}
					onClick={() => setActiveTab(key)}
				/>
			))}
		</nav>
	)
}
