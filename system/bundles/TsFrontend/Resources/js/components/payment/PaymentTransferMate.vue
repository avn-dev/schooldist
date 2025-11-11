<script>
import { validate as validateMail } from 'email-validator';
import { validationMixin } from 'vuelidate';
import { required, requiredIf } from 'vuelidate/lib/validators';
import PaymentMixin from '@TcFrontend/common/components/payment/PaymentMixin';
import PaymentMethod from '@TcFrontend/common/components/PaymentMethod';
import PaymentPopup from '@TcFrontend/common/components/PaymentPopup';
import TransfermateInput from './transfermate/Input.vue';
import { scrollToElement } from '@TcFrontend/common/utils/widget';

const PAGE_PAYMENT = 0;
const PAGE_STUDENT = 1;
const PAGE_PAYER = 2;
const PAGE_COMPLETION = 3;
const PAYER_TYPE_STUDENT = 'student';

const FIELD_MAPPING = {
	payer_name: 'student_name',
	payer_chinese_name: 'student_chinese_name',
	payer_nationality: 'student_country',
	payer_address: 'student_address',
	payer_postal_code: 'student_postal_code',
	payer_city: 'student_city',
	payer_phone_number: 'student_phone_number',
	payer_email: 'student_email',
};

const VALIDATORS = {
	isTrue: (value) => value === true,
	isChina: (values) => values.country_pay_from === 'CN',
	isIndia: (values) => values.country_pay_from === 'IN',
	isStudentIdRequired: (values) => ['BR', 'CN'].includes(values.country_pay_from),
	isStateRequired(values) { return this.states.hasOwnProperty(values.country_pay_from); },
	isPayerIdRequired(values) { return this.idValidation.some(item => item[0] === values.country_pay_from) },
	checkDocumentId(value) {
		const validation = this.idValidation.find(item => item[0] === this.values.country_pay_from);
		if (!validation) {
			return true;
		}
		return (new RegExp(validation[2])).test(value);
	}
};

export default {
	components: {
		PaymentMethod,
		PaymentPopup,
		TransfermateInput
	},
	mixins: [PaymentMixin, validationMixin],
	provide() {
		return {
			translations: this.translations,
			'$v': this.$v
		};
	},
	data() {
		const pages = []
		pages[PAGE_PAYMENT] = this.translations.your_payment;
		pages[PAGE_STUDENT] = this.translations.student_details;
		pages[PAGE_PAYER] = this.translations.payer_details;
		pages[PAGE_COMPLETION] = this.translations.completion;

		return {
			page: 0,
			pages,
			methods: [],
			payment: {},
			idValidation: this.method.additional.config.id_validation,
			countries: this.method.additional.config.countries,
			states: this.method.additional.config.states,
			payerTypes: [
				{ key: PAYER_TYPE_STUDENT, label: this.translations.student },
				{ key: 'parent', label: this.translations.parent_of_student },
				{ key: 'relative', label: this.translations.relative_of_student },
				{ key: 'other', label: this.translations.other },
			],
			values: {
				method: {},
				...this.method.additional.values
			},
			PAGE_PAYMENT,
			PAGE_STUDENT,
			PAGE_PAYER,
			PAGE_COMPLETION,
			PAYER_TYPE_STUDENT,
			tosLabel: this.translations.agree_tos.replace(/{link}(.+){\/link}/, `<a href="${this.method.additional.config.tos_url}" target="_blank">$1</a>`)
		};
	},
	computed: {
		stateOptions() {
			if (this.callValidator('isStateRequired')) {
				const country = this.values.country_pay_from;
				return Object.entries(this.states[country]).map(([key, label]) => {
					return { key: `${country}-${key}`, label };
				})
			}
			return [];
		},
		idNumberLabel() {
			return this.idValidation.find(c => c[0] === this.values.country_pay_from)?.[1] ?? '';
		},
		payingFromCountry() {
			return this.countries.find(o => o.key === this.values.student_country);
		}
	},
	watch: {
		'values.country_pay_from'() {
			if (!this.$v.values.country_pay_from.$invalid) {
				this.requestMethods();
			}
		},
		'values.payer_type'() {
			this.syncPayerValues();
		}
	},
	mounted() {
		this.$emit('loading', false);
	},
	methods: {
		prevPage() {
			this.$emit('error', null);
			this.page--;
			if (this.page === PAGE_PAYER && this.values.payer_type === PAYER_TYPE_STUDENT) {
				// Payer details überspringen
				this.page--;
			}
			scrollToElement(this.$parent.$el);
		},
		nextPage() {
			this.$emit('error', null);
			this.$v.$touch();
			let field, el;
			if (
				(this.page === PAGE_PAYMENT && !(field = this.findFieldWithError(['country_pay_from', 'state', 'payer_document', 'tos', 'india_fee_exceed', 'method']))) ||
				(this.page === PAGE_STUDENT && !(field = this.findFieldWithError(['payer_type', ...Object.values(FIELD_MAPPING), 'student_country', 'student_dob']))) ||
				(this.page === PAGE_PAYER && !(field = this.findFieldWithError(['who_is_making_the_payment', ...Object.keys(FIELD_MAPPING)])))
			) {
				this.page++;
				if (this.page === PAGE_PAYER && this.values.payer_type === PAYER_TYPE_STUDENT) {
					// Payer details überspringen
					this.syncPayerValues(); // Erneut ausführen, damit CN Name oder SSN gesetzt werden
					this.page++;
				}

				this.$v.$reset();
				scrollToElement(this.$parent.$el);
			} else {
				if ((el = this.$el.querySelector(`[data-error-field=${field}]`))) {
					scrollToElement(el);
				}
			}

			if (this.page === PAGE_COMPLETION) {
				this.requestPayment();
			}
		},
		setPage(page) {
			if (!this.isPageDisabled(page)) {
				this.page = page;
			}
		},
		requestMethods() {
			this.$v.$reset();
			this.values.method = {};
			this.requestWrapper().then(data => {
				if (data?.methods) {
					return this.methods = data.methods;
				}
			});
		},
		requestPayment() {
			this.requestWrapper().then(data => {
				if (data) {
					this.payment = data;
				}
				if (this.payment.flow === 'redirect') {
					this.$nextTick(() => this.$refs.popup?.openPopup());
				}
			});
		},
		requestWrapper() {
			this.$emit('loading', true);
			this.$emit('update:method', { ...this.method, additional: this.values, page: this.page })
			return this.request().then(data => {
				this.$emit('loading', false);
				if (data.error) {
					// TransferMate-Error direkt ans Frontend
					this.$emit('error', 'payment', data, data.error);
					return;
				}
				return data;
			}).catch(resp => {
				this.$emit('error', 'payment', resp);
				this.$emit('loading', false);
			})
		},
		callValidator(validator) {
			// Gleicher Aufruf wie von Vuelidate bei required
			return VALIDATORS[validator].call(this, this.values);
		},
		findFieldWithError(fields) {
			return fields.find(f => this.$v.values[f].$invalid)
		},
		isPageDisabled(page) {
			return page > this.page || page === PAGE_PAYER && this.values.payer_type === PAYER_TYPE_STUDENT;
		},
		focus() {
			this.$refs.popup?.focus();
		},
		syncPayerValues() {
			// Textfelder vorbefüllen
			this.values.who_is_making_the_payment = null;
			this.values.student_document = null;
			if (this.values.payer_type === PAYER_TYPE_STUDENT) {
				this.values.who_is_making_the_payment = this.payerTypes.find(o => o.key === PAYER_TYPE_STUDENT)?.label;
				this.values.student_document = this.values.payer_document;
			}
			// Alle Werte von Payer auf Student setzen oder löschen
			for (const [payerField, studentField] of Object.entries(FIELD_MAPPING)) {
				if (this.values.payer_type === PAYER_TYPE_STUDENT) {
					this.values[payerField] = this.values[studentField];
				} else {
					this.values[payerField] = null;
				}
			}
		}
	},
	validations: {
		values: {
			method: { required },
			country_pay_from: { required },
			state: { required: requiredIf(VALIDATORS.isStateRequired) },
			india_fee_exceed: { required: requiredIf(VALIDATORS.isIndia) },
			payer_type: { required },
			tos: { required, isTrue: VALIDATORS.isTrue },
			who_is_making_the_payment: { required },
			student_document: { required: requiredIf(VALIDATORS.isStudentIdRequired), checkDocumentId: VALIDATORS.checkDocumentId },
			student_name: { required },
			student_chinese_name: { required: requiredIf(VALIDATORS.isChina) },
			student_address: { required },
			student_postal_code: { required },
			student_city: { required },
			student_country: { required },
			student_phone_number: { required },
			student_email: { required, validateMail },
			student_dob: { required },
			payer_document: { required: requiredIf(VALIDATORS.isPayerIdRequired), checkDocumentId: VALIDATORS.checkDocumentId },
			payer_name: { required },
			payer_chinese_name: { required: requiredIf(VALIDATORS.isChina) },
			payer_nationality: { required },
			payer_address: { required },
			payer_postal_code: { required },
			payer_city: { required },
			payer_phone_number: { required },
			payer_email: { required, validateMail }
		}
	}
}
</script>

<template>
	<div class="payment-transfermate">

		<div class="bs-stepper">
			<div class="bs-stepper-header" role="tablist">
				<template v-for="(label, index) in pages">
					<div
						class="step"
						:class="{ active: index === page }"
					>
						<button
							type="button"
							class="step-trigger"
							:class="{ disabled: isPageDisabled(index) }"
							role="tab"
							@click="setPage(index)"
						>
							<span class="bs-stepper-circle">{{ index + 1 }}</span>
							<span class="bs-stepper-label">{{ label }}</span>
						</button>
					</div>
					<div v-if="index + 1 < pages.length" class="bs-stepper-line"></div>
				</template>
			</div>
		</div>

		<div
			v-if="page === PAGE_PAYMENT"
			:key="page"
		>
			<transfermate-input
				v-model="values.country_pay_from"
				name="country_pay_from"
				:options="countries"
				type="select"
			>
				<template #form-text>
					<small
						v-if="payingFromCountry && payingFromCountry.key !== values.country_pay_from"
						class="form-text"
					>
						<a
							href="#"
							@click.prevent="values.country_pay_from = payingFromCountry.key"
						>
							{{ translations.paying_from.replace('{country}', payingFromCountry.label) }}
						</a>
					</small>
				</template>
			</transfermate-input>
			<transfermate-input
				v-if="callValidator('isStateRequired')"
				v-model="values.state"
				name="state"
				:options="stateOptions"
				type="select"
			/>
			<transfermate-input
				v-if="callValidator('isPayerIdRequired')"
				v-model="values.payer_document"
				name="payer_document"
				:label="`${translations.payer} ${idNumberLabel}`"
			/>
			<transfermate-input
				v-if="callValidator('isIndia')"
				v-model="values.india_fee_exceed"
				name="india_fee_exceed"
				type="select"
				:options="[{ key: '1', label: translations.yes }, { key: '0', label: translations.no }]"
			/>

			<div class="form-group custom-control custom-checkbox">
				<input
					v-model="values.tos"
					:id="$id('tos')"
					type="checkbox"
					class="custom-control-input"
					:class="{ 'is-invalid': $v.values.tos.$error }"
					data-error-field="tos"
				>
				<label
					:for="$id('tos')"
					class="custom-control-label"
					v-html="tosLabel"
				/>
			</div>

			<div
				v-if="methods.length"
				class="payment-methods row"
				data-error-field="method"
			>
				<label
					class="col-12"
					:class="{ 'is-invalid': $v.values.method.$error }"
				>
					{{ translations.select_payment_method }}
				</label>
				<div
					v-for="paymentMethod in methods"
					class="col-lg-4 col-md-6"
				>
					<payment-method
						:key="paymentMethod.key"
						:method="paymentMethod"
						:request="request"
						:active="!!(paymentMethod && paymentMethod.key === values.method.key)"
						:disabled="disabled || paymentMethod.locked"
						@change="values.method = paymentMethod"
					/>
				</div>
				<p class="col-12">{{ translations.additional_fees_and_taxes }}</p>
			</div>

		</div>

		<div
			v-if="page === PAGE_STUDENT"
			:key="page"
		>
			<transfermate-input
				v-model="values.payer_type"
				name="payer_type"
				type="select"
				:options="payerTypes"
			/>
			<transfermate-input
				v-if="callValidator('isStudentIdRequired')"
				v-model="values.student_document"
				name="student_document"
				:label="`${translations.student} ${idNumberLabel}`"
			/>
			<transfermate-input
				v-model="values.student_name"
				name="student_name"
				:label="translations.name"
			/>
			<transfermate-input
				v-if="callValidator('isChina')"
				v-model="values.student_chinese_name"
				name="student_chinese_name"
				label="Student Chinese Name"
			/>
			<transfermate-input
				v-model="values.student_address"
				name="student_address"
				:label="translations.address"
			/>
			<transfermate-input
				v-model="values.student_postal_code"
				name="student_postal_code"
				:label="translations.postal_code"
			/>
			<transfermate-input
				v-model="values.student_city"
				name="student_city"
				:label="translations.city"
			/>
			<transfermate-input
				v-model="values.student_country"
				name="student_country"
				:label="translations.country"
				:options="countries"
				type="select"
			/>
			<transfermate-input
				v-model="values.student_phone_number"
				name="student_phone_number"
				:label="translations.phone_number"
				type="tel"
			/>
			<transfermate-input
				v-model="values.student_email"
				name="student_email"
				:label="translations.email"
				type="email"
			/>
			<transfermate-input
				v-model="values.student_dob"
				name="student_dob"
				:label="translations.date_of_birth"
				type="date"
			/>
		</div>

		<div
			v-if="page === PAGE_PAYER"
			:key="page"
		>
			<transfermate-input
				v-model="values.payer_name"
				name="payer_name"
				:label="translations.name"
			/>
			<transfermate-input
				v-if="callValidator('isChina')"
				v-model="values.payer_chinese_name"
				name="payer_chinese_name"
				label="Payer Chinese Name"
			/>
			<transfermate-input
				v-model="values.who_is_making_the_payment"
				name="who_is_making_the_payment"
				:label="translations.relationship_to_student"
			/>
			<transfermate-input
				v-model="values.payer_nationality"
				name="payer_nationality"
				:label="translations.nationality"
				:options="countries"
				type="select"
			/>
			<transfermate-input
				v-model="values.payer_address"
				name="payer_address"
				:label="translations.address"
			/>
			<transfermate-input
				v-model="values.payer_postal_code"
				name="payer_postal_code"
				:label="translations.postal_code"
			/>
			<transfermate-input
				v-model="values.payer_city"
				name="payer_city"
				:label="translations.city"
			/>
			<transfermate-input
				v-model="values.payer_phone_number"
				name="payer_phone_number"
				:label="translations.phone_number"
				type="tel"
			/>
			<transfermate-input
				v-model="values.payer_email"
				name="payer_email"
				:label="translations.email"
				type="email"
			/>
		</div>

		<div
			v-if="page === PAGE_COMPLETION"
			:key="page"
		>
			<div v-if="payment.flow === 'direct'">
				<p>{{ translations.bank_transfer_description }}</p>
				<div v-html="payment.instructions"></div>
			</div>
			<div v-if="payment.flow === 'redirect'">
				<payment-popup
					ref="popup"
					:url="payment.redirect_url"
					:request="() => requestStatus(payment)"
					:description-backdrop="translations.backdrop_description"
					:description-focus="translations.focus_description"
					:description-redirect="translations.redirect_description"
					@approve="$emit('approve', payment)"
					@cancel="$emit('cancel', payment)"
					@loading="(state, text) => $emit('loading', state, text)"
				/>
			</div>
		</div>

		<div class="row form-group">
			<div
				v-if="page !== PAGE_PAYMENT"
				class="col-12 col-md-2"
			>
				<button
					class="btn btn-secondary btn-block"
					@click="prevPage"
				>
					<i class="fas fa-chevron-left" /> {{ translations.back }}
				</button>
			</div>
			<div
				class="col-12 col-md-10"
				:class="{ 'col-md-12': page === PAGE_PAYMENT }"
			>
				<button
					v-if="page !== PAGE_COMPLETION"
					class="btn btn-primary btn-block"
					@click="nextPage"
				>
					{{ translations.next }}: {{ pages[page + 1] }} <i class="fas fa-chevron-right" />
				</button>
				<button
					v-if="page === PAGE_COMPLETION && payment.flow === 'direct'"
					class="btn btn-success btn-block"
					@click="$emit('approve', payment)"
				>
					{{ translations.bank_transfer_button }}
				</button>
			</div>
		</div>

	</div>
</template>
