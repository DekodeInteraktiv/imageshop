import React from 'react';
import { __ } from '@wordpress/i18n';
import clsx from "clsx";

export default function Infobox({ children, className }) {
	return (
		<div className={clsx('bg-accent/10 border border-accent/20 rounded-md p-4 text-sm text-gray-700 mb-4', className)}>
			{children}
		</div>
	);
}
