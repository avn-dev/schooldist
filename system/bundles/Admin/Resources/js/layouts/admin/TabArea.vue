<script lang="ts">
import { computed, defineComponent, ref, watch, nextTick, type Ref } from 'vue3'
import * as debounce from 'debounce-promise'
import { Tab } from "../../types/backend/app"
import { LoadingState } from "../../types/backend/router"
import { getPrimaryColor, buildPrimaryColorCssClass, getPrimaryColorContrastShade } from "../../utils/primarycolor"
import { useTabs } from "../../composables/tabs"
import { useInterface } from "../../composables/interface"
import TabButton from "./tabarea/TabButton.vue"

const MAX_TAB_SIZE = 200
const MIN_TAB_SIZE = 120
const GAP_TABS = 4 // see gap-x-1 = 4px

export default defineComponent({
	name: "TabArea",
	components: { TabButton },
	setup() {
		const { scope } = useInterface()
		const { allowSaving, orderedTabs, tabs, switchTab, moveTab, removeTab, getTab } = useTabs()
		const outerContainer: Ref<HTMLElement|null> = ref(null)
		const innerContainer: Ref<HTMLElement|null> = ref(null)
		const tabSize: Ref<number> = ref(MAX_TAB_SIZE)
		const overlappingSize: Ref<number> = ref(0)
		const scrollingLeft: Ref<number> = ref(0)
		let scrollingInterval: number|null = null

		const draggingElement: Ref<Tab|null> = ref(null)

		const scroll = (direction: string) => {
			if (direction === 'start') {
				scrollingLeft.value = 0
				stopScrolling()
			} else if (direction === 'end') {
				scrollingLeft.value = overlappingSize.value * -1
				stopScrolling()
			} else if (direction === 'active') {

				if (!innerContainer.value || !outerContainer.value || overlappingSize.value === 0) {
					return
				}

				// TODO
				const activeTab: HTMLElement | null = innerContainer.value.getElementsByClassName('active')[0] as HTMLElement ?? null

				if (activeTab) {
					let found = false
					do {
						const left = (scrollingLeft.value < 0) ? scrollingLeft.value * -1 : 0
						const offset = [activeTab.offsetLeft, activeTab.offsetLeft + activeTab.offsetWidth]
						const visibleArea = [left, left + outerContainer.value.offsetWidth]

						if (offset[0] < visibleArea[0]) {
							scrollingLeft.value += 2
						} else if (offset[1] > visibleArea[1]) {
							scrollingLeft.value -= 2
						} else {
							found = true
						}
					} while (found === false)
				}

			} else if (!scrollingInterval) {
				scrollingInterval = window.setInterval(() => {
					if (direction === 'right') {
						if (scrollingLeft.value > (overlappingSize.value * -1)) {
							scrollingLeft.value -= 2
						} else {
							scrollingLeft.value = overlappingSize.value * -1
							stopScrolling()
						}
					} else {
						if (scrollingLeft.value < 0) {
							scrollingLeft.value += 2
						} else {
							scrollingLeft.value = 0
							stopScrolling()
						}
					}
				}, 1)
			}
		}

		const stopScrolling = () => {
			if (scrollingInterval) {
				clearInterval(scrollingInterval)
				scrollingInterval = null
			}
		}

		const dragStart = (tab: Tab) => {
			draggingElement.value = tab
		}

		const dragOver = (index: number, e: MouseEvent) => {

			if (draggingElement.value === null) {
				return
			}

			moveTab(draggingElement.value.payload.id, index)

			const outerContainerRect = outerContainer.value?.getBoundingClientRect()

			if (outerContainerRect) {
				if (
					e.clientX >= outerContainerRect.x &&
					e.clientX <= (outerContainerRect.x + 50)
				) {
					scroll('left')
				} else if (
					e.clientX >= (outerContainerRect.x + outerContainerRect.width - 50) &&
					e.clientX <= (outerContainerRect.x + outerContainerRect.width)
				) {
					scroll('right')
				} else {
					stopScrolling()
				}
			}
		}

		const dragEnd = () => {
			draggingElement.value = null
			scroll('active')
		}

		const calculateOverSizing = async () => {

			if (!outerContainer.value || !innerContainer.value) {
				console.error('Missing ref elements for tabarea sizing')
				return
			}

			const originalOverlappingSize = overlappingSize.value

			const outerWidth = outerContainer.value.offsetWidth
			const innerWidth = innerContainer.value.offsetWidth
			const spacePerTabAvailable = tabSize.value - MIN_TAB_SIZE

			let overSizingPx = (innerWidth > outerWidth)
				? innerWidth - outerWidth
				: 0

			if (overSizingPx > 0 && spacePerTabAvailable > 0) {
				// Split oversizing length across all tabs
				const minimizedSpaceForEachTab = (overSizingPx / tabs.value.length) + GAP_TABS + 10
				if (minimizedSpaceForEachTab <= spacePerTabAvailable) {
					// Tabs can still be minimized to avoid scrolling
					tabSize.value = tabSize.value - minimizedSpaceForEachTab
					overSizingPx = 0
				}
			} else if (overSizingPx === 0 && tabSize.value < MAX_TAB_SIZE) {
				// Enlarge tabs again
				let newtabSize = tabSize.value + ((outerWidth - innerWidth) / tabs.value.length) - GAP_TABS - 10
				if (newtabSize > MAX_TAB_SIZE) {
					newtabSize = MAX_TAB_SIZE
				} else if (newtabSize < MIN_TAB_SIZE) {
					newtabSize = MIN_TAB_SIZE
				}
				tabSize.value = newtabSize
			}

			if (overSizingPx === 0) {
				overlappingSize.value = 0
				scrollingLeft.value = 0
			} else {
				overlappingSize.value = overSizingPx
				if (originalOverlappingSize === 0) {
					await nextTick()
					// Start calculation again with navigation buttons visible
					await calculateOverSizing()
				}
			}

		}

		const tabModel = computed({
			get() {
				const activeTab = tabs.value.find((tab) => tab.payload.active)
				return (activeTab) ? activeTab.payload.id : ''
			},
			set(value) {
				const tab = getTab(value)
				if (tab) {
					switchTab(tab.payload.id)
				}
			}
		})

		// Window resize
		watch(scope, debounce(async () => {
			await calculateOverSizing()
			scroll('active')
		}, 100))

		watch(tabs, debounce(async () => {
			await nextTick()
			await calculateOverSizing()
			scroll('active')
		}, 100))

		return {
			LoadingState,
			overlappingSize,
			allowSaving,
			orderedTabs,
			tabModel,
			outerContainer,
			innerContainer,
			scrollingLeft,
			tabSize,
			draggingElement,
			switchTab,
			removeTab,
			scroll,
			stopScrolling,
			dragStart,
			dragOver,
			dragEnd,
			getPrimaryColor,
			buildPrimaryColorCssClass,
			getPrimaryColorContrastShade
		}
	}
})
</script>

<template>
	<div class="lg:hidden">
		<select
			v-model="tabModel"
			:class="[
				'w-full px-3 py-2 my-1 rounded',
				buildPrimaryColorCssClass('bg', getPrimaryColorContrastShade(), 20),
				buildPrimaryColorCssClass('text', getPrimaryColorContrastShade('text'))
			]"
		>
			<option
				v-for="tab in orderedTabs"
				:key="`tab-select-${tab.payload.id}`"
				:value="tab.payload.id"
			>
				{{ Array.isArray(tab.payload.text) ? tab.payload.text.join(' &raquo; ') : tab.payload.text }}
			</option>
		</select>
	</div>
	<div class="w-full hidden lg:inline-flex">
		<div class="flex w-full space-x-1 items-center">
			<div class="flex-none">
				<button
					v-show="overlappingSize !== 0"
					type="button"
					:class="[
						'group relative rounded px-2 py-1',
						buildPrimaryColorCssClass('hover:bg', getPrimaryColorContrastShade(), 20),
						buildPrimaryColorCssClass('text', getPrimaryColorContrastShade('text'))
					]"
					:disabled="scrollingLeft == 0"
					@click="scroll('start')"
					@mouseover="scroll('left')"
					@mouseleave="stopScrolling"
				>
					<i class="fa fa-chevron-left" />
				</button>
			</div>
			<div
				ref="outerContainer"
				class="outer-container grow relative h-8 overflow-hidden"
			>
				<div
					ref="innerContainer"
					class="inner-container absolute h-full items-center flex gap-x-1 whitespace-nowrap transition-all"
					:style="[`left: ${scrollingLeft}px`]"
				>
					<div
						v-for="(tab, index) in orderedTabs"
						:key="`tab-${tab.payload.id}`"
						:style="`width: ${tabSize}px`"
						draggable="true"
						@dragstart="dragStart(tab)"
						@dragover.prevent="dragOver(index, $event)"
						@dragend="dragEnd()"
					>
						<!-- Placeholder -->
						<div
							v-if="draggingElement === tab"
							:class="[
								'h-full w-full px-2 py-1.5 border-dashed border-2 rounded-md',
								buildPrimaryColorCssClass('bg', getPrimaryColorContrastShade(), 20),
								buildPrimaryColorCssClass('border', getPrimaryColorContrastShade(), 10)
							]"
						>
							<span class="invisible">
								.
							</span>
						</div>
						<!-- Tab -->
						<TabButton
							v-else
							:tab="tab"
							:closable="orderedTabs.length > 1 && tab.state.state !== LoadingState.loading"
						/>
					</div>
					<!-- TODO Plus Button? -->
				</div>
			</div>
			<div class="flex-none">
				<button
					v-show="overlappingSize !== 0"
					type="button"
					:class="[
						'group relative rounded px-2 py-1',
						buildPrimaryColorCssClass('hover:bg', getPrimaryColorContrastShade(), 20),
						buildPrimaryColorCssClass('text', getPrimaryColorContrastShade('text'))
					]"
					@click="scroll('end')"
					@mouseover="scroll('right')"
					@mouseleave="stopScrolling"
				>
					<i class="fa fa-chevron-right" />
				</button>
			</div>
		</div>
	</div>
</template>
