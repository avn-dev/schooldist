<script lang="ts">
// @ts-ignore
import { defineComponent, PropType, ref } from 'vue3'
import GuiFilterBarElementFilterSearch from './filter-element/ElementFilterSearch.vue'
import GuiFilterBarElementSelect from './filter-element/ElementSelect.vue'
import GuiFilterBarElementTimefilter from './filter-element/ElementTimefilter.vue'
import { EMITS, PROPS } from '../../composables/filter-element'
import FilterModel, { SidebarElement } from '../../models/filter'
// @ts-ignore
import { buildPrimaryColorElementCssClasses } from '@Admin/utils/primarycolor'

export default defineComponent({
	components: {
		GuiFilterBarElementFilterSearch,
		GuiFilterBarElementSelect,
		GuiFilterBarElementTimefilter
	},
	props: {
		...PROPS,
		filter: { type: Object as PropType<FilterModel|SidebarElement>, required: true }
	},
	emits: EMITS,
	expose: [
		'element',
		'filter'
	],
	setup() {
		return {
			element: ref(),
			FilterModel,
			SidebarElement,
			buildPrimaryColorElementCssClasses
		}
	},
	data() {
		return {
			header: this.filter instanceof SidebarElement && this.filter.key !== 'search_available'
		}
	},
	methods: {
		updateNegate(value: boolean) {
			// TODO TS2532: Object is possibly 'undefined'
			// Keine Ahnung, was das soll, denn Prop ist required
			const filter = this.filter as FilterModel
			filter.negated = value
			this.$emit('update:modelValue', filter.value)
		}
	}
})
</script>

<template>
	<li :class="{ header }">
		<template v-if="header">
			<div class="flex py-1 px-2 gap-x-2 rounded-md items-center font-semibold font-heading bg-gray-100/50 dark:bg-gray-900">
				<div
					:class="[
						'flex-none h-6 w-1 rounded-full inline-block',
						buildPrimaryColorElementCssClasses(),
					]"
				/>
				<div class="grow">
					{{ filter.label }}
				</div>
			</div>
		</template>
		<gui-filter-bar-element-filter-search
			v-else-if="filter instanceof SidebarElement && filter.key === 'search_available'"
			:model-value="filter.value"
			@update:model-value="$emit('update:modelValue', $event)"
		/>
		<gui-filter-bar-element-select
			v-else-if="filter instanceof FilterModel && filter.type === 'select'"
			ref="element"
			:model-value="modelValue"
			:filter="filter"
			:view="view"
			@reset:model-value="$emit('reset:modelValue', $event)"
			@update:model-value="$emit('update:modelValue', $event)"
			@update:negate="updateNegate($event)"
		/>
		<gui-filter-bar-element-timefilter
			v-else-if="filter instanceof FilterModel && filter.type === 'timefilter'"
			ref="element"
			:model-value="modelValue"
			:filter="filter"
			:view="view"
			@reset:model-value="$emit('reset:modelValue', $event)"
			@update:model-value="$emit('update:modelValue', $event)"
		/>
	</li>
</template>