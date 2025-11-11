<script lang="ts">
import { defineComponent, inject, PropType } from 'vue3'
import FilterModel from '../../models/filter'
import { GuiInstance } from '../../composables/gui-component'

export default defineComponent({
	props: {
		filter: { type: Object as PropType<FilterModel>, required: true }
	},
	emits: [
		'focus:filter',
		'remove:filter'
	],
	setup() {
		return {
			gui: inject('gui') as GuiInstance
		}
	}
})
</script>

<template>
	<li class="flex items-center gap-x-1 rounded bg-gray-100 border border-gray-100 hover:bg-gray-200 text-xs cursor-pointer px-1">
		<a
			:title="`${gui.getTranslation('filter_remove')}: ${filter.label}`"
			class="text-gray-800 hover:text-gray-900 py-1"
			@click.stop="$emit('remove:filter', filter)"
		>
			<i class="fa fa-times" />
		</a>
		<a
			:title="`${gui.getTranslation('filter_change')}: ${filter.label}`"
			class="text-gray-800 hover:text-gray-900 py-1"
			@click.stop="$emit('focus:filter', filter, $event)"
		>
			{{ filter.buildLabel() }}
		</a>
	</li>
</template>