<template>
	<div v-if="visible">

		<div v-show="$v.$error">
			<!-- ref="error" used in ServiceContainerMixin -->
			<div
				role="alert"
				class="alert alert-danger"
				ref="error"
			>
				{{ $t('error') }}
			</div>
		</div>

		<card-block
			v-for="service in blockServicesOptions"
			:key="service.key"
			:block="block"
			:service-key="service.key"
			:is-invalid="$v.$error"
			:title="buildServiceLabel(service)"
			:description="service.description"
			:icon="service.icon"
			:img="service.img"
			:value="isActive(service)"
			:translations="translations"
			view="card"
		>
			<template #content>
				<div>
					<card-block
						v-for="option in service.options"
						:key="option.key"
						:value="isActiveOption(service, option)"
						:service-key="option.key"
						:block="block"
						:translations="translations"
						size="xs"
						@input="updateOption(service, option, $event)"
					>
						<template #title>
							<span v-html="option.label.join('<br>')"></span>
						</template>
					</card-block>
				</div>
			</template>
		</card-block>

	</div>
</template>

<script>
import CardBlock from '../common/CardBlock';
import ServiceBlocksMixin from '../mixins/ServiceBlocksMixin';

export default {
	components: {
		CardBlock
	},
	mixins: [ServiceBlocksMixin],
	props: {
		optionsKey: { type: String } // options-key
	},
	computed: {
		blockServicesOptions() {
			return this.blockServices.map(service => {
				service = { ...service };
				service.options = this.$store.state.form[this.optionsKey][service.key];
				return service;
			});
		}
	},
	methods: {
		isActive(service) {
			return this.findServiceIndex(service.key) !== -1;
		},
		isActiveOption(service, option) {
			return this.findServiceIndex(service.key, option.key) !== -1;
		},
		updateOption(service, option, value) {
			if (value) {
				this.addService(service.key, { additional: option.key });
			} else {
				const index = this.findServiceIndex(service.key, option.key);
				if (index < 0) {
					this.$log.error('BlockServiceOptions::updateOption: No service found but unchecked', this.block, service, option);
					return;
				}
				this.$store.dispatch('deleteService', { type: this.block, index });
			}
		}
	}
}
</script>
