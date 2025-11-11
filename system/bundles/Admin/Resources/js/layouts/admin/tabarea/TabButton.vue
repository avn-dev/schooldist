<script lang="ts">
import { computed, defineComponent, type PropType } from 'vue'
import { Menu as HeadlessUiMenu } from '@headlessui/vue'
import { Tab, TabContextAction } from "../../../types/backend/app"
import { LoadingState } from "../../../types/backend/router"
import {
	buildPrimaryColorCssClass,
	ContrastMode,
	getPrimaryColor,
	getPrimaryColorContrastShade
} from "../../../utils/primarycolor"
import { useTabs } from "../../../composables/tabs"
import { useContextMenu } from "../../../composables/contextmenu"
import { useUser } from '../../../composables/user'
import { useTooltip } from '../../../composables/tooltip'
import l10n from "../../../l10n"
import ListItems from '../../../components/contextmenu/ListItems.vue'

export default defineComponent({
	name: "TabButton",
	components: { HeadlessUiMenu },
	props: {
		tab: { type: Object as PropType<Tab>, required: true },
		closable: { type: Boolean, default: true },
	},
	setup(props) {
		const { allowSaving, switchTab, removeTab, executeTabContextAction } = useTabs()
		const { toggleBookmark, hasBookmark } = useUser()
		const { openContextMenu } = useContextMenu()
		const { showTooltip } = useTooltip()

		const contrastShadeText = getPrimaryColorContrastShade(ContrastMode.text, getPrimaryColorContrastShade())

		const tabCss = computed(() => {
			let classes = ''

			const primaryColor = getPrimaryColor()

			if (props.tab.payload.active) {
				classes = [
					buildPrimaryColorCssClass('bg', getPrimaryColorContrastShade(), 60),
					(primaryColor.base <= 100) ? 'text-black' : 'text-white'
				].join(' ')
			} else {
				let color = (primaryColor.base <= 100) ? 'text-black' : 'text-white'
				if (props.tab.state.state === LoadingState.warning) {
					color = 'text-yellow-500'
				} else if (props.tab.state.state === LoadingState.failed) {
					color = 'text-red-400'
				}

				classes = [
					buildPrimaryColorCssClass('bg', getPrimaryColorContrastShade(), 30),
					buildPrimaryColorCssClass('hover:bg', getPrimaryColorContrastShade(), 40),
					color
				].join(' ')
			}

			return classes
		})

		const contextMenu = async (tab: Tab, event: MouseEvent) => {

			const bookmark = (tab.payload_storable && hasBookmark(tab.payload_storable)) ? 'tabs.context.remove_bookmark' : 'tabs.context.add_bookmark'

			const action = await openContextMenu<TabContextAction>(event, {
				component: ListItems,
				payload: {
					items: [
						[
							...(tab.payload_storable) ? [{text: l10n.translate(bookmark), action: TabContextAction.toggleBookmark, icon: 'far fa-star'}] : [],
							...[
								{text: l10n.translate('tabs.context.refresh'), action: TabContextAction.refresh, icon: 'fa fa-refresh'},
								{text: l10n.translate('tabs.context.clone'), action: TabContextAction.clone, icon: 'fa fa-clone'},
							]
						],
						[
							{text: l10n.translate('tabs.context.close_tabs_before'), action: TabContextAction.closeTabsBefore, icon: 'fa fa-arrow-left'},
							{text: l10n.translate('tabs.context.close_tabs_after'), action: TabContextAction.closeTabsAfter, icon: 'fa fa-arrow-right'},
							{text: l10n.translate('tabs.context.close_other_tabs'), action: TabContextAction.closeOtherTabs, icon: 'fas fa-arrows-alt-h'},
							{text: l10n.translate('tabs.context.close'), action: TabContextAction.close, icon: 'fa fa-times'}
						],
						[
							{text: l10n.translate('tabs.context.save_tabs'), action: TabContextAction.saveTabs, icon: 'far fa-save', disabled: allowSaving.value === false}
						]
					]
				}
			})

			if (action) {
				if (action === TabContextAction.toggleBookmark) {
					if (tab.payload_storable) {
						await toggleBookmark(tab.payload_storable)
					}
				} else {
					await executeTabContextAction(tab, action)
				}
			}
		}

		return {
			LoadingState,
			tabCss,
			contrastShadeText,
			switchTab,
			removeTab,
			contextMenu,
			buildPrimaryColorCssClass,
			getPrimaryColorContrastShade,
			showTooltip
		}
	}
})
</script>

<template>
	<HeadlessUiMenu class="">
		<div :class="{'active': tab.payload.active}">
			<button
				:key="tab.payload.id"
				type="button"
				:class="['w-full group flex rounded px-2 py-1.5 text-sm items-center transition-all', tabCss]"
				@click="switchTab(tab.payload.id)"
				@contextmenu.prevent="contextMenu(tab, $event)"
				@mouseenter="showTooltip(tab.payload.text.slice(-3).join(' / '), $event, 'bottom')"
			>
				<i
					v-if="tab.state.state === LoadingState.warning"
					:class="[
						'fa fa-warning mr-2 text-xs',
						(tab.payload.active) ? buildPrimaryColorCssClass('text', getPrimaryColorContrastShade()) : ''
					]"
				/>
				<i
					v-if="tab.state.state === LoadingState.failed"
					:class="[
						'fa fa-exclamation-circle mr-2 text-xs',
						(tab.payload.active) ? buildPrimaryColorCssClass('text', getPrimaryColorContrastShade()) : ''
					]"
				/>
				<i
					v-if="tab.payload.icon && [LoadingState.warning, LoadingState.failed].indexOf(tab.state.state) === -1"
					:class="[
						'mr-2',
						tab.payload.icon,
						buildPrimaryColorCssClass('text', contrastShadeText, 75)
					]"
				/>
				<span class="truncate grow text-left">
					{{ tab.payload.text.slice(-1).join(' / ') }}
				</span>
				<i
					v-if="tab.state.state === LoadingState.loading"
					class="fa fa-spinner fa-spin ml-1.5 px-1 text-xs"
				/>
				<button
					v-else-if="tab.payload.closable && closable"
					:class="[
						'ml-1 inline-flex items-center justify-center rounded-full',
						buildPrimaryColorCssClass('hover:bg', getPrimaryColorContrastShade(), 60)
					]"
					@click.stop="removeTab(tab.payload.id)"
				>
					<i class="fa fa-times text-xs size-4" />
				</button>
			</button>
		</div>
	</HeadlessUiMenu>
</template>
