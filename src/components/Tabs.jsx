import React from 'react';
import { __ } from '@wordpress/i18n';
import clsx from "clsx";

function Tab( { label, isActive, onClick } ) {
	const classNames = clsx(
		'imageshop:py-2 imageshop:px-4 imageshop:cursor-pointer imageshop:whitespace-nowrap imageshop:flex imageshop:justify-start imageshop:items-center imageshop:gap-4 imageshop:rounded-md imageshop:transition-colors',
		'imageshop:hover:bg-accent/40 imageshop:hover:text-black',
		isActive && 'imageshop:bg-primary imageshop:text-white'
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
		<nav aria-label={__('Tabs', 'imageshop-dam-connector')} className="imageshop:grid imageshop:grid-cols-1 imageshop:gap-2">
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
