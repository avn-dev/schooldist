<script lang="ts">
import { ref, type Ref, PropType, defineComponent } from 'vue3'
import { InterfaceBackend as Interface } from "../../types/backend/app"
import { buildPrimaryColorCssClass, getPrimaryColorContrastShade } from '../../utils/primarycolor'
import { sleep } from '../../utils/util'
import { useTooltip } from '../../composables/tooltip'
import { useTabs } from "../../composables/tabs"
import { useSearch } from "../../composables/search"
import AdminLayout from "../../layouts/AdminLayout.vue"
import TabArea from "../../layouts/admin/TabArea.vue"
import PageHeader from "../../layouts/admin/PageHeader.vue"
import TabContent from "../../layouts/admin/tabarea/TabContent.vue"
import router from "../../router"
import { safe } from '../../utils/promise'

export default defineComponent({
	name: "Admin",
	components: { TabContent, PageHeader, AdminLayout, TabArea },
	props: {
		interface: { type: Object as PropType<Interface>, required: true },
		title: { type: String, required: true },
	},
	setup() {
		const { tabs } = useTabs()
		const { loading: loadingSearch, openSearch } = useSearch()
		const { showTooltip } = useTooltip()
		const loadingBookmarks: Ref<boolean> = ref(false)

		const openBookmarks = async () => {
			loadingBookmarks.value = true
			await safe(router.openBookmarks())
			await sleep(100)
			loadingBookmarks.value = false
		}

		return {
			tabs,
			loadingSearch,
			loadingBookmarks,
			openSearch,
			openBookmarks,
			buildPrimaryColorCssClass,
			getPrimaryColorContrastShade,
			showTooltip
		}
	}
})
</script>

<template>
	<AdminLayout
		:interface="interface"
		:title="title"
	>
		<div class="h-full flex flex-col">
			<PageHeader class="flex-1">
				<nav class="w-full h-full flex font-sans space-x-1 items-center pl-2 pr-4">
					<div class="flex-none space-x-1 hidden lg:inline-flex">
						<button
							type="button"
							:class="[
								'rounded px-2 py-1',
								buildPrimaryColorCssClass('hover:bg', getPrimaryColorContrastShade(), 20),
								buildPrimaryColorCssClass('text', getPrimaryColorContrastShade('text')),
							]"
							@click="openBookmarks"
							@mouseenter="showTooltip($l10n.translate('interface.bookmarks'), $event, 'bottom')"
						>
							<i :class="[loadingBookmarks ? 'fa fa-spinner fa-spin' : 'fa fa-th-large']" />
						</button>
						<button
							type="button"
							:class="[
								'rounded px-2 py-1',
								buildPrimaryColorCssClass('hover:bg', getPrimaryColorContrastShade(), 20),
								buildPrimaryColorCssClass('text', getPrimaryColorContrastShade('text')),
							]"
							@click="openSearch"
							@mouseenter="showTooltip($l10n.translate('interface.search'), $event, 'bottom')"
						>
							<i :class="[loadingSearch ? 'fa fa-spinner fa-spin' : 'fa fa-search']" />
						</button>
					</div>
					<div class="grow">
						<TabArea />
					</div>
				</nav>
			</PageHeader>
			<div class="grow overflow-hidden">
				<TabContent
					v-for="tab in tabs"
					:key="`tab-content-${tab.payload.id}`"
					:tab="tab"
					class="h-full w-full overflow-auto"
					:data-state="tab.state.state"
				/>
			</div>
		</div>
	</AdminLayout>
</template>
