const { execSync } = require('child_process');

const CACHE_KEY = 'tc_frontend_hashed_widget_path_factory';

class DoneHook {
	apply(compiler) {
		const pluginName = this.constructor.name;
		compiler.hooks.done.tap(pluginName, () => {
			const logger = compiler.getInfrastructureLogger(pluginName);
			logger.info('Clearing widget path factory hashes (WidgetPathHashedFactory)');
			execSync(`./console core:cache:forget ${CACHE_KEY}`);
		});
	}
}

module.exports = DoneHook;
