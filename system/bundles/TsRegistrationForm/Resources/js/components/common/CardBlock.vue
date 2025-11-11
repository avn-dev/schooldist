<template>
	<div :class="['card card-block', `card-block-${size}`, { 'active': active, 'disabled': disabled }]">
		<img
			v-if="img"
			:src="$path(img)"
			class="card-img card-img-top"
			:alt="title"
		>

		<div class="row no-gutters">
			<div
				v-if="leftColumn"
				:class="['card-control', classCardControl]"
			>
				<div :class="['custom-control', 'custom-' + view]">
					<!-- Use checked/@change instead of v-model as v-model=false on radio does not reset checked -->
					<!-- Do NOT use @input as this does not work in IE11 for radio/checkbox: https://caniuse.com/input-event -->
					<input
						:id="id"
						:checked="data"
						:type="view"
						:name="view === 'radio' ? block : id"
						:disabled="!active && disabled"
						:class="['custom-control-input', { 'is-invalid': isInvalid && view === 'selection' }]"
						@change="data = $event.target.checked"
					>
					<label
						:for="id"
						class="custom-control-label"
						aria-hidden="true"
					>
						<!-- Label is required for custom controls -->
					</label>
				</div>
			</div>

			<div :class="['card-body', classCardBody]">
				<!-- h4/h5 -->
				<component
					:is="heading"
					class="card-title"
				>
					<label :for="id">
						<slot name="title">
							<i
								v-if="icon"
								:class="icon"
								aria-hidden="true"
							/>
							{{ title }}
						</slot>
					</label>
				</component>

				<!-- Show selection with yes/no for each available service -->
				<!-- This component provides an own $v validator -->
				<card-block-radio-selection
					v-if="view === 'selection'"
					v-model="data"
					:name="radioName"
					namespace="selections"
					:disabled="!active && disabled"
					:translations="translations"
				/>

				<div
					v-if="descriptionHtml"
					v-html="descriptionHtml"
				/>

				<dl
					v-else-if="descriptionList"
					class="row"
				>
					<template v-for="item in descriptionList">
						<dt class="col-md-3">
							{{ item[0] }}
						</dt>
						<dd class="col-md-9">
							{{ item[1] }}
						</dd>
					</template>
				</dl>

				<p
					v-if="description"
					class="card-text"
				>
					{{ description }}
				</p>

				<slot name="content" />

				<slot v-if="!useFooter && active" />
			</div>

			<div
				v-if="useFooter && active"
				class="card-footer"
			>
				<slot v-if="active" />
			</div>
		</div>
	</div>
</template>

<script>
// import VueSmoothReflow from 'vue-smooth-reflow';
import SmoothReflow from '../mixins/SmoothReflow'
// import DependencyMixin from '../mixins/DependencyMixin';
import TranslationsMixin from '../mixins/TranslationsMixin'
import InputField from './InputField'
import CardBlockRadioSelection from './CardBlockRadioSelection'

export default {
	components: {
		CardBlockRadioSelection,
		InputField
	},
	mixins: [SmoothReflow, TranslationsMixin],
	props: {
		block: { type: String, required: true },
		serviceKey: { type: [String, Number], required: true },
		view: { type: String, default: 'checkbox', validator: v => ['card', 'checkbox', 'radio', 'selection'].includes(v) },
		value: { type: Boolean, required: true },
		disabled: { type: Boolean, default: false },
		isInvalid: { type: Boolean, default: false },
		title: String,
		descriptionList: Array,
		descriptionHtml: { type: String, default: '' },
		description: String,
		icon: String,
		img: String,
		size: { type: String, default: 'md' },
		useFooter: { type: Boolean, default: false },
	},
	data() {
		return {
			id: this.$id(this.serviceKey),
			leftColumn: !['card', 'selection'].includes(this.view),
			radioName: `${this.block}_${this.serviceKey}`,
			classCardControl: '',
			classCardBody: '',
			heading: ['xs', 'sm'].includes(this.size) ? 'h5' : 'h4'
		}
	},
	computed: {
		data: {
			get() {
				return this.value
			},
			set(value) {
				this.$emit('input', value)
			}
		},
		active() {
			return this.value
		}
	},
	beforeMount() {
		this.calcCardClass()
		window.addEventListener('resize', this.calcCardClass)
	},
	mounted() {
		// this.$smoothReflow();
		this.$nextTick(() => {
			this.calcCardClass()
		})
	},
	// TODO Vue 3: beforeDestroy => beforeUnmount
	beforeUnmount() {
		window.removeEventListener('resize', this.calcCardClass)
	},
	methods: {
		calcCardClass() {
			const xsCols = this.leftColumn ? 2 : 1
			const mdCols = /*this.visible &&*/ this.$el && this.$el.getBoundingClientRect().width < 576 ? 2 : 1
			this.classCardControl = `col-${xsCols} col-md-${mdCols}`
			this.classCardBody = `col-${12 - xsCols} col-md-${12 - mdCols}`
			if (!this.leftColumn) {
				this.classCardBody += ` offset-${xsCols} offset-md-${mdCols}`
			}
		}
	}
}
</script>
