<script>
	import { createService } from '../../utils/store';
	import TranslationsMixin from '../mixins/TranslationsMixin';
	import DependencyMixin from '../mixins/DependencyMixin';

	export default {
		inheritAttrs: false,
		mixins: [TranslationsMixin, DependencyMixin],
		props: {
			block: { type: String, required: true }, // e.g. courses_123
			type: { type: String, required: true }, // e.g. course
			max: { type: Number },
			min: { type: Number, default: 0 }
		},
		data() {
			return {
				component: `service-${this.type}`,
				cssClass: `service-container-${this.type}`
			};
		},
		computed: {
			$v() {
				// Vuelidate
				// Service blocks may not always be rendered
				return this.$store.getters.$xv.services[this.block];
			},
			count() {
				// s.split: Ignore services which have been splitted by holidays
				return this.services.filter(s => !s.field_state?.holiday_split).length;
			},
			/**
			 * All services of CORRESPONDING block
			 * @returns {[]}
			 */
			services() {
				return this.$store.getters.getServices(this.block);
			}
		},
		watch: {
			// Watch change of DependencyMixin property
			visible(value) {
				if (value === false) {
					// Delete all services
					this.clearServices();
				} else {
					// After triggerVueInstanceValidators, do not show any general errors for just shown blocks
					this.$vReset();
				}
			}
		},
		methods: {
			$vFindElement(error) {
				if (!this.visible) {
					return;
				}
				const name = error.fieldName.split('.'); // e.g. services.courses.course
				if (name[0] !== 'services' || name[1] !== this.block) {
					return null;
				}
				// Prefer div before input as whole block is viewable then
				if (this.$refs.error) {
					return this.$refs.error;
				}
				const selector = `input[name="${name[2]}"], select[name="${name[2]}"]`;
				const el = this.$el.querySelector(selector);
				if (el) {
					return el;
				}
				this.$log.error('Could not find BlockServiceContainer element but namespace matched', error, this);
				return null;
			},
			$vReset() {
				const fields = Object.keys(this.$store.state.form.fields.services[this.block].fields);
				this.$store.commit('DELETE_SERVER_VALIDATION', fields.map(f => `services.${this.block}.${f}`));
				this.$v.$reset();
				this.$store.commit('DISABLE_STATE', { key: 'next', status: false });
			},
			/**
			 * First field from service (primary key/id)
			 * @returns {String}
			 */
			getServiceKey() {
				return this.$store.state.form.fields.services[this.block]['id'];
			},
			/**
			 * @param {String} [key]
			 * @param {Object} [data]
			 * @param {Boolean} [internal]
			 */
			addService(key, data, internal) {
				const service = createService.call(this, this.block, data);
				service[this.getServiceKey()] ??= key ?? null; // Set primary field to given key (key must be null for validator requiredIf)
				const payload = { type: this.block, service };
				if (internal) {
					// Do not trigger field actions (requests)
					this.$store.commit('INSERT_SERVICE', payload);
				} else {
					this.$store.dispatch('insertService', payload).then(() => this.$vReset());
				}
			},
			clearServices() {
				let p = [];
				for (let index = 0; index < this.services.length; index++) {
					p.push(this.$store.dispatch('deleteService', { type: this.block, index }));
				}
				return Promise.all(p).then(() => this.$vReset());
			}
		}
	}
</script>
