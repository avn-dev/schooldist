<template>
	<div>
		<button
			v-if="showPrev"
			type="button"
			:disabled="disabled"
			class="btn btn-primary"
			:class="{ 'float-left': align === 'justify' }"
			@click="prevPage"
		>
			<i :class="$icon('chevron-left')"></i>
			{{ $t('back') }}
		</button>
		<button
			v-if="showNext"
			type="button"
			:disabled="disabled"
			class="btn btn-primary"
			:class="{ 'disabled': disabledNext }"
			@click="nextPage"
		>
			{{ $t('next') }}
			<i :class="$icon('chevron-right')"></i>
		</button>
		<button
			v-if="showQuoteSubmit"
			type="button"
			:disabled="disabled"
			class="btn btn-info"
			:class="{ 'disabled': disabledNext }"
			@click="submit('quote')"
		>
			<i :class="$icon('bookmark')"></i>
			{{ $t('submit_quote') }}
		</button>
		<button
			v-if="showBookingSubmit"
			type="button"
			:disabled="disabled"
			class="btn btn-success"
			:class="{ 'disabled': disabledNext }"
			@click="submit('booking')"
		>
			<i v-if="$s('purpose') !== 'edit'" :class="$icon('shopping-cart')"></i>
			{{ $t('submit_booking') }}
		</button>
	</div>
</template>
<script>
	import { scrollToElement } from '@TcFrontend/common/utils/widget';
	import { submit } from '../../utils/helpers';
	import { focusFirstElementWithError, triggerValidators } from '../../utils/validation';
	import TranslationsMixin from '../mixins/TranslationsMixin';

	export default {
		mixins: [TranslationsMixin],
		props: {
			align: String
		},
		computed: {
			count() {
				return this.$store.getters.getPages.length;
			},
			currentPage() {
				return this.$store.state.form.state.page_current;
			},
			currentPageProps() {
				return this.$store.state.form.pages[this.$store.state.form.state.page_current];
			},
			showPrev() {
				return this.currentPage > 0 && ((!this.$s('debug') && this.currentPage !== this.count) || this.$s('debug'));
			},
			showNext() {
				return this.currentPage < this.count - 1;
			},
			showQuoteSubmit() {
				return this.currentPageProps.submit === 'enquiry';
			},
			showBookingSubmit() {
				// Only on last page and when no payment block is available
				return this.currentPageProps.submit === 'booking' && !this.$store.getters.checkDependencyVisibility('fields.payment')
			},
			disabled() {
				return this.$store.state.form.state.disable_form;
			},
			disabledNext() {
				return this.$store.state.form.state.disable_next;
			}
		},
		methods: {
			prevPage() {
				this.$store.commit('DISABLE_STATE', { key: 'next', status: false });
				this.$store.dispatch('prevPage').then(() => {
					scrollToElement(this.$root.$el.parentElement);
				});
			},
			nextPage() {
				this.triggerValidators();
				if (!this.$store.state.form.state.disable_next) {
					this.$store.dispatch('nextPage').then(() => {
						scrollToElement(this.$root.$el.parentElement);
					});
				}
			},
			submit(type) {
				this.triggerValidators();
				if (!this.$store.state.form.state.disable_next) {
					submit.call(this, type);
				}
			},
			triggerValidators() {
				const components = triggerValidators.call(this);
				if (components.length) {
					focusFirstElementWithError.call(this, components);
				}
			}
		}
	}
</script>
