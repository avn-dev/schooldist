import { App as Application, createApp, h } from 'vue3'
import { createInertiaApp } from '@inertiajs/vue3'
import mitt from 'mitt'
import { setupApp } from './utils/backend/app'
import registrar from './build'

createInertiaApp({
	id: 'app',
	// @ts-ignore
	resolve: (name: string) => require(`./pages/Backend/${name}`),
	// @ts-ignore
	async setup({ el, App, props, plugin }) {
		const app: Application = createApp({ render: () => h(App, props) })
			.use(plugin)

		await setupApp(app, registrar, props.initialPage, mitt())

		app.mount(el)
	}
})