<template>
	<div>

		<div
			v-if="type === 'button'"
			class="row course-grouping-buttons"
		>
			<div v-for="item in items" :key="item.key" class="col-md-6">
				<a
					href="#"
					role="button"
					:class="['btn btn-outline-secondary btn-block', { active: isActive(item.key), disabled }]"
					@click.prevent="change(item.key)"
				>
					<i v-if="item.icon" :class="item.icon"></i>
					{{ item.label }}
				</a>
			</div>
		</div>

		<div v-if="type === 'select'">
			<input-field
				type="select"
				name="grouping"
				:label="label"
				:options="items"
				:value="selected"
				:disabled="disabled"
				:empty-option="false"
				@input="change"
			></input-field>
		</div>

		<hr v-if="hr">

	</div>
</template>

<script>
	import InputField from '../../common/InputField';

	export default {
		components: {
			InputField
		},
		props: {
			items: { type: Array, required: true },
			index: { type: Number, required: true },
			selected: { type: [String, Number] },
			type: { type: String, default: 'button' },
			label: { type: String },
			disabled: { type: Boolean, default: false }
		},
		data() {
			return {
				// view: this.type === 'tab' && this.items.length <= 4 ? 'tab' : 'button'
			};
		},
		computed: {
			hr() {
				return this.view === 'button' && this.items.length && this.index !== 0 && this.selected;
			}
		},
		methods: {
			change(key) {
				this.$emit('change', key);
			},
			isActive(key) {
				return key === this.selected;
			}
		}
	}
</script>