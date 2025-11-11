import { createApp, h } from 'vue3'
import { createInertiaApp } from '@inertiajs/vue3'

createInertiaApp({
	id: 'app',
	// @ts-ignore
	resolve: name => require(`./pages/${name}`),
	// @ts-ignore
	setup({ el, App, props, plugin }) {
		createApp({ render: () => h(App, props) })
			.use(plugin)
			.mount(el)
	}
})