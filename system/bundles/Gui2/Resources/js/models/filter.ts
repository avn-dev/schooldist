export interface FilterOption {
	key: string | null
	label: string
}

export type FilterValue = null | string | Array<string>;

interface FilterObjectJson {
	key: string
	label: string
	type: string
	options: FilterOption[]
	value: FilterValue
	initial_value: FilterValue
	simple: boolean
	multiple: boolean
	negated: boolean
	negateable: boolean,
	show_in_bar: boolean
	additional_html: null | string
	collapsed: boolean,
	help_text: null | string
}

export type FilterQuery = {
	id: number
	name: string,
	visibility: string
}

export class SidebarElement {
	key: string
	label: string
	value: FilterValue
	constructor(key: string, label: string, value: FilterValue = null) {
		this.key = key
		this.label = label
		this.value = value
	}
}

export default class FilterModel {
	private config: FilterObjectJson

	constructor(filter: FilterObjectJson) {
		this.config = filter
	}

	get key(): string {
		return this.config.key
	}

	get label(): string {
		return this.config.label
	}

	get type(): string {
		return this.config.type
	}

	get value(): FilterValue {
		return this.config.value
	}

	set value(value: FilterValue) {
		this.config.value = value
	}

	get negated(): boolean {
		return this.config.negated
	}

	set negated(state: boolean) {
		this.config.negated = state
	}

	get options(): FilterOption[] {
		return this.config.options
	}

	get additionalHtml() {
		return this.config.additional_html
	}

	get helpText() {
		return this.config.help_text
	}

	reset() {
		this.value = this.config.initial_value
	}

	hasValue(): boolean {
		if (this.config.type === 'timefilter') {
			const value = this.value as Array<string>
			const initialValue = this.config.initial_value as Array<string>
			return value[0] !== initialValue[0] || value[1] !== initialValue[1]
		}
		if (this.config.multiple) {
			const value = this.value as Array<string>
			const initialValue = this.config.initial_value as Array<string>
			return value.length !== initialValue.length || !value.every((v: string, k: number) => v === initialValue[k])
		}
		return this.value !== this.config.initial_value
	}

	setOption(key: string) {
		if (this.config.type !== 'select') {
			throw new TypeError('Wrong filter type')
		}
		if (!this.config.options.some(o => o.key === key)) {
			throw new TypeError(`Option does not exist: ${key}`)
		}
		this.value = key
	}

	isMultiple(): boolean {
		return this.config.multiple
	}

	isNegateable(): boolean {
		return this.config.negateable
	}

	isSimple(): boolean {
		return this.config.simple
	}

	isShownInBar(): boolean {
		return this.config.show_in_bar
	}

	// Aktuell statisch und State wird von Component verwaltet
	isCollapsed(): boolean {
		return this.config.collapsed
	}

	buildLabel(): string {
		let value
		switch (this.type) {
			case 'select': {
				const operator = this.negated ? '≠ ' : ''
				value = this.isMultiple() ? this.value : [this.value]
				const label = (value as Array<string>).map((v: string) => this.options.find(o => o.key === v)?.label)
				return `${operator}${label.join(', ')}`
			}
			case 'timefilter':
				value = this.value as Array<string>
				if (value[0] && value[1]) {
					return `${this.label}: ${value[0]} – ${value[1]}`
				}
				if (value[1]) {
					return `${this.label}: ≤ ${value[1]}`
				}
				return `${this.label}: ≥ ${value[0]}`
			default:
				return `${this.key}: ${this.value}`
		}
	}

	compareForSearch(value: string): boolean {
		value = value.toLowerCase()
		const label = this.config.label.toLowerCase()
		if (label.includes(value)) {
			return true
		}
		if (this.config.type === 'select') {
			return this.config.options.some(o => o.label.toLowerCase().includes(value))
		}
		return false
	}
}
