<template>
	<nav class="nav">
		<a
			v-for="(page, index) in pages"
			class="nav-link"
			:class="{ active: isActive(index), visited: isVisited(index), error: hasError(index) }"
			href="#"
		>
			<span>{{ page.label }}</span>
		</a>
	</nav>
</template>

<script>
	import { getFormField } from '../../utils/helpers';

	export default {
		data() {
			return {
				pages: this.$store.getters.getPages
			}
		},
		computed: {
			currentPage() {
				return this.$store.state.form.state.page_current;
			}
		},
		methods: {
			isActive(index) {
				return this.currentPage === index;
			},
			isVisited(index) {
				return this.currentPage >= index;
			},
			hasError(index) {
				return this.$store.getters.$xvVue.activeErrors.some(error => {
					const field = error.fieldName.split('.');
					const def = getFormField(field, this.$store.state.form.fields);
					if (def && Number.isInteger(def.page) && def.page === index) {
						this.$log.info('Mark page for error', index, field, def);
						return true;
					}
				});
			}
		}
	}
</script>
