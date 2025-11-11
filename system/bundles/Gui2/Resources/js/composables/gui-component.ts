// @ts-ignore
import { PropType, provide } from 'vue3'
import type { EmitterType, GuiInstance } from '../types/gui'

// TODO Entfernen
export { GuiInstance }

export const PROPS = {
	gui: { type: Object as PropType<GuiInstance>, required: true },
	emitter: { type: Object as PropType<EmitterType>, required: true },
}

export function useDefault(props: Record<string, unknown>) {
	provide('gui', props.gui)
	provide('emitter', props.emitter)

	return {
		gui: props.gui as GuiInstance,
		emitter: props.emitter as EmitterType
	}
}
