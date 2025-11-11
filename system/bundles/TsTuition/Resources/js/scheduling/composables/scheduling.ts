import { ref, reactive, readonly, type Ref } from 'vue3'
import { Block, FilterValues } from "../types"
import { default as ky } from 'ky'
import { legacy } from "../../scheduling"

const loading: Ref<boolean> = ref(false)
const week: Ref<number> = ref(0)
const weekday: Ref<number> = ref(0)
// @ts-ignore
const filterValues = reactive<FilterValues>({} as FilterValues)
const blockSelection: Ref<Block[]> = ref([])

const setWeekAndWeekDay = (payloadWeek: number, payloadWeekday: number) => {
	week.value = payloadWeek
	weekday.value = payloadWeekday
}

const setFilterValue = async (name: string, value: any): Promise<any> => { // eslint-disable-line
	// @ts-ignore
	filterValues[name] = value
	return await loadBlocks()
}

const loadBlocks = (weekdayPayload?: number): Promise<any> => { // eslint-disable-line
	return new Promise((resolve, reject) => {
		if (typeof weekdayPayload === 'undefined') {
			weekdayPayload = weekday.value
		}

		loading.value = true
		let promise = null

		if (weekdayPayload !== weekday.value) {
			weekday.value = weekdayPayload
			promise = legacy.changeWeekday(weekdayPayload)
		} else {
			promise = legacy.loadBlocks()
		}

		promise
			.then((response) => resolve(response))
			.catch((e) => reject(e))
			.finally(() => loading.value = false)
	})

}

const hightlightBlock = async (block: Block): Promise<HTMLElement> => {
	if (block.weekday !== weekday.value) {
		await loadBlocks(block.weekday)
	}

	return legacy.highlightBlock(block)
}

const fillBlockSelection = async (search: string): Promise<Block[]> => {

	blockSelection.value = []

	try {
		blockSelection.value = await ky.get('/ts-tuition/scheduling/json/search', {
			// @ts-ignore
			searchParams: new URLSearchParams({ search: search, week: week.value, weekday: weekday.value, ...filterValues })
		}).json()
	} catch (e) {
		console.error(e)
	}

	return blockSelection.value
}

const unsetBlockSelection = () => {
	blockSelection.value = []
}

export function useScheduling() {

	return {
		loading: readonly(loading),
		week: readonly(week),
		weekday: readonly(weekday),
		filterValues: readonly(filterValues),
		blockSelection: readonly(blockSelection),
		setWeekAndWeekDay,
		setFilterValue,
		fillBlockSelection,
		unsetBlockSelection,
		hightlightBlock
	}
}