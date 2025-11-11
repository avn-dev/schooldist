<template>
	<input-field
		v-model="value"
		type="radio"
		:name="$id(name)"
		:label="null"
		:options="options"
		:disabled="disabled"
		:is-invalid="$v.$error"
		:errors="$t('error_required')"
	></input-field>
</template>

<script>
import InputField from './InputField';
import FieldMixin from '../mixins/FieldMixin';
import TranslationsMixin from '../mixins/TranslationsMixin';

export default {
	components: {
		InputField
	},
	mixins: [FieldMixin, TranslationsMixin],
	props: {
		name: { type: String, required: true },
		disabled: Boolean
	},
	data() {
		return {
			options: [
				{ key: true, label: this.$t('yes') },
				{ key: false, label: this.$t('no') }
			]
		};
	},
	watch: {
		value(value) {
			this.$emit('input', value);
		}
	},
	methods: {
		$vFindElement(error) {
			if (error.fieldName === `${this.namespace}.${this.name}`) {
				return this.$parent.$el;
			}
		}
	}
}
</script>