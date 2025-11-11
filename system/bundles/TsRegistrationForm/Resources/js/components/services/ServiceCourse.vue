<template>
	<div>
		<button-remove
			v-if="view === 'container' && index !== 0 || field_state.holiday_split"
			:label="$t('remove')"
			@click="remove"
		/>

		<h4 v-if="view === 'container'">
			{{ title }}
		</h4>

		<!-- Course grouping -->
		<course-grouping
			v-if="hasCourseGrouping"
			:items="groupingOptions0"
			:selected="grouping[0]"
			:index="0"
			:type="groupingSelection"
			:label="$t(groupingType)"
			:disabled="isDisabled('course')"
			@change="changeGrouping(0, $event)"
		/>

		<!-- Course fields -->
		<div
			v-if="showCard"
			class="course-card"
			:class="['course-card', `course-card-${view}`, { 'card': view === 'container' }]"
		>
			<div :class="{ 'card-body': view === 'container' }">

				<!-- Course grouping (second level) -->
				<course-grouping
					v-if="hasCourseGrouping"
					:items="groupingOptions1"
					:selected="grouping[1]"
					:index="1"
					:disabled="isDisabled('course')"
					type="button"
					@change="changeGrouping(1, $event)"
				/>

				<!-- Course options as blocks -->
				<div v-if="view === 'container' && !showCourseSelect && !isHidden('course')" class="row">
					<div v-for="courseOption in courseOptions" :key="courseOption.key" class="col-md-6 course-options">
						<div class="custom-control custom-radio">
							<input
								:id="$id(`course_${index}_${courseOption.key}`)"
								:name="$id('course')"
								type="radio"
								class="custom-control-input"
								:checked="courseOption.key === course"
								:disabled="isDisabled('course')"
								@change="course = courseOption.key"
							/>
							<label
								:for="$id(`course_${index}_${courseOption.key}`)"
								class="custom-control-label"
							>
								{{ courseOption.label }}
							</label>
							<info-icon :content="courseOption.description"></info-icon>
						</div>
					</div>
					<div v-if="$vs.course.$error" class="col-md-12">
						<div class="invalid-feedback" style="display: block;">
							{{ $t('error_required') }}
						</div>
					</div>
				</div>

				<hr v-if="!showCourseSelect && showFields">

				<div v-if="view === 'container' && showCourseSelect && !isHidden('course')">
					<input-field
						v-model="course"
						type="select"
						name="course"
						:label="$t('title')"
						:disabled="isDisabled('course')"
						:errors="$t('error_required')"
						:is-invalid="$vs.course.$error"
						:options="courseOptions"
					></input-field>
				</div>

				<div v-if="showFields" class="row">

					<div v-show="showLanguageSelect && !isHidden('language')" class="col-md-12">
						<input-field
							v-model.number="language"
							type="select"
							name="language"
							:label="$t('language')"
							:disabled="isDisabled('language')"
							:errors="$t('error_required')"
							:is-invalid="$vs.language.$error"
							:options="languageOptions"
						></input-field>
					</div>

					<div v-show="showLevelSelect" :class="showLessonsInput ? 'col-md-6' : 'col-md-12'">
						<input-field
							v-model.number="level"
							type="select"
							name="level"
							:label="$t('level')"
							:disabled="isDisabled('level')"
							:errors="$t('error_required')"
							:is-invalid="$vs.level.$error"
							:options="levelOptions"
						></input-field>
					</div>

					<div v-if="showLessonsInput" :class="showLevelSelect ? 'col-md-6' : 'col-md-12'">
						<input-field
							v-if="!lessonOptions.length"
							v-model.number="units"
							type="input"
							name="units"
							inputmode="numeric"
							pattern="[0-9]*"
							:label="buildUnitsLabel()"
							:errors="!$vs.units.between ? $t('error_too_many_units') : $t('error_required')"
							:is-invalid="$vs.units.$error"
						/>
						<input-field
							v-else-if="lessonOptions.length > 1"
							v-model.number="units"
							type="select"
							name="units"
							:label="buildUnitsLabel()"
							:errors="!$vs.units.between ? $t('error_too_many_units') : $t('error_required')"
							:is-invalid="$vs.units.$error"
							:options="lessonOptions"
						/>
					</div>

					<div v-if="showStartInput" :class="showDurationInput ? 'col-md-6' : 'col-md-12'">
						<input-field
							v-model="start"
							type="datepicker"
							name="start"
							:label="$t('start')"
							:disabled="isDisabled('start')"
							:available-dates="datepickerDatesAvailableStart"
							:min-page="datepickerPageMin"
							:max-page="datepickerPageMax"
							:columns="$screens({ default: 1, xl: 2 })"
							:errors="$t('error_required')"
							:is-invalid="$vs.start.$error"
						></input-field>
					</div>

					<!-- v-show: weekOptions needs to be evaluated -->
					<div v-show="showDurationInput" class="col-md-6">
						<input-field
							v-model.number="duration"
							type="select"
							name="duration"
							:label="$t('duration')"
							:disabled="isDisabled('duration')"
							:errors="$t('error_required')"
							:is-invalid="$vs.duration.$error"
							:options="weekOptions"
						></input-field>
					</div>

					<div v-if="courseType === 'program'" class="col-md-12">
						<input-field
							v-model.number="program"
							type="select"
							name="program"
							:label="$t('program')"
							:disabled="isDisabled('program')"
							:errors="$t('error_required')"
							:is-invalid="$vs.program.$error"
							:options="programOptions"
						></input-field>
					</div>

				</div>

			</div>

			<div v-if="additionalServiceOptions.length" class="card-footer">
				<additional-services
					v-model="additional_services"
					:options="additionalServiceOptions"
					:block="block"
					:disabled="$vs.$invalid"
					:translations="translations"
				></additional-services>
			</div>

		</div>
	</div>
</template>

<script>
	import { checkAge, convertDateToDateString, createDatepickerPageMinMaxObject, isDate, parseDate } from '../../utils/date';
	import { checkCourseAge, pluralize } from '../../utils/helpers';
	import { updateServiceField } from '../../utils/store';
	import { checkDependencies } from '../../utils/validation';
	import InfoIcon from '../common/InfoIcon';
	import InputField from '../common/InputField';
	import ButtonRemove from '../common/ButtonRemove';
	import ServiceMixin from '../mixins/ServiceMixin';
	import AdditionalServices from './common/AdditionalServices';
	import CourseGrouping from './course/CourseGrouping';

	export default {
		name: 'ServiceCourse',
		components: {
			ButtonRemove,
			AdditionalServices,
			CourseGrouping,
			InputField,
			InfoIcon
		},
		mixins: [ServiceMixin],
		props: {
			selection: { type: String, default: 'select' }, // select / block
			groupingType: { type: String }, // category / language
			groupingSelection: { type: String }, // tab / button
			hideFields: { type: Boolean, default: false },
			checkDependencies: { type: Boolean, default: false },
		},
		data() {
			return {
				grouping: [],
				title: this.$t('title_per_course').replace('{number}', this.index + 1)
			};
		},
		computed: {
			// Course definition of current selected course
			currentCourse() {
				return this.$store.state.form.courses.filter(c => c.key === this.course).shift() || {};
			},
			// Available courses in general
			coursesAvailable() {
				const courses = this.$store.state.form.courses.filter(c => {
					return this.field_state.course || ( // disabled|hidden
						c.blocks.includes(this.block) && (
							!this.checkDependencies || // First block must not have dependencies
							c.dependencies.length === 0 ||
							checkDependencies.call(this, [`services.$any.courses:fn:hasAnyCourse:${c.dependencies.join(',')}`])
						) && this.checkAge(c)
					);
				});
				// Remove course which doesn't exist anymore
				if (this.course && !courses.map(c => c.key).includes(this.course)) {
					this.$log.info(`${this.$options.name}:${this.block}: Remove invalid selected course ${this.course}`, courses);
					this.course = null;
				}
				return courses;
			},
			// Courses depending on grouping/tab
			courseOptions() {
				if (!this.hasCourseGrouping) {
					return this.coursesAvailable;
				}

				return this.coursesAvailable.filter(c => {
					if (this.groupingType === 'category') {
						return this.grouping.toString() === c.categories.toString();
					}

					return c[pluralize(this.groupingType)].includes(this.grouping[0]);
				});
			},
			languageOptions() {
				return this.$store.state.form.course_groupings.filter(g => {
					return g.type === 'language' && this.currentCourse.languages?.includes(g.key);
				});
			},
			programOptions() {
				if (!this.course) {
					return [];
				}
				return this.currentCourse.programs;
			},
			// Level depending on course
			levelOptions() {
				if (!this.course || !this.showLevelSelect) {
					return [];
				}
				if (this.currentCourse.levels.length) {
					return this.$store.state.form.course_levels.filter(l => this.currentCourse.levels.includes(l.key))
				}
				return this.$store.state.form.course_levels;
			},
			// Start dates depending on course
			dates() {
				if (!this.course) {
					return [];
				}
				if (this.field_state.holiday_split) {
					return [{ min: this.duration, max: this.duration, levels: [], languages: [], start: convertDateToDateString(this.start) }];
				}
				const key = this.currentCourse.dates_key;
				if (!this.$store.state.form.course_dates.hasOwnProperty(key)) {
					this.$log.error(`Could not find course dates for ${this.course} (${key})`, this.courseOptions);
				}
				const age = this.$store.getters.getField('birthdate');
				let dates = this.$store.state.form.course_dates[key] || [];
				dates = dates.filter(d => {
					// Start dates depending on level (only if field is visible)
					if (
						(
							this.showLevelSelect &&
							d.levels.length &&
							!d.levels.includes(this.level)
						) || (
							d.languages.length &&
							!d.languages.includes(this.language)
						) || (
							// 2nd age check: On basis of actual start date
							isDate(age) &&
							!this.field_state.start &&
							!this.field_state.duration &&
							!checkAge(this.currentCourse.age, age, parseDate(d.start))
						)
					) {
						return false;
					}
					return true;
				});
				return dates;
			},
			// Weeks depending on course and start date
			weekOptions() {
				if (!this.course || !isDate(this.start)) {
					return [];
				}
				if (this.field_state.holiday_split) {
					return [{ key: this.duration, label: this.duration }];
				}
				let weeks = [];
				const date = convertDateToDateString(this.start);
				const dates =  this.dates.filter((d) => d.start === date); // TODO Migrate to Array.find()
				if (!dates.length) {
					this.$log.error('Week options: No starting dates found for ' + date, this.dates);
					return weeks;
				}
				dates.forEach(date => {
					for (let i = date.min; i < date.max + 1; i++) {
						if (!weeks.some(obj => obj.key === i)) {
							weeks.push({ key: i, label: i });
						}
					}
				});
				weeks.sort((a, b) => a.key - b.key);
				if (weeks.length === 1) {
					this.duration = weeks[0].key;
				}
				return weeks;
			},
			lessonOptions() {
				if (!this.course) {
					return [];
				}
				const lessons = this.currentCourse.lessons.map(v => ({ key: v, label: v }));
				if (lessons.length === 1) {
					this.units = lessons[0].key;
				}
				return lessons;
			},
			additionalServiceOptions() {
				if (!this.course) {
					return [];
				}
				return this.currentCourse.additional_services
					.map(k => this.$store.state.form.fees.find(f => f.key === k))
					.filter(f => f.blocks.includes(this.block));
			},
			datepickerDatesAvailableStart() {
				const dates = this.dates.map(d => {
					return parseDate(d.start);
				});
				if (!dates.length) {
					return [''];
				}
				return dates;
			},
			datepickerPageMin() {
				const date = this.dates[0]?.start;
				if (date) {
					return createDatepickerPageMinMaxObject(parseDate(date));
				}
				return {};
			},
			datepickerPageMax() {
				const date = this.dates[this.dates.length - 1]?.start;
				if (date) {
					return createDatepickerPageMinMaxObject(parseDate(date));
				}
				return {};
			},
			courseType() {
				return this.currentCourse.type;
			},
			hasCourseGrouping() {
				if (this.field_state.course) {
					return false;
				}
				return !!this.groupingType;
			},
			showCard() {
				return !this.hideFields || (!this.hasCourseGrouping || this.grouping.length);
			},
			showFields() {
				// return !this.hideFields || this.course !== null;
				// Never show fields with grouping as showing field triggers date check and therefore always deletes date/weeks with grouping change
				return !this.hasCourseGrouping || this.course !== null;
			},
			showCourseSelect() {
				return this.selection === 'select' || this.field_state.course;
			},
			showLanguageSelect() {
				return this.languageOptions.length > 1 && this.groupingType !== 'language';
			},
			showLevelSelect() {
				return this.currentCourse.show_level;
			},
			showStartInput() {
				return this.courseType !== 'program' &&
					!this.currentCourse.bookable_only_in_full;
			},
			showDurationInput() {
				return !this.course || this.currentCourse.show_duration;
			},
			showLessonsInput() {
				return this.view === 'container' && this.courseType === 'unit';
			},
			groupingOptions0() {
				return this.generateGroupingLayer(0);
			},
			groupingOptions1() {
				return this.generateGroupingLayer(1);
			},
		},
		watch: {
			course: {
				immediate: true,
				handler(value, valueBefore) {
					// // Must be done by watcher as value before is needed for this check
					// if (value === null && valueBefore) {
					// 	const courseBefore = this.$store.state.form.courses.filter(c => c.key === valueBefore).shift() || {};
					// 	if (!this.checkAge(courseBefore)) {
					// 		this.$store.dispatch('addNotification', { key: 'course_removed_age', type: 'warning', message: this.$t('service_removed_age') });
					// 	}
					// }

					// Language must always be set (for grouping set selected, otherwise set first and only language of course)
					if (!this.showLanguageSelect && !this.language) {
						const firstLanguage = this.currentCourse?.languages?.[0] ?? null;
						this.language = (this.groupingType === 'language' ? this.grouping[0] : firstLanguage) ?? null;
					}
					// Program must always be set
					if (value && this.courseType !== 'program') {
						this.program = this.programOptions[0].key;
					}
				}
			},
			'$store.state.booking.fields.birthdate'() {
				const courseBefore = this.$store.state.form.courses.filter(c => c.key === this.course).shift() || {};
				if (!this.checkAge(courseBefore)) {
					this.$store.dispatch('addNotification', { key: 'course_removed_age', type: 'warning', message: this.$t('service_removed_age') });
				}
			}
		},
		mounted() {
			if (this.hasCourseGrouping) {
				if (this.groupingType === 'language' && this.language) {
					this.grouping = [this.language];
				} else if (this.currentCourse.hasOwnProperty(pluralize(this.groupingType))) {
					// Set groupings of selected course (e.g. going back one page)
					const first = this.currentCourse[pluralize(this.groupingType)][0];
					this.grouping = Array.isArray(first) ? [...first] : [first];
				} else if (!this.hideFields && this.groupingOptions0.length) {
					// Select first category
					this.changeGrouping(0, this.groupingOptions0[0].key);
				}
			}
		},
		methods: {
			isDisabled(field) {
				// Split courses are always disabled
				if (this.$store.state.form.state.disable_form || this.field_state[field]) {
					return true;
				}
				switch (field) {
					case 'course':
						return false;
					case 'level':
						return this.$vs.course.$invalid;
					case 'start':
						// If any course start date is based on a level, start depends on level
						if (this.currentCourse.dates_level_dependency) {
							return this.$vs.level.$invalid;
						}
						if (this.currentCourse.dates_language_dependency) {
							return this.$vs.language.$invalid;
						}
						return this.$vs.course.$invalid;
					case 'duration':
						return this.$vs.start.$invalid;
				}
			},
			isHidden(field) {
				return this.field_state[field] === 'hidden';
			},
			changeGrouping(index, key) {
				// if (index === 0 && this.groupingType === 'language') {
				// 	this.language = key;
				// }
				if (this.grouping[index] === key) {
					return;
				}
				this.grouping.splice(index); // Delete selections of all sub groupings
				this.$set(this.grouping, index, key); // this.grouping[index] = key; is not reactive
				if (index === 0 && !this.hideFields && this.groupingOptions1.length) {
					// Select second grouping as the course fields would be still shown (with !hideFields) but no courses
					this.$set(this.grouping, 1, this.groupingOptions1[0].key);
				}
				let p;
				if (!this.hideFields) {
					// Delete selected course to keep selected date/week
					// Also see this.showFields
					p = updateServiceField.call(this, this.block, this.index, 'course', null);
				} else {
					// With hideFields delete all selected course data (user needs to input everything again)
					p = this.reset();
				}
				// As setting this.course is asynchronous, promise chaining is needed
				p.then(() => this.$vs.$reset());
			},
			generateGroupingLayer(index) {
				if (!this.hasCourseGrouping) {
					return [];
				}

				const courseGroupingLayerKeys = this.coursesAvailable.reduce((carry, course) => {
					if (this.groupingType === 'category') {
						return carry.concat([index === 0 || this.grouping[0] === course.categories[0] ? course.categories[index] : null]);
					}

					if (index === 0) {
						return carry.concat(course[pluralize(this.groupingType)]);
					}

					return [];
				}, []);

				return this.$store.state.form.course_groupings.filter(g => {
					return g.type === this.groupingType && courseGroupingLayerKeys.includes(g.key);
				});
			},
			checkAge(course) {
				if (this.field_state.course) {
					return true;
				}

				return checkCourseAge.call(this, course);
			},
			buildUnitsLabel() {
				let label = '';
				
				if (this.currentCourse.lessons_unit === 'absolute') {
					label = this.$t('units_total');
				} else {
					label = this.$t('units_per_week');
				}

				return label;
			}
		}
	}
</script>
