<script lang="ts">
import { defineComponent, h, inject, type ComputedRef } from 'vue'
import GridItemContent from './GridItemContent.vue'
import { GridLayoutItem } from '../../types/backend/app'

export default defineComponent({
	name: "GridItem",
	props: {
		x: { type: Number, required: true },
		y: { type: Number, required: true },
		id: { type: String, default: null },
		rows: { type: Number, default: 1 },
		cols: { type: Number, default: 1 },
		minRows: { type: Number, default: 1 },
		minCols: { type: Number, default: 1 }
	},
	setup(props, { slots, attrs }) {
		const editable: ComputedRef<boolean>|undefined = inject('editable')
		const addItem = inject<((item: GridLayoutItem) => GridLayoutItem) | undefined>('addItem')
		const removeItem = inject<((item: GridLayoutItem) => void) | undefined>('removeItem')
		const startResize = inject<((item: GridLayoutItem, event: MouseEvent) => void) | undefined>('startResize')

		if (editable && addItem && removeItem && startResize) {
			// TODO move?
			const remove = () => removeItem(gridItem)
			const resize = (event: MouseEvent) => startResize(gridItem, event)

			const gridItem = addItem({
				...props,
				element: h(GridItemContent, attrs, {
					default: () => (slots.default) ? slots.default() : null,
					header: () => (slots.header) ? slots.header({ editable: editable.value, remove }) : null,
					footer: () => (slots.footer) ? slots.footer({ editable: editable.value, remove, resize }) : null
				})
			})
		} else {
			console.log('Missing injected methods or variables')
		}
	},
	render() {
		return h('div')
	}
})
</script>

