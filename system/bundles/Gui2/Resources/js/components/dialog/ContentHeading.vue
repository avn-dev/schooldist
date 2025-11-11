<script lang="ts">
import { defineComponent, type PropType } from 'vue'

export default defineComponent({
	name: "ContentHeading",
	props: {
		tag: { type: String as PropType<'h1' | 'h2' | 'h3' | 'h4' | 'h5'>, required: true },
		content: { type: String, required: true }
	}
})
</script>

<template>
	<component
		:is="tag"
		class="text-sm font-heading font-semibold leading-5 py-1 px-2 bg-gray-50 text-gray-500 rounded-md my-1"
	>
		{{ content }}
	</component>
</template>
