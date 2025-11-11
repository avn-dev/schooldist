<script lang="ts">
import { defineComponent, type PropType, type Ref, ref } from 'vue'
import { ComponentSize, DashboardWidget, GridLayoutChangedEvent, GridLayoutItemPayload } from '../types/backend/app'
import { ComponentApiInterface, ContentType, LoadingState } from '../types/backend/router'
import { safe } from '../utils/promise'
import { sleep } from '../utils/util'
import ButtonComponent from './ButtonComponent.vue'
import GridLayout from './GridLayout.vue'
import GridItem from './gridlayout/GridItem.vue'
import ContentLoad from '../layouts/admin/ContentLoad.vue'
import UnusedWidgets from './dashboard/UnusedWidgets.vue'
import LoadingContent from '../layouts/admin/content/LoadingContent.vue'
import router from '../router'

const COLORS: Record<string, { bg: string, text: string, border: string }> = {
	green: { bg: 'bg-green-300 dark:bg-green-700', text: 'text-green-800 dark:text-green-100', border: 'border-green-400/50 dark:border-green-800' },
	blue: { bg: 'bg-sky-200 dark:bg-sky-700', text: 'text-sky-800 dark:text-sky-100', border: 'border-sky-300/50 dark:border-sky-800' },
	yellow: { bg: 'bg-yellow-200', text: 'text-yellow-800', border: 'border-yellow-300/50' },
	gray: { bg: 'bg-gray-100 dark:bg-gray-950', text: 'text-gray-800 dark:text-gray-600', border: 'border-gray-200/75' }
}

type ColorName = keyof typeof COLORS
type ColorType = keyof (typeof COLORS)[ColorName]

export default defineComponent({
	name: "Dashboard",
	components: { LoadingContent, ContentLoad, GridItem, GridLayout, ButtonComponent },
	props: {
		api: { type: Object as PropType<ComponentApiInterface>, required: true },
		version: { type: String, required: true },
		widgets: { type: Array as PropType<DashboardWidget[]>, required: true },
		unusedWidgets: { type: Array as PropType<DashboardWidget[]>, default: () => [] },
	},
	setup(props) {
		const editMode: Ref<boolean> = ref(false)
		const localWidgets: Ref<DashboardWidget[]> = ref(props.widgets)
		const localUnusedWidgets: Ref<DashboardWidget[]> = ref(props.unusedWidgets)
		const loadingLegacyWidgetKey: Ref<string|null> = ref(null)

		const openSlideOver = async () => {
			const added = await router.openSlideOver<DashboardWidget[]>({
				content: { type: ContentType.component, payload: { component: UnusedWidgets, payload: { widgets: localUnusedWidgets } } },
				size: ComponentSize.medium,
				closable: true
			})

			if (added) {
				added.forEach((widget: DashboardWidget) => {
					localWidgets.value.push(widget)
					localUnusedWidgets.value = localUnusedWidgets.value.filter((loop: DashboardWidget) => loop.key !== widget.key)
				})
			}
		}

		const changed = async (items: GridLayoutItemPayload[], layout: GridLayoutItemPayload[], type: GridLayoutChangedEvent) => {

			if (type === GridLayoutChangedEvent.removed) {
				for (const item of items) {
					const widget = localWidgets.value.find((loop: DashboardWidget) => loop.key == item.id)
					if (widget) {
						widget.x = null
						widget.y = null
						localUnusedWidgets.value.push(widget)
						localWidgets.value = localWidgets.value.filter((loop: DashboardWidget) => loop.key !== widget.key)
					}
				}
			}

			await safe(props.api.action('save', { method: 'post', data: { layout } }))
		}

		const reload = async (widget: DashboardWidget) => {

			const index = localWidgets.value.indexOf(widget)
			if (index === -1) {
				console.warn(`Unknown widget ${widget.key}`)
				return
			}

			if (widget.type === 'component') {
				// @ts-ignore
				localWidgets.value[index].current_state = { state: LoadingState.reload }
				return
			}

			loadingLegacyWidgetKey.value = widget.key

			/* eslint-disable @typescript-eslint/no-unused-vars */
			const [error, response] = await safe<{ content: string, cache_timestamp: string }>(props.api.action('reload', { method: 'get', params: { widget: widget.key } }))

			if (response) {
				localWidgets.value[index].content = response.content
				localWidgets.value[index].cache_timestamp = response.cache_timestamp
			}

			await sleep(100)

			loadingLegacyWidgetKey.value = null
		}

		const print = (widget: DashboardWidget) => {
			if (widget.type === 'component') {
				return
			}

			const win = window.open('', 'printWindow', 'location=no,status=no,width=700,height=500')

			if (win) {
				let html = '<html><head>'
				html += '<link type="text/css" rel="stylesheet" href="/admin/css/admin.css" media="" />'
				html += '<title></title>'
				html += '</head><body><br /><div class="infoBox">'
				win.document.writeln(html)
				win.document.write(widget.content)
				win.document.writeln('</div></body></html>')
				win.print()
			}
		}

		const buildColor = (color: ColorName, types: ColorType[] = []): string | null => {
			if (!COLORS[color]) return null
			if (types.length === 0) types = Object.keys(COLORS[color]) as ColorType[]
			return types.map(type => COLORS[color][type]).join(' ')
		}

		return {
			LoadingState,
			editMode,
			localWidgets,
			localUnusedWidgets,
			loadingLegacyWidgetKey,
			changed,
			reload,
			print,
			openSlideOver,
			buildColor
		}
	}
})
</script>

<template>
	<div class="h-full flex flex-col gap-y-2 p-3 text-gray-900 dark:text-gray-200">
		<div class="flex-none">
			<section class="content-header flex items-center gap-2">
				<h1 class="flex-1 text-2xl tracking-tight text-gray-900 dark:text-gray-50">
					{{ $l10n.translate('dashboard.title') }}
					<small class="text-xs text-gray-400 dark:text-gray-500">
						{{ version }}
					</small>
				</h1>
				<ButtonComponent
					v-if="editMode && localUnusedWidgets.length > 0"
					color="primary"
					type="button"
					class="flex-none btn btn-default"
					@click="openSlideOver"
				>
					<i class="fa fa-plus" />
					{{ $l10n.translate('dashboard.settings.add') }}
				</ButtonComponent>
				<ButtonComponent
					color="gray"
					type="button"
					class="flex-none"
					@click="editMode = !editMode"
				>
					<i
						v-if="editMode"
						class="fas fa-check mr-1"
					/>
					<i
						v-else
						class="fa fa-gears mr-1"
					/>
					<span v-if="editMode">{{ $l10n.translate('dashboard.settings.finish') }}</span>
					<span v-else>{{ $l10n.translate('dashboard.settings') }}</span>
				</ButtonComponent>
			</section>
		</div>
		<GridLayout
			:debug="false"
			:editable="editMode"
			:col-dimension="4"
			@changed="changed"
		>
			<GridItem
				v-for="widget in localWidgets"
				:id="widget.key"
				:key="widget.key"
				:x="widget.x ?? 1"
				:y="widget.y ?? 1"
				:rows="widget.rows"
				:cols="widget.cols"
				:min-rows="widget.min_rows"
				:min-cols="widget.min_cols"
				:class="[
					'rounded-xl p-2 border-2',
					buildColor(widget.color) ?? 'bg-white border-white dark:bg-gray-800 dark:border-gray-900'
				]"
			>
				<template #header="{ editable, remove }">
					<div class="flex flex-row items-center gap-1 mb-2">
						<h5 class="grow font-semibold font-heading truncate">
							<i
								v-if="widget.icon"
								:class="[widget.icon, 'mr-1 text-gray-400']"
							/>
							{{ widget.title }}
						</h5>
						<button
							class="flex-none p-0.5"
							@click="reload(widget)"
						>
							<i class="fa fa-refresh" />
						</button>
						<button
							v-if="widget.printable"
							class="flex-none p-0.5"
							@click="print(widget)"
						>
							<i class="fa fa-print" />
						</button>
						<button
							v-if="widget.deletable && editable"
							class="flex-none p-0.5"
							@click="remove"
						>
							<i class="fa fa-times" />
						</button>
					</div>
				</template>
				<ContentLoad
					v-if="widget.type === 'component'"
					:content="widget.content"
					:current-state="widget.current_state ?? { state: LoadingState.none }"
					@date-as-of="(date: string) => widget.cache_timestamp = date"
				/>
				<!-- eslint-disable vue/no-v-html -->
				<div
					v-else
					class="h-full w-full"
				>
					<LoadingContent
						v-if="loadingLegacyWidgetKey === widget.key"
					/>
					<div
						v-else
						v-html="widget.content"
					/>
				</div>
				<template #footer="{ editable, resize }">
					<div class="flex place-content-end mt-2">
						<div
							v-if="widget.cache_timestamp"
							:class="['grow text-xs truncate mr-4', buildColor(widget.color, ['text']) ?? 'text-gray-400 dark:text-gray-500' ]"
						>
							{{ $l10n.translate('dashboard.cache_timestamp') }}: {{ widget.cache_timestamp	}}
						</div>
						<div class="relative flex-none">
							<i
								v-if="editable"
								class="absolute bottom-0 right-0 fas fa-chevron-right rotate-45 cursor-nwse-resize pl-2 pt-2"
								@mousedown.prevent="resize($event)"
							/>
						</div>
					</div>
				</template>
			</GridItem>
		</GridLayout>
	</div>
</template>
