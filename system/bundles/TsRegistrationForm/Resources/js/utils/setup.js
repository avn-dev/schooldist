import Vue from 'vue2';
import { getUrlParams, parseCookies } from '@TcFrontend/common/utils/widget';
import { requireComponents as requirePaymentProviders } from '@TcFrontend/common/utils/payment';
import { setup as setupApi, booking } from './api';
import { prefill } from './store';
import setupStore from '../store';

const setup = (widget) => {
	// Setup Vuex store
	const store = setupStore(widget.data.fields);

	// Setup Vue instance helper functions
	Vue.use(VuePlugin, { widget, store });

	// Setup API
	setupApi(widget.getPath('api'), widget.data.settings, widget.data.fields);

	// Set widget data
	store.commit('INITIAL_DATA', widget.data);

	// Execute initial mutations/actions (e.x. setInitialBookingData, triggerField)
	widget.data.mutations.forEach(mutation => {
		store.commit(mutation.handler, mutation);
	});
	widget.data.actions.forEach(action => {
		store.dispatch(action.handler, action);
	});

	const params = getUrlParams();

	// Must happen BEFORE booking request
	setTrackingKey(store, widget.data.settings.tracking_key, params);

	if (
		!widget.data.settings.has_booking &&
		params['booking']
	) {
		booking(store, params['booking']);
	}

	if (params['prefill']) {
		try {
			const json = JSON.parse(window.atob(params['prefill']));
			prefill(store, json);
		} catch (e) {
			console.error('Parsing of prefill param failed.', e)
		}
	}

	autoloadBlockComponents();
	requirePaymentProviders(Vue);
}

const autoloadBlockComponents = () => {
	const requireComponent = require.context('../components/blocks', false, /\.(vue)$/);
	requireComponent.keys().forEach(fileName => {
		const componentName = fileName.split('/').pop().replace(/\.\w+$/, '');
		Vue.component(componentName, requireComponent(fileName).default);
	});
};

const setTrackingKey = (store, key, params) => {
	if (!key) {
		return;
	}

	// const params = getUrlParams();
	if (params[key]) {
		store.commit('UPDATE_FIELD', { key: 'tracking_key', value: params[key] });
		return;
	}

	const cookies = parseCookies();
	if (cookies[key]) {
		store.commit('UPDATE_FIELD', { key: 'tracking_key', value: cookies[key] });
	}
}

const VuePlugin = {
	install(Vue, options) {
		Vue.prototype.$s = function(key) {
			return options.store.getters.$s(key);
		};

		Vue.prototype.$path = function(path) {
			path = path.replace('path:', '');
			return options.widget.getPath(path);
		};

		Vue.prototype.$icon = function(icon) {
			return options.store.state.form.icons[icon];
		};
	}
}

export default setup;
