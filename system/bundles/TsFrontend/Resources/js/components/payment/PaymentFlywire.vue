<script>
import PaymentMixin from '@TcFrontend/common/components/payment/PaymentMixin.vue';
import PaymentPopup from '@TcFrontend/common/components/PaymentPopup.vue';

export default {
	components: { PaymentPopup },
	mixins: [PaymentMixin],
	data() {
		return {
			payment: {}
		}
	},
	async mounted() {
		this.payment = await this.request();
		this.$emit('loading', false);
		this.$nextTick(() => this.$refs.popup?.openPopup());
	},
	methods: {
		focus() {
			this.$refs.popup?.focus();
		}
	}
}
</script>

<template>
	<div class="payment-flywire">
		<payment-popup
			v-if="payment.url"
			ref="popup"
			:url="payment.url"
			:request="() => requestStatus(payment)"
			:description-backdrop="translations.backdrop_description"
			:description-focus="translations.focus_description"
			:description-redirect="translations.redirect_description"
			:popup-width="600"
			:popup-height="800"
			:popup-autoclose="false"
			@approve="$emit('approve', payment)"
			@cancel="$emit('cancel', payment)"
			@loading="(state, text) => $emit('loading', state, text)"
		/>
	</div>
</template>
