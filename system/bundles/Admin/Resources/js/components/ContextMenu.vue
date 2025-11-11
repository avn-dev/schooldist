<script lang="ts">
import { defineComponent } from 'vue'
import { useContextMenu } from '../composables/contextmenu'

export default defineComponent({
	name: "ContextMenu",
	emits: ['close'],
	setup() {
		const { domElement, contextMenu, closeContextMenu } = useContextMenu()

		return {
			domElement,
			contextMenu,
			closeContextMenu
		}
	}
})
</script>

<template>
	<div
		ref="domElement"
		:class="['context-menu absolute max-h-[90vh] overflow-auto bg-white rounded-md shadow-lg dark:bg-gray-800 dark:border dark:border-gray-700 dark:text-gray-400', {'opacity-0': !contextMenu.visible }]"
		:style="{ top: contextMenu.y + 'px', left: contextMenu.x + 'px', 'z-index': contextMenu.level }"
	>
		<div
			v-if="contextMenu.open"
			class="h-full"
		>
			<component
				:is="contextMenu.component.component()"
				v-bind="contextMenu.component.payload"
			/>
		</div>
	</div>
</template>