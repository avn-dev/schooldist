<script lang="ts">
import { defineComponent, PropType } from 'vue'
import FilterModel from '../../models/filter'
// @ts-ignore
import { useContextMenu } from '@Admin/composables/contextmenu'
import SidebarElement from './SidebarElement.vue'

export default defineComponent({
	name: "FilterContextMenu",
	components: { SidebarElement },
	props: {
		element: { type: Object as PropType<FilterModel>, required: true }
	},
	setup() {
		const { closeContextMenu } = useContextMenu()
		return {
			closeContextMenu
		}
	}
})
</script>

<template>
	<div class="w-64 p-1">
		<SidebarElement
			:filter="element"
			:model-value="element.value"
			view="contextmenu"
			@update:model-value="closeContextMenu({ action: 'update', value: $event })"
			@reset:model-value="closeContextMenu({ action: 'reset' })"
		/>
	</div>
</template>
