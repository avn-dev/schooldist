<template>
	<div class="row">
		<div class="col-md-6">
			<input-field
				v-model="start"
				type="datepicker"
				name="start"
				:label="$t('start')"
				:disabled="$vs.insurance.$error"
				:errors="$t('error_required')"
				:is-invalid="$vs.start.$error"
				:attributes="datepickerAttributes"
				:available-dates="dates.start"
				:columns="$screens({ default: 1, xl: 2 })"
				:min-page="dates.startMinPage"
				:max-page="dates.startMaxPage"
			></input-field>
		</div>
		<div v-if="currentInsurance.type === 'week'" class="col-md-6">
			<input-field
				v-model="duration"
				type="select"
				name="duration"
				:options="weeks"
				:label="$t('duration')"
				:disabled="$vs.duration.$error"
				:errors="$t('error_required')"
				:is-invalid="$vs.duration.$error"
			></input-field>
		</div>
		<div v-else class="col-md-6">
			<input-field
				v-model="end"
				type="datepicker"
				name="end"
				:label="$t('end')"
				:disabled="$vs.start.$error"
				:errors="$t('error_required')"
				:is-invalid="$vs.end.$error"
				:attributes="datepickerAttributes"
				:available-dates="dates.end"
				:columns="$screens({ default: 1, xl: 2 })"
				:min-page="dates.endMinPage"
				:max-page="dates.endMaxPage"
			></input-field>
		</div>
	</div>
</template>

<script>
	import { addDateDays, createDatepickerPageMinMaxObject, isDate, parseDate } from '../../utils/date';
	import InputField from '../common/InputField.vue';
	import ButtonRemove from '../common/ButtonRemove';
	import ServiceMixin from '../mixins/ServiceMixin';

	export default {
		components: {
			ButtonRemove,
			InputField
		},
		mixins: [ServiceMixin],
		computed: {
			currentInsurance() {
				return this.$store.state.form.insurances.filter(i => i.key === this.insurance).shift() || {};
			},
			// dates() {
			// 	const dates = { start: [''], end: [''], minPage: null, maxPage: null };
			// 	if(!this.$store.getters.hasServicePeriod) {
			// 		return dates;
			// 	}
			// 	const start = parseDate(this.$store.state.form.periods.course_and_accommodation[0]);
			// 	const end = parseDate(this.$store.state.form.periods.course_and_accommodation[1]);
			// 	dates.start = [{ start, end: isDate(this.end) ? addDateDays(this.end, -1): end }];
			// 	dates.end = [{ start: isDate(this.start) ? addDateDays(this.start, 1): start, end }];
			// 	dates.minPage = createDatepickerPageMinMaxObject(start);
			// 	dates.maxPage = createDatepickerPageMinMaxObject(end);
			// 	this.setDates(dates);
			// 	return dates;
			// },
			dates() {
				const dates = { start: [], end: [], startMinPage: null, startMaxPage: null, endMinPage: null, endMaxPage: null };
				if (!this.currentInsurance.type) {
					return dates;
				}

				const start = parseDate(this.currentInsurance.start);
				const end = addDateDays(start, this.currentInsurance.duration / 52 * 365);
				const relativStart = isDate(this.start) ? addDateDays(this.start, 1) : start;
				const relativeEnd = isDate(this.end) ? addDateDays(this.end, -1) : end;

				// if (this.currentInsurance.type === 'day') {
					dates.start = [{ start, end: relativeEnd }];
					dates.end = [{ start: relativStart, end }];
				// } else {
				// 	dates.start = [];
				// 	let iter = addDateDays(start, 0); // Clone start
				// 	for (let i = 0; i < 53; i ++) {
				// 		dates.start.push(iter);
				// 		iter = addDateDays(iter, 7);
				// 	}
				// }

				dates.startMinPage = createDatepickerPageMinMaxObject(start);
				dates.startMaxPage = createDatepickerPageMinMaxObject(relativeEnd);
				dates.endMinPage = createDatepickerPageMinMaxObject(relativStart);
				dates.endMaxPage = createDatepickerPageMinMaxObject(end);

				this.setDates(dates);
				return dates;
			},
			weeks() {
				const weeks = [];
				for (let i = 1; i < 53; i++) {
					weeks.push({ key: i, label: i });
				}
				return weeks;
			},
			datepickerAttributes() {
				return this.generateDatepickerAttributes('service_period');
			}
		},
		methods: {
			setDates(dates) {
				// Set defaul dates if none given
				// if (this.insurance && !this.start && !this.end && dates.start.length && dates.end.length) {
				// 	this.start = dates.start[0].start;
				// 	this.end = dates.end[0].end;
				// }
				if (!this.$store.getters.hasServicePeriod || this.start || this.end) {
					return;
				}
				const serviceStart = parseDate(this.$store.state.form.periods.course_and_accommodation[0]);
				const serviceEnd = parseDate(this.$store.state.form.periods.course_and_accommodation[1]);
				if (
					// Date availability must be checked as setting this.start to invalid value resets value, retriggers dates(), retriggers setDates […]
					(serviceStart.valueOf() < dates.start[0].start.valueOf() || serviceStart.valueOf() > dates.start[0].end.valueOf()) ||
					(serviceEnd.valueOf() < dates.end[0].start.valueOf() || serviceEnd.valueOf() > dates.end[0].end.valueOf())
				) {
					return;
				}
				if (this.currentInsurance.type === 'day') {
					this.start = serviceStart;
					this.end = serviceEnd;
				} else {
					// // Search next possible start date
					// const start = dates.start.find(start => {
					// 	const end = addDateDays(start, 7);
					// 	return serviceStart >= start && serviceStart < end;
					// });
					const start = serviceStart;
					if (start) {
						this.start = start;
						this.duration = Math.ceil(Math.round(serviceEnd - start) / 604800000);
					}
				}
			}
		}
	}
</script>
