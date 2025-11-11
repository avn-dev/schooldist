
export type DialogValue = unknown|DialogValues[]
export type DialogValues = Record<string, DialogValue>

export type SelectOption = { key: string, label: string }
export type SelectOptions = SelectOption[]

export type RepeatableSectionValue = Record<string, string|number>

export type FieldDependency = {
	type: string,
	field: string,
	values: string[]
}

export type DialogComponent = {
	component: string,
	key: string,
	name: string, // key wird als name weitergegeben, da key in Vue reserved ist
	type?: string,
	label?: string,
	options?: Array<{ key: string, label: string }>,
	required?: boolean
}
