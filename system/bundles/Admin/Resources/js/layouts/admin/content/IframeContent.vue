<script lang="ts">
import { defineComponent, nextTick, watch, onMounted, PropType, ref, watchEffect, type Ref } from 'vue3'
import { ColorSchemeSetting } from "../../../types/interface"
import { ContentLoadingState, ContentSource, LoadingState } from "../../../types/backend/router"
import {
	isExternalFideloUrl,
	shouldSandboxUrl,
	pingUrl
} from '../../../utils/util'
import { useInterface } from '../../../composables/interface'
import LoadingContent from './LoadingContent.vue'

export default defineComponent({
	name: "IframeContent",
	components: { LoadingContent },
	props: {
		source: { type: Object as PropType<ContentSource>, required: true },
		currentState: { type: Object as PropType<ContentLoadingState>, required: true },
		url: { type: String, default: null },
		html: { type: String, default: null },
		sandbox: { type: String, default: '' }
	},
	emits: ['state'],
	setup(props, { emit }) {
		const iframeRef: Ref<HTMLIFrameElement| null> = ref(null)
		const srcValue: Ref<string | null | undefined> = ref(null)
		const htmlValue: Ref<string | null | undefined> = ref(null)
		const attributes: Ref<Record<string, string>> = ref({})

		const { colorScheme } = useInterface()

		const load = async () => {
			srcValue.value = null
			htmlValue.value = null

			emit('state', { state: LoadingState.loading })

			await nextTick()

			if (props.html) {
				htmlValue.value = props.html
			} else if (props.url) {

				if (shouldSandboxUrl(props.url) && !isExternalFideloUrl(props.url)) {
					attributes.value.sandbox = ''
				} else {
					delete attributes.value.sandbox
				}

				//srcValue.value = props.url

				const state = await pingUrl(props.url)

				if (state === 200) {
					srcValue.value = props.url
				} else if (state === 401) {
					emit('state', { state: LoadingState.unauthorized, text: state })
				} else if (state === 403) {
					emit('state', { state: LoadingState.forbidden, text: state })
				} else {
					emit('state', { state: LoadingState.failed, text: state })
				}
			}
		}

		/* eslint-disable @typescript-eslint/no-explicit-any */
		const sendMessage = (action: string, payload: any) => {
			if (!iframeRef.value?.contentWindow) {
				console.warn('Missing iframe content window')
				return
			} else if (typeof attributes.value.sandbox !== 'undefined') {
				console.warn('sendMessage() not available in sandbox mode')
				return
			}
			// Send message to iframe
			iframeRef.value.contentWindow.postMessage({ action: action, payload: payload })
		}

		const loaded = () => {
			emit('state', { state: LoadingState.loaded })
			sendMessage('SOURCE', props.source)
			sendMessage('COLOR_SCHEME', colorScheme.value)
		}

		watchEffect(() => {
			if (props.currentState.state === LoadingState.reload) {
				load()
			}
		})

		watch(colorScheme, (newValue: ColorSchemeSetting) => sendMessage('COLOR_SCHEME', newValue))

		onMounted(() => load())

		return {
			LoadingState,
			iframeRef,
			srcValue,
			htmlValue,
			attributes,
			loaded
		}
	}
})
</script>

<template>
	<div class="w-full h-full">
		<LoadingContent v-if="currentState.state === LoadingState.loading" />
		<iframe
			v-if="html && htmlValue"
			ref="iframeRef"
			:srcdoc="htmlValue"
			:class="[
				'w-full h-full',
				(currentState.state === LoadingState.loading) ? 'hidden' : ''
			]"
			v-bind="attributes"
			@load="loaded"
		/>
		<iframe
			v-else-if="url && srcValue"
			ref="iframeRef"
			:src="srcValue"
			:class="[
				'w-full h-full',
				(currentState.state === LoadingState.loading) ? 'hidden' : ''
			]"
			v-bind="attributes"
			@load="loaded"
		/>
	</div>
</template>
