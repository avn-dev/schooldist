import { useScheduling } from "./scheduling/composables/scheduling"
import { Block, Planification } from "./scheduling/types"
import { App, ComponentPublicInstance } from 'vue3'
import { createVueApp as baseCreateVueApp } from "@Gui2/util/util"
import { GuiInstance } from "@Gui2/types/gui"

class LegacyWrapper {
	window: Planification

	constructor(window: Planification) {
		this.window = window
	}

	changeWeekday(day: number): Promise<boolean> {
		return new Promise((resolve) => {
			this.window.changeWeekDay(day, false, () => resolve(true))
		})
	}

	loadBlocks(): Promise<boolean> {
		return new Promise((resolve) => {
			this.window.preparePlanification(false, () => resolve(true))
		})
	}

	highlightBlock(block: Block): Promise<HTMLElement> {
		return new Promise((resolve, reject) => {
			const element = document.getElementById(block.container)
			if (element) {
				this.window.selectBlock(null, element, true)
				resolve(element)
			} else {
				console.error('Unable to find block', block.container)
				reject()
			}
		})
	}

}

/* eslint-disable @typescript-eslint/no-explicit-any */
function createVueApp(component: string, element: Element, gui: GuiInstance, props: Record<string, any>): [App<Element>, ComponentPublicInstance] {
	// @ts-ignore
	return baseCreateVueApp(gui, element, props, () => [`${component}-scheduling`, require(`./components/scheduling/${component}.vue`)])
}

const SchedulingVueUtil = { createVueApp }

const scheduling = useScheduling()
// @ts-ignore
const legacy = new LegacyWrapper(window as Planification)

export {
	SchedulingVueUtil,
	scheduling,
	legacy
}