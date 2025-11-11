import { readonly, ref, type Ref } from 'vue3'
import { ContentLoadingState } from '../types/backend/router'
import { Gui2DialogPayload } from '../types/backend/app'
import { useInterface } from './interface'

const open: Ref<boolean> = ref(false)
const payload: Ref<Gui2DialogPayload | null> = ref(null)
const loadingState: Ref<ContentLoadingState> = ref({ state: 'none' } as ContentLoadingState)
const level: Ref<number> = ref(0)
const gui2: Ref<{ hash: string, instance_hash: string } | null> = ref(null)
/* eslint-disable @typescript-eslint/no-explicit-any */
const promise: Ref<((data?: any) => void) | null> = ref(null)

const openPreview = <T>(data: Gui2DialogPayload) => {
	payload.value = data
	open.value = true

	const { increaseZIndex } = useInterface()

	level.value = increaseZIndex()

	return new Promise<T>((resolve) => promise.value = resolve)
}

/* eslint-disable @typescript-eslint/no-explicit-any */
const closePreview = async (data?: any) => {
	const { removeZIndex } = useInterface()
	removeZIndex(level.value)

	payload.value = null
	open.value = false
	level.value = 0
	gui2.value = null

	if (promise.value) {
		promise.value(data)
		promise.value = null
	}

	return true
}

const setGui2Payload = (hash: string, instance_hash: string) => {
	gui2.value = { hash, instance_hash }
}

const setGui2LoadingState = (state: ContentLoadingState) => {
	loadingState.value = state
}

export function useGui2Dialog() {
	return {
		open: readonly(open),
		payload: readonly(payload),
		loadingState: readonly(loadingState),
		level: readonly(level),
		gui2: readonly(gui2),
		openPreview,
		closePreview,
		setGui2Payload,
		setGui2LoadingState
	}
}