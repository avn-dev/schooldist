<template>
	<div class="service-additional-services">
		<h5>{{ $t('additional_services') }}</h5>
		<card-block
			v-for="(service, index) in options"
			:key="service.key"
			:value="get(service)"
			:block="block"
			:service-key="service.key"
			:disabled="disabled"
			:title="service.label"
			:description="service.description"
			:icon="service.icon"
			:translations="translations"
			view="checkbox"
			size="sm"
			@input="update(service, $event)"
		>
			<!--<service-fee
				:block="block"
				:index="index"
				:translations="translations"
			></service-fee>-->
		</card-block>
	</div>
</template>

<script>
import CardBlock from '../../common/CardBlock';
import TranslationsMixin from '../../mixins/TranslationsMixin';

export default {
	components: {
		CardBlock
	},
	mixins: [TranslationsMixin],
	props: {
		value: { type: Array, required: true },
		options: { type: Array, required: true },
		block: { type: String, required: true },
		disabled: { type: Boolean }
	},
	methods: {
		get(service) {
			return this.value.some(s => s.fee === service.key);
		},
		update(service, value) {
			if (value) {
				// Needs to be fully replaced to not trigger direct Vuex mutation
				// this.additional_services.push({ fee: service.key });
				this.$emit('input', [...this.value, { fee: service.key }]);
			} else {
				this.$emit('input', this.value.filter(s => s.fee !== service.key));
			}
		}
	}
}
</script>