<script>
export default {
	props: {
		value: { type: null, required: true },
		type: { type: String, default: 'text' },
		name: { type: String, required: true },
		label: { type: String, default: null },
		options: { type: Array }
	},
	inject: ['translations', '$v'],
	emits: [
		'input'
	],
	data() {
		return {
			inputLabel: this.label ?? this.translations[this.name]
		}
	}
}
</script>

<template>
	<div
		class="form-group"
		:data-error-field="name"
	>
		<label :for="$id(name)">{{ inputLabel }} *</label>
		<input
			v-if="type === 'text' || type === 'email' || type === 'date' || type === 'tel'"
			:id="$id(name)"
			:value="value"
			:name="name"
			:type="type"
			class="form-control"
			:class="{ 'is-invalid': $v.values[name].$error }"
			@input="$emit('input', $event.target.value)"
		/>
		<select
			v-if="type === 'select'"
			:id="$id(name)"
			:value="value"
			:name="name"
			class="form-control"
			:class="{ 'is-invalid': $v.values[name].$error }"
			@input="$emit('input', $event.target.value)"
		>
			<option></option>
			<option
				v-for="option in options"
				:key="option.key"
				:value="option.key"
			>
				{{ option.label }}
			</option>
		</select>
		<slot name="form-text"></slot>
	</div>
</template>
