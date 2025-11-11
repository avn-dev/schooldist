import { readonly, ref, type Ref } from 'vue3'
import { SlideOverPanel, SlideOverPayload } from '../types/backend/app'
import {
	ContentLoadingState,
	LoadingState,
	RouterActionAdditionalPayload,
	RouterActionStorePayload
} from '../types/backend/router'
import { generateRandomString, sleep } from '../utils/util'
import { useInterface } from './interface'

const panels: Ref<SlideOverPanel[]> = ref([])

const openSlideOver = async <T>(payload: SlideOverPayload, payloadStorable?: RouterActionStorePayload, payloadAdditional?: RouterActionAdditionalPayload) => {

	if (panels.value.length > 0) {
		const index = panels.value.findIndex((panel: SlideOverPanel) => panel.active)
		if (index > -1) {
			const tmp = panels.value
			tmp[index].active = false
			panels.value = tmp
			// Entspricht "duration-500" aus SlideOver.vue
			await sleep(500)
		}
	}

	const { increaseZIndex } = useInterface()

	const panel: SlideOverPanel = {
		id: generateRandomString(12),
		active: true,
		payload: payload,
		payload_storable: payloadStorable,
		payload_additional: payloadAdditional,
		level: increaseZIndex(),
		state: { state: LoadingState.none },
	}

	// @ts-ignore
	const promise = new Promise<T>((resolve) => panel.promise = resolve)

	panels.value.push(panel)

	return promise
}

/* eslint-disable @typescript-eslint/no-explicit-any */
const closeSlideOver = async (id: string, data?: any, force?: boolean) => {

	const index = panels.value.findIndex((panel: SlideOverPanel) => panel.id === id)

	if (index === -1) {
		console.error(`Unknown panel ${id}`)
		return false
	}

	const panel = panels.value[index]

	if (!force && !panel.payload.closable) {
		console.warn('SlideOver panel is marked as not closeable')
		return false
	}

	let tmp = panels.value

	tmp[index].active = false
	panels.value = tmp

	// Entspricht "duration-500" aus SlideOver.vue
	await sleep(500)

	if (panel.promise) {
		panel.promise(data)
	}

	tmp = tmp.filter((panel: SlideOverPanel) => panel.id !== id)

	if (tmp.length > 0) {
		tmp[tmp.length - 1].active = true
	}

	panels.value = tmp

	useInterface().removeZIndex(panel.level)

	// Entspricht "duration-500" aus SlideOver.vue
	//await sleep(500)

	return true
}

const setSlideOverState = (id: string, state: ContentLoadingState) => {

	const index = panels.value.findIndex((panel: SlideOverPanel) => panel.id === id)

	if (index === -1) {
		console.error(`Unknown panel ${id}`)
		return false
	}

	const tmp = panels.value
	tmp[index].state = state
	panels.value = tmp
}

export function useSlideOver() {
	return {
		panels: readonly(panels),
		openSlideOver,
		closeSlideOver,
		setSlideOverState
	}
}