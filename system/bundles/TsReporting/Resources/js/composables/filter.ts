
export const EMITS = [
	'update:model-value'
]

export const PROPS = {
	modelValue: {
		type: null,
		required: true
	},
	type: {
		type: String,
		value: null
	},
	options: {
		type: Array
	}
}
