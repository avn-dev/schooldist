<template>
	<div class="fidelo-payment-form">
		<div class="container">
			<loading-overlay
				:active="loading"
				icon="spinner-border"
				@click="clickLoadingOverlay"
				v-slot="slotProps"
			>
				<p v-if="loadingText" v-html="loadingText"></p>
			</loading-overlay>
			<div class="card">
				<div class="card-body">
					<div class="row card-title">
						<h2 class="col-md-6">{{ $t('payment') }}</h2>
						<h3 v-if="data.invoice" class="col-md-6 text-md-right">{{ $t('invoice') }} {{ data.invoice }}</h3>
					</div>
					<hr>
					<div v-if="successMessage" class="alert alert-success" v-html="successMessage"></div>
					<div v-if="errorMessage" class="alert alert-danger">
						{{ errorMessage }}
					</div>
					<div v-if="dataLoaded" class="row">
						<div class="col-md-6 col-customer-details">
							<h4>{{ $t('customer') }} {{ data.customer_number }}</h4>
							<address>
								<template v-for="(line, index) in data.address">
									<strong v-if="index === 0">{{ line }}</strong>
									<template v-else>{{ line }}</template>
									<br>
								</template>
							</address>
						</div>
						<div class="col-md-6 col-payment-details">
							<h4>{{ $t('payment_details') }}</h4>
							<ul class="list-unstyled">
								<li>
									<strong>{{ $t('amount_total') }}</strong>
									<span>{{ data.amount_total }}</span>
								</li>
								<li>
									<strong>{{ $t('amount_payed') }}</strong>
									<span>{{ data.amount_payed }}</span>
								</li>
								<li>
									<strong>{{ $t('amount_due') }}</strong>
									<span>{{ data.amount_due }}</span>
								</li>
								<li :class="{ 'payment-is-due': data.is_due }">
									<strong>{{ $t('date_due') }}</strong>
									<time>{{ data.date_due }}</time>
								</li>
								<li v-if="data.full_amount_option && !successMessage" class="pay-full-amount">
									<label :for="$id('pay_full_amount')">{{ $t('pay_full_amount') }}</label>
									<div class="custom-control custom-checkbox">
										<input
											:id="$id('pay_full_amount')"
											:checked="payFullAmount"
											type="checkbox"
											class="custom-control-input"
											@input="changeAmount($event.target.checked)"
										>
										<label class="custom-control-label" :for="$id('pay_full_amount')"></label>
									</div>
								</li>
							</ul>
						</div>
					</div>
					<div v-if="dataLoaded && !successMessage" class="payment-methods">
						<payment-method
							v-for="paymentMethod in paymentMethods"
							:key="paymentMethod.key"
							ref="methods"
							:method="paymentMethod"
							:request="requestPayment"
							:active="!!(method && paymentMethod.key === method.key)"
							:disabled="loading"
							@change="select(paymentMethod)"
							@approve="approve"
							@cancel="cancel"
							@error="error"
							@loading="setLoading"
							@update:method="method = $event"
						/>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
	import VueSmoothReflow from 'vue-smooth-reflow';
	import LoadingOverlay from '@TcFrontend/common/components/LoadingOverlay';
	import PaymentMethod from './../../common/components/PaymentMethod';
	import { findMethodComponent } from '../../common/utils/payment';

	// https://bootsnipp.com/snippets/Pb15
	// https://codepen.io/daplo/pen/xYVQPz
	// https://w3hubs.com/simple-invoice-template-in-bootstrap-4/
	export default {
		components: {
			LoadingOverlay,
			PaymentMethod
		},
		mixins: [VueSmoothReflow],
		data() {
			return {
				data: {},
				errorMessage: '',
				successMessage: '',
				loading: true,
				loadingText: null,
				translations: {},
				paymentMethods: [],
				method: null,
				payFullAmount: false,
				requestPayment: (params) => {
					this.errorMessage = '';
					// When payment data is already there no further request is needed (e.g. changeAmount)
					if (this.data.payment && !params) {
						return new Promise(resolve => {
							resolve(this.data.payment);
						});
					}
					return this.request(params).then(resp => {
						return resp.data.payment;
					}).catch(err => {
						this.handleResponseError(err);
					});
				}
			}
		},
		computed: {
			dataLoaded() {
				return Object.keys(this.data).length;
			}
		},
		mounted() {
			this.$smoothReflow();
			this.load();
		},
		methods: {
			load() {
				this.loading = true;
				this.errorMessage = '';
				this.request({ preliminary: true }).then(resp => {
					this.loading = false;
					this.data = resp.data;
					this.translations = resp.data.translations;
					if (resp.data.methods.length) {
						this.paymentMethods = resp.data.methods;
					}
				}).catch(err => {
					this.loading = false;
					this.handleResponseError(err);
				});
			},
			select(method) {
				this.errorMessage = '';
				this.loading = true;
				this.method = method;
			},
			approve(payload) {
				this.loading = true;
				this.$http.post('submit', {
					method: this.method,
					process: this.getProcess(),
					process_following: this.data.process,
					payment: payload
				}).then(resp => {
					this.errorMessage = '';
					this.successMessage = resp.data.message;
				}).catch(err => {
					this.handleResponseError(err);
				}).then(() => {
					this.loading = false;
				});
			},
			cancel() {
				// Do nothing
			},
			error(type, error, errorMessage) {
				this.loading = false;
				if (!type) {
					// Delete error if component does not emit approve()
					this.errorMessage = '';
					return;
				}
				this.errorMessage = errorMessage ? errorMessage : this.$t('internal_error');
				console.error('Payment form error:', type, error);
			},
			request(params) {
				return this.$http.post('load', {
					process: this.getProcess(),
					process_following: this.data.process,
					method: this.method,
					pay_full_amount: this.payFullAmount,
					...params
				});
			},
			setLoading(state, text) {
				this.loading = state;
				this.loadingText = text;
			},
			getProcess() {
				const params = new URLSearchParams(window.location.search);
				return params.get('payment');
			},
			changeAmount(payFullAmount) {
				this.data = {};
				this.method = null;
				this.payFullAmount = payFullAmount;
				this.load();
			},
			handleResponseError(error) {
				if (error.response?.data?.message) {
					this.errorMessage = error.response.data.message;
				} else {
					this.errorMessage = this.$t('internal_error');
				}
				console.error('Payment form error:', error);
			},
			clickLoadingOverlay() {
				const component = findMethodComponent(this.$refs.methods, this.method.key);
				if (component) {
					component.$refs?.component?.focus();
				}
			}
		}
	}
</script>
