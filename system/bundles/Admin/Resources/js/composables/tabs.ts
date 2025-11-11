import { computed, nextTick, readonly, ref, type Ref } from 'vue3'
import { ComponentSize, Tab, TabContextAction, TabOpening, TabPayload } from '../types/backend/app'
import {
	ContentLoadingState,
	ContentType,
	LoadingState,
	RouterAction,
	RouterActionStorePayload
} from '../types/backend/router'
import { generateRandomString } from '../utils/util'
import { useNavigation } from './navigation'
import { safe } from '../utils/promise'
import ExistingTab from '../layouts/admin/tabarea/ExistingTab.vue'
import TooManyTabs from '../layouts/admin/tabarea/TooManyTabs.vue'
import router from '../router'
import l10n from '../l10n'

const loading: Ref<boolean> = ref(false)
const maxTabs: Ref<number> = ref(100)
const allowSaving: Ref<boolean> = ref(true)
const history: Ref<string[]> = ref([])
const tabs: Ref<Tab[]> = ref([])

const api = () => {
	return router.component('tabArea')
}

const orderedTabs = computed(() =>
	tabs.value.slice().sort((a: Tab, b: Tab) => a.position - b.position)
)

const addTab = async (payload: TabPayload, payloadStorable: null | RouterActionStorePayload): Promise<null> => {

	// TODO Referenz vermeiden
	payload = JSON.parse(JSON.stringify(payload))

	const tab: Tab = {
		payload: payload,
		payload_storable: payloadStorable,
		state: { state: LoadingState.none },
		position: tabs.value.length
	}

	if ((tabs.value.length + 1) > maxTabs.value) {
		await router.openModal<string>({
			title: l10n.translate('tabs.too_many.title'),
			content: { type: ContentType.component, payload: { component: TooManyTabs } },
			size: ComponentSize.small,
			closable: true
		})
		return null
	}

	let opening: string | null = TabOpening.new

	const existingTab = getTab(tab.payload.id)

	if (existingTab) {
		opening = await router.openModal<string>({
			title: l10n.translate('tabs.existing_tab'),
			content: { type: ContentType.component, payload: { component: ExistingTab } },
			size: ComponentSize.large,
			closable: true
		})
	}

	if (opening === TabOpening.new) {
		if (existingTab) {
			tab.payload.id = generateRandomString(12)
		}

		tabs.value.push(tab)

		await nextTick()

		if (tab.payload.active) {
			await switchTab(tab.payload.id)
		}
	} else if (existingTab && opening === TabOpening.existing) {
		await switchTab(existingTab.payload.id)
	} else if (existingTab && opening === TabOpening.reload) {
		setTabLoadingState(existingTab.payload.id, { state: LoadingState.reload })
		await switchTab(existingTab.payload.id)
	}

	return null
}

const switchTab = async (id: string): Promise<boolean> => {

	const tab = getTab(id)

	if (!tab) {
		return false
	}

	const tmp: Tab[] = []
	tabs.value.forEach((loop: Tab) => {
		loop.payload.active = loop.payload.id === tab.payload.id
		if (loop.payload.active && loop.state.state === LoadingState.none) {
			loop.state.state = LoadingState.loading
		}
		tmp.push(loop)
	})
	tabs.value = tmp

	history.value.push(tab.payload.id)

	const { collapseNavigationNodes, highlightNode } = useNavigation()

	if (tab.payload.navigationNodeId) {
		await highlightNode(tab.payload.navigationNodeId)
	} else {
		collapseNavigationNodes()
	}

	return true
}

const moveTab = async (id: string, toIndex: number): Promise<void> => {
	/*const currentIndex = tabs.value.findIndex((loop: Tab) => loop.payload.id === id)
	if (currentIndex !== -1 && currentIndex !== index) {
		const copy = tabs.value
		const currentTab = copy.splice(currentIndex, 1)[0]
		copy.splice(index, 0, currentTab)
		tabs.value = copy
	}*/

	const ordered = tabs.value.slice().sort((a: Tab, b: Tab) => a.position - b.position)
	const fromIndex = ordered.findIndex((loop: Tab) => loop.payload.id === id)
	if (fromIndex === -1) return

	// Target index between [0, n-1]
	const n = ordered.length
	toIndex = Math.max(0, Math.min(toIndex, n - 1))
	if (fromIndex === toIndex) return

	const moving = ordered[fromIndex]

	if (fromIndex < toIndex) {
		// Move everything in between up one position
		for (let i = fromIndex + 1; i <= toIndex; i++) {
			ordered[i].position -= 1
		}
	} else {
		// Move everything in between down one position
		for (let i = toIndex; i < fromIndex; i++) {
			ordered[i].position += 1
		}
	}

	moving.position = toIndex
}

const removeTab = async (id: string) => {
	if (tabs.value.length === 1) {
		console.error('Cannot remove last tab')
		return false
	}

	const tab = getTab(id)

	if (!tab) {
		console.error('Tab not found')
		return false
	}

	// Remove history of tab
	history.value = history.value.filter((loop: string): boolean => loop !== tab.payload.id)
	// Remove tab
	tabs.value = tabs.value.filter((loop: Tab) => loop.payload.id !== tab.payload.id)

	// If it was an active tab we have to go back in history
	if (tab.payload.active) {
		let lastTab = null
		if (history.value.length > 0) {
			lastTab = tabs.value.find((loop: Tab): boolean => loop.payload.id === history.value[history.value.length - 1])
		}
		if (!lastTab) {
			// Fallback on the first tab
			lastTab = tabs.value[0]
		}
		await switchTab(lastTab.payload.id)
	}

	return true
}

const removeTabs = async (payload: Tab[]) => {
	payload.forEach((loop: Tab) => removeTab(loop.payload.id))
}

const setTabLoadingState = (id: string, state: ContentLoadingState) => {
	const index = tabs.value.findIndex((loop: Tab) => loop.payload.id === id)

	if (index !== -1) {
		const tmp = tabs.value
		tmp[index].state = state
		tabs.value = tmp
	}
}

const getTab = (id: string): Tab | null => {
	const tab = tabs.value.find((loop: Tab) => loop.payload.id === id)
	return tab ?? null
}

const executeTabContextAction = async (tab: Tab, action: TabContextAction) => {

	const index = tabs.value.findIndex((loop: Tab) => loop.payload.id === tab.payload.id)

	switch (action) {
		case TabContextAction.refresh:
			setTabLoadingState(tab.payload.id, { state: LoadingState.reload })
			return switchTab(tab.payload.id)
		case TabContextAction.clone: {
			const newTab = JSON.parse(JSON.stringify(tab))
			newTab.payload.id = generateRandomString(12)

			const originalIndex = tabs.value.findIndex((loop: Tab) => loop.payload.id === tab.payload.id)
			if (originalIndex !== -1) {
				tabs.value = [
					...tabs.value.slice(0, originalIndex + 1),
					newTab,
					...tabs.value.slice(originalIndex + 1)
				]
			} else {
				tabs.value.push(newTab)
			}

			await nextTick()

			return switchTab(newTab.payload.id)
		}
		case TabContextAction.close:
			return removeTab(tab.payload.id)
		case TabContextAction.closeTabsBefore:
			if (index !== -1) {
				return removeTabs(tabs.value.slice(0, index))
			}
			break
		case TabContextAction.closeTabsAfter:
			if (index !== -1) {
				return removeTabs(tabs.value.slice(index + 1, tabs.value.length))
			}
			break
		case TabContextAction.closeOtherTabs:
			if (index !== -1) {
				return removeTabs(tabs.value.filter((loop: Tab) => loop.payload.id !== tab.payload.id))
			}
			break
		case TabContextAction.saveTabs:
			return await save()
		default:
			console.error(`Missing implementation of tab context action "${action}"`)
	}

	return false
}

const fetchTabs = async () => {
	/* eslint-disable @typescript-eslint/no-unused-vars */
	const [error, response] = await safe<{
		allow_saving: boolean,
		max_tabs: number,
		tabs: RouterAction[]
	}>(api().action('init'))

	if (response) {
		allowSaving.value = response.allow_saving
		maxTabs.value = response.max_tabs

		//tabs.value = []

		const activeTab = response.tabs.find((loop: RouterAction) => (loop.payload as TabPayload).active)

		response.tabs.forEach(async (loop: RouterAction) => {
			const payload = loop.payload as TabPayload
			payload.active = !!(activeTab && (activeTab.payload as TabPayload).id === payload.id)
			await addTab(payload, loop.payload_storable)
		})
	}
}

const save = async () => {
	const payload = tabs.value.slice().sort((a: Tab, b: Tab) => a.position - b.position)
		.filter((tab: Tab) => tab.payload_storable)
		.map((tab: Tab) => tab.payload_storable)

	const [, response] = await safe(api().action('save', { method: 'POST', data: { tabs: payload } }))

	return response
}

export function useTabs() {
	return {
		loading: readonly(loading),
		allowSaving: readonly(allowSaving),
		tabs: readonly(tabs),
		orderedTabs: readonly(orderedTabs),
		fetchTabs,
		addTab,
		getTab,
		switchTab,
		removeTab,
		moveTab,
		executeTabContextAction,
		setTabLoadingState,
	}
}