<script>
import InputField from '../../common/InputField';
import TranslationsMixin from '../../mixins/TranslationsMixin.vue';
import { checkCourseAge } from '../../../utils/helpers';

export default {
	components: {
		InputField
	},
	mixins: [TranslationsMixin],
	model: {
		prop: 'services'
	},
	props: {
		services: { type: Array, required: true },
		selected: { type: Array, required: true }
	},
	data() {
		return {
			category: null,
			subCategory: null,
			language: null
		}
	},
	computed: {
		filled() {
			return Object.values(this.$data).some(v => !!v);
		},
		categories() {
			const keys = this.services.map(s => s.categories[0]);
			return this.$store.state.form.course_groupings.filter(g => g.type === 'category' && keys.includes(g.key));
		},
		subCategories() {
			const keys = this.services.filter(s => s.categories[0] === this.category).map(s => s.categories[1]);
			return this.$store.state.form.course_groupings.filter(g => g.type === 'category' && keys.includes(g.key));
		},
		languages() {
			const keys = this.services.reduce((c, s) => c.concat(s.languages), []);
			return this.$store.state.form.course_groupings.filter(g => g.type === 'language' && keys.includes(g.key));
		}
	},
	watch: {
		'$store.state.booking.fields.birthdate': {
			immediate: true,
			handler() {
				this.update();
			}
		}
	},
	methods: {
		update() {
			if (!this.subCategories.length > 0) {
				this.subCategory = null;
			}

			this.$emit('input', this.services.filter(s => {
				return (
					// Wenn ausgewählt, immer behalten, ansonsten Component + Reactivity weg
					this.selected.some(c => s.key === c.course) || (
						(!this.category || s.categories[0] === this.category) &&
						(!this.subCategory || s.categories[1] === this.subCategory) &&
						(!this.language || s.languages.includes(this.language)) &&
						checkCourseAge.call(this, s)
					)
				);
			}));
		},
		reset() {
			Object.keys(this.$data).forEach(k => this[k] = null);
			this.update();
		}
	}
}
</script>

<template>
	<div class="course-filter">
		<div
			v-if="categories.length > 1"
			class="course-filter-categories"
		>
			<a
				v-for="item in categories"
				:key="item.key"
				href="#"
				role="button"
				:class="['btn btn-outline-secondary btn-block', { active: item.key === category }]"
				@click.prevent="category = item.key === category ? null : item.key; update()"
			>
				<i v-if="item.icon" :class="item.icon"/>
				{{ item.label }}
			</a>
		</div>
		<div
			v-if="subCategories.length > 1"
			class="course-filter-categories"
		>
			<a
				v-for="item in subCategories"
				:key="item.key"
				href="#"
				role="button"
				:class="['btn btn-outline-secondary btn-block', { active: item.key === subCategory }]"
				@click.prevent="subCategory = item.key === subCategory ? null : item.key; update()"
			>
				<i v-if="item.icon" :class="item.icon"/>
				{{ item.label }}
			</a>
		</div>
		<input-field
			v-if="languages.length > 1"
			type="select"
			name="grouping"
			:label="$t('language')"
			:value="language"
			:options="languages"
			@input="language = $event; update()"
		/>
		<button
			v-if="filled"
			type="button"
			class="btn btn-sm btn-outline-primary float-right clearfix"
			@click="reset"
		>
			{{ $t('clear_filters') }}
		</button>
	</div>
</template>