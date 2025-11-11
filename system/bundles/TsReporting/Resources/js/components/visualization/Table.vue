<script lang="ts">
import { defineComponent } from 'vue3'
import { Component, HeadCell, ValueCell } from '../../composables/visualization'
import type { QueryRowValue, QueryRowValueColumn } from '../../types'
import TableView from './pivot/TableView.vue'

export default defineComponent({
	components: {
		TableView
	},
	extends: Component,
	setup(props) {
		const head = [props.definitions.map((d) => new HeadCell(d.label, d))]
		const body = props.rows.map(row => {
			const r: ValueCell[] = []
			props.definitions.forEach((def, i) => {
				const cell = new ValueCell(row[i], def)
				if (def.type === 'column') {
					const tuple = row[i] as QueryRowValueColumn
					cell.value = tuple[0]
					cell.label = tuple[1]
				} else {
					cell.value = def.getItemLabel(cell.value as QueryRowValue)
				}
				r.push(cell)
			})
			return r
		})

		return {
			head,
			body,
			HeadCell,
			ValueCell
		}
	},
	methods: {
		export() {
			return { head: this.head, body: this.body }
		}
	}
})
</script>

<template>
	<table-view
		:head="head"
		:body="body"
	/>
</template>
