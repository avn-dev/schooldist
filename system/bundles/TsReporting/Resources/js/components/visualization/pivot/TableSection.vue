<script lang="ts">
import { defineComponent, PropType } from 'vue3'
import { SumCell, ValueCell, NullCell } from '../../../composables/visualization'

export default defineComponent({
	props: {
		rows: { type: Array as PropType<ValueCell[][]>, required: true },
		type: { type: String, required: true }
	},
	setup(props) {
		return {
			element: props.type === 'thead' ? 'th' : 'td' ,
			NullCell,
			SumCell
		}
	}
})
</script>

<template>
	<component :is="type">
		<tr
			v-for="(row, index) in rows"
			:key="index"
		>
			<component
				:is="element"
				v-for="(cell, j) in row.filter(r => !(r instanceof NullCell))"
				:key="j"
				:title="cell.label"
				:rowspan="cell.rowspan > 1 ? cell.rowspan : null"
				:colspan="cell.colspan > 1 ? cell.colspan : null"
				:class="[cell.align && cell.align !== 'left' ? `text-${cell.align}` : '', cell instanceof SumCell ? 'sum' : '']"
				:style="[cell instanceof SumCell ? 'font-weight: bold' : '']"
				:data-key="cell.key"
			>
				{{ cell.formatted }}
			</component>
		</tr>
	</component>
</template>
