import { field } from '../utils/api';
import { deepMapObject } from '../utils/helpers';
import { convertValues } from '../utils/store';

const Booking = {
	state: {
		type: null,
		services: {
			courses: [],
			accommodations: [],
			transfers: [],
			insurances: [],
			fees: [],
			activities: []
		},
		fields: {},
		selections: {}
	},
	getters: {
		$xv: () => {}, // Vuelidate validator, will be overridden (context)
		$xvVue: () => {}, // Vuelidate vue instance, will be overridden (context)
		getField: (state) => (field, namespace) => {
			if (!namespace) {
				namespace = 'fields';
			}
			return state[namespace][field];
		},
		// block: courses_123, accommodations_123 (block ID)
		getServices: (state) => (block) => {
			return state.services[block];
		},
		// type: courses, accommodations
		// DO NOT USE WITH INDEX BASED MUTATIONS/ACTIONS
		getAllServices: (state, getters) => (type) => {
			let services = [];
			getters.getServiceBlocks(type).forEach((key) => {
				services = services.concat(state.services[key]);
			});
			return services;
		},
		// type: courses, accommodations
		// DO NOT USE WITH INDEX BASED MUTATIONS/ACTIONS
		getValidServices: (state, getters, rootState) => (type) => {
			let services = [];
			getters.getServiceBlocks(type).forEach((key) => {
				const validator = getters.$xv.services[key].$each.$iter;
				for (let index in validator) {
					const serviceKey = rootState.form.fields.services[key]['id'];
					if (
						validator.hasOwnProperty(index) &&
						!validator[index].$invalid &&
						// Non required service would be still valid
						validator[index].$model[serviceKey]
					) {
						services.push(validator[index].$model);
					}
				}
			});
			return services;
		}
	},
	mutations: {
		INITIAL_BOOKING_DATA (state, payload) {
			state.services = payload.services;
			state.fields = payload.fields;
			state.selections = payload.selections;
		},
		SET_BOOKING_TYPE (state, type) {
			state.type = type;
		},
		INSERT_SERVICE (state, { type, service }) {
			state.services[type].push(service);
		},
		UPDATE_SERVICE_FIELD (state, { type, index, key, value }) {
			state.services[type][index][key] = value;
		},
		UPDATE_FIELD (state, { key, value }) {
			if (!state.fields.hasOwnProperty(key)) {
				console.error('Field does not exist initially! This could lead to reactivity issues.', key, value);
			}
			state.fields[key] = value;
		},
		UPDATE_SELECTION (state, { key, value }) {
			state.selections[key] = value;
		},
		REPLACE_SERVICE (state, { type, index, service }) {
			state.services[type].splice(index, 1, service);
		},
		// RESET_SERVICE (state, { type, index }) {
		// 	for (const key in state.services[type][index]) {
		// 		if (state.services[type][index].hasOwnProperty(key)) {
		// 			state.services[type][index][key] = null;
		// 		}
		// 	}
		// },
		DELETE_SERVICE (state, { type, index }) {
			state.services[type] = state.services[type].filter((service, key) => {
				return index !== key;
			});
		},
		DELETE_ALL_SERVICES (state, { type }) {
			state.services[type].splice(0);
		}
	},
	actions: {
		setInitialBookingData(context, payload) {
			payload = deepMapObject(payload, convertValues, null);
			context.commit('INITIAL_BOOKING_DATA', payload);
		},
		insertService(context, payload) {
			context.commit('INSERT_SERVICE', payload);
			field(['services', payload.type], context);
		},
		updateField(context, payload) {
			context.commit('UPDATE_FIELD', payload);
			return field(['fields', payload.key], context);
		},
		updateServiceField(context, payload) {
			context.commit('UPDATE_SERVICE_FIELD', payload);
			field(['services', payload.type, payload.key], context);
		},
		replaceService(context, payload) {
			context.commit('REPLACE_SERVICE', payload);
			field(['services', payload.type], context);
		},
		// resetService(context, payload) {
		// 	context.commit('RESET_SERVICE', payload);
		// 	field(['services', payload.type], context);
		// },
		deleteService(context, payload) {
			context.commit('DELETE_SERVICE', payload);
			field(['services', payload.type], context);
		},
		deleteAllServices(context, payload) {
			context.commit('DELETE_ALL_SERVICES', payload);
			field(['services', payload.type], context);
		},
		triggerField(context, payload) {
			field(payload.field, context);
		}
	}
};

export default Booking;
