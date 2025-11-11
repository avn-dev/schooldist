<template>
	<div :class="['form-group input-field', cssClass, type === 'checkbox' ? 'custom-control custom-checkbox' : '']">

		<!-- Checkbox -->
		<input
			v-if="type === 'checkbox'"
			v-model="data"
			:id="id"
			:name="id"
			type="checkbox"
			:disabled="disabled"
			:class="['custom-control-input', { 'is-invalid': isInvalid }]"
		>

		<!-- Label -->
		<label
			v-if="label !== null"
			:for="idLabel"
			:class="{ 'custom-control-label': type === 'checkbox' }"
		>
			{{ label }}
		</label>

		<!-- General input -->
		<input
			v-if="type === 'input' || type === 'email' || type === 'tel' || type === 'time'"
			v-model="data"
			:id="id"
			:type="type"
			:name="name"
			:class="['form-control', { 'is-invalid': isInvalid }]"
			:disabled="disabled"
			v-bind="$attrs"
		>

		<!-- Textarea -->
		<textarea
			v-if="type === 'textarea'"
			v-model="data"
			:id="id"
			:name="name"
			:class="['form-control', { 'is-invalid': isInvalid }]"
			:disabled="disabled"
			v-bind="$attrs"
		></textarea>

		<!-- Select -->
		<select
			v-if="type === 'select' || type === 'multiselect'"
			v-model="data"
			:id="id"
			:name="name"
			:class="['custom-select', { 'is-invalid': isInvalid }]"
			:disabled="disabled"
			:multiple="type === 'multiselect'"
			v-bind="$attrs"
		>
			<option v-if="emptyOption && type !== 'multiselect'" :value="null">{{ $s('translation_empty_option') }}</option>
			<option
				v-for="option in options"
				:key="option.key"
				:value="option.key"
				:disabled="option.disabled || false"
			>
				{{ option.label }}
			</option>
		</select>

		<!-- Radio buttons -->
		<div v-if="type === 'radio'" :class="['row', { 'is-invalid': isInvalid }]">
			<div v-for="option in options" :key="option.key" class="col">
				<div class="custom-control custom-radio">
					<input
						v-model="data"
						type="radio"
						:id="generateIdWithKey(option.key)"
						:name="id"
						:value="option.key"
						:disabled="disabled"
						:class="['custom-control-input', { 'is-invalid': isInvalid }]"
					>
					<label :for="generateIdWithKey(option.key)" class="custom-control-label">{{ option.label }}</label>
				</div>
			</div>
		</div>

		<!-- Datepicker -->
		<input-datepicker
			v-model="data"
			v-if="type === 'datepicker'"
			v-bind="$attrs"
			v-on="$listeners"
			:id="id"
			:name="name"
			:disabled="disabled"
			:is-invalid="isInvalid"
			:errors="errors"
		></input-datepicker>

		<!-- Upload -->
		<input-upload
			v-model="data"
			v-if="type === 'upload'"
			v-bind="$attrs"
			v-on="$listeners"
			:id="id"
			:name="name"
			:disabled="disabled"
			:is-invalid="isInvalid"
			:errors="errors"
		></input-upload>

		<!-- Error messages -->
		<div v-if="!hasSubComponent" class="invalid-feedback">
			{{ errors }}
		</div>

	</div>
</template>

<script>
	import InputDatepicker from './InputDatepicker';
	import InputUpload from './InputUpload';
	import { checkDatepickerRangeDate, isDate } from '../../utils/date';

	export default {
		name: 'InputField',
		components: {
			InputDatepicker,
			InputUpload
		},
		inheritAttrs: false,
		props: {
			type: { type: String, required: true },
			value: { type: null, required: true },
			label: String,
			name: { type: String, required: true },
			disabled: { type: Boolean, default: false },
			errors: String,
			isInvalid: { type: Boolean, default: false },
			options: Array,
			emptyOption: { type: Boolean, default: true }
		},
		computed: {
			id() {
				return this.$id(this.name);
			},
			idLabel() {
				return this.type !== 'radio' ? this.$id(this.name) : '';
			},
			data: {
				get() {
					return this.value;
				},
				set(value) {
					this.$emit('input', value)
				}
			},
			cssClass() {
				return `input-field-${this.type}`;
			},
			hasSubComponent() {
				return this.type === 'datepicker' || this.type === 'upload';
			}
		},
		beforeUpdate() {
			// Vue does not refresh select value if option does not exit anymore
			if (
				this.type === 'select' &&
				// If value of v-model does not exist in select, v-model is undefined (as example value in booking is null)
				this.data !== undefined &&
				// Prevent possible infinite loop
				this.data !== null &&
				!this.options.some(option => option.key === this.data && !option.disabled)
			) {
				this.$emit('input', null);
			}

			// V-calendar does nothing if selected date is not in available-dates anymore
			if (
				this.type === 'datepicker' &&
				this.$attrs.hasOwnProperty('available-dates') &&
				isDate(this.data) &&
				!checkDatepickerRangeDate(this.$attrs['available-dates'], this.data)
			) {
				this.$log.info(`${this.$options.name}:${this.name}: Removed selected datepicker date`, this.data, this.$attrs['available-dates']);
				this.$emit('input', null);
			}
		},
		methods: {
			generateIdWithKey(key) {
				const id = this.$id(this.name);
				return `${id}-${key}`;
			}
		}
	}
</script>
