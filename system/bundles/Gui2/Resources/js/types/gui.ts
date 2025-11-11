import { Ref } from 'vue3'
import { Emitter } from 'mitt'
import { default as FilterModel, FilterQuery } from '../models/filter'

type EmitterEvents = {
	// eslint-disable-next-line
	[key: string]: any
}
export type EmitterType = Emitter<EmitterEvents>

type GuiOptions = {
	[key: string]: unknown
	help_url: string
	info_icon_filter_key: string
	info_icon_edit_mode: boolean
}

// TS Typ-Definition für ATG2 (gui2.js)
export type GuiInstance = {
	// [key: string]: unknown
	hash: string,
	name: string,
	filters: Ref<FilterModel[]>
	filterQuery: Ref<FilterQuery>
	filterQueries: Ref<FilterQuery[]>
	executeFilterSearch(loadBars?: boolean, hash?: string, additionalParams?: string): void
	getFilterparam(A?: unknown, B?: unknown): string
	getTranslation(key: string): string
	loadTable(loadBars?: boolean, hash?: string, limit?: number, additionalParams?: string, showLoading?: boolean): void
	openHelp(): void
	openInfoIconDialog(key: string): void
	options: GuiOptions
	prepareCalendar(el: HTMLInputElement, B?: unknown, C?: unknown, D?: unknown, E?: unknown): void
	prepareOpenDialog(action: string, additional?: string, additioanlParam?: string): void
	request(query: string, B?: unknown, C?: unknown, D?: unknown, E?: unknown, F?: unknown, G?: unknown): void
	request2(body: URLSearchParams|Record<string, unknown>): Promise<Record<string, unknown>>
	requestCallback(data: Record<string, unknown>): void
	bSaveAsNewEntry: boolean|string,
	selectedRowId: number
	useRowIdWithoutRows: boolean
}
