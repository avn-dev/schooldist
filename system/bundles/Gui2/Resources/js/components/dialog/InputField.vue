<script lang="ts">
import { defineComponent } from 'vue3'
import { EMITS, PROPS, useDefault } from '../../composables/dialog/input'

export default defineComponent({
	inheritAttrs: false,
	props: PROPS,
	emits: EMITS,
	setup(props) {
		return useDefault(props)
	}
})
</script>

<template>
	<!-- .GUIDialogRow: notwendig für checkElementIsHidden() -->
	<div
		v-show="visible"
		class="relative text-xs text-gray-900 py-2 sm:grid sm:grid-cols-12 sm:gap-4"
	>
		<label class="block font-semibold leading-6 sm:col-span-4 sm:pt-1.5 sm:px-3 text-right">
			{{ label }}
			<span v-if="required"> *</span>
		</label>
		<div class="relative sm:col-span-8 sm:items-center">
			<!-- data-placeholder: Label for old-style error messages -->
			<input
				class="w-full rounded border border-gray-100 p-2 bg-white"
				:class="{ required }"
				:value="modelValue"
				:data-placeholder="label"
				@input="$emit('update:modelValue', $event.target.value)"
			>
		</div>
	</div>
</template>
