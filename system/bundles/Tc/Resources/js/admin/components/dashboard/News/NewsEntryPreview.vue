<script lang="ts">
import { type PropType } from "vue"
import { NewsEntry } from '../../../types'

export default {
	name: "NewsEntryPreview",
	props: {
		entry: {type: Object as PropType<NewsEntry>, required: true },
	}
}
</script>

<template>
	<div class="max-h-[80vh] text-sm p-4 overflow-auto">
		<!-- eslint-disable vue/no-v-html -->
		<div v-html="entry.content" />
		<div class="flex justify-end">
			<span class="text-xs text-gray-400">
				{{ entry.date }}
			</span>
		</div>
	</div>
</template>
