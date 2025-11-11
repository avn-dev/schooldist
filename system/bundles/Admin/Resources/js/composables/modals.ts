import { readonly, ref, nextTick, type Ref } from 'vue'
import { Modal, ModalPayload } from '../types/backend/app'
import {
	ContentLoadingState,
	ContentType,
	LoadingState,
	RouterActionAdditionalPayload,
	RouterActionStorePayload
} from '../types/backend/router'
import { generateRandomString } from '../utils/util'
import { useInterface } from './interface'

const modals: Ref<Modal[]> = ref([])

const openModal = async <T>(payload: ModalPayload, payloadStorable?: RouterActionStorePayload, payloadAdditional?: RouterActionAdditionalPayload) => {

	if (
		payload.content.type === ContentType.component &&
		typeof payload.content.payload.component !== 'function'
	) {
		// TODO Prevent vue warning for performance issue [Vue received a Component which was made a reactive object]
		const component = payload.content.payload.component
		payload.content.payload.component= () => component
	}

	const { increaseZIndex } = useInterface()

	const modal: Modal = {
		id: generateRandomString(12),
		payload: payload,
		payload_storable: payloadStorable,
		payload_additional: payloadAdditional,
		level: increaseZIndex(),
		state: { state: LoadingState.none }
	}

	// @ts-ignore
	const promise: Promise<T> = new Promise<T>((resolve) => modal.promise = resolve)

	modals.value.push(modal)

	return promise
}

/* eslint-disable @typescript-eslint/no-explicit-any */
const closeModal = async (id: string, data?: any, force?: boolean) => {

	const index = modals.value.findIndex((modal: Modal) => modal.id === id)

	if (index === -1) {
		console.error(`Unknown modal ${id}`)
		return false
	} else if (index !== (modals.value.length - 1)) {
		// TODO Bug when multiple Modals are opened at the same time
		console.warn(`Only last modal can be closed`)
		return false
	}

	const modal = modals.value[index]

	if (!force && modal.payload && !modal.payload.closable) {
		console.warn('Modal is marked as not closeable')
		return
	}

	if (modal.promise) {
		modal.promise(data)
	}

	modals.value = modals.value.filter((loop: Modal) => loop.id !== modal.id)

	useInterface().removeZIndex(modal.level)

	await nextTick()
}

const setModalLoadingState = (id: string, state: ContentLoadingState) => {
	const index = modals.value.findIndex((loop: Modal) => loop.id === id)
	if (index !== -1) {
		const copy = modals.value
		copy[index].state = state
		modals.value = copy
	}
}

export function useModals() {
	return {
		modals: readonly(modals),
		openModal,
		closeModal,
		setModalLoadingState
	}
}