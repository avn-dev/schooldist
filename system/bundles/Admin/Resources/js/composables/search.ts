import { ref, readonly, reactive, nextTick, type Ref } from 'vue3'
import * as debounce from 'debounce-promise'
import { Bookmark } from '../types/backend/app'
import { ComponentApiInterface, RouterAction } from '../types/backend/router'
import { safe } from '../utils/promise'
import router from '../router'

export type SearchResult = { hits: number, rows: SearchNode[] }
export type SearchNode = { action: RouterAction, matches: string[] }

const open: Ref<boolean> = ref(false)
// TODO LoadingState
const loading: Ref<boolean> = ref(false)
// TODO LoadingState
const searching: Ref<boolean> = ref(false)
const query: Ref<string> = ref('')
const result: Ref<SearchResult | null> = ref(null)
const menu: { recent: Bookmark[], quick_actions: Bookmark[], instances: Array<{ key: string, text: string }>, selected_instance: string|null } = reactive({
	recent: [],
	quick_actions: [],
	instances: [],
	selected_instance: null
})

const api = (): ComponentApiInterface => {
	return router.component('search')
}

const openSearch = async () => {

	loading.value = true
	/* eslint-disable @typescript-eslint/no-unused-vars */
	const [error, response] = await safe<{ recent: Bookmark[], quickActions: Bookmark[], instances: Array<{ key: string, text: string }> }>(api().action('init'))
	loading.value = false

	if (response) {
		Object.assign(menu, response)
		open.value = true
	}
}

const closeSearch = async () => {
	open.value = false
	resetSearch()

	await nextTick()
}

const resetSearch = () => {
	query.value = ''
	searching.value = false
	result.value = null
}

const search = debounce(async (payload: string) => {

	if (open.value === false) {
		await openSearch()
	}

	query.value = payload

	if (query.value.length > 0) {
		await execute()
	} else {
		resetSearch()
	}

}, 500)

const changeInstance = async (instance: string) => {
	menu.selected_instance = instance

	if (query.value.length > 0) {
		return await execute()
	}
}

const storeRecentResult = async (payload: SearchNode) => {
	if (payload.action.payload_storable) {
		return await safe(api().action('store', { method: 'POST', data: { action: payload.action.payload_storable } }))
	}
}

const execute = async () => {
	if (!searching.value && query.value.length > 0) {
		searching.value = true

		/* eslint-disable @typescript-eslint/no-unused-vars */
		const [error, response] = await safe(api().action<SearchResult>('search', {
			method: 'POST',
			data: { query: query.value, instance: menu.selected_instance }
		}))

		if (response) {
			result.value = response
		}

		searching.value = false
	}
}

export function useSearch() {
	return {
		open: readonly(open),
		loading: readonly(loading),
		searching: readonly(searching),
		query: readonly(query),
		result: readonly(result),
		menu: readonly(menu),
		openSearch,
		closeSearch,
		resetSearch,
		search,
		storeRecentResult,
		changeInstance
	}
}