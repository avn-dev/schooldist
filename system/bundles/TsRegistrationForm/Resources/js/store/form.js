import { submit } from '../utils/api';
import { pushState } from '../utils/history';

const dynamicFields = [
	'prices',
	'accommodation_dates',
	'accommodation_dates_map',
	'notifications',
	'periods',
	'remote_validation'
];

/**
 * As object property changes can't be observed, Vue/Vuex sets getters/setters to all properties.
 * For the data tree reactivity this is not needed. If the entire value is changed (REPLACE_DATE),
 * reactivity will be triggered anyway.
 *
 * This is not a deep freeze but it's enough for Vue/Vuex to skip reactivity.
 *
 * @param {String} key
 * @param {*} value
 * @returns {*}
 */
const freeze = (key, value) => {
	if (
		!dynamicFields.includes(key) &&
		typeof value === 'object' // Needed for IE11 as ES5 threw an error for non-objects; ES2015 just returns value
	) {
		return Object.freeze(value);
	}
	return value;
};

const Form = {
	state: {
		// TODO Move into own store?
		state: {
			page_current: 0,
			loading_form: false,
			loading_prices: false,
			disable_form: false,
			disable_next: false,
			scroll_to_notifications: false
		},
		// INITIAL_DATA – WILL BE FROZEN TO PREVENT REACTIVIY GETTERS/SETTERS
		settings: {},
		fields: {},
		pages: [],
		icons: {},
		course_levels: [],
		courses: [],
		course_dates: {}, // key = md5
		course_groupings: [],
		accommodations: [],
		transfer_locations: [],
		insurances: [],
		fees: [],
		activities: [],
		// API Requests
		prices: [],
		accommodation_dates: {}, // key = md5
		accommodation_dates_map: {}, // key = accommodation_id, value = md5
		activity_dates: {}, // key = activity_id, value = []
		notifications: [],
		periods: {
			course: [],
			accommodation_default: [],
			course_and_accommodation: [],
		},
		remote_validation: {},
		confirm_message: null,
		// Not needed but silences INITIAL_DATA
		mutations: [],
		actions: [],
		dependency_visibility: new Map
	},
	getters: {
		$s: (state) => (key) => {
			return state.settings[key];
		},
		getPages: (state) => {
			return state.pages.filter(p => !p.hide);
		},
		getLoadingState: (state) => (key) => {
			return state.state['loading_' + key];
		},
		// type: courses => [courses_123, courses_321]
		getServiceBlocks: (state) => (type) => {
			let keys = [];
			for (const [key, value] of Object.entries(state.settings.blocks)) {
				if (value === type) {
					keys.push(key);
				}
			}
			return keys;
		},
		hasServicePeriod(state) {
			return !!state.periods.course_and_accommodation.length;
		},
		checkDependencyVisibility: (state) => (field) => {
			return state.dependency_visibility.get(field)
		},
	},
	mutations: {
		INITIAL_DATA (state, payload) {
			Object.keys(payload).forEach(key => {
				let value = payload[key];
				// if (key === 'booking') {
				// 	this.dispatch('setInitialBookingData', value);
				// 	return;
				// }
				if (!state.hasOwnProperty(key)) {
					console.error(`${key} does not exist in INITIAL_DATA`);
					return;
				}

				state[key] = freeze(key, value);
			});
		},
		REPLACE_DATA (state, { key, value }) {
			// Replace entire key of state object triggers reactivity anyway
			state[key] = freeze(key, value);
		},
		PREV_PAGE (state) {
			if (state.state.page_current > 0) {
				state.state.page_current--;
				state.notifications = [];
			}
		},
		NEXT_PAGE (state) {
			state.state.page_current++;
			state.notifications = [];
		},
		SET_STATE (state, { key, status }) {
			state.state[key] = status;
		},
		DISABLE_STATE (state, { key, status }) {
			state.state['disable_' + key] = status;
		},
		ADD_NOTIFICATION (state, notification) {
			this.commit('DELETE_NOTIFICATION', notification.key);
			state.notifications.push(notification);
		},
		DELETE_NOTIFICATION (state, key) {
			const index = state.notifications.findIndex(n => n.key === key);
			if (index >= 0) {
				state.notifications.splice(index, 1);
			}
		},
		ADD_SERVER_VALIDATION (state, { key, messages }) {
			// state.remote_validation[key] = messages is not reactive
			state.remote_validation = { ...state.remote_validation, [key]: messages }
		},
		DELETE_SERVER_VALIDATION (state, keys) {
			keys.forEach(k => {
				state.remote_validation[k] = [];
			});
		},
		DEPENDENCY_VISIBILITY (state, { field, visible }) {
			const original = state.dependency_visibility.get(field)
			if (original !== visible) {
				state.dependency_visibility.set(field, visible)
				// Trigger reactivity
				state.dependency_visibility = new Map(state.dependency_visibility)
			}
		}
	},
	actions: {
		prevPage(context) {
			context.commit('PREV_PAGE');
		},
		nextPage(context) {
			context.commit('NEXT_PAGE');
			pushState({ page: context.state.state.page_current });
		},
		addNotification(context, notification) {
			context.commit('ADD_NOTIFICATION', notification);
			if (!context.rootGetters.$xv.$anyError) {
				context.commit('SET_STATE', { key: 'scroll_to_notifications', status: true });
			}
		},
		async triggerVuelidate(context, payload) {
			context.rootGetters.$xv.$touch();
			if (payload.except && payload.except.length) {
				await context.dispatch('resetVuelidate', payload.except);
			}
			return new Promise((resolve, reject) => {
				context.rootGetters.$xvVue.activeErrors.length ? reject(context.rootGetters.$xv) : resolve(context.rootGetters.$xv);
			});
		},
		resetVuelidate(context, keys) {
			if (
				keys &&
				keys.length > 0
			) {
				// Currently only fields are supported
				keys.forEach(f => {
					let field;
					[, field] = f.split('.');
					context.rootGetters.$xv.fields[field].$reset();
				});
			} else {
				context.rootGetters.$xv.$reset();
			}
		},
		submit(context) {
			return submit(context);
		}
	}
};

export default Form;
