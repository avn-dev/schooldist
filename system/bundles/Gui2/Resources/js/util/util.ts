import { App, ComponentPublicInstance, createApp } from 'vue3'
import { GuiInstance } from '../types/gui'

function resolveComponent(component: string) {
	if (component.includes('.')) {
		const parts = component.split('.')
		// @ts-ignore
		const object = window['__FIDELO__'][parts[0]][parts[1]]
		if (!object) {
			console.error('Could not find component ' + component)
		}
		return object
	}
	return component
}


function createVueApp(gui: GuiInstance, element: Element, props: Record<string, any>, requireComponent: () => [string, any]): [App<Element>, ComponentPublicInstance] { // eslint-disable-line
	// eslint-disable-next-line
	const [name, impl] = requireComponent()
	const app = createApp({ name, ...impl['default'] }, { gui, ...props })
	if (impl.setup) {
		impl.setup(app)
	}
	return [app, app.mount(element)]
}

export {
	resolveComponent,
	createVueApp
}
