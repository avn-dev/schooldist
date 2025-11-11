<script lang="ts">
import { defineComponent, type PropType } from 'vue'
// @ts-ignore
import { buildPrimaryColorElementCssClasses } from '@Admin/utils/primarycolor'
// @ts-ignore
import { ComponentSize } from "@Admin/types/backend/app"
// @ts-ignore
import { ContentType } from "@Admin/types/backend/router"
import { NewsEntry } from '../../types'
// @ts-ignore
import router from '@Admin/router'
import NewsEntryPreview from './News/NewsEntryPreview.vue'

export default defineComponent({
	name: "NewsWidget",
	props: {
		news: { type: Object as PropType<NewsEntry>, required: true },
	},
	setup() {

		const openEntry = async (entry: NewsEntry) => {
			await router.openModal<string>({
				title: entry.title,
				content: { type: ContentType.component, payload: { component: NewsEntryPreview, payload: { entry: entry } }},
				size: ComponentSize.medium,
				closable: true
			})
		}

		return {
			openEntry,
			buildPrimaryColorElementCssClasses
		}
	}
})
</script>

<template>
	<div
		v-if="news.length === 0"
		class="text-center py-6"
	>
		{{ $l10n.translate('dashboard.news.empty') }}
	</div>
	<ul
		v-else
		role="list"
		class="text-xs flex flex-col gap-1"
	>
		<li
			v-for="entry in news"
			:key="entry.key"
		>
			<div
				:class="[
					'group flex items-center gap-1 p-2 rounded-md  cursor-pointer',
					(entry.important) ? buildPrimaryColorElementCssClasses() : 'bg-gray-50 hover:bg-gray-100 text-gray-900 dark:bg-gray-900 dark:hover:bg-gray-950 dark:text-gray-50'
				]"
				@click="openEntry(entry)"
			>
				<div class="min-w-0">
					<span class="text-xs font-semibold">{{ entry.title }}</span>
					&centerdot; <span :class="(entry.important) ? buildPrimaryColorElementCssClasses() : 'text-gray-400 dark:text-gray-200'">{{ entry.date }}</span>
				</div>
			</div>
		</li>
	</ul>
</template>
