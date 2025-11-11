<script lang="ts">

import { defineComponent } from 'vue3'

export default defineComponent({
	name: "SelectFilter",
	props: {
		name: { type: String, required: true },
		options: { type: Array, required: true },
		emptyOptions: { type: String, default: '0' }
	},
	emits: ['value'],
	setup(props, ctx) {

		const changeValue = (value: any) => { // eslint-disable-line
			//value = (value == props.emptyOptions) ? [] : [value]
			ctx.emit('value', props.name, value)
		}

		return {
			changeValue
		}
	}
})
</script>

<template>
	<select
		class="input-sm p-1 bg-white rounded ring-1 ring-gray-100/75"
		@change="changeValue($event.target.value)"
	>
		<option
			v-for="option in options"
			:key="option.value"
			:value="option.value"
		>
			{{ option.text }}
		</option>
	</select>
</template>
