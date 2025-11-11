import { defineComponent, PropType } from 'vue3'
import type {
	FormatDefinition,
	QueryConfig,
	QueryDefinition,
	QueryDefinitionItems,
	QueryRows,
	QueryRowValue
} from '../types'
import { formatDate, formatNumber } from '../util/formatter'

export const Component = defineComponent({
	props: {
		config: { type: Object as PropType<QueryConfig>, required: true },
		definitions: { type: Array as PropType<Definition[]>, required: true },
		rows: { type: Array as PropType<QueryRows>, required: true }
	},
	methods: {
		export() {
			return undefined
		}
	}
})

export class Definition {
	type: string
	key: string
	index: number // Index in this.definitions (Daten in QueryRow sind genauso sortiert)
	label: string
	// sort: string
	format?: FormatDefinition
	items: QueryDefinitionItems
	pivot?: string // TODO
	subtotals?: boolean // TODO

	constructor(definiton: QueryDefinition, index: number) {
		this.index = index
		this.type = definiton.type
		this.key = definiton.key
		this.label = definiton.label
		// this.sort = definiton.sort
		this.format = definiton.format
		this.items = definiton.items
		this.pivot = definiton?.pivot
		this.subtotals = definiton?.subtotals
	}

	findItem(key: QueryRowValue): QueryDefinitionItems[number]|undefined {
		if (this.items) {
			return this.items.find(item => item[0] === key)
		}
		return undefined
	}

	getItemLabel(key: QueryRowValue): QueryRowValue {
		return this.findItem(key)?.[1] ?? key
	}

	getItemSortValue(key: QueryRowValue): QueryRowValue {
		return this.findItem(key)?.[2] ?? this.getItemLabel(key)
	}
}

abstract class BaseCell {
	value: QueryRowValue|null
	label?: string
	definition?: Definition
	key: string|null = null // Dient eigentlich nur zur Identifikation für HeadCell und Pseudo-Headcells (ValueCell als Grouping)
	colspan?: number
	rowspan?: number
	format = true

	constructor(value: QueryRowValue|null, definition?: Definition) {
		this.value = value
		this.definition = definition
	}

	get formatted() {
		if (this.format && this.definition) {
			if (typeof this.value === 'number' && this.definition.format?.type === 'number') {
				return formatNumber(this.value, this.definition.format.locale as string, this.definition.format.style, this.definition.format.currency)
			}
			if (this.value && this.definition.format?.type === 'date') {
				return formatDate(this.value.toString(), this.definition.format.locale as string, this.definition.format.unit as string, this.definition.format.labels ?? {})
			}
			// if (this.definition.type === 'grouping') {
			// 	return this.definition.getItemLabel(this.value as QueryRowValue)
			// }
		}
		return this.value
	}

	get align() {
		return 'left'
	}

	isEmpty() {
		return this.value === null
	}
}

export class HeadCell extends BaseCell {
	format = false
	path: QueryRowValue[] = ['']
}

export class ValueCell extends BaseCell {
	get align() {
		if (
			this.definition &&
			this.definition.format?.type === 'number'
		) {
			return 'right'
		}
		return 'left'
	}
}

export class SumCell extends ValueCell {

}

export class NullCell extends BaseCell {
	isEmpty() {
		return true
	}

	static create() {
		return new this(null, undefined)
	}
}
