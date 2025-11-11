import Vue from 'vue2';
import Vuex from 'vuex';
import { validationMixin } from 'vuelidate';
import form from './form';
import booking from './booking';
import { createVuelidateObject } from '../utils/validation';
import { multiErrorExtractorMixin } from 'vuelidate-error-extractor'

Vue.use(Vuex);

let store;

const createStore = (formFields) => {
	if (store) {
		return store;
	}

	const debug = process.env.NODE_ENV !== 'production';

	// Hack because of circular dependency
	booking.getters.$xv = function () {
		return storeValidation.$v.booking;
	};
	booking.getters.$xvVue = function() {
		return storeValidation;
	};

	store = new Vuex.Store({
		strict: debug,
		modules: {
			form,
			booking
		},
	});

	const validationVuelidate = createVuelidateObject(formFields);

	// Vuelidate only works with a Vue instance
	// https://jsfiddle.net/42jntdku/1/
	const storeValidation = new Vue({
		mixins: [validationMixin, multiErrorExtractorMixin],
		store,
		propsData: {
			validator: {} // Needs to fake Vue component init to use validator prop later
		},
		computed: {
			booking() {
				return this.$store.state.booking
			}
		},
		validations: {
			booking: validationVuelidate
		}
	});
	// Hack pre-made validator as validator for vue-error-extractor to use it without components
	Vue.set(storeValidation, 'validator', storeValidation.$v.booking);

	return store;
};

export default createStore;