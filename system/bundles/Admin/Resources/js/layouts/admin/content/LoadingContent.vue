<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
	name: "LoadingContent"
})
</script>

<template>
	<div class="grid min-h-full place-items-center p-6">
		<div class="text-center">
			<i class="fa fa-spinner fa-spin text-3xl text-gray-300" />
		</div>
	</div>
</template>
