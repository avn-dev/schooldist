import { PropType, inject, ref } from 'vue3'
import { default as FilterModel, FilterValue } from '../models/filter'
import { GuiInstance } from './gui-component'

type PropsType = {
	filter: FilterModel,
	modelValue: FilterValue,
	view: string
// eslint-disable-next-line
} & any // Readonly<LooseRequired<Props & UnionToIntersection<ExtractOptionProp<Mixin>> […]

export const PROPS = {
	filter: { type: Object as PropType<FilterModel>, required: true },
	modelValue: { type: null, required: true },
	view: { type: String, required: true }
}

export const EXPOSE = [
	'focus'
]

export const EMITS = [
	'reset:modelValue',
	'update:modelValue',
	'update:negate'
]

export function useDefault(props: PropsType) {
	const collapsible = props.view === 'side' && props.filter.isCollapsed()
	const collapsed = ref(!props.filter.hasValue() && collapsible)
	return {
		gui: inject('gui') as GuiInstance,
		collapsible,
		collapsed,
		collapse: () => {
			if (collapsible) {
				collapsed.value = !collapsed.value
			}
		}
	}
}

export function focus(element: HTMLElement) {
	// scrollIntoView funktioniert nicht wegen falscher Höhe und overflow: hidden #17915
	// element.scrollIntoView({ behavior: 'smooth', block: 'center' })
	// element.focus({ preventScroll: true })
	element.focus()
}
