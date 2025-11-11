<script lang="ts">
import { defineComponent } from 'vue3'
import { DatePicker } from 'v-calendar'
import { EMITS, PROPS } from '../../composables/filter'

export default defineComponent({
	components: {
		DatePicker
	},
	inject: ['locale', 'date_format'],
	props: PROPS,
	emits: EMITS,
	data() {
		return {
			start: null as Date|null,
			end: null as Date|null,
			localeObject: {
				id: this.locale,
				firstDayOfWeek: 2,
				masks: {
					input: this.date_format
				}
			}
		}
	},
	watch: {
		modelValue() {
			if (this.modelValue) {
				this.start = this.modelValue.start
				this.end = this.modelValue.end
			} else {
				this.start = null
				this.end = null
			}
		}
	},
	methods: {
		update(type: string, value: Date|null) {
			if (type === 'start') {
				this.start = value
			}
			if (type === 'end') {
				this.end = value
			}

			if (this.start && this.end) {
				this.$emit('update:model-value', { start: this.start, end: this.end })
			} else {
				this.$emit('update:model-value', null)
			}
		}
	}
})
</script>

<template>
	<div class="vc-inputs">
		<date-picker
			:model-value="start"
			is-required
			:locale="localeObject"
			:max-date="end"
			show-iso-weeknumbers
			timezone="UTC"
			@update:model-value="update('start', $event)"
		>
			<!-- eslint-disable @typescript-eslint/no-explicit-any -->
			<template #default="{ inputValue, inputEvents }">
				<input
					:value="inputValue"
					class="form-control input-sm"
					v-on="inputEvents"
				>
			</template>
		</date-picker>
		<date-picker
			:model-value="end"
			is-required
			:locale="localeObject"
			:min-date="start"
			show-iso-weeknumbers
			timezone="UTC"
			@update:model-value="update('end', $event)"
		>
			<!-- eslint-disable @typescript-eslint/no-explicit-any -->
			<template #default="{ inputValue, inputEvents }">
				<input
					:value="inputValue"
					class="form-control input-sm"
					v-on="inputEvents"
				>
			</template>
		</date-picker>
	</div>
</template>
