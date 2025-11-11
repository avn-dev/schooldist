import { App, ComponentPublicInstance, createApp } from 'vue3'
import mitt from 'mitt'
import { GuiInstance } from './types/gui'
import FilterModel from './models/filter'
import 'print-this'

/*function watchComponents() {
	const observer = new MutationObserver(function (mutations) {
		mutations.forEach(mutation => {
			mutation.addedNodes.forEach(node => {
				if (node instanceof HTMLElement) {
					if(node.hasAttribute('data-vue-component')) {
						createVueApp(node)
					} else {
						node.querySelectorAll('[data-vue-component]').forEach((el: Element) => createVueApp(el))
					}
				}
			})
			mutation.removedNodes.forEach(node => {
				if (node instanceof HTMLElement) {
					if(node.hasAttribute('data-vue-component')) {
						//node.__vue_app__?.unmount()
					} else {
						node.querySelectorAll('[data-vue-component]').forEach((el: Element) => {
							// @ts-ignore
							el.__vue_app__?.unmount()
						})
					}
				}
			})
		})
	})

	// @ts-ignore
	observer.observe(document.querySelector('body'), {
		childList: true,
		characterData: true,
		subtree: true
	})
}

function createVueApp(element: Element) {
	if (!(element instanceof HTMLElement)) {
		return
	}
	const requireComponent = require.context('./components', false, /\.(vue)$/)
	requireComponent.keys().forEach((fileName: string) => {
		const componentName = fileName.split('/').pop()?.replace(/\.\w+$/, '')
		if (element.dataset.vueComponent === componentName) {
			// @ts-ignore
			const gui = window.aGUI[element.dataset.hash]
			const name = `${element.dataset.vueComponent}-${gui.name}`
			const app = createApp({ name, ...requireComponent(fileName).default }, { gui, emitter: EMITTER })
			app.mount(element)
		}
	})
}*/

// function identity<Type>(arg: Type): Type {
function createVueApp(component: string, element: Element, gui: GuiInstance, props: Record<string, undefined>): [App<Element>, ComponentPublicInstance] {
	// eslint-disable-next-line
	const impl = require(`./components/${component}.vue`)
	const name = `${component}-${gui.name}`
	const app = createApp({ name, ...impl['default'] }, { gui, emitter: EMITTER, ...props })
	if (impl.setup) {
		impl.setup(app)
	}
	return [app, app.mount(element)]
}

const EMITTER = mitt()
const Gui2 = { createVueApp, FilterModel }

export {
	EMITTER,
	Gui2
}
