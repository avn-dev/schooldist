import Vue from 'vue2';
import vueCustomElement from 'vue-custom-element'
import Vuelidate from 'vuelidate'
import vuelidateErrorExtractor from 'vuelidate-error-extractor';
import VueLoadScript from 'vue-plugin-load-script';
import VueLogger from 'vuejs-logger';
import VueUniqueId from 'vue-unique-id';
import { checkCustomElements, checkElement, checkFidelo } from '@TcFrontend/common/utils/widget';
import setup from './utils/setup';
import RegistrationForm from './components/RegistrationForm.vue';

if (
	!checkFidelo() ||
	!checkElement('fidelo-widget') ||
	!checkCustomElements('fidelo-widget')
) {
	throw 'registration.js: Fatal Error';
}

Vue.use(VueLogger, { logLevel: 'debug', showLogLevel: true, showConsoleColors: true });
Vue.use(Vuelidate);
Vue.use(vuelidateErrorExtractor); // Provides required $vuelidateErrorExtractor()
Vue.use(VueLoadScript);
Vue.use(vueCustomElement);
Vue.use(VueUniqueId);

setup(window.__FIDELO__);

// Initialize as web component
Vue.customElement('fidelo-widget', RegistrationForm);
