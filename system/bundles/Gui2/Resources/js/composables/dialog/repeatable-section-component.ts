import { PropType } from 'vue3'
import { EMITS, PROPS as PROPS2 } from './dialog-component'
import type { RepeatableSectionValue } from '../../types/dialog'

export const PROPS = {
	...PROPS2,
	sectionKey: { type: String, required: true },
	sectionValue: { type: Object as PropType<RepeatableSectionValue>, required: true }
} as const

export { EMITS }
