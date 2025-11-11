<script lang="ts">
import { defineComponent } from 'vue'
import { useTooltip } from '../composables/tooltip'

export default defineComponent({
	name: "Tooltip",
	setup() {
		const { domElement, tooltip } = useTooltip()

		return {
			domElement,
			tooltip
		}
	}
})
</script>

<template>
	<div
		ref="domElement"
		:class="['max-w-96 absolute bg-gray-950 text-white p-1 text-xs rounded text-left transition-all', (tooltip.visible) ? 'opacity-95' : 'opacity-0']"
		:style="{ top: `${tooltip.y}px`, left: `${tooltip.x}px`, zIndex: tooltip.visible ? 9999 : -1 }"
	>
		<div v-if="tooltip.open">
			{{ tooltip.text }}
		</div>
	</div>
</template>
