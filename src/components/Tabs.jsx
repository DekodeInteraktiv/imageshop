import React from 'react';

function Tab( { label, isActive, onClick } ) {
	return (
		<button
			className={`p-2 rounded-t-md border-1 border-b-transparent cursor-pointer hover:bg-white ${isActive ? 'text-primary bg-white border-b-primary' : 'border-b-white'}`}
			onClick={onClick}
		>
			{label}
		</button>
	);
}

export default function Tabs({ tabs, activeTab, setActiveTab }) {
	return (
		<div className="flex flex-row justify-center gap-4">
			{Object.entries(tabs).map(([key, { label }]) => (
				<Tab
					key={key}
					label={label}
					isActive={activeTab === key}
					onClick={() => setActiveTab(key)}
				/>
			))}
		</div>
	)
}
