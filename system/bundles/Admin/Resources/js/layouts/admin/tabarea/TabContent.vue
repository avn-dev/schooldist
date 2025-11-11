<script lang="ts">
import { PropType, defineComponent } from 'vue'
import { Tab } from "../../../types/backend/app"
import { RouterTarget, LoadingState } from "../../../types/backend/router"
import { useTabs } from "../../../composables/tabs"
import ContentLoad from "../ContentLoad.vue"

export default defineComponent({
	name: "TabContent",
	components: { ContentLoad },
	props: {
		tab: { type: Object as PropType<Tab>, required: true }
	},
	setup() {
		const { setTabLoadingState, removeTab } = useTabs()
		return {
			RouterTarget,
			LoadingState,
			setTabLoadingState,
			removeTab
		}
	}
})
</script>

<template>
	<div
		v-if="tab.state.state !== LoadingState.none"
		:class="{
			'hidden': !tab.payload.active
		}"
	>
		<ContentLoad
			:source="{ source: RouterTarget.tab, payload: { id: tab.payload.id } }"
			:current-state="tab.state"
			:content="tab.payload.content"
			@state="(state) => setTabLoadingState(tab.payload.id, state)"
			@close="removeTab(tab.payload.id)"
		/>
	</div>
</template>