<template>
	<div :class="['payment-stripe', { disabled }]">
		<component
			:is="component"
			:data="data"
			:stripe="stripe"
			:translations="translations"
			@loading="$emit('loading', $event)"
			@submit="submit($event)"
		></component>
	</div>
</template>

<script>
import PaymentMixin from '@TcFrontend/common/components/payment/PaymentMixin.vue';
import StripeCard from './stripe/StripeCard.vue';

// https://stripe.com/docs/payments/accept-a-payment?integration=elements
// https://stripe.com/docs/js/elements_object/create_element
export default {
	components: {
		StripeCard,
	},
	mixins: [PaymentMixin],
	data() {
		return {
			component: 'stripe-card',
			data: {},
			stripe: null
		}
	},
	mounted() {
		this.$loadScript(this.url).then(() => {
			// Return promise
			return this.request();
		})
		.then((data) => {
			this.data = data;
			this.stripe = window.Stripe(data.api_key);
		})
		.catch((e) => {
			this.$emit('error', 'script', e);
		});
	},
	methods: {
		submit(promise) {
			promise.then(result => {
				if (result.error) {
					// Pass all Stripe errors to end-user as there are too many possible errors
					// TODO Log
					this.$emit('error', 'provider', result.error, result.error.message);
					return;
				}
				this.$emit('approve', result.paymentIntent);
			})
			.catch(error => {
				// Can this Promise actually be rejected?
				this.$emit('error', 'provider_catch', error);
			});
		}
	}
}
</script>
