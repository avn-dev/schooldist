<script lang="ts">
import { defineComponent, computed, type ComputedRef, type PropType } from 'vue3'
import { type Tenant } from '../../../types/backend/app'
import { buildPrimaryColorElementCssClasses} from "../../../utils/primarycolor"
import { useContextMenu } from '../../../composables/contextmenu'

export default defineComponent({
	name: "TenantsList",
	props: {
		tenants: { type: Array as PropType<Tenant[]>, required: true }
	},
	setup(props) {
		const { closeContextMenu } = useContextMenu()

		const filteredTenants: ComputedRef<Tenant[]> = computed(() => props.tenants.filter((tenant) => !tenant.selected))

		return {
			filteredTenants,
			closeContextMenu,
			buildPrimaryColorElementCssClasses
		}
	}
})
</script>

<template>
	<nav class="w-64 h-full overflow-y-auto px-1">
		<div class="relative">
			<ul
				role="list"
				class="flex flex-col divide-y divide-gray-50"
			>
				<li
					v-for="tenant in filteredTenants"
					:key="tenant.key"
					class="py-1"
				>
					<div
						:class="['flex gap-x-2 p-1 items-center hover:bg-gray-50 rounded cursor-pointer', {'bg-gray-50': tenant.selected}]"
						@click="closeContextMenu(tenant)"
					>
						<div
							:class="['size-8 flex-none rounded', (!tenant.color) ? buildPrimaryColorElementCssClasses() : '']"
							:style="[(tenant.color) ? `background-color: ${tenant.color}` : '']"
						>
							<img
								v-if="tenant.logo"
								:src="tenant.logo"
								:alt="tenant.label"
								class="h-full w-full rounded object-contain object-center"
							>
						</div>
						<div class="text-sm min-w-0 truncate">
							{{ tenant.label }}<br>
							<span class="text-gray-500 text-xs">{{ tenant.text }}</span>
						</div>
					</div>
				</li>
			</ul>
		</div>
	</nav>
</template>
