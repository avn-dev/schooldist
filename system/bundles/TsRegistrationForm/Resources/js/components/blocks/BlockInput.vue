<template>
	<input-field
		v-if="visible"
		v-model="value"
		:type="type"
		:label="label"
		:name="name"
		:options="options"
		:empty-option="emptyOption"
		:is-invalid="$v.$error"
		:errors="errors"
		:disabled="disabled"
		v-bind="$attrs"
	></input-field>
</template>

<script>
	// import { parseDate } from '../../utils/date';
	import DependencyMixin from '../mixins/DependencyMixin';
	import FieldMixin from '../mixins/FieldMixin';
	import InputField from '../common/InputField';

	export default {
		mixins: [DependencyMixin, FieldMixin],
		components: {
			InputField
		},
		props: {
			type: { type: String, required: true },
			label: String,
			options: Array,
			emptyOption: { type: Boolean, default: true }
		},
		computed: {
			disabled() {
				return this.$store.state.form.state.disable_form;
			}
		},
		watch: {
			// Watch change of DependencyMixin property
			// If this property changes, reset server validation (like server-side e-mail format validation) and reset validation status
			visible(value) {
				if (value === false) {
					// Delete value as submitted values will be validated if given (like e-mail for format)
					this.value = null;
				}
				this.$store.commit('DELETE_SERVER_VALIDATION', [`fields.${this.name}`]);
				this.$v.$reset();
			}
		},
		// beforeCreate() {
		// 	if (
		// 		this.$attrs.hasOwnProperty('max-date') &&
		// 		!this.$attrs['max-date'] instanceof Date
		// 	) {
		// 		// V-calendar needs Date object
		// 		this.$attrs['max-date'] = parseDate(this.$attrs['max-date'].replace('date:', ''));
		// 	}
		// },
		methods: {
			$vFindElement(error) {
				if (this.visible && error.fieldName === `fields.${this.name}`) {
					return this.$el.querySelector('label');
					// return this.$el.querySelector('input, select, textarea');
				}
			}
		}
	}
</script>
