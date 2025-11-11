<script>
import { updateField } from '@TsRegistrationForm/utils/store';

export default {
	props: {
		html: { type: String, required: true }
	},
	mounted() {
		this.$el.querySelectorAll('input').forEach(el => {
			el.addEventListener('input', ev => {
				updateField.call(this, el.name, ev.target.value, 'fields');
			});
		});
	}
}
</script>

<template>
	<div v-html="html" />
</template>