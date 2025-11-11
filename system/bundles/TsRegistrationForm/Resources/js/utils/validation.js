// TODO Komplett so umstellen, dass immer eine Wrapper-Funktion durchlaufen wird, die nullable/required/dependencies ausführt (mit withParams für Verständlichkeit)
/**
 * @fileoverview Client-side validation with Vuelidate
 */
import { scrollToElement } from '@TcFrontend/common/utils/widget';
import { between, helpers as vuelidateHelpers, integer, required, requiredIf } from 'vuelidate/lib/validators';
import { pluralize } from './helpers';

const helpers = {
	findService(key, model) {
		return this.$store.state.form[pluralize(key)].filter(v => {
			return v.key === model[key];
		}).shift() || {};
	},
};

// Do NOT USE arrow functions! Vuelidate binds 'this' to Vue instance.
const validators = {
	// Results from server validation
	remote: function (field_, dependencies) {
		return function (value, model) {
			// Append index to services namespace by searching corresponding object
			// https://github.com/vuelidate/vuelidate/issues/393
			let field = field_
			const name = field.split('.', 3);
			if (name[0] === 'services') {
				const index = this.$store.getters.getServices(name[1]).findIndex(s => s === model);
				field = `${name[0]}.${name[1]}.${index > -1 ? index : 0}.${name[2]}`;
			}
			return (
				// Check for dependencies: If field is hidden after server validation, this field would still trigger error
				!checkDependencies.call(this, dependencies) ||
				!this.$store.state.form.remote_validation.hasOwnProperty(field) ||
				!this.$store.state.form.remote_validation[field].length
			);
		};
	},
	// Check for field dependency before required check
	requiredIfDependency: function(dependencies) {
		return function (value) {
			return checkDependencies.call(this, dependencies);
		}
	},
	// Required checkbox
	sameAsTrue: function (dependencies) {
		return function (value) {
			return !checkDependencies.call(this, dependencies) || value === true;
		};
	},
	/**
	 * Check if value is included in values
	 * @param {[]} values
	 * @param {[]} dependencies
	 * @returns {function(*=): *}
	 */
	in: function (values, dependencies) {
		return function (value) {
			return !checkDependencies.call(this, dependencies) || values.includes(value)
		};
	},
	time: function (value) {
		return /^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/.test(value);
	},
	/**
	 * Dependency check: Is there any given course chosen?
	 */
	hasAnyCourse: function(courseIds) {
		courseIds = courseIds.split(',').map(v => parseInt(v));
		return function (services) {
			if (!services) {
				return false;
			}
			return services.some(s => {
				return courseIds.includes(parseInt(s.course)); // "123:class_321" => 123
			});
		};
	},
	/**
	 * Dependency check: Is there any given accommodation combination chosen?
	 */
	hasAnyAccommodation: function(combinations) {
		combinations = combinations.split(',');
		return function (services) {
			if (!services) {
				return false;
			}
			return services.some(s => {
				return combinations.includes(`${s.accommodation}_${s.roomtype}_${s.board}`);
			});
		};
	},
	// // In rule for services structure, converting compare values to integer
	// inServicesInt: function(field, values) {
	// 	return function (services) {
	// 		if (!services) {
	// 			return false;
	// 		}
	// 		return services.some((service) => {
	// 			return values.includes(service[field]);
	// 		});
	// 	};
	// },
	requiredIfCourseLevel: function(courseModel) {
		const course = helpers.findService.call(this, 'course', courseModel);
		return course.show_level || false;
	},
	requiredIfCourseStart: function(courseModel) {
		const course = helpers.findService.call(this, 'course', courseModel);
		return course.type !== 'program' &&
			!course.bookable_only_in_full;
	},
	requiredIfCourseDuration: function(courseModel) {
		const course = helpers.findService.call(this, 'course', courseModel);
		return course.type !== 'exam' &&
			course.type !== 'program' &&
			!course.bookable_only_in_full;
	},
	requiredIfCourseUnit: function (courseModel) {
		const course = helpers.findService.call(this, 'course', courseModel);
		return course.type === 'unit' && !course.key.includes('class');
	},
	requiredIfAccommodation: function (accommodationModel) {
		if (accommodationModel.accommodation !== null) {
			return true;
		}
	},
	requiredIfTransfer: function (transferModel) {
		return (
			(transferModel.mode & 1 && transferModel.type === 'arrival') || // TRANSFER_MODE_ARRIVAL
			(transferModel.mode & 2 && transferModel.type === 'departure') // TRANSFER_MODE_DEPARTURE
		);
	},
	requiredIfTransferHasLocations: function () {
		return this.$store.state.form.transfer_locations.length;
	},
	requiredIfInsuranceIsWeekly: function(insuranceModel) {
		const insurance = helpers.findService.call(this, 'insurance', insuranceModel);
		return insurance.type === 'week';
	},
	requiredIfInsuranceIsDaily: function(insuranceModel) {
		const insurance = helpers.findService.call(this, 'insurance', insuranceModel);
		return insurance.type === 'day';
	},
	requiredIfActivityIsWeekly: function (activityModel) {
		const activity = helpers.findService.call(this, 'activity', activityModel);
		return activity.mode === 'availability' && activity.type === 'week';
	},
	requiredIfActivityIsBlock: function (activityModel)  {
		const activity = helpers.findService.call(this, 'activity', activityModel);
		return activity.mode === 'availability' && activity.type === 'block';
	}
};

/**
 * Check for field dependencies
 * 'this' needs to be a vue instance!
 *
 * @param {[]} dependencies
 * @returns {Boolean}
 */
const checkDependencies = function(dependencies) {
	if (!dependencies) {
		// TODO Would it be better to return false to skip dependencies?
		return true;
	}
	return dependencies.every((rule) => {
		let value;
		let [field, ...fn] = rule.split(':');
		field = field.split('.');
		fn = convertRule(fn.join(':'), {});
		if (field[0] === 'services') {
			if (field[1] === '$any') {
				value = this.$store.getters.getAllServices(field[2]);
			} else {
				value = this.$store.getters.getServices(field[1]);
			}
		} else {
			value = this.$store.getters.getField(field[1]);
		}
		return fn(value);
	});
};

/**
 * Convert map of client validation rules
 *
 * @param {{}} data
 * @returns {{}}
 */
const convertRules = function (data) {
	const newRules = {};
	for (let [key, rule] of Object.entries(data.validation)) {
		newRules[key] = convertRule(rule, data);
	}
	return newRules;
};

/**
 * Convert string rule
 * @param {String} rule
 * @param {{}} [fieldData]
 * @returns {(function(*=): boolean|*)|(function(*): boolean)|*}
 */
const convertRule = function (rule, fieldData) {

	if (!rule.startsWith('fn:')) {
		console.error('Unknown validator namespace: ' + rule);
		return;
	}

	let fn, params;
	[, fn, ...params] = rule.split(':');
	switch (fn) {
		case 'between':
			const func = between(parseInt(params[0]), parseInt(params[1]));
			return (value) => {
				return !vuelidateHelpers.req(value) || func(value);
			};
		case 'integer':
			return integer;
		// case 'minLength':
		// 	return minLength(parseInt(params[0]));
		case 'required':
			return required;
		case 'requiredIf':
			if (!validators[params[0]]) {
				console.error('Unknown validator closure: ' + params[0]);
			}
			return requiredIf(validators[params[0]]);
		case 'requiredIfDependency':
			return requiredIf(validators.requiredIfDependency(fieldData.dependencies));
		case 'remote':
			return validators.remote(params[0]);
		case 'sameAsTrue':
			return validators.sameAsTrue(fieldData.dependencies);
		case 'in':
			params[0] = params[0].split(',');
			return validators.in(params[0], fieldData.dependencies);
		case 'time':
			return (value) => {
				return !vuelidateHelpers.req(value) || validators.time(value);
			};
		// case 'inServicesInt':
		// 	params[1] = params[1].split(',').map((value) => parseInt(value));
		// 	return validators.inServicesInt(params[0], params[1]);
		case 'hasAnyCourse':
		case 'hasAnyAccommodation':
			return validators[fn].apply(null, params);
	}

	console.error('Unknown validator: ' + rule);

}

/**
 * Create object for Vuelidate validation
 * This must match with the tree in the booking store!
 *
 * @param {{}} formFields
 * @returns {{services: {}, fields: {}}}
 */
const createVuelidateObject = function (formFields) {

	const validation = {
		fields: {},
		services: {},
		selections: {}
	};

	for (let [type, serviceData] of Object.entries(formFields.services)) {
		const fields = {};
		for (let [field, fieldData] of Object.entries(serviceData.fields)) {
			fields[field] = convertRules(fieldData);
		}
		validation.services[type] = {
			...convertRules(serviceData),
			$each: fields
		};
	}

	['fields', 'selections'].forEach((namespace) => {
		for (let [field, fieldData] of Object.entries(formFields[namespace])) {
			fieldData.key = field;
			validation[namespace][field] = convertRules(fieldData);
		}
	});

	return validation;

};

/**
 * Get all CURRENTLY RENDERED Vue children instances having a validator ($v)
 *
 * @param {Vue} vue
 * @returns {Vue[]}
 */
const getChildComponentsWithValidator = (vue) => {
	let components = [];
	const recurse = (vue) => {
		vue.$children.forEach((child) => {
			if (typeof child.$v !== 'undefined') {
				components.push(child);
			}
			recurse(child);
		});
	};
	recurse(vue);
	return components;
};

function getChildComponentsWithError(vueComponentInstance) {
	return getChildComponentsWithValidator(vueComponentInstance.$root).filter(c => {
		return c.$v.$error;
	});
}

/**
 * Trigger Vuelidate of all CURRENTLY RENDERED Vue children
 * @returns {[]} All components with an error (Vuelidate.$error = true)
 * @this Vue
 */
const triggerValidators = function () {
	getChildComponentsWithValidator(this.$root).forEach(c => {
		if (typeof c.validate === 'function') {
			c.validate()
		} else {
			// TODO covered with FieldMixin::validate()?
			c.$v.$touch()
		}
	});
	const invalidComponents = getChildComponentsWithError(this.$root);

	if (invalidComponents.length) {
		this.$store.dispatch('addNotification', { key: 'validation_error', type: 'danger', message: this.$s('translation_error') }) // Do not change key
	}

	this.$store.commit('DISABLE_STATE', { key: 'next', status: invalidComponents.length !== 0 });
	return invalidComponents;
};

/**
 * Find first HTMLElement in a collection of Vue components which is eligible to focus an error
 * @param {Vue[]} invalidComponents
 * @returns {null|HTMLElement}
 */
const findFirstElementWithError = function (invalidComponents) {
	const errors = this.$store.getters.$xvVue.activeErrors;
	let element = null;

	for (let c of invalidComponents) {
		for (const error of errors) {
			if (typeof c.$vFindElement !== 'function') {
				this.$log.error('Component with validator does not have $vFindElement', c);
				continue;
			}
			element = c.$vFindElement(error);
			if (element) {
				this.$log.info('Found first element with error to focus', error, element);
				return element;
			}
		}
	}

	return element;
}

/**
 * @param {Vue[]} invalidComponents
 * @this Vue
 */
const focusFirstElementWithError = function (invalidComponents) {
	if (!invalidComponents.length) {
		return;
	}

	const el = findFirstElementWithError.call(this, invalidComponents);
	this.$log.info('Validation errors', this.$store.getters.$xvVue.activeErrors, el);

	if (!el) {
		this.$log.error('Could not find any component to focus error', invalidComponents);
		return;
	}

	// In the current tick the corresponding element could be still hidden and has no offset; y=0
	this.$nextTick(() => {
		// Scroll to element
		scrollToElement(el);

		// Focus element
		// Search first element in container of given element (usually label)
		const elInput = el.parentElement.querySelector('input, select, textarea');
		if (elInput && elInput.focus) {
			elInput.focus({ preventScroll: true });
		}
	});
};

// /**
//  * $xvVue.activeErrors: vuelidate-error-extractor
//  * 'this' needs to be a vue instance!
//  */
// const triggerVueInstanceValidators = function () {
// 	const invalidComponents = getChildComponentsWithValidator(this.$root).filter(c => {
// 		c.$v.$touch();
// 		return c.$v.$error;
// 	});
//
// 	let firstErrorElement = null;
// 	const errors = this.$store.getters.$xvVue.activeErrors;
//
// 	this.$log.info('Validation errors', errors);
//
// 	firstErrorElement:
// 		for (let c of invalidComponents) {
// 			for (const error of errors) {
// 				if (typeof c.$vFindElement !== 'function') {
// 					this.$log.error('Component with validator does not have $vFindElement', c);
// 					continue;
// 				}
// 				firstErrorElement = c.$vFindElement(error);
// 				if (firstErrorElement) {
// 					this.$log.info('Found first element with error to focus', error, firstErrorElement);
// 					break firstErrorElement;
// 				}
// 			}
// 		}
//
// 	if (invalidComponents.length) {
// 		if (firstErrorElement) {
// 			this.$scrollTo(firstErrorElement, {
// 				onDone: (el) => {
// 					// Search first element in container of given element (usually label)
// 					const elInput = el.parentElement.querySelector('input, select, textarea');
// 					if (elInput && elInput.focus) {
// 						elInput.focus({ preventScroll: true });
// 					}
// 				}
// 			});
// 		} else {
// 			this.$log.error('Could not find any component to focus error', invalidComponents, errors);
// 		}
//
// 		this.$store.dispatch('addNotification', { key: 'validation_error', type: 'danger', message: this.$s('translation_error') }) // Do not change key
// 	}
//
// 	this.$store.commit('DISABLE_STATE', { key: 'next', status: invalidComponents.length !== 0 });
// };

export {
	checkDependencies,
	createVuelidateObject,
	convertRule,
	focusFirstElementWithError,
	getChildComponentsWithError,
	triggerValidators
};
