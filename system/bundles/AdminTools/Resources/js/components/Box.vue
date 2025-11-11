<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
	name: "Box"
})
</script>

<template>
	<div class="bg-gray-100/50 text-gray-900 dark:bg-gray-800 dark:text-gray-50 p-2 rounded-xl">
		<slot />
	</div>
</template>
