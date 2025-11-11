<template>
	<div v-if="visible" :class="[cssClass, { 'row': showTwoColumns }]">

		<div
			v-show="$v.$error"
			:class="{ 'col-md-12': showTwoColumns }"
		>
			<!-- ref="error" used in ServiceContainerMixin -->
			<div
				role="alert"
				class="alert alert-danger"
				ref="error"
			>
				{{ $t('error') }}
			</div>
		</div>

		<div
			v-show="showChooseOneHint"
			:class="{ 'col-md-12': showTwoColumns }"
		>
			<div
				role="alert"
				class="alert alert-info"
			>
				{{ $t('choose_one') }}
			</div>
		</div>

		<component
			v-if="filterComponent"
			:is="filterComponent"
			:services="blockServices"
			:selected="services"
			:translations="translations"
			@input="blockServicesFiltered = $event"
		/>

		<p
			v-if="filterComponent && !blockServicesFiltered.length"
			class="alert alert-info no-results"
		>
			{{ $t('no_results') }}
		</p>

		<div
			v-for="service in blockServicesFiltered"
			:key="service.key"
			:class="{ 'col-md-6': showTwoColumns }"
		>
			<card-block
				:block="block"
				:service-key="service.key"
				:view="view"
				:disabled="disabled"
				:is-invalid="$v.$error"
				:title="buildServiceLabel(service)"
				:description="service.description"
				:description-list="service.description_list"
				:description-html="service.description_html"
				:icon="service.icon"
				:img="service.img"
				:value="getBlockValue(service.key)"
				:translations="translations"
				:use-footer="useCardFooter"
				@input="update(service.key, $event)"
			>
				<component
					:key="findBlockServiceIndex(service.key)"
					:is="component"
					:block="block"
					:index="findBlockServiceIndex(service.key)"
					:translations="translations"
					view="block"
				></component>
			</card-block>
		</div>

	</div>
</template>

<script>
	import { updateField } from '../../utils/store';
	import CardBlock from '../common/CardBlock';
	import ServiceBlocksMixin from '../mixins/ServiceBlocksMixin';
	import ServiceCourse from '../services/ServiceCourse';
	import ServiceTransfer from '../services/ServiceTransfer';
	import ServiceInsurance from '../services/ServiceInsurance';
	import ServiceFee from '../services/ServiceFee';
	import ServiceActivity from '../services/ServiceActivity';
	import CourseFilter from '../services/course/CourseFilter.vue';

	export default {
		components: {
			CardBlock,
			CourseFilter,
			ServiceCourse,
			ServiceTransfer,
			ServiceInsurance,
			ServiceFee,
			ServiceActivity
		},
		mixins: [ServiceBlocksMixin],
		props: {
			view: { type: String, required: true },
			showTwoColumns: { type: Boolean, default: false }, // show-two-columns
			checkServices: { type: Boolean, default: true },
			useCardFooter: { type: Boolean, default: false },
			filterComponent: String
		},
		data() {
			return {
				blockServicesFiltered: []
			};
		},
		computed: {
			disabled() {
				if (!this.checkServices) return false;
				return (
					this.$store.state.form.state.disable_form ||
					this.$s('has_service_period_blocks') && (
						!this.$store.getters.hasServicePeriod || (
							!this.$store.getters.getValidServices('courses').length &&
							!this.$store.getters.getValidServices('accommodations').length
						)
					)
				);
			},
			required() {
				// required / requiredIf
				return Object.values(this.$v.$params).some(p => p?.type.includes('required'));
			},
			showChooseOneHint() {
				return this.checkServices && !this.$v.$error && this.$v.$invalid && this.required && this.blockServices.length > 1;
			}
		},
		watch: {
			// Watch change of DependencyMixin property (exists in parent mixin also!)
			visible(value) {
				if (this.view === 'selection' && value === false) {
					// Reset selection (yes/no) fields
					this.blockServices.forEach(s => updateField.call(this, `${this.block}_${s.key}`, null, 'selections'));
				}
			},
			blockServices: {
				immediate: true,
				handler(value) {
					this.blockServicesFiltered = value;
				}
			}
		},
		methods: {
			findBlockServiceIndex(key) {
				const field = this.getServiceKey();
				return this.services.findIndex(i => i[field] === key);
			},
			getBlockValue(key) {
				return this.findBlockServiceIndex(key) > -1;
			},
			update(key, value) {
				switch (this.view) {
					case 'checkbox':
						return this.updateCheckbox(key, value);
					case 'selection':
						return this.updateSelection(key, value);
					case 'radio':
						return this.updateRadio(key);
					default:
						this.$log.error('BlockServiceBlocks::update: No func for view', this.view);
				}
			},
			updateCheckbox(key, value) {
				if (value) {
					this.addService(key);
				} else {
					const index = this.findBlockServiceIndex(key);
					if (index < 0 && this.view !== 'selection') {
						this.$log.error('BlockServiceBlocks::update: No service found but unchecked', this.block, key);
						return;
					}
					this.$store.dispatch('deleteService', { type: this.block, index });
				}
			},
			updateSelection(key, value) {
				// Values of radios are true/false, so this works like checkbox
				this.updateCheckbox(key, value);
			},
			updateRadio(key) {
				// Selection between all services; delete all selected services
				this.clearServices().then(() => {
					this.addService(key);
				});
			}
		}
	}
</script>
