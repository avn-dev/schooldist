<script lang="ts">
import { defineComponent, inject, PropType } from 'vue3'
import { GuiInstance } from '../../../composables/gui-component'
import FilterModel from '../../../models/filter'

export default defineComponent({
	props: {
		filter: { type: Object as PropType<FilterModel>, required: true }
	},
	emits: [
		'update:negate'
	],
	setup() {
		return {
			gui: inject('gui') as GuiInstance
		}
	}
})
</script>

<template>
	<div class="flex flex-row items-center gap-x-2">
		<div class="radio">
			<label>
				<input
					type="radio"
					:checked="!filter.negated"
					:disabled="!filter.hasValue()"
					@change="$emit('update:negate', false)"
				>
				{{ gui.getTranslation('filter_is') }}
			</label>
		</div>
		<div class="radio">
			<label>
				<input
					type="radio"
					:checked="filter.negated"
					:disabled="!filter.hasValue()"
					@change="$emit('update:negate', true)"
				>
				{{ gui.getTranslation('filter_is_not') }}
			</label>
		</div>
	</div>
</template>