<script lang="ts">
import { PropType, defineComponent, onMounted, nextTick, watchEffect, ref, Component } from 'vue3'
import axios, { type AxiosError } from 'axios'
import {
	ContentLoadingState,
	ContentParameters,
	LoadingState,
	RouterActionStorePayload
} from "../../../types/backend/router"
import { safe } from '../../../utils/promise'
import { useInterface } from '../../../composables/interface'
import LoadingContent from './LoadingContent.vue'
import router from '../../../router'

export default defineComponent({
	name: "ComponentContent",
	components: { LoadingContent },
	props: {
		currentState: { type: Object as PropType<ContentLoadingState>, default: () => ({ state: LoadingState.none }) },
		/* eslint-disable vue/prop-name-casing */
		api_key: { type: String, default: '' },
		component: { type: [String, Function] as PropType<string | (() => string|Component)>, required: true },
		/* eslint-disable @typescript-eslint/no-explicit-any */
		payload: { type: Object as PropType<Record<string, any>>, default: () => ({}) },
		payloadStorable: { type: Object as PropType<RouterActionStorePayload|null>, default: null },
		parameters: { type: String as PropType<ContentParameters>, default: null },
		initialized: { type: Boolean, default: true }
	},
	emits: ['state', 'close', 'dateAsOf'],
	setup(props, { emit }) {
		const localPayload = ref(props.payload)
		const localCurrentState = ref(props.currentState)

		const api = (props.api_key) ? router.component(props.api_key, props.parameters) : null

		if (api) {
			const { debug, colorScheme } = useInterface()
			api.debug(debug.value)
			api.header('X-Admin-Color', colorScheme.value)
		}

		const setCurrentState = async (newState: ContentLoadingState, doEmit = true) => {
			localCurrentState.value = newState
			await nextTick()

			if (doEmit) {
				// TODO give client some time to render
				//setTimeout(() => emit('state', newState), 50)
				emit('state', newState)
			}
		}

		const load = async (force = false) => {

			await setCurrentState({ state: LoadingState.loading })

			if (api && (!props.initialized || force)) {
				/* eslint-disable @typescript-eslint/no-explicit-any */
				const [error, response] = await safe<Record<any, any>>(api.action('init', { method: 'get', params: (force) ? { force } : {}}))

				// TODO Access denied?
				if (response) {
					localPayload.value = { ...localPayload, ...response }

					await nextTick()

					if (response.date_as_of) {
						emit('dateAsOf', response.date_as_of)
					}
				} else if (error) {
					if (axios.isAxiosError(error)) {
						const status = (error as AxiosError).response?.status
						if (status === 401) {
							await setCurrentState({ state: LoadingState.unauthorized, text: status })
						} else if (status === 403) {
							await setCurrentState({ state: LoadingState.forbidden, text: status })
						} else {
							await setCurrentState({ state: LoadingState.failed, text: status })
						}
					} else {
						await setCurrentState({ state: LoadingState.failed, text: error.message })
					}

					return
				}
			}

			await setCurrentState({ state: LoadingState.loaded }, false)
		}

		onMounted(() => load())

		watchEffect(() => {
			if (props.currentState.state === LoadingState.reload) {
				load(true)
			}
		})

		return {
			LoadingState,
			localPayload,
			localCurrentState,
			api,
			setCurrentState
		}
	}
})
</script>

<template>
	<LoadingContent
		v-if="localCurrentState.state === LoadingState.loading"
	/>
	<!-- eslint-disable @typescript-eslint/no-explicit-any -->
	<component
		:is="(typeof component === 'function') ? component() : component"
		v-else-if="localCurrentState.state === LoadingState.loaded"
		:api="api"
		:payload-storable="payloadStorable"
		v-bind="localPayload"
		@close="(...args) => $emit('close', ...args)"
		@vue:mounted="setCurrentState({ state: LoadingState.loaded })"
	/>
	<div v-else>
		Oops - {{ localCurrentState.state }}
	</div>
</template>
