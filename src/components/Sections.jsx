import React from 'react';
import clsx from "clsx";

export default function Sections({ children, className }) {
	return (
		<div className={clsx('grid grid-cols-1 gap-12', className)}>
			{children}
		</div>
	);
}

export function Section({ children, className }) {
	return (
		<div className={clsx('grid grid-cols-1 gap-4 w-full', className)}>
			{children}
		</div>
	);
}

export function SectionHeader({ children, className }) {
	return (
		<div className={clsx('grid grid-cols-1 gap-2 pb-2', className)}>
			{children}
		</div>
	);
}

export function SectionTitle({ children, className }) {
	return (
		<h3 className={clsx('text-lg font-semibold m-0! pb-2 border-b-1 border-accent/20', className)}>
			{children}
		</h3>
	);
}

export function SectionDescription({ children, className }) {
	return (
		<p className={clsx('text-sm text-gray-600 w-full max-w-3xl m-0!', className)}>
			{children}
		</p>
	);
}
