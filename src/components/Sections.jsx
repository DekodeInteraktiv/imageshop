import React from 'react';
import clsx from "clsx";

export default function Sections({ children, className }) {
	return (
		<div className={clsx('imageshop:grid imageshop:grid-cols-1 imageshop:gap-12', className)}>
			{children}
		</div>
	);
}

export function Section({ children, className }) {
	return (
		<div className={clsx('imageshop:grid imageshop:grid-cols-1 imageshop:gap-4 imageshop:w-full', className)}>
			{children}
		</div>
	);
}

export function SectionHeader({ children, className }) {
	return (
		<div className={clsx('imageshop:grid imageshop:grid-cols-1 imageshop:gap-2 imageshop:pb-2', className)}>
			{children}
		</div>
	);
}

export function SectionTitle({ children, className, subtitle = false }) {
	const classnames = clsx(
		! subtitle
			? 'imageshop:text-lg imageshop:font-semibold imageshop:m-0! imageshop:pb-2 imageshop:border-b-1 imageshop:border-accent/20'
			: 'imageshop:text-base! imageshop:font-semibold imageshop:m-0! imageshop:pb-2',
		className
	)

	return (
		<h3 className={classnames}>
			{children}
		</h3>
	);
}

export function SectionDescription({ children, className }) {
	return (
		<p className={clsx('imageshop:text-sm imageshop:text-gray-600 imageshop:w-full imageshop:max-w-3xl imageshop:m-0!', className)}>
			{children}
		</p>
	);
}
