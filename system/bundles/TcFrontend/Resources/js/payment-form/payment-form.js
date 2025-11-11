import axios from 'axios';
import Vue from 'vue2';
import VueCustomElement from 'vue-custom-element'
import VueLoadScript from 'vue-plugin-load-script';
import VueUniqueId from 'vue-unique-id';

import { checkCustomElements, checkElement, checkFidelo, parseCookies } from '../common/utils/widget';
import { requireComponents } from '../common/utils/payment';
import PaymentForm from './components/PaymentForm';


if (
	!checkFidelo() ||
	!checkElement('fidelo-widget') ||
	!checkCustomElements('fidelo-widget')
) {
	throw 'payment-form.js: Fatal Error';
}

const Fidelo = __FIDELO__;

Vue.prototype.$http = axios.create({
	baseURL: __FIDELO__.getPath('api'),
	headers: {
		'X-Requested-With': 'XMLHttpRequest',
		'X-Combination-Key': Fidelo.data.key,
		'X-Combination-Language': Fidelo.data.language
	}
});

if (process.env.NODE_ENV !== 'production') {
	Vue.prototype.$http.interceptors.request.use(config => {
		const cookies = parseCookies();
		if (cookies.hasOwnProperty('XDEBUG_SESSION')) {
			config.url += '?XDEBUG_SESSION_START=' + cookies.XDEBUG_SESSION;
		}
		return config;
	});
}

Vue.prototype.$t = (key) => {
	return Fidelo.data.translations[key];
};

Vue.use(VueCustomElement);
Vue.use(VueLoadScript);
Vue.use(VueUniqueId);

requireComponents(Vue);

Vue.customElement('fidelo-widget', PaymentForm);
