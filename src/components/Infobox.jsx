import React from 'react';
import { __ } from '@wordpress/i18n';
import clsx from "clsx";

export default function Infobox({ children, className }) {
	return (
		<div className={clsx('imageshop:bg-accent/10 imageshop:border imageshop:border-accent/20 imageshop:rounded-md imageshop:p-4 imageshop:text-sm imageshop:text-gray-700 imageshop:mb-4', className)}>
			{children}
		</div>
	);
}
