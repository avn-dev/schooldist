<script lang="ts">
import { defineComponent, type PropType } from 'vue'

export default defineComponent({
	name: "RoundedBox",
	props: {
		mode: { type: String as PropType<'dark'|'light'>, default: 'light' },
	}
})
</script>

<template>
	<div>
		<div class="h-full bg-white dark:bg-gray-800 dark:text-gray-400 dark:border dark:border-gray-700 rounded-md shadow">
			<slot />
		</div>
	</div>
</template>

<!--<template>
	<div class="bg-white p-0.5 rounded-md">
		<div class="h-full bg-white dark:bg-gray-800 dark:text-gray-400 dark:border dark:border-gray-700 rounded-md shadow border border-gray-100/50">
			<slot />
		</div>
	</div>
</template>-->

<!--<template>
	<div :class="['w-full p-2 rounded-xl border backdrop-blur-[1px]', { 'bg-black/25 border-black/10': mode === 'dark', 'bg-white/20 border-white/15': mode === 'light' }]">
		<div class="h-full bg-white dark:bg-gray-800 dark:text-gray-400 dark:border dark:border-gray-700 rounded-md shadow">
			<slot />
		</div>
	</div>
</template>-->
