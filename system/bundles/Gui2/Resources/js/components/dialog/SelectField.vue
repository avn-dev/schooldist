<script lang="ts">
import { defineComponent, PropType } from 'vue3'
import type { SelectOptions } from '../../types/dialog'
import { EMITS, PROPS, useDefault } from '../../composables/dialog/input'

export default defineComponent({
	inheritAttrs: false,
	props: {
		...PROPS,
		multiple: { type: Boolean, default: false },
		options: { type: Array as PropType<SelectOptions>, default: () => [] }
	},
	emits: EMITS,
	setup(props) {
		return useDefault(props)
	},
	computed: {
		// v-model kümmert sich um ganze Logik beim Unterschied zwischen select und select multiple
		value: {
			get() {
				return this.modelValue
			},
			set(value: string|string[]) {
				this.$emit('update:modelValue', value)
			}
		},
		optionMissing() {
			if (!this.modelValue || this.multiple) return
			return !this.options.some(o => o.key === this.modelValue)
		}
	}
})
</script>

<template>
	<!-- .GUIDialogRow: notwendig für checkElementIsHidden() -->
	<div
		v-show="visible"
		class="relative text-xs text-gray-900 py-2 sm:grid sm:grid-cols-12 sm:gap-4"
		:class="{'has-warning': optionMissing}"
	>
		<label class="block font-semibold leading-6 sm:col-span-4 sm:pt-1.5 sm:px-3 text-right">
			{{ label }}
			<span v-if="required"> *</span>
		</label>
		<div class="relative sm:col-span-8 sm:items-center">
			<!-- data-placeholder: Label for old-style error messages -->
			<select
				v-model="value"
				class="w-full rounded border border-gray-100 p-2 bg-white"
				:class="{ required }"
				:multiple="multiple"
				:data-placeholder="label"
			>
				<option v-if="!multiple" />
				<option
					v-for="option in options"
					:key="option.key"
					:value="option.key"
				>
					{{ option.label }}
				</option>
				<option
					v-if="optionMissing"
					:value="modelValue"
				>
					{{ gui.getTranslation('unknown') }}: {{ modelValue }}
				</option>
			</select>
		</div>
	</div>
</template>
