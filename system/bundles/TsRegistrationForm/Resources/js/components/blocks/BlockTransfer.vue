<script>
import CardBlock from '../common/CardBlock';
import InputField from '../common/InputField';
import ServiceContainerMixin from '../mixins/ServiceContainerMixin';
import ServiceTransfer from '../services/ServiceTransfer';
import { updateServiceField } from '../../utils/store';
import { parseDate } from '@TsRegistrationForm/utils/date';
import { checkDependencies } from '../../utils/validation';

export default {
	components: {
		CardBlock,
		InputField,
		ServiceTransfer,
	},
	mixins: [ServiceContainerMixin],
	props: {
		icon: { type: String },
		typeOptions: { type: Array, required: true },
		extended: { type: Boolean, required: true },
		fieldsByType: { type: Boolean, required: true },
		fieldSettings: { type: Object, required: true }
	},
	computed: {
		arrivalIndex() {
			return this.findServiceIndexByType('arrival');
		},
		departureIndex() {
			return this.findServiceIndexByType('departure');
		},
		transfer_mode: {
			get() {
				for (const s of this.services) {
					if (s.mode !== null) {
						return s.mode;
					}
				}
				return null;
			},
			set(value) {
				this.services.forEach((s, i) => {
					updateServiceField.call(this, this.block, i, 'mode', value);
				});
			}
		},
		$vTransferMode() {
			if (this.$store.getters.$xv.services[this.block].$each[0]) {
				return this.$store.getters.$xv.services[this.block].$each[0].mode.$error;
			}
			return false;
		},
		// DependencyMixin überschreiben
		visible() {
			return checkDependencies.call(this, this.dependencies) && this.$store.state.form.transfer_locations.length;
		}
	},
	watch: {
		transfer_mode() {
			if (this.$store.getters.hasServicePeriod && this.extended) {
				if (this.arrivalIndex !== null) {
					updateServiceField.call(this, this.block, this.arrivalIndex, 'date', parseDate(this.$store.state.form.periods.course_and_accommodation[0]));
				}
				if (this.departureIndex !== null) {
					updateServiceField.call(this, this.block, this.departureIndex, 'date', parseDate(this.$store.state.form.periods.course_and_accommodation[1]));
				}
			}
		}
	},
	mounted() {
		this.checkServices();
	},
	beforeUpdate() {
		this.checkServices();
	},
	methods: {
		findServiceIndexByType(type) {
			const index = this.services.findIndex(s => s.type === type);
			return index > -1 ? index : null;
		},
		checkService(type) {
			let index = this.findServiceIndexByType(type);
			if (index === null) {
				this.addService(type, null, true);
			}
		},
		checkServices() {
			this.checkService('arrival');
			this.checkService('departure');
		},
		applyDepartureLocations(origin, destination) {
			if (this.departureIndex !== null) {
				updateServiceField.call(this, this.block, this.departureIndex, 'origin', origin);
				updateServiceField.call(this, this.block, this.departureIndex, 'destination', destination);
			}
		}
	}
}
</script>

<template>
	<div
		v-if="visible"
		:class="cssClass"
		class="card"
	>

		<div class="card-body">

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

			<h4 class="card-title">
				<i v-if="icon" :class="icon" aria-hidden="true"></i>
				{{ $t('title') }}
			</h4>

			<p
				v-if="$t('description')"
				class="card-text"
			>
				{{ $t('description') }}
			</p>

			<div v-if="fieldSettings.type.visible" class="row">
				<div class="col-md-6">
					<input-field
						v-model="transfer_mode"
						type="select"
						name="transfer_mode"
						:label="$t('type')"
						:errors="$t('error_required')"
						:is-invalid="$vTransferMode"
						:options="typeOptions"
					/>
				</div>
			</div>

			<div v-if="!fieldsByType || !!(transfer_mode & 1)">
				<h5 v-if="extended">
					{{ $t('arrival') }}
				</h5>
				<service-transfer
					v-if="arrivalIndex !== null"
					:block="block"
					:index="arrivalIndex"
					:translations="translations"
					:simple="!extended"
					:preferred="transfer_mode !== 2"
					@apply-departure-locations="applyDepartureLocations"
				/>
			</div>

			<div v-if="!fieldsByType || !!(transfer_mode & 2)">
				<h5 v-if="extended">
					{{ $t('departure') }}
				</h5>
				<service-transfer
					v-if="departureIndex !== null"
					:block="block"
					:index="departureIndex"
					:translations="translations"
					:simple="!extended"
					:preferred="transfer_mode === 2"
				/>
			</div>

		</div>
	</div>
</template>
