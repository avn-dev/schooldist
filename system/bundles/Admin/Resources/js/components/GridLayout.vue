<script lang="ts">
import { computed, defineComponent, onMounted, provide, ref, type Ref, type VNode } from 'vue'
import { GridLayoutChangedEvent, GridLayoutItem, GridLayoutItemPayload } from '../types/backend/app'

/**
 * PostCSS clean up
 * grid-cols-1
 * grid-cols-2
 * grid-cols-3
 * grid-cols-4
 * grid-cols-5
 * grid-cols-5
 * grid-cols-7
 * grid-cols-8
 * grid-cols-9
 * grid-cols-10
 * grid-cols-11
 * grid-cols-12
 */
export default defineComponent({
	name: "GridLayout",
	props: {
		debug: { type: Boolean, default: false },
		editable: { type: Boolean, default: true },
		colDimension: { type: Number, default: 8 }, // rem => 128px
		gap: { type: Number, default: 0.5 }, // rem => 8px
		gridDimension: { type: Number, default: 12 },
		emptySpaces: { type: Boolean, default: false },
	},
	emits: ['changed'],
	setup(props, { emit }) {
		const editableRef = computed(() => props.editable)
		const containerRef: Ref<HTMLElement|null> = ref(null)
		const gridItems: Ref<GridLayoutItem[]> = ref([])
		const draggingElement: Ref<GridLayoutItem|null> = ref(null)
		const resizingElement: Ref<GridLayoutItem|null> = ref(null)
		const otherElements: Ref<VNode[]> = ref([])
		const resizingStart: Ref<{ clientX: number, clientY: number, rows: number, cols: number }|null> = ref(null)
		const maxRows: Ref<number> = ref(0)
		let mounted = false
		let layoutCache: GridLayoutItemPayload[] = []

		onMounted(() => {
			//fillEmptySpaces(gridItems.value)
			mounted = true
		})

		const calculateMaxRows = () => {
			maxRows.value = Math.max(...gridItems.value.map((item) => item.y + item.rows))
		}

		const removeItem = (item: GridLayoutItem) => {
			if (!props.editable) {
				console.warn('Grid layout is not editable')
				return
			}

			gridItems.value = gridItems.value.filter((loop: GridLayoutItem) => loop.id !== item.id)

			//fillEmptySpaces([item])

			fireChangedEvent([item], GridLayoutChangedEvent.removed)
		}

		const buildItemPayload = (items: GridLayoutItem[]): GridLayoutItemPayload[] => {
			return items.map((item: GridLayoutItem, index: number) => ({ index: index, id: item.id, x: item.x, y: item.y, rows: item.rows, cols: item.cols } as GridLayoutItemPayload))
		}

		const fillLayoutCache = () => {
			layoutCache = buildItemPayload(gridItems.value)
		}

		const restoreLayout = () => {

			if (layoutCache.length === 0) {
				console.warn(`Layout cache is empty`)
				return
			}

			layoutCache.forEach((cache) => {
				gridItems.value[cache.index].x = cache.x
				gridItems.value[cache.index].y = cache.y
				gridItems.value[cache.index].rows = cache.rows
				gridItems.value[cache.index].cols = cache.cols
			})

			fillLayoutCache()
		}

		const addItem = (item: GridLayoutItem) => {

			const validate = (value: number, min: number, max: number, defaultValue: number) => (value >= min && value <= max) ? value : defaultValue
			let x = validate(item.x, 1, props.gridDimension, 1)
			let y = validate(item.y, 1, 100, 1)
			const rows = validate(item.rows, 1, 100, 2)
			const cols = validate(item.cols, 1, props.gridDimension, props.gridDimension)

			const overlapping = getOverlappingItems(x, y, rows, cols)
			if (overlapping.length > 0) {
				const freeSpace = findFreeSpace(rows, cols)
				x = freeSpace.x
				y = freeSpace.y
			}

			const gridItem = {
				id: item.id ?? `item-${gridItems.value.length}`,
				x: x,
				y: y,
				rows: rows,
				cols: cols,
				minRows: validate(item.minRows, 1, props.gridDimension, 1),
				minCols: validate(item.minCols, 1, props.gridDimension, 1),
				element: item.element
			}

			const isEmpty = gridItems.value.length === 0

			gridItems.value.push(gridItem)

			calculateMaxRows()

			if (mounted && !isEmpty) {
				//fillEmptySpaces([gridItem])
				fireChangedEvent([gridItem], GridLayoutChangedEvent.added)
			}

			fillLayoutCache()

			return gridItem
		}

		const moveItem = async (item: GridLayoutItem, x: number, y: number, moveOverlappings = true) => {

			const [moved, items] = await _moveItem(item, x, y, moveOverlappings)

			if (moved && moveOverlappings) {
				//fillEmptySpaces([item])
				fillLayoutCache()
				fireChangedEvent(items, GridLayoutChangedEvent.moved)
			} else if (moveOverlappings) {
				restoreLayout()
			}
		}

		const _moveItem = async (item: GridLayoutItem, x: number, y: number, moveOverlappings = true, ignoreItems: GridLayoutItem[] = [], level = 0): Promise<[boolean, GridLayoutItem[], GridLayoutItem[]]> => {

			const index = gridItems.value.indexOf(item)
			let moved: GridLayoutItem[] = []

			if (index !== -1) {
				// Calculate new item position
				const newX = ((x + item.cols) > (props.gridDimension + 1)) ? (props.gridDimension + 1) - item.cols : x
				//const newY = ((y + item.rows) > (props.gridDimension + 1)) ? (props.gridDimension + 1) - item.rows : y
				const newY = y

				gridItems.value[index].x = newX
				gridItems.value[index].y = newY

				if (moveOverlappings) {
					const originalPosition = layoutCache.find(loop => loop.id === item.id)

					if (!originalPosition) {
						console.error(`Missing original position of item "${item.id}"`)
						return [false, [], []]
					}

					if (
						newX === originalPosition.x && newY === originalPosition.y &&
						item.rows === originalPosition.rows && item.cols === originalPosition.cols
					) {
						return [true, [], []]
					}

					moved.push(item)

					const overlappingItems = getOverlappingItems(newX, newY, item.rows, item.cols, [item, ...ignoreItems])

					for (const overlappingItem of overlappingItems) {

						if (moved.indexOf(overlappingItem) !== -1) {
							continue
						}

						let childMoved = false
						let childBlockingItems: GridLayoutItem[] = []
						let childItems: GridLayoutItem[] = []
						let fallback = true

						if (level === 0) {
							const colsTopAvailable = (newY - 1)
							const colsLeftAvailable = (newX - 1)
							const colsRightAvailable = props.gridDimension - (newX - 1 + item.cols)

							// TODO verfeinern
							if (newX > originalPosition.x && colsLeftAvailable >= overlappingItem.cols) {
								for (let i = (newX - overlappingItem.cols); i >= 1; i--) {
									const childOverlappingItems = getOverlappingItems(i, overlappingItem.y, overlappingItem.rows, overlappingItem.cols, [item, overlappingItem])
									if (childOverlappingItems.length === 0) {
										[childMoved, childItems, childBlockingItems] = await _moveItem(overlappingItem, i, overlappingItem.y, true, [item, overlappingItem], (level + 1))
										fallback = false
										break
									}
								}
							} else if (newX < originalPosition.x && colsRightAvailable >= overlappingItem.cols) {
								for (let i = (newX + item.cols); i <= props.gridDimension; i++) {
									const childOverlappingItems = getOverlappingItems(i, overlappingItem.y, overlappingItem.rows, overlappingItem.cols, [item, overlappingItem])
									if (childOverlappingItems.length === 0) {
										[childMoved, childItems, childBlockingItems] = await _moveItem(overlappingItem, i, overlappingItem.y, true, [item, overlappingItem], (level + 1))
										fallback = false
										break
									}
								}
							} else if (colsTopAvailable >= overlappingItem.rows) {
								for (let i = (newY - overlappingItem.rows); i >= 1; i--) {
									const childOverlappingItems = getOverlappingItems(overlappingItem.x, i, overlappingItem.rows, overlappingItem.cols, [item, overlappingItem])
									if (childOverlappingItems.length === 0) {
										[childMoved, childItems, childBlockingItems] = await _moveItem(overlappingItem, overlappingItem.x, i, true, [item, overlappingItem], (level + 1))
										fallback = false
										break
									}
								}
							}
						}

						if (fallback) {
							[childMoved, childItems, childBlockingItems] = await _moveItem(overlappingItem, overlappingItem.x, newY + item.rows, true, [item], (level + 1))
						}

						if (childMoved) {
							moved = [...moved, ...childItems]
						} else {
							console.error(`Moving of item ${overlappingItem.id} failed`, childBlockingItems)
							return [false, [], childBlockingItems]
						}
					}

					if (props.debug) {
						console.info(`Moving item ${gridItems.value[index].id} from {${originalPosition.x}, ${originalPosition.y}} to {${newX}, ${newY}}`, level, ignoreItems.map((item) => item.id))
					}
				}

				calculateMaxRows()

				return [true, moved, []]
			}

			return [false, [], []]
		}

		const getOverlappingItems = (x: number, y: number, rows: number, cols: number, ignoreItems: GridLayoutItem[] = []) => {
			const coordinates = buildCoordinates(x, y, rows, cols)
			const overlapping: GridLayoutItem[] = []

			gridItems.value.forEach((item: GridLayoutItem) => {
				if (ignoreItems.indexOf(item) === -1) {
					const subCoordinates = buildCoordinates(item.x, item.y, item.rows, item.cols)
					const found = subCoordinates.find((subCoordinate) => typeof coordinates.find((loop) => loop.x === subCoordinate.x && loop.y === subCoordinate.y) !== 'undefined')
					if (found) {
						overlapping.push(item)
					}
				}
			})

			return overlapping
		}

		const findFreeSpace = (rows: number, cols: number) => {
			for (let y = 1; y <= (maxRows.value + rows); y++) {
				for (let x = 1; x <= (props.gridDimension - cols + 1); x++) {
					const overlappingItems: GridLayoutItem[] = getOverlappingItems(x, y, rows, cols)
					if (overlappingItems.length === 0) {
						return {x: x, y: y}
					}
				}
			}

			return { x: 1, y: maxRows.value + rows }
		}

		/*const fillEmptySpaces = (items: GridLayoutItem[] = []) => {
			// TODO
			return

			if (props.emptySpaces) {
				return
			}

			for (let [index, item] of items.entries()) {

				let newX = item.x
				let newY = item.y
				let newRows = item.rows
				let newCols = item.cols

				for (let y = (item.y - 1); y >= (item.y - 5); y--) {
					if (y <= 0) break
					const overlappingItems = getOverlappingItems(item.x, y, item.rows, item.cols, [item])
					if (overlappingItems.length === 0) {
						newY = y
						if (newRows < (maxRows.value - 1)) newRows++
					} else {
						break
					}
				}

				for (let x = (item.x - 1); x >= 1; x--) {
					const overlappingItems = getOverlappingItems(x, newY, item.rows, item.cols, [item])
					if (overlappingItems.length === 0) {
						newX = x
						if (newCols < props.gridDimension) newCols++
					} else {
						break
					}
				}

				/*for (let rows = (newY + newRows - 1); rows <= (maxRows.value - newY - 1); rows++) {
					const overlappingItems = getOverlappingItems(newX, newY, rows, item.cols, [item])

					//if (item.id === 'both_0') {
						console.log('rows', item.id, newX, newY, rows, overlappingItems)
					//}

					if (overlappingItems.length === 0) {
						newRows = rows
					} else {
						break
					}
				}

				console.log('start cols', item.id, newCols, props.gridDimension)

				for (let cols = newCols; cols <= (props.gridDimension - newX + 1); cols++) {
					const overlappingItems = getOverlappingItems(newX, newY, newRows, cols, [item])

					console.log('cols', item.id, newX, newY, cols, overlappingItems)

					if (overlappingItems.length === 0) {
						newCols = cols
					} else {
						break
					}
				}

				if (newX !== item.x || newY !== item.y || newRows !== item.rows || newCols !== item.cols) {
					console.log(`Fill ${item.id} from {${item.x},${item.y}-${item.rows},${item.cols}} to {${newX},${newY}-${newRows},${newCols}}`)
					gridItems.value[index].x = newX
					gridItems.value[index].y = newY
					gridItems.value[index].rows = newRows
					gridItems.value[index].cols = newCols
				}
			}

		}*/

		const buildCoordinates = (x: number, y: number, rows: number, cols: number) => {
			const coordinates = []
			for (let i = y; i <= (y + (rows - 1)); i++) {
				for (let j = x; j <= (x + (cols - 1)); j++) {
					if (j <= props.gridDimension) {
						coordinates.push({ x: j, y: i })
					}
				}
			}
			return coordinates
		}

		const startDrag = (item: GridLayoutItem, event: MouseEvent) => {
			if (resizingElement.value) {
				event.preventDefault()
				return
			}

			draggingElement.value = item
		}

		const endDrag = () => {
			if (!draggingElement.value) {
				return
			}
			moveItem(draggingElement.value, draggingElement.value.x, draggingElement.value.y)
			resetDragState()
		}

		const handleDragOver = (x: number, y: number) => {
			if (!draggingElement.value) {
				return
			}
			moveItem(draggingElement.value, x, y, false)
		}

		const handleDrop = (x: number, y: number) => {
			if (!draggingElement.value) {
				return
			}
			// TODO currently not working
			console.log('drop', x, y)
			moveItem(draggingElement.value, x, y)
			resetDragState()
		}

		const resetDragState = () => {
			draggingElement.value = null
		}

		const startResizing = (item: GridLayoutItem, event: MouseEvent) => {
			if (!props.editable) {
				console.warn('Grid layout is not editable')
				return
			}
			resizingElement.value = item
			resizingStart.value = { clientX: event.clientX, clientY: event.clientY, rows: item.rows, cols: item.cols }
			document.addEventListener('mousemove', resize)
			document.addEventListener('mouseup', stopResize)
		}

		const resize = (event: MouseEvent) => {
			if (!resizingStart.value || !resizingElement.value || !containerRef.value) {
				return
			}

			const diffX = event.clientX - resizingStart.value.clientX
			const diffY = event.clientY - resizingStart.value.clientY

			// Calculate dimension of one single column
			const containerRect = containerRef.value.getBoundingClientRect()
			const colWidth = containerRect.width / props.gridDimension
			const rowHeight = containerRect.height / maxRows.value

			const colsDiff = Math.round(diffX / colWidth)
			const rowsDiff = Math.round(diffY / rowHeight)

			const index = gridItems.value.indexOf(resizingElement.value)

			if ((resizingStart.value.cols + colsDiff) >= resizingElement.value.minCols) {
				gridItems.value[index].cols = resizingStart.value.cols + colsDiff
			}

			if ((resizingStart.value.rows + rowsDiff) >= resizingElement.value.minRows) {
				gridItems.value[index].rows = resizingStart.value.rows + rowsDiff
			}
		}

		const stopResize = async () => {
			if (!resizingElement.value) {
				return
			}
			// Move item with original coordinates to reallocate all overlapping items
			await moveItem(resizingElement.value, resizingElement.value.x, resizingElement.value.y)
			resizingElement.value = null
			resizingStart.value = null
			document.removeEventListener('mousemove', resize)
			document.removeEventListener('mouseup', stopResize)
		}

		const fireChangedEvent = (changed: GridLayoutItem[], type: GridLayoutChangedEvent) => {
			emit('changed', buildItemPayload(changed), buildItemPayload(gridItems.value), type)
		}

		provide('addItem', addItem)
		provide('removeItem', removeItem)
		provide('startResize', startResizing)
		provide('editable', editableRef)

		return {
			containerRef,
			gridItems,
			otherElements,
			draggingElement,
			resizingElement,
			maxRows,
			startDrag,
			endDrag,
			handleDragOver,
			handleDrop,
			resetDragState,
			startResizing,
			removeItem
		}
	}
})
</script>

<template>
	<div :style="`height: ${maxRows * colDimension}rem`">
		<div class="hidden">
			<slot />
		</div>
		<div
			ref="containerRef"
			class="relative"
			:style="`height: ${maxRows * colDimension}rem`"
		>
			<!-- when start dragging `transition-all` destroys drag event in Chrome -->
			<div
				v-for="(gridItem, index) in gridItems"
				:key="`grid-item-${index}`"
				:class="['absolute', (draggingElement === gridItem || resizingElement === gridItem) ? 'z-50' : 'z-10 transition-all' ]"
				:style="[
					`height: ${gridItem.rows * colDimension}rem; width: ${gridItem.cols * (100/gridDimension)}%;`,
					`top: ${(gridItem.y - 1) * colDimension}rem; left: ${(gridItem.x - 1) * (100/gridDimension)}%`,
					(gap > 0 && (gridItem.x + gridItem.cols) < gridDimension) ? `padding-right: ${gap}rem` : '',
					(gap > 0) ? `padding-bottom: ${gap}rem` : ''
				]"
				:data-test="(gridItem.x + gridItem.rows)"
				:draggable="editable"
				@dragstart="startDrag(gridItem, $event)"
				@dragend="endDrag($event)"
			>
				<!-- Placeholder -->
				<div
					v-show="draggingElement === gridItem || resizingElement === gridItem"
					class="h-full w-full bg-gray-100/50 border-dashed border-2 border-gray-300 rounded-md"
					:data-x="gridItem.x"
					:data-y="gridItem.y"
				/>
				<!-- Box -->
				<div
					:class="[
						'group h-full w-full overflow-auto',
						{'invisible': draggingElement === gridItem || resizingElement === gridItem}
					]"
				>
					<div
						v-show="debug"
						:class="['relative p-2 bg-white rounded-xl h-full w-full border-2 border-gray-200', {'cursor-grab': editable}]"
					>
						<i
							class="absolute right-2 top-2 cursor-pointer fa fa-times pl-2 pb-2"
							@click="removeItem(gridItem, $event)"
						/>
						{{ gridItem.id }} - {{ `x:${gridItem.x}-y:${gridItem.y}` }} - {{ `rows:${gridItem.rows}-cols:${gridItem.cols}` }}
						<i
							class="absolute right-2 bottom-2 cursor-nwse-resize fas fa-chevron-right rotate-45 pl-2 pt-2"
							@mousedown.prevent="startResizing(gridItem, $event)"
						/>
					</div>
					<component
						:is="gridItem.element"
						v-show="!debug"
						:class="['h-full w-full overflow-auto', {'cursor-grab': editable}]"
						:data-x="gridItem.x"
						:data-y="gridItem.y"
						:data-cols="gridItem.cols"
						:data-rows="gridItem.rows"
					/>
				</div>
			</div>
			<div
				v-show="draggingElement || resizingElement"
				class="absolute top-0 left-0 bottom-0 right-0 z-40"
			>
				<div
					v-for="y in maxRows"
					:key="`grid-layer-${y}`"
					:class="`w-full grid grid-cols-${gridDimension}`"
				>
					<div
						v-for="x in gridDimension"
						:key="`grid-layer-${y}-${x}`"
						class="inline-flex items-enter justify-center"
						:style="[
							`height: ${colDimension}rem`,
							(gap > 0 && x < gridDimension) ? `padding-right: ${gap}rem` : '',
							(gap > 0) ? `padding-bottom: ${gap}rem` : ''
						]"
						@dragover="handleDragOver(x, y, $event)"
						@drop="handleDrop(x, y, $event)"
					>
						<span :class="{'hidden': !debug}">{{ x }} - {{ y }}</span>
					</div>
				</div>
			</div>
		</div>
	</div>
	<component
		:is="otherElement"
		v-for="(otherElement, index) in otherElements"
		:key="index"
	/>
</template>
