<script lang="ts">

import { defineComponent } from 'vue3'
import BlockSelection from "./Filter/BlockSelection.vue"
import SelectFilter from "./Filter/SelectFilter.vue"
import { useScheduling } from "../../scheduling/composables/scheduling"

export default defineComponent({
	name: "Filters",
	components: { BlockSelection, SelectFilter },
	props: {
		floors: { type: Array, required: true },
	},
	setup() {
		const { setFilterValue } = useScheduling()

		return {
			setFilterValue
		}
	}
})
</script>

<template>
	<div class="flex flex-row items-stretch gap-x-2">
		<BlockSelection />
		<SelectFilter
			v-if="floors.length > 0"
			name="floor"
			:options="floors"
			@value="setFilterValue"
		/>
	</div>
</template>
