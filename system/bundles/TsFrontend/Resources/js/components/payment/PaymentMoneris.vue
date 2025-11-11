<script>
import PaymentMixin from '@TcFrontend/common/components/payment/PaymentMixin.vue';

/**
 * Callbacks:
 *  - page_loaded
 *  - cancel_transaction
 *  - error_event
 *  - payment_receipt
 *  - payment_complete
 */
export default {
	mixins: [PaymentMixin],
	mounted() {
		this.$loadScript(this.url).then(() => {
			return this.request();
		}).then(data => {
			this.$emit('loading', false);

			if (!data.ticket) {
				this.$emit('error', 'server');
				return;
			}

			const moneris = new window.monerisCheckout();
			moneris.setMode(data.mode);
			moneris.setCheckoutDiv('moneris-checkout'); // document.getElementById
			moneris.setCallback('cancel_transaction', () => this.$emit('cancel'));
			moneris.setCallback('error_event', e => this.$emit('error', 'provider', e));
			moneris.setCallback('payment_complete', data => {
				moneris.closeCheckout();
				this.$emit('approve', JSON.parse(data));
			});
			moneris.startCheckout(data.ticket);
		}).catch(e => {
			this.$emit('error', 'script', e);
		});
	}
}
</script>

<template>
	<div class="payment-moneris">
		<div id="moneris-checkout"></div>
	</div>
</template>
