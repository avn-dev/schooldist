<template>
	<div :class="['payment-klarna', { disabled }]">
		<div ref="klarna"></div>
		<button class="btn btn-block" :class="{ 'btn-primary': !disabled }" @click="submit">{{ buttonLabel }}</button>
	</div>
</template>

<script>
import PaymentMixin from '@TcFrontend/common/components/payment/PaymentMixin.vue';

export default {
	mixins: [PaymentMixin],
	data() {
		return {
			category: this.method.key.replace('klarna_', ''),
			buttonLabel: '…',
			payment: {}
		};
	},
	mounted() {
		this.$loadScript(this.url).then(() => {
			return this.request();
		}).then(data => {
			this.payment = data;
			this.buttonLabel = this.translations['pay_now'].replace('{amount}', data.amount);
			// https://docs.klarna.com/klarna-payments/api-call-descriptions/load-klarna-payments/
			window.Klarna.Payments.init({
				client_token: data.client_token
			});
			window.Klarna.Payments.load({
				container: this.$refs.klarna,
				payment_method_category: this.category
			}, (data) => {
				// TODO According to Klarna the payment methods must be hidden
				if (!data.show_form) {
					this.$emit('cancel');
				}
				this.$emit('loading', false);
			});
		}).catch((e) => {
			this.$emit('error', 'script', e);
		});
	},
	methods: {
		submit() {
			// https://docs.klarna.com/klarna-payments/api-call-descriptions/authorize-the-purchase/
			window.Klarna.Payments.authorize({
				payment_method_category: this.category
			}, (data) => {
				if (data.approved) {
					this.$emit('approve', {...this.payment, ...data});
					return;
				}

				console.error('Klara: Not approved', data);

				// E.g.: address data missing (soft fail)
				if (data.error) {
					return this.$emit('cancel');
				}

				// E.g.: Cancelled(?)
				if (!data.approved && data.show_form) {
					return this.$emit('cancel');
				}

				// E.g.: show_form: false
				this.$emit('error', data);
			});
		}
	}
}
</script>
