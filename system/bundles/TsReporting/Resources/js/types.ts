import { SelectOptions } from '@Gui2/types/dialog'

export type Report = {
	id: string,
	name: string
}

export type Translations = Record<string, string>

export type QueryConfig = {
	visualization?: string,
	pivot?: {
		show_grand_totals: boolean,
		show_row_totals: boolean,
		grand_totals: string,
		subtotals_for_label: string,
		row_totals: string
	}
	message?: string,
	debug?: string
}

export type QueryDefinitionItems = Array<[key: string, label: string, sort: string|null]>

export type QueryDefinition = {
	key: string,
	type: string,
	label: string,
	// sort: string,
	format?: FormatDefinition,
	items: QueryDefinitionItems,
	pivot?: string // TODO
	subtotals?: boolean // TODO
}

export type QueryDefinitions = QueryDefinition[]

export type QueryFilter = {
	key: string,
	name: string,
	component: string,
	type: string|null,
	value: unknown,
	options: SelectOptions,
	dependencies: string[],
	required: boolean
}

export type QueryRow = QueryRowValue[]

export type QueryRowValueColumn = [value: string|number, label: string]

export type QueryRowValue = string|number|QueryRowValueColumn

export type QueryRows = QueryRow[]

export type QueryPayload = {
	config: QueryConfig,
	definitions: QueryDefinitions,
	filters: QueryFilter[],
	rows: QueryRows
}

export type FormatDefinition = {
	type: string
	locale?: string
	style?: string
	currency?: string
	unit?: string
	labels?: Record<string, string>
	summable?: boolean
}
