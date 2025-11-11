<script>
import InputField from '../common/InputField.vue';
import ServiceMixin from '../mixins/ServiceMixin';

export default {
	components: {
		InputField
	},
	mixins: [ServiceMixin],
	props: {
		simple: { type: Boolean, default: false },
		preferred: { type: Boolean, default: false }
	},
	computed: {
		locations() {
			return this.$store.state.form.transfer_locations;
		},
		originOptions() {
			if (!this.type) {
				return [];
			}
			const type = this.type === 'departure' ? 'destination' : 'origin';
			return this.mapAccommodationOption(this.locations.filter(t => t.type === type));
		},
		destinationOptions() {
			if (!this.origin) {
				return [];
			}
			const type = this.type === 'departure' ? 'origin' : 'destination';
			const location = this.locations.find(l => l.key === this.origin);
			if (!location?.locations) {
				return [];
			}
			return this.mapAccommodationOption(this.locations.filter(l => {
				return l.type === type && location.locations.includes(l.key);
			}));
		},
		datepickerAttributes() {
			return this.generateDatepickerAttributes('service_period');
		},
	},
	watch: {
		origin() {
			// Select first option if there's only one
			const options = this.destinationOptions.filter(l => !l.disabled);
			if (options.length === 1) {
				this.destination = options[0].key;
			}
			this.checkDepartureLocations();
		},
		destination() {
			this.checkDepartureLocations();
		}
	},
	methods: {
		// Disable accommodation option if there is no accommodation available
		mapAccommodationOption(locations) {
			if (this.$s('transfer_force_accommodation_option')) {
				return locations;
			}
			return locations.map(l => {
				if (l.key.includes('accommodation') && !this.$store.getters.getValidServices('accommodations').length) {
					l = {...l}; // Shallow copy to not change value in Vuex
					l.disabled = true;
				}
				return l;
			});
		},
		checkDepartureLocations() {
			if (this.type === 'arrival' && this.$s('purpose') !== 'edit') {
				this.$emit('apply-departure-locations', this.destination, this.origin);
			}
		},
		buildLabel(field) {
			let label = this.$t(field);
			const required = this.$vs[field].$params.requiredIf.prop.call(this, this.$vs.$model); // requiredIfTransfer
			if (required) {
				label += ' *';
			}
			return label;
		}
	}
}
</script>

<template>
	<div>
		<div class="row">
			<div v-if="!simple || preferred" class="col-md-6">
				<input-field
					v-model="origin"
					type="select"
					name="origin"
					:label="buildLabel('origin')"
					:errors="$t('error_required')"
					:is-invalid="$vs.origin.$error"
					:options="originOptions"
				/>
			</div>
			<div v-if="!simple || preferred" class="col-md-6">
				<input-field
					v-model="destination"
					type="select"
					name="destination"
					:label="buildLabel('destination')"
					:errors="$t('error_required')"
					:disabled="!origin"
					:is-invalid="$vs.destination.$error"
					:options="destinationOptions"
				/>
			</div>
		</div>
		<div
			v-if="!simple"
			class="row"
		>
			<div class="col-md-6">
				<!-- Aktuell bewusst in keiner Richtung limitiert -->
				<input-field
					v-model="date"
					type="datepicker"
					name="date"
					:label="buildLabel('date')"
					:errors="$t('error_required')"
					:is-invalid="$vs.date.$error"
					:attributes="datepickerAttributes"
				/>
			</div>
			<div class="col-md-6">
				<input-field
					v-model="time"
					type="time"
					name="time"
					:label="$t('time')"
					:errors="!$vs.time.time ? $s('translation_error_time') : $t('error_required')"
					:is-invalid="$vs.time.$error"
				/>
			</div>
			<div class="col-md-6">
				<input-field
					v-model="airline"
					type="input"
					name="airline"
					:label="$t('airline')"
					:errors="$t('error_required')"
					:is-invalid="$vs.airline.$error"
				/>
			</div>
			<div class="col-md-6">
				<input-field
					v-model="flight_number"
					type="input"
					name="flight_number"
					:label="$t('flight_number')"
					:errors="$t('error_required')"
					:is-invalid="$vs.flight_number.$error"
				/>
			</div>
			<div class="col-md-12">
				<input-field
					v-model="comment"
					type="textarea"
					name="comment"
					:label="$t('comment')"
					:errors="$t('error_required')"
					:is-invalid="$vs.comment.$error"
				/>
			</div>
		</div>
	</div>
</template>
