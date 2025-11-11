<script>
	import { updateField } from '../../utils/store';

	/**
	 * Components using this mixin must define $vFindElement() method!
	 */
	export default {
		props: {
			name: { type: String, required: true },
			namespace: { type: String, default: 'fields' }, // fields | selections
			errorMessage: String, /* General error message (required message) */
		},
		computed: {
			$v() {
				return this.$store.getters.$xv[this.namespace][this.name];
			},
			value: {
				get() {
					return this.$store.getters.getField(this.name, this.namespace)
				},
				set(value) {
					this.updateField(value);
				}
			},
			errors() {
				if (
					!this.$v.remote &&
					this.$store.state.form.remote_validation[`${this.namespace}.${this.name}`]
				) {
					return this.$store.state.form.remote_validation[`${this.namespace}.${this.name}`].join(' ');
				}
				return this.errorMessage;
			}
		},
		methods: {
			/**
			 * Update field like with setter but provides access to promise
			 * @param {*} value
			 * @returns {Promise<*>}
			 */
			updateField(value) {
				return updateField.call(this, this.name, value, this.namespace);
			},
			validate() {
				this.$v.$touch()
			}
		}
	}
</script>
