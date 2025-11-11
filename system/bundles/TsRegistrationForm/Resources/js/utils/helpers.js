import { scrollToElement } from '@TcFrontend/common/utils/widget';
import { checkAge, isDate, parseDate } from '@TsRegistrationForm/utils/date';

/**
 * https://stackoverflow.com/a/25334239
 *
 * @param {*} obj
 * @param {Function} fn
 * @param {*} ctx
 * @returns {{}|*}
 */
const deepMapObject = (obj, fn, ctx) => {
	const isObj = (val) => (val !== null && typeof val === 'object' && !(val instanceof Date));
	if (Array.isArray(obj)) {
		return obj.map((val, key) => {
			return isObj(val) ? deepMapObject(val, fn, ctx) : fn.call(ctx, val, key);
		});
	} else if (isObj(obj)) {
		const res = {};
		for (const key of Object.keys(obj)) {
			const val = obj[key];
			if (isObj(val)) {
				res[key] = deepMapObject(val, fn, ctx);
			} else {
				res[key] = fn.call(ctx, val, key);
			}
		}
		return res;
	} else {
		return obj;
	}
};

/**
 * Search field in fields definitions
 *
 * @param {[]} field
 * @param {{}} fields
 * @returns {*}
 */
const getFormField = (field, fields) => {
	let def = fields[field[0]][field[1]];
	if (field[2]) {
		// services
		def = def.fields[field[2]];
	}
	return def;
};

/**
 * Submit form
 * @param {String} type
 * @this Vue
 */
const submit = function (type) {
	this.$store.commit('SET_BOOKING_TYPE', type);
	return this.$store.dispatch('submit').then(() => {
		scrollToElement(this.$root.$el.parentElement);
	}).catch(() => {
		scrollToElement(this.$root.$el.parentElement);
	});
};

/**
 * Check course age by dates
 * @param course typeof this.$store.state.form.courses
 * @returns {Boolean}
 * @this Vue
 */
const checkCourseAge = function(course) {
	const dates = this.$store.state.form.course_dates[course.dates_key];
	const age = this.$store.getters.getField('birthdate');

	return !isDate(age) || !dates.length || (
		// 1st age check: First start date or last start date must be in period
		checkAge(course.age, age, parseDate(dates[0].start)) ||
		checkAge(course.age, age, parseDate(dates[dates.length - 1].start))
	);
}

/**
 * Very simple pluralize (activity => activities)
 * @param {String} str
 * @returns {String}
 */
const pluralize = (str) => {
	if (str.endsWith('y')) {
		return str.slice(0, -1) + 'ies';
	}
	return str + 's';
};

const VueHtmlEvalDirective = {
	bind (el, binding) {
		// Injected script tags will never be executed by any browser
		const regex = /<script\b[^>]*>([\s\S]*?)<\/script>/gm;
		let match;
		while (match = regex.exec(binding.value)) {
			eval(match[1]);
		}
		el.innerHTML = binding.value;
	}
}

export {
	checkCourseAge,
	deepMapObject,
	getFormField,
	submit,
	pluralize,
	VueHtmlEvalDirective
};
