import { App, ComponentPublicInstance } from 'vue3'
import { createVueApp as baseCreateVueApp } from "@Gui2/util/util"
import { GuiInstance } from '@Gui2/types/gui'

/* eslint-disable @typescript-eslint/no-explicit-any */
function createVueApp(component: string, element: Element, gui: GuiInstance, props: Record<string, any>): [App<Element>, ComponentPublicInstance] {
	// @ts-ignore
	return baseCreateVueApp(gui, element, props, () => [`${component}-tuition`, require(`./components/${component}.vue`)])
}

const TuitionVueUtil = { createVueApp }

export {
	TuitionVueUtil
}