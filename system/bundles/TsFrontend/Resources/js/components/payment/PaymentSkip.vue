<script>
import PaymentMixin from '@TcFrontend/common/components/payment/PaymentMixin.vue';

export default {
	mixins: [PaymentMixin],
	mounted() {
		this.$emit('loading', false);
	},
	methods: {
		approve() {
			this.$emit('approve', { skip: true });
		}
	}
}
</script>

<template>
	<div>
		<p>
			{{ translations.description }}
		</p>
		<button
			type="button"
			class="btn btn-success btn-block"
			@click="approve"
		>
			<i :class="$icon('shopping-cart')"></i>
			{{ translations.book_now }}
		</button>
	</div>
</template>
