import { PropType } from 'vue3'
import type { DialogValue } from '../../types/dialog'

export const PROPS = {
	modelValue: { type: [Array, String, Object, null] as PropType<DialogValue>, required: true },
	name: { type: String, required: true }
} as const
// as const: https://github.com/vuejs/core/issues/3014

export const EMITS = [
	'update:modelValue'
]
