import { convertDate, isDate, parseDate } from './date';
import { deepMapObject } from './helpers';

/**
 * Update single field with state change and validation
 *
 * @param {string} field
 * @param {string} value
 * @param {string} [namespace]
 * @returns {Promise<any>}
 */
const updateField = function (field, value, namespace) {
	if (!namespace) {
		namespace = 'fields';
	}
	if (namespace === 'services') {
		throw new Error('updateField is not for services');
	}
	const currentValue = this.$store.getters.getField(field, namespace);
	if (!checkValueChange(value, currentValue)) {
		// Return empty promise to be able to chain then() calls
		return new Promise(resolve => resolve());
	}
	let promise;
	if (namespace === 'fields') {
		promise = this.$store.dispatch('updateField', { key: field, value });
	} else {
		promise = new Promise(resolve => resolve());
		this.$store.commit('UPDATE_SELECTION', { key: field, value })
	}
	promise.then(() => {
		// Trigger Vuelidate on field
		this.$store.commit('DELETE_SERVER_VALIDATION', [`${namespace}.${field}`]);
		this.$store.getters.$xv[namespace][field]?.$touch();
		this.$store.commit('DISABLE_STATE', { key: 'next', status: false });
	});
	return promise;
};

/**
 * Update service field with change and validation
 *
 * @param {string} type
 * @param {Number} index INT
 * @param {string} field
 * @param {*} value
 * @returns {Promise<any>}
 */
const updateServiceField = function (type, index, field, value) {
	const currentValue = this.$store.getters.getServices(type)[index][field];
	if (!checkValueChange(value, currentValue)) {
		// Return empty promise to be able to chain then() calls
		return new Promise(resolve => resolve());
	}
	return this.$store.dispatch('updateServiceField', {
		type: type,
		index: index,
		key: field,
		value: value
	}).then(() => {
		// Trigger Vuelidate on field
		const validationKeys = Object.keys(this.$store.state.form.fields.services[type].fields).map(f => `services.${type}.${index}.${f}`);
		this.$store.commit('DELETE_SERVER_VALIDATION', validationKeys);
		this.$store.getters.$xv.services[type].$each[index][field].$touch();
		this.$store.commit('DISABLE_STATE', { key: 'next', status: false });
	});
}

const checkValueChange = (val, curVal) => {
	if (val === undefined) {
		val = null;
	}
	if (
		val === curVal || (
			isDate(val) && isDate(curVal) &&
			val.getTime() === curVal.getTime()
		)
	) {
		return false;
	}
	return true;
};

/**
 * Create empty service object. Needs to be used with .call(this). No arrow function!
 * @param {String} block
 * @param {Object} [data]
 * @returns Object
 */
const createService = function (block, data) {
	// const fields = Object.keys(this.$store.state.form.fields.services[block].fields);
	// const pairs = fields.map((f) => [f, null]);
	// return Object.fromEntries(pairs);
	const service = {};
	for (let [key, field] of Object.entries(this.$store.state.form.fields.services[block].fields)) {
		service[key] = field.type === 'array' ? [] : (field.type === 'object' ? {} : null); // scalar
		if (data && data.hasOwnProperty(key)) {
			service[key] = data[key];
		}
	}
	return service;
};

/**
 * Dynamically create computed Vue properties with dependency to store values (within beforeCreate)
 *
 * @param {Object} vue
 */
const createServiceFieldProps = function (vue) {
	const block = vue.$options.propsData.block;
	const fields = vue.$store.state.form.fields.services[block].fields;
	Object.keys(fields).forEach((field) => {
		vue.$options.computed[field] = {
			get() {
				return vue.$store.getters.getServices(block)[vue.index][field];
			},
			set(value) {
				updateServiceField.call(vue, block, vue.index, field, value);
			}
		};
	});
};

const convertValues = (val) => {
	if (
		typeof val === 'string' &&
		val.startsWith('date:')
	) {
		return parseDate(val.replace('date:', ''));
	}

	return val;
};

const normalizeValues = (val) => {
	if (isDate(val)) {
		return convertDate(val);
	}
	return val;
}

async function prefill(store, data) {
	const context = { $store: store };
	data = deepMapObject(data, convertValues, null);
	if (data.fields.school) {
		await updateField.call(context, 'school', data.fields.school, 'fields');
	}
	for (const [field, value] of Object.entries(data.fields ?? {})) {
		updateField.call(context, field, value, 'fields');
	}
	for (const [type, services] of Object.entries(data.services ?? {})) {
		const block = Object.keys(store.getters.$s('blocks')).find(key => store.getters.$s('blocks')[key] === type);
		if (!block) {
			console.error('No block found for service', type, services);
			continue;
		}
		store.dispatch('deleteAllServices', { type: block });
		for (const s of services) {
			const service = createService.call(context, block, s);
			store.dispatch('insertService', { type: block, service });
		}
	}
}

export {
	prefill,
	convertValues,
	createService,
	createServiceFieldProps,
	normalizeValues,
	updateField,
	updateServiceField
};
