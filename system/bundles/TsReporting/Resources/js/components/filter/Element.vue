<script lang="ts">
import { defineComponent, PropType } from 'vue3'
import type { QueryFilter } from '../../types'
import TimeframeFilter from './Timeframe.vue'
import SelectFilter from './Select.vue'

export default defineComponent({
	components: {
		TimeframeFilter,
		SelectFilter
	},
	props: {
		filter: {
			type: Object as PropType<QueryFilter>,
			required: true
		}
	},
	emits: [
		'update'
	]
})
</script>

<template>
	<li :class="['filter-element', `filter-element-${filter.component}`, filter.required ? 'filter-element-required' : '']">
		<label>{{ filter.name }}</label>
		<component
			:is="`${filter.component}-filter`"
			:model-value="filter.value"
			:options="filter.options"
			:type="filter.type"
			@update:model-value="$emit('update', $event)"
		/>
		<a
			v-if="!filter.required"
			@click="$emit('update', null)"
		>
			<i class="fa fa-times" />
		</a>
	</li>
</template>
