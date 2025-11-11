const path = require('path');
const webpack = require('webpack');
const VueLoaderPlugin15 = require('vue-loader-v15/lib/plugin');
const { VueLoaderPlugin: VueLoaderPlugin16 } = require('vue-loader-v16');
const { createBabelRule, createCssRules, createTypeScriptRule, WebpackBundleConfig, RESOLVE_EXTENSIONS, TARGET_FRONTEND } = require('./system/bundles/Core/Resources/js/webpack/util');

const mode = process.env.NODE_ENV;
const context = path.resolve(__dirname);
const bundleConfig = new WebpackBundleConfig(context, process.env).generate();

// TODO Source-Maps
const config = [
	// externals funktioniert nicht auf entry-Ebene, daher separat bundeln und bei backend dann alles rauswerfen
	{
		mode: mode,
		entry: {
			'Vue': {
				import: './system/bundles/Core/Resources/js/vue.ts',
				filename: 'system/bundles/Core/Resources/assets/js/vue.js',
				library: { name: [ 'Vue' ], type: 'assign-properties' }
			}
		},
		output: { path: context },
		context: context,
		// plugins: [...bundleConfig['backend'].plugins],
		plugins: [new webpack.DefinePlugin({
			__VUE_OPTIONS_API__: true,
			__VUE_PROD_DEVTOOLS__: true
		})],
		name: 'vendor'
	},
	{
		mode: mode,
		entry: bundleConfig['backend'].entries,
		output: { path: context },
		name: 'backend',
		module: {
			rules: [
				{
					test: /\.vue$/,
					loader: 'vue-loader-v16' // v16 = Vue 3
				},
				createTypeScriptRule(),
				...createCssRules(),
			]
		},
		resolve: {
			alias: {
				'vue$': path.resolve(__dirname, 'node_modules/vue3'),
				...bundleConfig['backend'].alias
			},
			extensions: RESOLVE_EXTENSIONS
		},
		context: context,
		plugins: [...bundleConfig['backend'].plugins, new VueLoaderPlugin16()],
		optimization: {
			splitChunks: {
				cacheGroups: bundleConfig['backend'].cacheGroups
			}
		},
		externals: {
			'@vue/reactivity': 'Vue',
			'@vue/runtime-core': 'Vue',
			'@vue/runtime-dom': 'Vue',
			'@vue/shared': 'Vue'
		},
		// experiments: {
		// 	outputModule: true
		// }
	},
	{
		mode: mode,
		entry: bundleConfig['frontend'].entries,
		output: { path: context },
		name: 'frontend',
		module: {
			rules: [
				{
					test: /\.vue$/,
					loader: 'vue-loader-v15' // v15 = Vue 2
				},
				createBabelRule(),
				...createCssRules()
			]
		},
		resolve: {
			alias: {
				'vue$': path.resolve(__dirname, 'node_modules/vue2'),
				...bundleConfig['frontend'].alias
			},
			extensions: RESOLVE_EXTENSIONS
		},
		context: context,
		target: `browserslist:${TARGET_FRONTEND.join(',')}`,
		plugins: [...bundleConfig['frontend'].plugins, new VueLoaderPlugin15()],
		optimization: {
			splitChunks: {
				cacheGroups: bundleConfig['frontend'].cacheGroups
			}
		}
	}
];

// console.dir(config, { depth: 6 });

module.exports = config;
