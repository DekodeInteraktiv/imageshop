import React from "react";
import { createRoot } from 'react-dom/client';
import Settings from "./Settings";

const domNode = document.getElementById('imageshop-settings');

// Check that the DOM node exists before rendering
if (domNode) {
	const root = createRoot(domNode);
	root.render( <Settings /> );
}
