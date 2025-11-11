<template>
	<div :class="['payment-paypal', { disabled }]" ref="container"></div>
</template>

<script>
	import PaymentMixin from './PaymentMixin';

	export default {
		mixins: [PaymentMixin],
		mounted() {
			this.$loadScript(this.url).then(() => {
				// https://developer.paypal.com/docs/business/javascript-sdk/javascript-sdk-reference/#buttons
				window.paypal.Buttons({
					// When user clicks one of the buttons
					createOrder: () => {
						return this.request().then((data) => {
							return data.order_id;
						});
					},
					// When user approves payment
					onApprove: (data) => {
						this.$emit('approve', data);
					},
					// When user cancels payment
					onCancel: () => {
						this.$emit('cancel');
					},
					// When there is any error in process (including error within onApprove/onCancel)
					onError: (e) => {
						this.$emit('error', 'provider', e);
					}
				}).render(this.$refs.container);
			})
			.catch((e) => {
				this.$emit('error', 'script', e);
			})
			.then(() => {
				this.$emit('loading', false);
			});
		}
	}
</script>
