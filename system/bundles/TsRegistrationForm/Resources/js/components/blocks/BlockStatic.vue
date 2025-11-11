<template>
	<div v-if="visible">
		<h2 v-if="type === 'h2'">{{ text }}</h2>
		<h3 v-if="type === 'h3'">{{ text }}</h3>
		<hr v-if="type === 'hr'">
		<p v-if="type === 'text'" v-html="text"></p>
		<p v-if="type === 'confirm'" v-eval="confirmMessage"></p>
	</div>
</template>

<script>
	import DependencyMixin from '../mixins/DependencyMixin';
	import { VueHtmlEvalDirective } from '../../utils/helpers';

	export default {
		directives: {
			eval: VueHtmlEvalDirective
		},
		mixins: [DependencyMixin],
		props: {
			type: {
				type: String,
				required: true
			},
			text: String
		},
		computed: {
			confirmMessage() {
				return this.$store.state.form.confirm_message;
			}
		}
	}
</script>
