<script>
import ServiceContainerMixin from './ServiceContainerMixin';
import { pluralize } from '@TsRegistrationForm/utils/helpers';

export default {
	mixins: [ServiceContainerMixin],
	computed: {
		blockServices() {
			return this.$store.state.form[pluralize(this.type)].filter((s) => !s.hasOwnProperty('blocks') || s.blocks.includes(this.block));
		}
	},
	methods: {
		findServiceIndex(key, option) {
			const field = this.getServiceKey();
			return this.services.findIndex(s => s[field] === key && (!option || s.additional === option));
		},
		buildServiceLabel(service) {
			// Add required label programatically if it's either selection or required and one selectable service
			// Otherwise an info is shown (see template) as only one chosen service is required
			if (
				this.view === 'selection' ||
				this.required && this.blockServices.length === 1
			) {
				return `${service.label} *`;
			}
			return service.label;
		}
	}
}
</script>
