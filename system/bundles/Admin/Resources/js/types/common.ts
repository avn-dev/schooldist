export type SelectOptionValueType = number|string
export type SelectOption = { value: SelectOptionValueType, text: string, additional?: Record<string, any> }

export type Passkey = SelectOption & {
	created: string
	last_login?: string
}

export type Device = SelectOption & {
	current: boolean
	created: string
	last_login?: string
	standard: boolean
}