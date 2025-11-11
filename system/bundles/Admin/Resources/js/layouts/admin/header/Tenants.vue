<script lang="ts">
import { defineComponent, computed, type PropType } from 'vue3'
import { Tenant } from '../../../types/backend/app'
import { getPrimaryColor, buildPrimaryColorCssClass, getPrimaryColorContrastShade } from "../../../utils/primarycolor"
import { useContextMenu } from '../../../composables/contextmenu'
import { useTooltip } from '../../../composables/tooltip'
import { useInterface } from '../../../composables/interface'
import TenantsList from './TenantsList.vue'

export default defineComponent({
	name: "Tenants",
	props: {
		tenants: { type: Array as PropType<Tenant[]>, required: true }
	},
	emits: ['close'],
	setup(props) {
		const { switchTenant } = useInterface()
		const { showTooltip } = useTooltip()
		const { openContextMenu } = useContextMenu()

		const currentTenant = computed(() => props.tenants.find((loop: Tenant) => loop.selected))

		const openMenu = async (event: MouseEvent) => {

			const elementRect = (event.target as HTMLElement)?.parentElement?.getBoundingClientRect()

			if (elementRect) {
				const selection = await openContextMenu<Tenant|null>(event,
					{ component: TenantsList, payload: { tenants: props.tenants } },
					elementRect.x,
					elementRect.y + elementRect.height + 5
				)

				if (selection) {
					await switchTenant(selection)
				}
			}
		}

		return {
			currentTenant,
			openMenu,
			showTooltip,
			getPrimaryColor,
			buildPrimaryColorCssClass,
			getPrimaryColorContrastShade
		}
	}
})
</script>

<template>
	<div
		:class="[
			'group max-w-32 flex items-center h-full rounded cursor-pointer',
			buildPrimaryColorCssClass('hover:bg', getPrimaryColorContrastShade(), 20),
			buildPrimaryColorCssClass('text', getPrimaryColorContrastShade())
		]"
		@mouseenter="showTooltip(currentTenant.label, $event, 'bottom')"
		@click.stop="openMenu($event)"
	>
		<div
			v-if="currentTenant.logo"
			:class="['h-full max-w-full flex flex-col', (currentTenant.show_label) ? 'items-end' : 'items-center']"
		>
			<div :class="['max-h-full grow content-center']">
				<img
					:src="currentTenant.logo"
					:class="[
						'object-contain object-center',
						(currentTenant.show_label) ? 'max-h-6 group-hover:rounded-tr' : 'max-h-full max-w-full group-hover:rounded'
					]"
					:alt="currentTenant.label"
				>
			</div>
			<div
				v-if="currentTenant.show_label"
				:class="[
					'flex-none max-w-full rounded text-xs truncate whitespace-nowrap px-0.5 group-hover:bg-transparent',
					buildPrimaryColorCssClass('bg', getPrimaryColorContrastShade(), 50),
					(getPrimaryColor().base <= 100) ? 'text-black' : 'text-white'
				]"
			>
				{{ currentTenant.label }}
			</div>
		</div>
		<div
			v-else
			class="p-2 truncate whitespace-nowrap font-semibold"
		>
			{{ currentTenant.label }}
		</div>
	</div>
</template>
