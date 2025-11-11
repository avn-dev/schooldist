
/**
 * Add or sub days to JS Date
 *
 * @param {Date} date
 * @param {Number} days
 * @returns {Date} New instance
 */
const addDateDays = (date, days) => {
	const d = new Date(date.getTime());
	d.setDate(d.getDate() + days);
	return d;
};

/**
 * Create page config object for v-calendar
 *
 * @param {Date} date
 * @returns {{}|{month: number, year: number}}
 */
const createDatepickerPageMinMaxObject = (date) => {
	if (!date) {
		return {};
	}
	// TODO Remove
	if (!isDate(date)) {
		console.error('createDatepickerPageMinMaxObject: Converted date');
		date = new Date(date);
	}
	return { year: date.getFullYear(), month: date.getMonth() + 1 };
};

/**
 * Check if date is in dates, basing on v-calendar available/disabled-dates logic
 *
 * @param {[Date|String]} dates
 * @param {Date} date Expects LOCAL date from v-calendar
 * @returns {*}
 */
const checkDatepickerRangeDate = (dates, date) => {
	return dates.some(d => {
		if (typeof d === 'string') {
			return d === convertDateToDateString(date);
		}
		// Must be local dates!
		if (isDate(d)) {
			return d.getTime() === date.getTime();
		}
		if (d.start && d.end) {
			return date >= d.start && date <= d.end;
		}
		console.error('Unknown v-calendar available/disabled-dates logic:', d);
		return true;
	})
};

/**
 * Inverse of parseDate: Convert local Date to UTC date, strip local timezone
 * @param date
 * @returns {*}
 */
const convertDate = (date) => {
	return new Date(date.getTime() - date.getTimezoneOffset() * 60000);
};

/**
 * Date() -> YYYY-MM-DD
 *
 * @param {Date} date
 * @returns {String}
 */
const convertDateToDateString = (date) => {
	const month = (date.getMonth() + 1).toString().padStart(2, '0');
	const day = date.getDate().toString().padStart(2, '0');
	return `${date.getFullYear()}-${month}-${day}`;
};

// /**
//  * Locale Date to UTC date
//  *
//  * @param {Date} date
//  * @returns {Date}
//  */
// const fixLocalDate = (date) => {
// 	return new Date(date.getTime() - (date.getTimezoneOffset() * 60000));
// };

/**
 * @param {*} date
 * @returns {Boolean}
 */
const isDate = (date) => {
	return (
		Object.prototype.toString.call(date) === '[object Date]' &&
		!isNaN(date.getTime())
	);
};

/**
 * Y-m-d to local Date with UTC (needed for v-calendar)
 *
 * new Date(0, 0, 0) => UTC date
 * new Date('0000-0-00') => Local date or error
 *
 * @param {String} dateString
 * @returns {Date}
 */
const parseDate = (dateString) => {
	const split = dateString.split('-');
	return new Date(parseInt(split[0]), parseInt(split[1]) - 1, parseInt(split[2]));
};

/**
 *
 * @param {Date} birthDate
 * @param {Date} atDate
 * @returns {null|Number}
 */
const calculateAge = function (birthDate, atDate) {
	if (!isDate(birthDate) || !isDate(atDate)) {
		return null;
	}
	var age = atDate.getFullYear() - birthDate.getFullYear();
	var m = atDate.getMonth() - birthDate.getMonth();
	if (m < 0 || (m === 0 && atDate.getDate() < birthDate.getDate())) {
		age--;
	}
	return age;
};

/**
 * https://stackoverflow.com/a/7091965
 * @param {{min: Number, max: Number}} ageDef
 * @param {Date} birthDate
 * @param {Date} atDate
 */
const checkAge = function (ageDef, birthDate, atDate) {
	const age = calculateAge(birthDate, atDate);
	if (age === null) {
		return true;
	}
	if (ageDef.min && age < ageDef.min) {
		return false;
	}
	if (ageDef.max && age > ageDef.max) {
		return false;
	}
	return true;
};

export {
	addDateDays,
	createDatepickerPageMinMaxObject,
	checkAge,
	checkDatepickerRangeDate,
	convertDate,
	convertDateToDateString,
	isDate,
	parseDate
};
