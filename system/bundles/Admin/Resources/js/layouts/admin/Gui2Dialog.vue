<script lang="ts">
import { defineComponent } from 'vue'
import { Dialog as HeadlessUiDialog } from '@headlessui/vue'
import { ContentLoadingState, LoadingState as LoadingStateType, RouterTarget } from '../../types/backend/router'
import { useGui2Dialog } from "../../composables/gui2_dialog"
import { sleep } from '../../utils/util'
import ContentLoad from './ContentLoad.vue'

export default defineComponent({
	name: "Gui2Dialog",
	components: { ContentLoad, HeadlessUiDialog },
	setup() {
		const { level, payload, loadingState, setGui2LoadingState, closePreview } = useGui2Dialog()

		const setLoadingState = async (state: ContentLoadingState) => {
			if (state.state === LoadingStateType.loaded) {
				// give gui2 some time to open dialog before loading bar disappears
				await sleep(1000)
			}
			setGui2LoadingState(state)
		}

		return {
			RouterTarget,
			payload,
			level,
			loadingState,
			setLoadingState,
			closePreview
		}
	}
})
</script>

<template>
	<!-- Open in Headless-UI portal (z-index) -->
	<HeadlessUiDialog
		as="div"
		:open="payload !== null"
		class="absolute top-0 left-0 h-screen w-screen"
		:style="{ 'z-index': level }"
		@close="closePreview"
	>
		<ContentLoad
			:content="payload.content"
			:current-state="loadingState"
			:class="{'bg-gray-900 opacity-80': loadingState?.state === 'loading'}"
			@state="setLoadingState($event)"
		/>
	</HeadlessUiDialog>
</template>
