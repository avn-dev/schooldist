<script lang="ts">
// @ts-ignore
import { defineComponent, inject, PropType } from 'vue3'
// @ts-ignore
import {
	Dialog as HeadlessUiDialog,
	DialogPanel as HeadlessUiDialogPanel,
	TransitionRoot as HeadlessUiTransitionRoot,
	TransitionChild as HeadlessUiTransitionChild
} from '@headlessui/vue'
import { GuiInstance } from '../types/gui'
import GuiSidebarElement from './filter-bar/SidebarElement.vue'
import GuiFilterQuery from './filter-bar/FilterQuery.vue'
import FilterModel, { FilterValue, SidebarElement } from '../models/filter'
// @ts-ignore
import RoundedBox from "@Admin/components/RoundedBox.vue"
// @ts-ignore
import { buildPrimaryColorElementCssClasses } from "@Admin/utils/primarycolor"

export default defineComponent({
	components: {
		GuiSidebarElement,
		GuiFilterQuery,
		HeadlessUiDialog,
		HeadlessUiDialogPanel,
		HeadlessUiTransitionRoot,
		HeadlessUiTransitionChild,
		RoundedBox
	},
	props: {
		filters: { type: Array as PropType<Array<FilterModel>>, required: true },
		visible: { type: Boolean, required: true }
	},
	emits: [
		'close',
		'reset:filter',
		'update:filter',
		'update:query'
	],
	expose: ['focusFilter'],
	setup() {
		return {
			gui: inject('gui') as GuiInstance,
			buildPrimaryColorElementCssClasses
		}
	},
	data() {
		return {
			currentTab: 'filter',
			elements: {} as Record<string, typeof GuiSidebarElement>,
			filterSearch: '',
			typeLabels: ['timefilter', 'select'],
			tabs: [
				{ key: 'filter', translation: 'filter' }
			]
		}
	},
	computed: {
		sidebarElements() {
			const filters = this.filters.filter(f => f.type !== 'input')
			let elements: Array<FilterModel|SidebarElement> = []
			let available: Array<FilterModel|SidebarElement> = []
			const used = filters.filter((filter: FilterModel) => filter.hasValue())
			if (used.length) {
				elements.push(new SidebarElement('label_used', this.gui.getTranslation('filter_label_used')))
				elements = elements.concat(used)
			}
			elements.push(new SidebarElement('label_available', this.gui.getTranslation('filter_label_available')))
			elements.push(new SidebarElement('search_available', this.gui.getTranslation('filter_search'), this.filterSearch))
			available = filters.filter((filter: FilterModel) => !filter.hasValue() && filter.compareForSearch(this.filterSearch))
			this.typeLabels.forEach(t => {
				const k = available.findIndex(e => e instanceof FilterModel && e.type === t)
				if (k !== -1) {
					available.splice(k, 0, new SidebarElement(`label_${t}`, this.gui.getTranslation(`filter_label_${t}`))) // filter_label_timefilter, filter_label_select
				}
			})
			elements = elements.concat(available)
			return elements
		}
	},
	methods: {
		updateFilter(element: FilterModel|SidebarElement, event: FilterValue) {
			if (element instanceof SidebarElement && element.key === 'search_available') {
				this.filterSearch = event as string
				return
			}
			if (element instanceof FilterModel) {
				const focus = !element.hasValue()
				this.$emit('update:filter', element, event)
				if (focus) {
					this.focusFilter(element)
				}
			}
		},
		setFilterRef(component: InstanceType<typeof GuiSidebarElement>) {
			if (!component) {
				// app.unmount()
				return
			}
			// TODO as: Vue 3 und expose harmornieren noch nicht mit TypeScript: https://github.com/vuejs/vue-next/issues/4397
			this.elements[(component.filter as FilterModel).key] = component
		},
		focusFilter(filter: FilterModel) {
			this.filterSearch = ''
			// Hier wird für das Leeren der Suche und das Scrollen eine minimale Verzögerung benötigt ($nextTick ist zu wenig)
			setTimeout(() => this.elements[filter.key].element.focus(), 250)
		}
	}
})
</script>

<template>
	<HeadlessUiTransitionRoot
		as="template"
		:show="visible"
	>
		<HeadlessUiDialog
			as="div"
			class="relative z-50"
			@close="$emit('close')"
		>
			<HeadlessUiTransitionChild
				as="template"
				class="opacity-0"
				enter="ease-in-out duration-500"
				enter-from="opacity-0"
				enter-to="opacity-100"
				leave="ease-in-out duration-500"
				leave-from="opacity-100"
				leave-to="opacity-0"
			>
				<div class="fixed inset-0 bg-gray-900 opacity-80 transition-opacity dark:bg-gray-950 dark:opacity-90" />
			</HeadlessUiTransitionChild>
			<div class="fixed inset-0">
				<div class="absolute inset-0">
					<div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
						<HeadlessUiTransitionChild
							as="template"
							class="translate-x-full"
							enter="transform transition ease-in-out duration-500 sm:duration-700"
							enter-from="translate-x-full"
							enter-to="translate-x-0"
							leave="transform transition ease-in-out duration-500 sm:duration-700"
							leave-from="translate-x-0"
							leave-to="translate-x-full"
						>
							<HeadlessUiDialogPanel class="pointer-events-auto relative w-[22rem]">
								<div class="absolute left-0 top-0 -ml-6 flex pr-1 pt-4 lg:-ml-8 lg:pr-4">
									<button
										type="button"
										class="relative text-gray-200 hover:text-white"
										@click="$emit('close')"
									>
										<i
											class="fa fa-times"
											aria-hidden="true"
										/>
									</button>
								</div>
								<div class="flex flex-col text-sm h-full p-2 gap-y-2">
									<RoundedBox
										v-if="tabs.length > 1"
										class="flex-none text-sm"
									>
										<div class="flex-none p-2">
											<nav class="-mb-px flex">
												<button
													v-for="tab in tabs"
													:key="tab.key"
													class="w-1/3 py-1 px-0.5 text-center text-sm rounded-md"
													:class="[
														currentTab === tab.key
															? buildPrimaryColorElementCssClasses()
															: 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 hover:border-gray-300 dark:hover:text-gray-100'
													]"
													:aria-current="currentTab === tab.key ? 'page' : undefined"
													@click="currentTab = tab.key"
												>
													{{ gui.getTranslation(tab.translation) }}
												</button>
											</nav>
										</div>
									</RoundedBox>
									<div
										v-if="currentTab === 'filter'"
										class="grow"
									>
										<div class="h-full flex flex-col gap-y-2">
											<RoundedBox class="flex-none">
												<gui-filter-query
													@update:query="$emit('update:query', $event)"
												/>
											</RoundedBox>
											<!-- h-64 damit das overflow funktioniert -->
											<RoundedBox class="h-64 max-h-full grow">
												<div class="h-full overflow-auto p-2">
													<transition-group
														tag="ul"
														name="gui-sidebar-transition"
														class="sidebar-menu flex flex-col gap-y-2"
													>
														<gui-sidebar-element
															v-for="element in sidebarElements"
															:key="element.key"
															:ref="setFilterRef"
															:model-value="element.value"
															:filter="element"
															view="side"
															@update:model-value="updateFilter(element, $event)"
															@reset:model-value="$emit('reset:filter', element, $event)"
														/>
													</transition-group>
												</div>
											</RoundedBox>
										</div>
									</div>
								</div>
							</HeadlessUiDialogPanel>
						</HeadlessUiTransitionChild>
					</div>
				</div>
			</div>
			<!--<div
				class="gui-sidebar control-sidebar control-sidebar-dark"
				:class="{ hidden: visible }"
			>
				<ul class="nav nav-tabs nav-justified control-sidebar-tabs">
					<li :class="{ active: tab === 'filter'}">
						<a @click="tab = 'filter'"><i class="fa fa-filter" /></a>
					</li>
					<li>
						<a @click="$emit('close')"><i class="fa fa-times" /></a>
					</li>
				</ul>
				<div v-if="tab === 'filter'">
					<gui-filter-query
						@update:query="$emit('update:query', $event)"
					/>
					<transition-group
						tag="ul"
						name="gui-sidebar-transition"
						class="sidebar-menu"
					>
						<gui-sidebar-element
							v-for="element in sidebarElements"
							:key="element.key"
							:ref="setFilterRef"
							:model-value="element.value"
							:filter="element"
							view="side"
							@update:model-value="updateFilter(element, $event)"
							@reset:model-value="$emit('reset:filter', element, $event)"
						/>
					</transition-group>
				</div>
			</div>-->
		</HeadlessUiDialog>
	</HeadlessUiTransitionRoot>
</template>