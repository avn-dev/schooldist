const path = require('path');
const fs = require('fs');
const { execSync } = require('child_process');
const IgnoreEmitPlugin = require('ignore-emit-webpack-plugin');
const CopyPlugin = require('copy-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const ESLintPlugin = require('eslint-webpack-plugin');
const DoneHook = require('./doneHook');

const RESOLVE_EXTENSIONS = ['.js', '.ts', '.vue'];
const TARGET_FRONTEND = ['>1%',  'ie 11', 'edge 18', 'not op_mini all'];
const INTERFACES = ['backend', 'frontend']

class WebpackBundleConfig {
	constructor(context, env) {
		this.context = context;
		this.config = {};
		this.env = env;

		for (const name of INTERFACES) {
			this.config[name] = {
				name,
				entries: {},
				alias: {},
				ignore: {},
				includes: {},
				plugins: [],
				cacheGroups: {},
				ignores: [],
				copyPatterns: []
			};
		}
	}
	generate() {
		const bundleConfigOutput = execSync('./console core:bundles:config --json',).toString();
		const bundleConfig = JSON.parse(bundleConfigOutput);
		const bundleFilter = Object.hasOwn(this.env, 'npm_config_bundles') ? this.env.npm_config_bundles.split(',') : [];

		for (const bundle of bundleConfig) {
			bundle.relPath = bundle.path.replace(this.context, '').replace(/^\//, '');

			// Aliase für Bundle eintragen damit dieser immer zur Verfügung steht, auch wenn das Bundle selber keine
			// 'webpack'-Einstellungen hast
			for (const dir of [['js', '@'], ['scss', '~']]) {
				if (fs.existsSync(path.join(bundle.path, dir[0]))) {
					for (const name of INTERFACES) {
						this.config[name].alias[`${dir[1]}${bundle.name}`] = path.join(bundle.path, dir[0]);
					}
				}
			}

			if (!bundle.hasOwnProperty('webpack')) {
				// Bundle hat selber keine Dateien
				continue;
			}

			for (const bundleEntry of bundle.webpack) {
				if (!Object.hasOwn(this.config, bundleEntry.config)) {
					throw new Error('Config does not exist: ' + bundleEntry.config);
				}

				if (!bundleEntry.entry || bundleFilter.length && !bundleFilter.includes(bundle.name)) {
					continue;
				}

				this.generateEntry(bundle, bundleEntry);
			}
		}

		this.generatePlugins();

		return this.config;
	}
	generateEntry(bundle, bundleEntry) {
		bundleEntry.name = `${bundle.name}::${path.basename(bundleEntry.entry)}`;

		const entry = {
			import: `./${path.join(bundle.relPath, bundleEntry.entry)}`,
			filename: path.join(bundle.relPath, 'assets', this.buildOutputPath(bundleEntry))
		};

		if (bundleEntry?.rule === 'copy') {
			// Kopieren geht alleinig über ein Plugin und darf daher kein Entry für Webpack sein
			this.handleCopy(bundleEntry, entry);
			return;
		}

		this.handleScss(bundleEntry, entry);

		// Global exportieren
		if (bundleEntry.library) {
			entry.library = bundleEntry.library;
		}

		if (this.config[bundleEntry.config].entries.hasOwnProperty(bundleEntry.name)) {
			throw new Error(`Entry with name ${bundleEntry.name} does already exist`);
		}
		this.config[bundleEntry.config].entries[bundleEntry.name] = entry;
	}
	buildOutputPath(bundleEntry) {
		if (bundleEntry.output === '&') {
			// .ts => .js
			if (bundleEntry.entry.endsWith('.ts')) {
				return bundleEntry.entry.replace('.ts', '.js');
			}
			// scss in Pfad und Dateierweiterung ersetzen
			if (/\.scss$/.test(bundleEntry.entry)) {
				return bundleEntry.entry.replace(/(\.|\/?)s(css)/g, '$1$2')
			}
			return bundleEntry.entry;
		}
		return bundleEntry.output;
	}
	handleCopy(bundleEntry, entry) {
		this.config[bundleEntry.config].copyPatterns.push({
			from: path.basename(entry.import),
			to: entry.filename,
			// context setzen, da ansonsten glob die Verzeichnisstruktur (z.B. node_modules) kopiert
			context: path.dirname(entry.import)
		});
	}
	handleScss(bundleEntry, entry) {
		if (!/\.scss$/.test(bundleEntry.entry)) {
			return;
		}

		// Pro SCSS ein optimization.splitChunks.cacheGroups, damit jedes Entry eine eigene CSS-Datei darstellt
		this.config[bundleEntry.config].cacheGroups[bundleEntry.name] = {
			type: 'css/mini-extract',
			// .css entfernen, da Standard bei MiniCssExtractPlugin: [name].css
			name: entry.filename, //.replace(/\.css$/, ''),
			chunks: (chunk) => chunk.name === bundleEntry.name,
			enforce: true
		};

		// .js ergänzen, da eigentliche Dateien von Webpack .js sind (darf sich nicht mit .css überschneiden)
		entry.filename += '.js';

		// .js-Dateien gar nicht erst generieren
		this.config[bundleEntry.config].ignores.push(entry.filename);
	}
	generatePlugins() {
		for (const config of Object.values(this.config)) {
			this.config[config.name].plugins.push(new MiniCssExtractPlugin({
				filename: ({ chunk }) => {
					// Sollten .js-Dateien .css importieren, werden diese Dateien hiermit getrennt und im entsprechenden Verzeichnis generiert
					if (chunk.name.match(/\.(js|ts)$/)) {
						return chunk.filenameTemplate.replace(/js/g, 'css');
					}
					return chunk.name;
				},
			}));

			if (config.ignores.length) {
				this.config[config.name].plugins.push(new IgnoreEmitPlugin(config.ignores));
			}

			if (config.copyPatterns.length) {
				this.config[config.name].plugins.push(new CopyPlugin({ patterns: config.copyPatterns }));
			}

			if (config.name === 'backend') {
				// Vue 3 Options
				// this.config[config.name].plugins.push(new webpack.DefinePlugin({
				// 	__VUE_OPTIONS_API__: true,
				// 	__VUE_PROD_DEVTOOLS__: true
				// }));

				// ESLint nur für Backend
				this.config[config.name].plugins.push(new ESLintPlugin({
					extensions: ['js', 'vue', 'ts']
				}));
			}

			if (Object.hasOwn(this.env, 'npm_config_webpack_analyzer')) {
				const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;
				this.config[config.name].plugins.push(new BundleAnalyzerPlugin({
					analyzerMode: 'static',
					reportFilename: `public/report-${config.name}.html`
				}));
			}

			this.config[config.name].plugins.push(new DoneHook());
		}
	}
}

function createBabelRule() {
	return {
		test: /\.js$/,
		// exclude: [bundleConfig.excludes], // TODO
		use: [
			{
				loader: 'babel-loader',
				options: {
					cacheDirectory: true,
					presets: [
						[
							'@babel/preset-env',
							{
								debug: true,
								modules: false,
								targets: TARGET_FRONTEND,
								useBuiltIns: false
							}
						]
					]
				}
			}
		]
	}
}

function createCssRules() {
	return [
		{
			test: /\.css$/,
			use: [
				{ loader: MiniCssExtractPlugin.loader },
				{ loader: 'css-loader', options: { importLoaders: 1 } }
			]
		},
		{
			test: /\.scss$/,
			exclude: /node_modules/,
			use: [
				{ loader: MiniCssExtractPlugin.loader },
				{ loader: 'css-loader' },
				{ loader: 'postcss-loader' },
				{ loader: 'sass-loader' },
			]
		}
	];
}

function createTypeScriptRule() {
	return {
		test: /\.tsx?$/,
		loader: 'ts-loader',
		options: {
			appendTsSuffixTo: [/\.vue$/],
		},
		exclude: /node_modules/
	}
}

function generateTailwindConfigFiles() {
    const bundleConfigOutput = execSync('./console core:bundles:config --json',).toString();
    const bundleConfig = JSON.parse(bundleConfigOutput);

    let files = [];
    for (const bundle of bundleConfig) {

        if (
            !bundle.hasOwnProperty('tailwind') ||
            !bundle.tailwind.hasOwnProperty('content')
        ) {
            continue;
        }

        files = files.concat(bundle.tailwind.content)
    }

    return [...new Set(files)]
}

module.exports = {
	createBabelRule,
	createCssRules,
	createTypeScriptRule,
    generateTailwindConfigFiles,
	WebpackBundleConfig,
	RESOLVE_EXTENSIONS,
	TARGET_FRONTEND
};
