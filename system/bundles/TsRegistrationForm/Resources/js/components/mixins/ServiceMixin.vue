<script>
	import TranslationsMixin from '../mixins/TranslationsMixin';
	import { createService, createServiceFieldProps } from '../../utils/store';
	import { parseDate } from '../../utils/date';

	export default {
		mixins: [TranslationsMixin],
		props: {
			block: { type: String, required: true },
			index: { type: Number, required: true },
			view: { type: String, default: 'container' }
		},
		computed: {
			$vs() {
				// Vuelidate
				// $vs: Don't validate single services ($v is used in BlockServiceContainer)
				return this.$store.getters.$xv.services[this.block].$each[this.index];
			}
		},
		beforeCreate() {
			createServiceFieldProps(this);
		},
		methods: {
			reset() {
				const service = createService.call(this, this.block);
				return this.$store.dispatch('replaceService', {
					type: this.block,
					index: this.index,
					service
				});
			},
			remove() {
				return this.$store.dispatch('deleteService', {
					type: this.block,
					index: this.index
				});
			},
			generateDatepickerAttributes(type) {
				const attrs = [];
				switch (type) {
					case 'service_period':
						if (this.$store.getters.hasServicePeriod) {
							const servicePeriod = this.$store.state.form.periods.course_and_accommodation;
							attrs.push({ dot: 'blue', dates: parseDate(servicePeriod[0]), popover: { label: this.$t('service_start') } });
							attrs.push({ dot: 'blue', dates: parseDate(servicePeriod[1]), popover: { label: this.$t('service_end') } });
						}
						return attrs;
					case 'course':
						const courseDates = this.$store.state.form.periods.course;
						if (courseDates.length) {
							attrs.push({ dot: 'blue', dates: parseDate(courseDates[0]), popover: { label: this.$t('course_start') } });
							attrs.push({ dot: 'blue', dates: parseDate(courseDates[1]), popover: { label: this.$t('course_end') } });
						}
						return attrs;
					default:
						return attrs;
				}
			}
		}
	}
</script>
