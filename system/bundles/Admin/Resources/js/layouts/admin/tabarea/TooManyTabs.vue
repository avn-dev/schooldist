<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
	name: "TooManyTabs"
})
</script>

<template>
	<div class="sm:flex sm:items-start py-6 px-3 ">
		<div class="mt-2">
			<p class="text-sm text-black dark:text-gray-50">
				{{ $l10n.translate('tabs.too_many.text') }}
			</p>
		</div>
	</div>
</template>
