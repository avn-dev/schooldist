<template>
	<div class="card">
		<loading-overlay :active="isLoading" :icon="$icon('spinner')"></loading-overlay>
		<div class="card-body">
			<h4 class="card-title">{{ $t('summary') }}</h4>
			<ul class="list-group list-group-flush price-list price-list-no-lines">
				<li class="list-group-item price-list-item-total">
					<span>{{ $t('total') }}</span>
					<span>{{ prices.total }}</span>
				</li>
				<li v-if="prices.deposit" class="list-group-item price-list-item-deposit">
					<div>
						{{ $t('deposit') }}
						<ul class="list-unstyled price-description">
							<li>{{ $t('deposit_description') }}</li>
						</ul>
					</div>
					<span>{{ prices.deposit }}</span>
				</li>
				<li v-if="prices.deposit" class="list-group-item">
					<label :for="$id('pay_full_amount')">{{ $t('pay_full_amount') }}</label>
					<div class="custom-control custom-checkbox">
						<input v-model="payFullAmount" type="checkbox" class="custom-control-input" :id="$id('pay_full_amount')">
						<label class="custom-control-label" :for="$id('pay_full_amount')"></label>
					</div>
				</li>
			</ul>
			<div v-for="block in prices.blocks" :class="['price-block', block.type]">
				<h5>{{ block.title }}</h5>
				<div class="card">
					<div class="card-body">
						<ul :class="['list-group list-group-flush price-list', { 'price-list-no-lines': ['extras', 'fees'].includes(block.type) }]">
							<li v-for="(item, index) in block.items" :key="index" class="list-group-item">
								<div>
									{{ item.title }}
									<ul v-if="item.description.length" class="list-unstyled price-description">
										<li v-for="description in item.description">
											{{ description }}
										</li>
									</ul>
								</div>
								<span>{{ item.price }}</span>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
	import LoadingOverlay from '@TcFrontend/common/components/LoadingOverlay';
	import SmoothReflow from '../mixins/SmoothReflow';
	import TranslationsMixin from '../mixins/TranslationsMixin';
	import { updateField } from '@TsRegistrationForm/utils/store';

	// http://demo.tutorialzine.com/2018/02/freebie-2-beautiful-checkout-forms/#payment
	// https://mdbootstrap.com/docs/jquery/ecommerce/design-blocks/shopping-cart/
	export default {
		components: {
			LoadingOverlay
		},
		mixins: [SmoothReflow, TranslationsMixin],
		computed: {
			prices() {
				return this.$store.state.form.prices;
			},
			isLoading() {
				return this.$store.getters.getLoadingState('prices');
			},
			payFullAmount: {
				get() {
					return this.$store.getters.getField('payment_full')
				},
				set(value) {
					updateField.call(this, 'payment_full', value);
				}
			}
		}
	}
</script>
