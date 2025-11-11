import { readonly, ref, type Ref } from 'vue3'
import { ChatState } from '../types/backend/app'
import { checkEnum } from '../utils/util'

const features: Ref<{ helpdesk?: string, support_chat?: string}> = ref({})
const chatState: Ref<ChatState> = ref(ChatState.offline)

const init = async (payload: { helpdesk?: string, support_chat?: string }) => {
	features.value = payload
	return true
}

const isEnabled = (): boolean => {
	return Object.keys(features.value).length > 0
}

const hasFeature = (feature: string): boolean => {
	return Object.keys(features.value).includes(feature)
}

const setChatState = (state: ChatState): void => {
	if (checkEnum(state, ChatState)) {
		chatState.value = state
	} else {
		console.error(`Invalid support chat state: ${state} [${Object.values(ChatState).join(', ')}]`)
	}
}

export function useSupport() {
	return {
		chatState: readonly(chatState),
		features: readonly(features),
		init,
		isEnabled,
		hasFeature,
		setChatState
	}
}