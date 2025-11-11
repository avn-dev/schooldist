<script lang="ts">
import { defineComponent, inject } from 'vue3'
import * as debounce from 'debounce-promise'
import { GuiInstance } from '../../../composables/gui-component'

export default defineComponent({
	props: {
		modelValue: { type: String, required: true }
	},
	emits: [
		'update:modelValue'
	],
	setup() {
		return {
			gui: inject('gui') as GuiInstance
		}
	},
	computed: {
		debounce: {
			get() {
				return this.modelValue
			},
			set: debounce(function (value) {
				// @ts-ignore
				this.$emit('update:modelValue', value)
			}, 500)
		},
	}
})
</script>

<template>
	<div class="flex flex-row items-center pr-2 rounded mx-0.5 items-center ring-1 ring-gray-100 text-gray-500 hover:text-gray-600 hover:ring-gray-200 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 dark:hover:text-gray-100">
		<input
			v-model="debounce"
			class="grow rounded py-1 px-2 bg-white"
			:placeholder="gui.getTranslation('filter_search')"
		>
		<a
			v-show="modelValue"
			class="flex-none gui-form-control-feedback-clickable text-gray-500"
			@click="$emit('update:modelValue', '')"
		>
			<i class="fa fa-times" />
		</a>
	</div>
</template>
