<script lang="ts">
import { defineComponent, onMounted, type PropType} from 'vue'
import IframeContent from '../layouts/admin/content/IframeContent.vue'
import { useGui2Dialog } from '../composables/gui2_dialog'

export default defineComponent({
	name: "Gui2Dialog",
	components: { IframeContent },
	props: {
		gui2: { type: Object as PropType<{ hash: string, instance_hash: string }>, required: true },
		html: { type: String, required: true },
	},
	emits: ['state'],
	setup(props) {

		const { loadingState, setGui2Payload } = useGui2Dialog()

		onMounted(() => {
			if (props.gui2) {
				setGui2Payload(props.gui2.hash, props.gui2.instance_hash)
			}
		})

		return {
			loadingState
		}
	}
})
</script>

<template>
	<div class="w-full h-full">
		<IframeContent
			:source="{ source: 'gui2_dialog' }"
			:current-state="loadingState"
			:html="html"
			@state="$emit('state', $event)"
		/>
	</div>
</template>
