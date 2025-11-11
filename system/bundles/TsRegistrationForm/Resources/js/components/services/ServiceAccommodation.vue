<template>
	<div>
		<div v-for="accommodation in accommodations" class="card accommodation-card" :class="{ 'active': isActive(accommodation) }">
			<img v-if="accommodation.img" class="" :src="$path(accommodation.img)" :alt="accommodation.label">
			<div class="card-body">
				<h4 class="card-title">
					{{ accommodation.label }}
					<button-remove v-if="isActive(accommodation)" :label="$t('remove')" @click="resetCombination"></button-remove>
				</h4>
				<p class="card-text">{{ accommodation.description }}</p>
				<hr>
				<div class="accommodation-combinations">
					<card-block
						v-for="combination in accommodation.combinations"
						:key="`${combination.key_room}_${combination.key_board}`"
						:value="isActive(accommodation, combination)"
						:service-key="accommodation.key"
						:block="block"
						:disabled="isDisabled(accommodation)"
						:title="combination.label_room"
						:description="combination.label_board"
						:translations="translations"
						view="radio"
						size="xs"
						@input="changeCombination(accommodation, combination)"
					>
					</card-block>
				</div>
			</div>
			<div v-if="isActive(accommodation)" class="card-footer">
				<div class="row accommodation-date">
					<input-field
						v-model="start"
						type="datepicker"
						name="start"
						:label="$t('start')"
						:disabled="isDisabled(accommodation)"
						:is-invalid="$vs.start.$error"
						:attributes="datepickerAttributes"
						:available-dates="dates.start"
						:columns="$screens({ default: 1, xl: 2 })"
						:min-page="dates.startMinPage"
						:max-page="dates.startMaxPage"
						class="col-md-6"
					></input-field>
					<input-field
						v-model="end"
						type="datepicker"
						name="end"
						:label="$t('end')"
						:disabled="isDisabled(accommodation)"
						:is-invalid="$vs.end.$error"
						:attributes="datepickerAttributes"
						:available-dates="dates.end"
						:columns="$screens({ default: 1, xl: 2 })"
						:min-page="dates.endMinPage"
						:max-page="dates.endMaxPage"
						class="col-md-6"
					></input-field>
				</div>
				<additional-services
					v-if="additionalServices.length"
					v-model="additional_services"
					:options="additionalServices"
					:block="block"
					:disabled="$vs.$invalid"
					:translations="translations"
				></additional-services>
			</div>
		</div>
	</div>
</template>

<script>
	// import VueSmoothReflow from 'vue-smooth-reflow';
	import { addDateDays, createDatepickerPageMinMaxObject, isDate, parseDate } from '../../utils/date';
	import ButtonRemove from '../common/ButtonRemove';
	import CardBlock from '../common/CardBlock';
	import InputField from '../common/InputField.vue';
	import AdditionalServices from './common/AdditionalServices';
	import ServiceMixin from '../mixins/ServiceMixin';

	export default {
		components: {
			ButtonRemove,
			CardBlock,
			InputField,
			AdditionalServices
		},
		mixins: [ServiceMixin],
		computed: {
			accommodations() {
				// Selected accommodations of course settings
				const courseIds = this.$store.getters.getAllServices('courses').map(c => c.course);
				const coursesAccommodations = this.$store.state.form.courses // Combinations per course
					.filter(c => courseIds.includes(c.key) && c.accommodations.length)
					.map(c => c.accommodations);

				// None selected course has defined accommodation combinations
				if (!coursesAccommodations.length) {
					return this.$store.state.form.accommodations;
				}

				// Check all acc combinations of all courses
				let accommodations = [];
				for (const accommodation of this.$store.state.form.accommodations) {
					// Least common factor for ALL selected courses
					const combinations = accommodation.combinations.filter(c => {
						const key = `${accommodation.key}_${c.key_room}_${c.key_board}`;
						return coursesAccommodations.every(ca => ca.includes(key));
					});
					// Include accommodation/category if any combination is available
					if (combinations.length) {
						const acc = { ...accommodation }; // Shallow copy/dereference from store
						acc.combinations = combinations;
						accommodations.push(acc);
					}
				}

				return accommodations;
			},
			dates() {
				const dates = { start: [], end: [], startMinPage: null, startMaxPage: null, endMinPage: null, endMaxPage: null };
				const datesKey = this.$store.state.form.accommodation_dates_map[this.accommodation] || null;
				const datesAcc = this.$store.state.form.accommodation_dates[datesKey] || [];

				if (!datesAcc.length) {
					// Lock all dates
					// https://github.com/nathanreyes/v-calendar/issues/571
					dates.start = [''];
					dates.end = [''];
					return dates;
				}

				const allDates = [];
				datesAcc.forEach(dateAcc => {
					const date = {
						start: parseDate(dateAcc.start),
						end: parseDate(dateAcc.end)
					};

					// Start date not after given end date and vice versa (entire range object)
					if (
						(dateAcc.type === 'start' && isDate(this.end) && this.end < date.start) ||
						(dateAcc.type === 'end' && isDate(this.start) && this.start > date.end)
					) {
						return;
					}

					// Given end date intersects start date range: Limit last available start date
					if (dateAcc.type === 'start' && isDate(this.end) && this.end >= date.start && this.end <= date.end) {
						date.end = addDateDays(this.end, -1);
					}

					// Given start date intersects end date range: Limit first available end date
					if (dateAcc.type === 'end' && isDate(this.start) && this.start >= date.start && this.start <= date.end) {
						date.start = addDateDays(this.start, 1);
					}

					allDates.push(date.start);
					allDates.push(date.end);
					dates[dateAcc.type].push(date); // dates[start] / dates[end]
				});

				// v-calendar is too stupid to disable months when no dates are available
				dates.startMinPage = createDatepickerPageMinMaxObject(allDates.reduce((a, b) => a < b ? a : b));
				dates.startMaxPage = createDatepickerPageMinMaxObject(allDates.reduce((a, b) => a > b ? a : b));
				dates.endMinPage = dates.startMinPage;
				dates.endMaxPage = dates.startMaxPage;

				if (isDate(this.start)) {
					dates.endMinPage = createDatepickerPageMinMaxObject(this.start);
				}

				if (isDate(this.end)) {
					dates.startMaxPage = createDatepickerPageMinMaxObject(this.end);
				}

				if (!dates.start.length || !dates.end.length) {
					dates.start = [''];
					dates.end = [''];
				}

				return dates;
			},
			datepickerAttributes() {
				return this.generateDatepickerAttributes('course');
			},
			currentAccommodation() {
				return this.accommodations.find(a => a.key === this.accommodation) || {};
			},
			currentCombination() {
				if (!this.currentAccommodation.combinations) {
					return {};
				}
				return this.currentAccommodation.combinations.find(c => c.key_room === this.roomtype && c.key_board === this.board) || {};
			},
			additionalServices() {
				if (!this.currentCombination.additional_services) {
					return [];
				}
				return this.currentCombination.additional_services.map(k => {
					return this.$store.state.form.fees.find(f => f.key === k);
				});
			}
		},
		mounted() {
			// const cards = this.$el.querySelectorAll('.accommodation-card');
			// cards.forEach(el => {
			// 	this.$smoothReflow({ el, property: 'transform' });
			// });
		},
		methods: {
			isActive(accommodation, combination) {
				const isAccommodation = this.accommodation === accommodation.key;
				if (!combination) {
					return isAccommodation;
				}
				return (
					isAccommodation &&
					this.roomtype === combination.key_room &&
					this.board === combination.key_board
				);
			},
			isDisabled(accommodation) {
				const datesKey = this.$store.state.form.accommodation_dates_map[accommodation.key] || null;
				const datesAcc = this.$store.state.form.accommodation_dates[datesKey] || [];
				return this.$store.state.form.state.disable_form || !datesAcc.length;
			},
			changeCombination(accommodation, combination) {
				this.accommodation = accommodation.key;
				this.roomtype = combination.key_room;
				this.board = combination.key_board;
				this.setDates();
			},
			resetCombination() {
				this.accommodation = null;
				this.roomtype = null;
				this.board = null;
				this.start = null;
				this.end = null;
			},
			setDates() {
				const dates = this.$store.state.form.periods.accommodation_default;
				if (!dates || !dates.length) {
					return;
				}
				const start = parseDate(dates[0]);
				const end = parseDate(dates[1]);

				// Check if given accommodation_default dates are available actually
				// Otherwise datepicker would remove these dates and validation would trigger error (bad UX)
				const includesStart = this.dates.start.find(d => start >= d.start && start <= d.end);
				const includesEnd = this.dates.end.find(d => end >= d.start && end <= d.end);
				if (!includesStart || !includesEnd) {
					this.$log.warn('Accommodation default dates are not available:', dates, this.dates, !!includesStart, !!includesEnd);
					return;
				}

				this.start = start;
				this.end = end;
			}
		}
	}
</script>
