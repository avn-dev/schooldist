<script lang="ts">
import { PropType, defineComponent, ref, onMounted, watchEffect, type Ref } from 'vue'
import axios, { type AxiosError } from 'axios'
import { ContentLoadingState, ContentParameters, LoadingState } from "../../../types/backend/router"
import { safe } from "../../../utils/promise"
import LoadingContent from './LoadingContent.vue'
import router from "../../../router"

export default defineComponent({
	name: "HtmlContent",
	components: { LoadingContent },
	props: {
		currentState: { type: Object as PropType<ContentLoadingState>, required: true },
		url: { type: String, required: true },
		parameters: { type: Object as PropType<ContentParameters>, default: () => ({}) },
	},
	emits: ['state'],
	setup(props, { emit }) {
		const html: Ref<string> = ref('')

		const load = async () => {
			html.value = ''
			emit('state', { state: LoadingState.loading })

			const [error, response] = await safe<string>(router.get(props.url, {
				headers: { 'Accept': 'application/json' },
				params: props.parameters
			}))

			if (response) {
				html.value = response
				emit('state', { state: LoadingState.loaded })
			} else if (error) {
				if (axios.isAxiosError(error)) {
					const status = (error as AxiosError).response?.status
					if (status === 401) {
						emit('state', { state: LoadingState.unauthorized, text: status })
					} else if (status === 403) {
						emit('state', { state: LoadingState.forbidden, text: status })
					} else {
						emit('state', { state: LoadingState.failed, text: status })
					}
				} else {
					emit('state', { state: LoadingState.failed, text: error.message })
				}
			}
		}

		onMounted(() => load())

		watchEffect(() => {
			if (props.currentState.state === LoadingState.reload) {
				load()
			}
		})

		return {
			html,
			LoadingState
		}
	}
})
</script>

<template>
	<LoadingContent v-if="currentState.state === LoadingState.loading" />
	<!-- eslint-disable vue/no-v-html -->
	<div
		v-else
		class="h-full w-full"
		v-html="html"
	/>
</template>
