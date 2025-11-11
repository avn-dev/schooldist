import axios from 'axios';
import AwesomeDebouncePromise from 'awesome-debounce-promise';
import { parseCookies } from '@TcFrontend/common/utils/widget';
import { deepMapObject, getFormField } from './helpers';
import { convertValues, normalizeValues } from './store';

// TODO #17229 Must be removed
let instance;
let formFields = {};
const debounceRequests = {};
const debounceCancelTokens = {};

const ACTION_SETTINGS = {
	booking: {
		debounce: 0,
		loadingState: 'form'
	},
	school_change: {
		debounce: 0,
		loadingState: 'form'
	},
	dates: {
		debounce: 500,
		showError: false
	},
	prices: {
		debounce: 1000,
		loadingState: 'prices'
	},
	submit: {
		loadingState: 'form'
	}
};

/**
 * @returns {AxiosInstance}
 */
const http = () => {
	return instance;
};

const setup = (baseURL, settings, formFields_) => {
	formFields = formFields_;
	instance = axios.create({
		baseURL: baseURL,
		headers: {
			'X-Requested-With': 'XMLHttpRequest',
			'X-Combination-Key': settings.key,
			'X-Combination-Language': settings.language
		},
		params: settings.api_query_params || {},
		timeout: 30000
	});
};

const buildPath = (path) => {
	const params = { ...http().defaults.params };

	// Not included in production build
	if (process.env.NODE_ENV !== 'production') {
		const cookies = parseCookies();
		if (cookies['XDEBUG_SESSION']) {
			params['XDEBUG_SESSION_START'] = cookies['XDEBUG_SESSION'];
		}
		if (cookies['XDEBUG_PROFILE']) {
			params['XDEBUG_PROFILE'] = cookies['XDEBUG_PROFILE'];
		}
	}

	const joinedParams = Object.keys(params).map(key => key + '=' + params[key]).join('&');
	return path + (joinedParams ? '?' : '') + joinedParams;
};

const createPayload = (context, additional) => {
	return deepMapObject({
		...(context.rootState || context.state).booking,
		...additional,
		//tz_offset: new Date().getTimezoneOffset()
	}, normalizeValues);
};

const handleLoadingState = (action, context, status) => {
	const state = (context.rootState || context.state);
	if (
		ACTION_SETTINGS[action] && ACTION_SETTINGS[action].loadingState &&
		state.form.state['loading_' + ACTION_SETTINGS[action].loadingState] !== status // Avoid Vuex debugger bloat
	) {
		context.commit('SET_STATE', {
			key: `loading_${ACTION_SETTINGS[action].loadingState}`,
			status
		});
	}
};

const debounce = (action, context, trigger) => {
	if (!debounceRequests.hasOwnProperty(action)) {
		debounceRequests[action] = AwesomeDebouncePromise(
			() => {
				// Cancel already running request
				if (debounceCancelTokens[action]) {
					debounceCancelTokens[action].cancel();
				}

				handleLoadingState(action, context, true);
				debounceCancelTokens[action] = axios.CancelToken.source();
				return http().post(buildPath(action), createPayload(context, { trigger }), { cancelToken: debounceCancelTokens[action].token });
			},
			ACTION_SETTINGS[action].debounce
		);
	}
	return debounceRequests[action]();
};

/**
 * AJAX callback: Apply Vuex mutations/actions to form state
 *
 * @param {Promise} promise
 * @param {string} action
 * @param context Vuex
 */
const appendResponseHandler = (promise, action, context) => {
	const notifcationKey = 'internal_error'; // `${action}_error`;

	const splitHandlerData = (data) => {
		const handler = data.handler;
		delete data.handler;
		if (data.pass) {
			// If special key "pass" is given, pass this argument solely
			return [handler, data.pass];
		}
		return [handler, deepMapObject(data, convertValues, null)];
	}

	const handler = (data) => {
		// If this request was successful remove possible internal error message
		const index = (context.rootState || context.state).form.notifications.findIndex(n => n.key === notifcationKey);
		if (index >= 0) {
			context.commit('DELETE_NOTIFICATION', notifcationKey)
		}
		if (data.hasOwnProperty('mutations')) {
			data['mutations'].forEach(mutation => {
				const handler = splitHandlerData(mutation);
				context.commit(handler[0], handler[1]);
			});
		}
		if (data.hasOwnProperty('actions')) {
			data['actions'].forEach(action => {
				const handler = splitHandlerData(action);
				context.dispatch(handler[0], handler[1]);
			});
		}
	};

	promise.then(resp => {
		handler(resp.data);
	}).catch(error => {
		if (
			typeof error.response?.data === 'object' && (
				error.response.data.hasOwnProperty('actions') ||
				error.response.data.hasOwnProperty('mutations')
			)
		) {
			handler(error.response.data);
		} else if (ACTION_SETTINGS?.[action]?.showError !== false) {
			// Classic PHP
			console.error('API request error', error, error.response);
			context.dispatch('addNotification', {
				key: notifcationKey,
				type: 'danger',
				message: (context.rootGetters || context.getters).$s('translation_internal_error')
			});
		}
	}).then(() => {
		handleLoadingState(action, context, false);
	});
};

/**
 * Form field change: Run AJAX requests defined for this field
 *
 * @param {string[]} field
 * @param context Vuex
 */
const field = (field, context) => {
	const def = getFormField(field, formFields);
	const actions = def?.actions || [];
	const promises = [];
	actions.forEach(action => {
		handleLoadingState(action, context, true);
		const promise = debounce(action, context, field.join('.'));
		appendResponseHandler(promise, action, context);
		promises.push(promise);
	});
	return Promise.all(promises);
};

/**
 * Replace all booking data
 *
 * @param context Vuex
 * @param {String} key
 */
const booking = (context, key) => {
	const promise = http().post(buildPath('booking'), createPayload(context, { key }), { timeout: 60000 });
	handleLoadingState('booking', context, true);
	appendResponseHandler(promise, 'booking', context);
};

/**
 * Submit request including form loading state
 *
 * TODO If a device looses the connection what to do?
 *
 * @param context Vuex
 * @returns {Promise<AxiosResponse<any>>}
 */
const submit = (context) => {
	const action = 'submit';
	const promise = http().post(buildPath(action), createPayload(context), { timeout: 600000 * 2 });
	handleLoadingState(action, context, true);
	appendResponseHandler(promise, action, context);
	return promise;
};

/**
 * File download request
 *
 * @param {Object} data
 * @returns {Promise<AxiosResponse<any>>}
 */
const file = (data) => {
	return http().post(buildPath('file'), data, { responseType: 'blob' });
};

/**
 * Upload request (async)
 *
 * @param {FormData} formData
 * @param {Function} onUploadProgress
 * @returns {Promise<AxiosResponse<any>>}
 */
const upload = (formData, onUploadProgress) => {
	return http().put(buildPath('upload'), formData, { onUploadProgress });
};

const payment = (context, additional) => {
	const promise = http().post(buildPath('payment'), createPayload(context, additional), { timeout: 60000 });
	appendResponseHandler(promise, 'payment', context);
	return promise;
};

export {
	http,
	setup,
	field,
	booking,
	submit,
	file,
	upload,
	payment
}
