<script lang="ts">
import { defineComponent, ref, Ref, Teleport as teleport_, TeleportProps, VNodeProps } from 'vue3'
import * as debounce from 'debounce-promise'
import GuiSidebarElement from './filter-bar/SidebarElement.vue'
import GuiFilterTag from './filter-bar/FilterTag.vue'
import GuiSidebar from './GuiSidebar.vue'
import { default as FilterModel, FilterQuery, FilterValue } from '../models/filter'
import { PROPS, useDefault } from '../composables/gui-component'
import { useContextMenu } from '@Admin/composables/contextmenu'
import { buildPrimaryColorElementCssClasses } from '@Admin/utils/primarycolor'
import ContextMenu from '@Admin/components/ContextMenu.vue'
import FilterContextMenu from './filter-bar/FilterContextMenu.vue'

export default defineComponent({
	components: {
		GuiSidebarElement,
		GuiFilterTag,
		GuiSidebar,
		ContextMenu,
		FilterContextMenu
	},
	props: PROPS,
	setup(props) {
		const filters = props.gui?.filters as Ref<FilterModel[]>
		const sidebar = ref<InstanceType<typeof GuiSidebar>>()
		const search = ref<HTMLFormElement>()
		// TODO Workaround für TypeScript Error: https://github.com/vuejs/vue-next/issues/2855
		const Teleport = teleport_ as {
			new (): {
				$props: VNodeProps & TeleportProps
			}
		}

		return {
			...useDefault(props),
			filters,
			sidebar,
			search,
			Teleport,
			buildPrimaryColorElementCssClasses
		}
	},
	data() {
		return {
			// @ts-ignore
			searchField: this.filters.find((e: FilterModel) => e.type === 'input'),
			searchDebounced: debounce(() => this.executeSearch(), 900),
			sidebarVisible: false,
			// @ts-ignore
			barFilters: this.filters.filter((e: FilterModel) => e.isShownInBar())
		}
	},
	computed: {
		// Aktive Filter, die nicht immer in der Bar angezeigt werden
		filterTags() {
			return this.filters.filter(element => {
				return element.type !== 'input' && !element.isShownInBar() && element.hasValue()
			})
		}
	},
	mounted() {
		this.emitter.emit('gui.filter-bar.mounted')
	},
	methods: {
		updateFilter(element: FilterModel, value: FilterValue, debounce = false) {
			element.value = value
			debounce ? this.searchDebounced() : this.executeSearch()
		},
		updateQuery(query: FilterQuery) {
			this.gui.filterQuery.value = query
			this.gui.loadTable(true, undefined, undefined, '&filter_query_changed')
		},
		resetFilter(element: FilterModel) {
			element.reset()
			this.executeSearch()
		},
		async focusFilter(element: FilterModel, event: MouseEvent) {
			const { openContextMenu } = useContextMenu()

			const elementRect = (event.target as HTMLElement)?.parentElement?.getBoundingClientRect()

			if (elementRect) {
				/* eslint-disable @typescript-eslint/no-explicit-any */
				const action = await openContextMenu<{action: string, value?: any}>(event,
					{ component: FilterContextMenu, payload: { element: element } },
					elementRect.x,
					elementRect.y + elementRect.height + 5
				)

				if (action) {
					if (action.action === 'update') {
						this.updateFilter(element, action.value)
					} else if (action.action === 'reset') {
						this.resetFilter(element)
					}
				}
			}

			//this.sidebarVisible = true
			//this.sidebar.focusFilter(element)
		},
		clearInput() {
			this.resetFilter(this.searchField as FilterModel)
			if (this.search) {
				this.search.focus()
			}
		},
		executeSearch() {
			this.gui.executeFilterSearch(false)
		}
	}
})
</script>

<template>
	<ul class="gui-filter-bar flex-wrap">
		<li v-if="gui?.options.info_icon_edit_mode || gui?.options.help_url">
			<a
				class="gui-help-btn"
				:class="{'inactive': !gui?.options.help_url}"
				@click="gui?.openHelp()"
			>
				<i class="fa fa-question" />
				{{ gui.getTranslation('help') }}
			</a>
		</li>
		<!-- .has-feedback für fa-times -->
		<li class="has-feedback relative">
			<input
				ref="search"
				:value="searchField.value"
				type="text"
				class="text-xs bg-white border-none px-1.5 py-1 rounded items-center relative ring-1 bg-white text-gray-500 ring-gray-100/75 hover:text-gray-600 hover:ring-gray-200 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 dark:hover:text-gray-100"
				:placeholder="gui.getTranslation('filter_search')"
				@keyup="updateFilter(searchField, $event.target.value, true)"
			>
			<a
				v-show="searchField.value"
				class="cursor-pointer absolute top-1 right-2 text-gray-500"
				@click="clearInput"
			>
				<i class="fa fa-times" />
			</a>
		</li>

		<gui-sidebar-element
			v-for="element in barFilters"
			:key="element.key"
			:model-value="element.value"
			:filter="element"
			view="bar"
			@reset:model-value="resetFilter(element)"
			@update:model-value="updateFilter(element, $event)"
		/>

		<gui-filter-tag
			v-for="element in filterTags"
			:key="element.id"
			:filter="element"
			@remove:filter="resetFilter"
			@focus:filter="focusFilter"
		/>

		<li>
			<button
				type="button"
				:class="[
					//'flex items-center gap-x-1 rounded p-1 text-xs font-medium bg-gray-300 text-gray-900 hover:bg-gray-400',
					'flex flex-nowrap items-center gap-x-1 text-gray-700 border border-gray-100/75 px-1.5 py-1 rounded cursor-pointer hover:bg-gray-100'
					//buildPrimaryColorElementCssClasses()
				]"
				@click="sidebarVisible = !sidebarVisible"
			>
				<i class="fa fa-filter" />
				{{ gui.getTranslation('filter') }}
			</button>
		</li>

		<ContextMenu />

		<component
			:is="Teleport"
			to="body"
		>
			<gui-sidebar
				ref="sidebar"
				:filters="filters"
				:visible="sidebarVisible"
				@reset:filter="resetFilter"
				@update:filter="updateFilter"
				@update:query="updateQuery"
				@close="sidebarVisible = false"
			/>
		</component>
	</ul>
</template>