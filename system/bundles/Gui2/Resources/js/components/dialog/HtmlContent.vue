<script lang="ts">
import { defineComponent } from 'vue3'

export default defineComponent({
	props: {
		content: { type: String, required: true }
	}
})
</script>

<template>
	<!-- eslint-disable vue/no-v-html -->
	<div v-html="content" />
</template>
