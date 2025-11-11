import { createApp, h } from 'vue3'
import { createInertiaApp } from '@inertiajs/vue3'

createInertiaApp({
	id: 'app',
	// @ts-ignore
	resolve: (name: string) => require(`./pages/Auth/${name}`),
	// @ts-ignore
	async setup({ el, App, props, plugin }) {
		createApp({ render: () => h(App, props) })
			.use(plugin)
			.mount(el)
	}
})