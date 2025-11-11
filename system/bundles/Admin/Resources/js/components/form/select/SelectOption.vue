<script lang="ts">
import { defineComponent, onBeforeUnmount, inject, h, type PropType, type VNode } from 'vue'
import SelectOptionWrapper from './SelectOptionWrapper.vue'
import { SelectOption as SelectOptionType, SelectOptionValueType } from '@Admin/types/common'

const enum Types { option = 'option', separator = 'separator'}

type SelectOptionWithVNode = SelectOptionType & { element: VNode }

export default defineComponent({
	name: "SelectOption",
	props: {
		value: { type: [String, Number] as PropType<string | number>, required: true },
		text: { type: String, required: true },
		type: { type: String, default: 'option' },
	},
	setup(props, { slots, attrs }) {
		const addOption = inject<(option: SelectOptionWithVNode) => void>('addOption')
		const removeOption = inject<(value: SelectOptionValueType) => void>('removeOption')
		const select = inject<(value: SelectOptionType) => void>('select')
		const hasValue = inject<(value: SelectOptionValueType|SelectOptionValueType[]) => void>('hasValue')

		if (!addOption || !removeOption || !select || !hasValue) {
			console.warn('[SelectOption] missing injected variables')
			return {}
		}

		const active = (value?: SelectOptionValueType|SelectOptionValueType[]) => hasValue(value ?? props.value)

		const slotParams = (props.type === Types.option)
			? { select, active }
			: {}

		addOption({
			...props,
			element: h(SelectOptionWrapper, attrs, {
				default: () => (slots.default) ? slots.default(slotParams) : null,
			})
		})

		onBeforeUnmount(() => removeOption(props.value))

		return {}
	},
	render() {
		return null
	}
})
</script>
