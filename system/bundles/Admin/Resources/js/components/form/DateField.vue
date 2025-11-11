<script lang="ts">
import { defineComponent, computed, type PropType } from 'vue'
import { DatePicker } from 'v-calendar'

/**
 * TODO extend
 */
export default defineComponent({
	name: "DateField",
	components: { DatePicker },
	props: {
		modelValue: { type: Object as PropType<Date | null>, default: null },
		locale: { type: String, default: 'en' },
		dateFormat: { type: String, default: 'DD/MM/YYYY' },
		firstDayOfWeek: { type: Number, default: 2 },
		placeholder: { type: String, default: '' },
	},
	emits: ['update:modelValue', 'change'],
	setup(props, { emit }) {
		const fieldValue = computed({
			get: () => props.modelValue,
			set(value) {
				emit('update:modelValue', value)
				emit('change', value)
			}
		})

		const localeObject = {
			id: props.locale,
			firstDayOfWeek: props.firstDayOfWeek,
			masks: {
				input: props.dateFormat
			}
		}

		return {
			localeObject,
			fieldValue
		}
	}
})
</script>

<template>
	<div class="flex flex-row items-center gap-1 bg-white rounded">
		<i class="fa fa-calendar" />
		<DatePicker
			v-model.date="fieldValue"
			mode="date"
			timezone="UTC"
			:locale="localeObject"
			:popover="{ visibility: 'click' }"
			@update:model-value="$emit('change', $event)"
		>
			<!-- eslint-disable @typescript-eslint/no-explicit-any -->
			<template #default="{ inputValue, inputEvents }">
				<input
					:value="inputValue"
					class="form-control input-sm"
					v-on="inputEvents"
				>
			</template>
		</DatePicker>
	</div>
</template>
