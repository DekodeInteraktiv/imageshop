import React from 'react';
import { __ } from '@wordpress/i18n';
import clsx from "clsx";

function Tab( { label, isActive, onClick, className } ) {
	const classNames = clsx(
		'imageshop:py-2 imageshop:px-4 imageshop:cursor-pointer imageshop:whitespace-nowrap imageshop:flex imageshop:justify-start imageshop:items-center imageshop:gap-4 imageshop:rounded-md imageshop:transition-colors',
		'imageshop:hover:bg-accent/40 imageshop:hover:text-black',
		isActive && 'imageshop:bg-primary imageshop:text-white',
		className
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
		<nav aria-label={__('Tabs', 'imageshop-dam-connector')} className="imageshop:flex imageshop:flex-col imageshop:gap-2 imageshop:h-full">
			{Object.entries(tabs).map(([key, { label, bottom }]) => (
				<Tab
					key={key}
					label={label}
					className={bottom && 'imageshop:mt-auto'}
					isActive={activeTab === key}
					onClick={() => setActiveTab(key)}
				/>
			))}
		</nav>
	)
}
