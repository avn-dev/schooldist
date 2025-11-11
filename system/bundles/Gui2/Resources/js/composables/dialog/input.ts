import { computed, inject, ExtractPropTypes, PropType } from 'vue3'
import { EMITS, PROPS as dialogProps } from './dialog-component'
import { DialogValues, FieldDependency } from '../../types/dialog'
import { GuiInstance } from '../../types/gui'

export { EMITS }

export const PROPS = {
	...dialogProps,
	modelValue: { type: null, required: true },
	label: { type: String, required: true },
	required: { type: Boolean, default: false },
	dependencies: { type: Array as PropType<FieldDependency[]>, default: () => [] }
}

export function useDefault(props: ExtractPropTypes<typeof PROPS>) {
	const gui = inject('gui') as GuiInstance
	const values = inject('values') as DialogValues
	return {
		gui,
		values,
		visible: computed(function () {
			const dependencies = props.dependencies.filter(d => d.type === 'visibility')
			if (!dependencies.length) {
				return true
			}
			return dependencies.every((d: FieldDependency) => d.values.includes(values[d.field] as string))
		})
	}
}
