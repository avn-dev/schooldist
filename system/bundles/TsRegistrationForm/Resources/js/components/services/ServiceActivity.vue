<template>
	<div class="row">
		<div v-if="currentActivity.type === 'week'" class="col-md-6">
			<input-field
				v-model="start"
				type="datepicker"
				name="start"
				:label="$t('start')"
				:disabled="$vs.activity.$error"
				:errors="$t('error_required')"
				:is-invalid="$vs.start.$error"
				:attributes="datepickerAttributes"
				:available-dates="dates.start"
				:columns="$screens({ default: 1, xl: 2 })"
				:min-page="dates.minPage"
				:max-page="dates.maxPage"
			></input-field>
		</div>
		<div v-if="currentActivity.type === 'week'" class="col-md-6">
			<input-field
				v-model="duration"
				type="select"
				name="duration"
				:options="weeks"
				:label="$t('duration')"
				:disabled="$vs.start.$error"
				:errors="$t('error_required')"
				:is-invalid="$vs.duration.$error"
			></input-field>
		</div>
		<div v-if="currentActivity.type === 'block'" class="col-md-6">
			<input-field
				v-model.number="units"
				type="input"
				name="units"
				inputmode="numeric"
				pattern="[0-9]*"
				:label="$t('units')"
				:errors="!$vs.units.between ? $t('error_too_many_units') : $t('error_required')"
				:is-invalid="$vs.units.$error"
			></input-field>
		</div>
	</div>

</template>

<script>
import InputField from '../common/InputField.vue';
import ServiceMixin from '../mixins/ServiceMixin';
import { addDateDays, createDatepickerPageMinMaxObject, convertDateToDateString, parseDate } from '../../utils/date';

export default {
	components: {
		InputField
	},
	mixins: [ServiceMixin],
	computed: {
		currentActivity() {
			return this.$store.state.form.activities.filter(a => a.key === this.activity).shift() || {};
		},
		datesActivity() {
			if (!this.activity || !this.$store.state.form.activity_dates[this.activity]?.length) {
				return [];
			}
			return this.$store.state.form.activity_dates[this.activity];
		},
		dates() {
			const dates = { start: [''], minPage: null, maxPage: null };
			if (!this.datesActivity.length) {
				return dates;
			}
			let max = 0;
			dates.start = this.datesActivity.map(d => {
				max = Math.max(max, d.max);
				return parseDate(d.start);
			});
			dates.minPage = createDatepickerPageMinMaxObject(dates.start[0]);
			dates.maxPage = createDatepickerPageMinMaxObject(addDateDays(dates.start[0], 7 * max));
			// const courseDates = this.$store.state.form.periods.course;
			// const dates = { start: [], minPage: null, maxPage: null };
			// if (!courseDates.length) {
			// 	return dates;
			// }
			// let start = parseDate(courseDates[0]);
			// const end = parseDate(courseDates[1]);
			// for (; start < end; start = addDateDays(start, 7)) {
			// 	dates.start.push(start);
			// }
			this.setDates(dates);
			return dates;
		},
		weeks() {
			const weeks = [];
			if (this.datesActivity.length && this.start) {
				const start = convertDateToDateString(this.start);
				const date = this.datesActivity.find(d => d.start === start);
				if (!date) {
					this.$log.error('Activity options: No starting dates found for ' + start, this.dates);
					return weeks;
				}
				for (let i = date.min; i < date.max + 1; i++) {
					if (!weeks.some(obj => obj.key === i)) {
						weeks.push({ key: i, label: i });
					}
				}
				// const end = parseDate(this.$store.state.form.periods.course[1]);
				// let date = addDateDays(this.start, 0);
				// for (let week = 1; date < end; date = addDateDays(date, 7), week++) {
				// 	weeks.push({ key: week, label: week });
				// }
			}
			return weeks;
		},
		datepickerAttributes() {
			return this.generateDatepickerAttributes('course');
		},
	},
	methods: {
		setDates(dates) {
			if (!this.start && dates.start.length) {
				this.start = dates.start[0];
			}
		}
	}
}
</script>
