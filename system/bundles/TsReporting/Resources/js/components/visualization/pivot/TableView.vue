<script lang="ts">
import { defineComponent, PropType } from 'vue3'
import { HeadCell, SumCell, ValueCell } from '../../../composables/visualization'
import TableSection from './TableSection.vue'

export default defineComponent({
	components: {
		TableSection
	},
	props: {
		head: { type: Array as PropType<HeadCell[][]>, required: true },
		body: { type: Array as PropType<ValueCell[][]>, required: true },
		foot: { type: Array as PropType<ValueCell[][]>, default: () => [] }
	},
	setup() {
		return {
			HeadCell,
			SumCell,
			ValueCell
		}
	}
})
</script>

<template>
	<table class="table table-hover table-bordered">
		<table-section
			type="thead"
			:rows="head"
		/>
		<table-section
			type="tbody"
			:rows="body"
		/>
		<table-section
			type="tfoot"
			:rows="foot"
		/>
	</table>
</template>
