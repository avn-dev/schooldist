<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import {
	Menu as HeadlessUiMenu,
	MenuItems as HeadlessUiMenuItems,
	MenuItem as HeadlessUiMenuItem,
} from '@headlessui/vue'
import { useContextMenu } from '../../composables/contextmenu'

export type ListMenuItem = {
	text: string,
	action: string,
	icon?: string,
	icon_color?: string,
	disabled?: boolean
	highlight?: boolean
}

export default defineComponent({
	name: "ListItems",
	components: {
		HeadlessUiMenu,
		HeadlessUiMenuItems,
		HeadlessUiMenuItem
	},
	props: {
		heading: { type: String, default: null },
		items: { type: Array as PropType<[ListMenuItem][]>, required: true }
	},
	emits: ['action'],
	setup() {
		const { closeContextMenu } = useContextMenu()

		const action = (item: ListMenuItem) => {
			if (!item.disabled) {
				closeContextMenu(item.action)
			}
		}

		return {
			action
		}
	}
})
</script>

<template>
	<HeadlessUiMenu
		as="div"
		class="w-56 px-1"
	>
		<div
			v-if="heading"
			class="text-xs bg-gray-50 text-gray-900 font-medium rounded mt-1 px-2 py-1"
		>
			{{ heading }}
		</div>
		<HeadlessUiMenuItems
			as="div"
			class="divide-y divide-gray-100/75 dark:divide-gray-900"
			static
		>
			<ul
				v-for="(group, groupIndex) in items"
				:key="groupIndex"
				class="flex flex-col gap-1 py-1"
			>
				<HeadlessUiMenuItem
					v-for="(item, index) in group"
					:key="index"
					v-slot="{ active }: { active: boolean }"
					as="li"
					:disabled="!!item.disabled"
				>
					<button
						type="button"
						:class="[
							'text-sm/6 text-gray-800 w-full group flex items-center gap-x-2 px-2 py-0.5 text-sm rounded-md',
							{
								'opacity-75 cursor-default': item.disabled,
								'bg-gray-50 dark:bg-gray-600 dark:text-gray-300': active || item.highlight,
							}
						]"
						@click="action(item)"
					>
						<i
							:class="[
								'flex-none w-4 text-gray-400 group-hover:text-gray-500',
								item.icon
							]"
							:style="{ color: item.icon_color?? '' }"
						/>
						<span class="grow text-left">
							{{ item.text }}
						</span>
					</button>
				</HeadlessUiMenuItem>
			</ul>
		</HeadlessUiMenuItems>
	</HeadlessUiMenu>
</template>
