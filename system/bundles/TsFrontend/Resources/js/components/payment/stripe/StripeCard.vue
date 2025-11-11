<template>
	<div>
		<div class="row">
			<div class="col-sm-12 form-group">
				<label>{{ translations.card_number }}</label>
				<input v-if="!initialized" class="form-control" disabled>
				<div ref="cardNumber"></div>
			</div>
			<div class="col-sm-6 form-group">
				<label>{{ translations.expiration_date }}</label>
				<input v-if="!initialized" class="form-control" disabled>
				<div ref="cardExpiry"></div>
			</div>
			<div class="col-sm-6 form-group">
				<label>{{ translations.security_code }}</label>
				<input v-if="!initialized" class="form-control" disabled>
				<div ref="cardCvc"></div>
			</div>
			<div class="col-sm-12">
				<button class="btn btn-block" :class="{ 'btn-primary': initialized }" @click="submit" :disabled="!initialized">{{ buttonLabel }}</button>
			</div>
		</div>
	</div>
</template>

<script>
export default {
	props: {
		data: { type: Object },
		stripe: { type: Object },
		translations: { type: Object, required: true }
	},
	data() {
		return {
			buttonLabel: '…',
			elements: {
				cardNumber: null,
				cardExpiry: null,
				cardCvc: null
			},
			initialized: false,
		};
	},
	watch: {
		stripe() {
			if (!this.initialized && this.stripe) {
				this.createElements();
			}
		}
	},
	beforeDestroy() {
		if (this.initialized) {
			Object.values(this.elements).forEach(el => el.destroy());
		}
	},
	methods: {
		createElements() {
			const stripeElements = this.stripe.elements();
			const options = {
				classes: {
					base: 'form-control form-control-stripe',
					focus: 'form-control-focus',
					invalid: 'is-invalid'
				},
				style: {
					base: {
						color: window.getComputedStyle(this.$parent.$el).getPropertyValue('--input-color').trim()
					}
				}
			};

			// Create Stripe elements
			Object.keys(this.elements).forEach(key => {
				const opts = key === 'cardNumber' ? { showIcon: true, ...options } : options;
				this.elements[key] = stripeElements.create(key, opts);
			});

			const promises = [];
			for (const [key, el] of Object.entries(this.elements)) {
				// Keep loading spinner until all stripe elements are ready
				promises.push(new Promise(resolve => {
					el.on('ready', resolve);
				}));

				// Mount Stripe elements' DOM
				el.mount(this.$refs[key]);
			}

			this.buttonLabel = this.translations['pay_now'].replace('{amount}', this.data.amount);
			this.initialized = true; // Change before promise resolving to keep updates in same tick
			this.$emit('loading', false);
		},
		submit() {
			let p;
			this.$emit('loading', true); // Needed for Stripe's confirmCardPayment()
			if (!this.stripePaymentIntent) {
				// https://stripe.com/docs/js/payment_intents/confirm_card_payment
				p = this.stripe.confirmCardPayment(this.data.client_secret, {
					payment_method: {
						type: 'card',
						card: this.elements['cardNumber'], // Any element from created elements can be passed
						billing_details: this.data.billing_details
					}
				});
			} else {
				// Request to Stripe can only be done once; fake promise
				// TODO Actually this was only required for dev
				p = new Promise(resolve => {
					resolve({ paymentIntent: this.stripePaymentIntent });
				});
			}
			this.$emit('submit', p);
		}
	}
}
</script>
