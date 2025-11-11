<script lang="ts">
import { defineComponent } from 'vue3'
import { Component, HeadCell, ValueCell } from '../../composables/visualization'
import { PivotConfig, PivotTable } from '../../services/pivot-visualization'
import TableView from './pivot/TableView.vue'

export default defineComponent({
	components: {
		TableView
	},
	extends: Component,
	setup(props) {
		const config = new PivotConfig()
		config.showGrandTotals = props.config.pivot?.show_grand_totals ?? config.showGrandTotals
		config.showRowTotals = props.config.pivot?.show_row_totals ?? config.showRowTotals
		config.labelGrandTotals = props.config.pivot?.grand_totals ?? config.labelGrandTotals
		config.labelRowTotals = props.config.pivot?.row_totals ?? config.labelRowTotals
		config.labelSubTotals = props.config.pivot?.subtotals_for_label ?? config.labelSubTotals

		const service = new PivotTable(props.definitions, config)
		const [head, body, foot] = service.generate(props.rows)

		return {
			head,
			body,
			foot,
			HeadCell,
			ValueCell
		}
	},
	methods: {
		export() {
			return {
				head: this.head,
				body: this.body,
				foot: this.foot
			}
		}
	}
})
</script>

<template>
	<table-view
		:head="head"
		:body="body"
		:foot="foot"
	/>
</template>
