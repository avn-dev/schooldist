<script lang="ts">
import { defineComponent, watchEffect, ref, type Ref, type PropType } from 'vue'
import {
	Content,
	ContentSource,
	ContentType,
	ContentLoadingState,
	LoadingState,
	RouterActionStorePayload,
	RouterActionAdditionalPayload
} from "../../types/backend/router"
import HtmlContent from "./content/HtmlContent.vue"
import UrlContent from "./content/IframeContent.vue"
import ComponentContent from "./content/ComponentContent.vue"
import FailedContent from "./content/FailedContent.vue"

export default defineComponent({
	name: "ContentLoad",
	components: { FailedContent },
	props: {
		source: { type: Object as PropType<ContentSource>, default: null },
		currentState: { type: Object as PropType<ContentLoadingState>, default: () => ({ state: LoadingState.none }) },
		content: { type: Object as PropType<Content>, required: true },
		payloadStorable: { type: Object as PropType<RouterActionStorePayload>, default: null },
		payloadAdditional: { type: Object as PropType<RouterActionAdditionalPayload>, default: null }
	},
	emits: ['state', 'close', 'dateAsOf'],
	setup(props, { emit }) {
		const failedState: Ref<ContentLoadingState|null> = ref(null)

		let component = null
		if (props.content.type === ContentType.html) {
			component = HtmlContent
		} else if (props.content.type === ContentType.iframe) {
			component = UrlContent
		} else if (props.content.type === ContentType.component) {
			component = ComponentContent
		}

		const setState = (payload: ContentLoadingState) => {
			if ([LoadingState.failed, LoadingState.unauthorized, LoadingState.forbidden].includes(payload.state)) {
				failedState.value = payload
			}
			emit('state', payload)
		}

		watchEffect(() => {
			if (props.currentState.state === LoadingState.reload) {
				failedState.value = null
			}
		})

		return {
			failedState,
			component,
			setState
		}
	}
})
</script>

<template>
	<FailedContent
		v-if="failedState"
		:state="failedState"
	/>
	<component
		:is="component"
		v-else
		:source="source"
		:current-state="currentState"
		:initialized="content.initialized"
		:parameters="content.parameters"
		:payload-storable="payloadStorable"
		v-bind="{ ...content.payload, ...payloadAdditional ?? {}}"
		@state="setState($event)"
		@close="(data, force) => $emit('close', data, force)"
		@date-as-of="$emit('dateAsOf', $event)"
	/>
</template>