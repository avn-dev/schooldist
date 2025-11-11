<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
	name: "GridItemContent"
})
</script>

<template>
	<div class="flex flex-col">
		<slot
			name="header"
			class="flex-none"
		/>
		<div class="grow overflow-auto">
			<slot />
		</div>
		<slot
			name="footer"
			class="flex-none"
		/>
	</div>
</template>

