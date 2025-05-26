const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		...defaultConfig.entry(),
		onboarding: './src/onboarding.jsx',
		settings: './src/settings.jsx',
	},
};
