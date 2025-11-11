<script>
import { checkDependencies } from '../../utils/validation';

export default {
	props: {
		dependencies: { type: Array }
	},
	computed: {
		visible() {
			return checkDependencies.call(this, this.dependencies);
		}
	},
	methods: {
		triggerDependencyVisibility(visible) {
			// TODO that can be better defined
			if (this.namespace && this.name) {
				this.$store.commit('DEPENDENCY_VISIBILITY', { field: `${this.namespace}.${this.name}`, visible })
			}
		}
	},
	watch: {
		visible(value) {
			this.triggerDependencyVisibility(value)
		}
	},
	mounted() {
		this.triggerDependencyVisibility(this.visible)
	}
}
</script>
