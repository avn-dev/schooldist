<template>
	<div v-if="visible">
		<loading-overlay
			:active="loading"
			:icon="$icon('spinner')"
			@click="clickLoadingOverlay"
		>
			<p v-if="loadingText" v-html="loadingText"></p>
		</loading-overlay>
		<div v-show="$v.$error" class="alert alert-danger" ref="error">
			{{ errors }}
		</div>
		<!--<div v-if="approved" class="alert alert-info">
			<strong>{{ $t('payment_authorized')}}</strong>
			<a href="#" @click.prevent="load">
				<i :class="$icon('times')"></i>
				{{ $t('undo') }}
			</a>
		</div>-->
		<div v-show="locked && !loading" class="alert alert-warning">
			{{ $t('payment_locked') }}
			<a href="#" @click.prevent="retry">
				<i :class="$icon('redo')"></i>
				{{ $t('retry') }}
			</a>
		</div>
		<div
			v-if="true /*!approved*/"
			class="payment-methods"
		>
			<loading-overlay :active="locked"></loading-overlay>
			<payment-method
				v-for="method in methods"
				:key="method.key"
				ref="methodComponents"
				:method="method"
				:request="request"
				:active="isMethod(method)"
				:disabled="loading"
				@change="select(method)"
				@approve="approve"
				@cancel="cancel"
				@error="error"
				@loading="setLoading"
				@update:method="selection = $event"
			></payment-method>
		</div>
	</div>
</template>

<script>
import LoadingOverlay from '@TcFrontend/common/components/LoadingOverlay';
import PaymentMethod from '@TcFrontend/common/components/PaymentMethod';
import { findMethodComponent } from '@TcFrontend/common/utils/payment';
import { scrollToElement } from '@TcFrontend/common/utils/widget';
import { submit } from '../../utils/helpers';
import FieldMixin from '../mixins/FieldMixin';
import CardBlock from '../common/CardBlock';
import TranslationsMixin from '../mixins/TranslationsMixin';
import DependencyMixin from "../mixins/DependencyMixin.vue";
import { payment } from '@TsRegistrationForm/utils/api';
import { getChildComponentsWithError, focusFirstElementWithError } from '@TsRegistrationForm/utils/validation';

export default {
	mixins: [FieldMixin, TranslationsMixin, DependencyMixin],
	components: {
		CardBlock,
		LoadingOverlay,
		PaymentMethod
	},
	data() {
		return {
			loading: false,
			loadingText: null,
			locked: true,
			methods: [],
			selection: null,
			storeWatchers: [],
			request: (params) => {
				this.error(false);
				return payment(this.$store, { method: this.selection, ...params }).then(resp => {
					return resp.data.payment;
				});
			}
		};
	},
	computed: {
		// approved() {
		// 	return !this.$v.$error && this.value !== null;
		// }
	},
	watch: {
		'$store.state.booking': {
			deep: true,
			immediate: true,
			handler() {
				// Wenn Validierung von irgendeinem Feld außer BlockPayment, dann resetten
				if (this.$store.getters.$xvVue.errors.some(err => err.hasError && err.fieldName !== `fields.${this.name}`)) {
					this.locked = true;
					this.clearValue();
					return;
				}

				// Wenn bei Validierung alles in Ordnung und BlockPayment noch nicht initiiert, dann sofort laden
				if (this.locked) {
					this.load();
				}
			}
		},
		'$store.state.booking.fields.payment_full'() {
			if (!this.locked) {
				this.load();
			}
		}
	},
	methods: {
		$vFindElement(error) {
			if (error.fieldName === `fields.${this.name}`) {
				return this.$refs.error;
			}
		},
		load() {
			this.setLoading(true);
			this.clearValue();
			this.locked = true;
			payment(this.$store, { preliminary: true }).then(resp => {
				if (resp.data.methods) {
					this.locked = false;
					this.methods = resp.data.methods;
				}
				this.setLoading(false);
			}).catch((resp) => {
				this.setLoading(false);
				if (!resp.response || resp.response.status !== 400 || typeof resp.response.data !== 'object') {
					this.error('payment', resp);
				}
			});
		},
		retry() {
			this.$store.dispatch('triggerVuelidate', { except: [`fields.${this.name}`] }).then(() => {
				this.load();
			}).catch(() => {
				this.$log.error('BlockPayment validation failed:', this.$store.getters.$xvVue.activeErrors);
				this.focusError();
			});
		},
		select(method) {
			this.setLoading(true);
			this.clearValue();
			this.selection = method;
			const component = findMethodComponent(this.$refs.methodComponents, method.key);
			if (component) {
				scrollToElement(component.$el);
			}
		},
		isMethod(method) {
			return method.key === this.selection?.key;
		},
		input(value) {
			this.value = value;
			this.$v.$touch(); // Touch explictly as value might not have been changed if null
		},
		approve(payload) {
			payload.method = this.selection;
			this.input(payload);
			submit.call(this, 'booking');//.then(() => this.clearValue());
		},
		cancel() {
			this.input(null);
			this.setLoading(false);
		},
		validate() {
			if (!this.visible && this.value === null)  {
				this.input({ method: { key: 'skip', provider: 'skip'}})
			}
			this.$v.$touch()
		},
		error(type, error, errorMessage) {
			if (!type) {
				this.$store.commit('DELETE_SERVER_VALIDATION', [`fields.${this.name}`]);
				this.$v.$reset();
				return;
			}
			this.$log.error('BlockPayment error:', error, errorMessage);
			errorMessage = errorMessage ? errorMessage : this.$s('translation_internal_error');
			this.$store.commit('ADD_SERVER_VALIDATION', { key: `fields.${this.name}`, messages: [errorMessage] });
			this.input(null);
			this.setLoading(false);
			this.focusError();
		},
		clearValue() {
			this.selection = null;
			this.updateField(null).then(() => this.$v.$reset());
		},
		setLoading(state, text) {
			this.loading = state;
			this.loadingText = text;
		},
		clickLoadingOverlay() {
			const component = findMethodComponent(this.$refs.methodComponents, this.selection.key);
			if (component) {
				component.$refs?.component?.focus();
			}
		},
		focusError() {
			const invalidComponents = getChildComponentsWithError(this);
			if (invalidComponents.length) {
				focusFirstElementWithError.call(this, invalidComponents);
			} else {
				scrollToElement(this.$root.$el.parentElement);
			}
		}
	}
}
</script>
