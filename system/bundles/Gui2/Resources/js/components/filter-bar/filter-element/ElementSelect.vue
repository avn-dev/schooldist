<script lang="ts">
import { defineComponent, ref } from 'vue3'
// @ts-ignore 2307 type-definitions noch nicht verfügbar für Vue 3/Beta
import VSelect from 'vue-select'
import CollapseTransition from '@ivanv/vue-collapse-transition/src/CollapseTransition.vue'
import { FilterValue } from '../../../models/filter'
import { EMITS, EXPOSE, PROPS, focus, useDefault } from '../../../composables/filter-element'
import GuiFilterBarElementLabel from './ElementLabel.vue'
import GuiFilterBarElementNegateable from './ElementNegateable.vue'

export default defineComponent({
	components: {
		VSelect,
		CollapseTransition, // TODO Funktioniert nicht mit Composable/setup()
		GuiFilterBarElementLabel,
		GuiFilterBarElementNegateable
	},
	props: PROPS,
	emits: EMITS,
	expose: EXPOSE,
	setup(props) {
		return {
			...useDefault(props),
			element: ref<HTMLInputElement|VSelect>()
		}
	},
	errorCaptured(err) {
		if (err instanceof TypeError && err.stack?.includes('vue-select')) {
			// TODO https://github.com/sagalbot/vue-select/issues/1532
			console.warn('v-select beta error, needs to be updated', err)
			return false
		}
		throw err
	},
	methods: {
		focus() {
			if (this.filter?.isSimple()) {
				// this.$el funktioniert, da Root-Element <div> ist
				focus(this.$el.querySelector('select'))
			} else {
				// Vue-Select
				focus(this.element?.searchEl)
			}
		},
		update(event: FilterValue) {
			if (event === null) {
				// null (clear icon) muss mit reset-Event ausgeführt werden für initial_value
				this.$emit('reset:modelValue', event)
			} else {
				this.$emit('update:modelValue', event)
			}
		}
	}
})
</script>

<template>
	<div
		:class="{
			'px-2 rounded hover:bg-gray-50': view === 'side',
			'flex flex-row gap-x-1 items-center': view === 'bar',
			'py-2 bg-gray-50': (view === 'side' && !collapsed),
			'py-1': (view === 'side' && collapsed)
		}"
	>
		<gui-filter-bar-element-label
			:filter="filter"
			:collapsible="collapsible"
			:collapsed="collapsed"
			:view="view"
			@collapse="collapse"
		/>
		<collapse-transition>
			<div
				v-show="!collapsed"
				:class="{
					'flex flex-row gap-x-0.5': view === 'bar',
					'flex flex-col gap-y-0.5': view === 'side',
				}"
			>
				<gui-filter-bar-element-negateable
					v-if="view === 'side' && filter.isNegateable()"
					:filter="filter"
					@update:negate="$emit('update:negate', $event)"
				/>
				<select
					v-if="filter.isSimple()"
					ref="element"
					:value="modelValue"
					:class="['w-full rounded ring-1 ring-inset bg-white', {'p-2 ring-gray-100': (view === 'side' || view === 'contextmenu'), 'px-1.5 ring-gray-100/75': view === 'bar' }]"
					:data-view="view"
					@input="$emit('update:model-value', $event.target.value)"
				>
					<option
						v-for="option in filter.options"
						:key="option.key"
						:value="option.key"
					>
						{{ option.label }}
					</option>
				</select>

				<v-select
					v-if="!filter.isSimple()"
					ref="element"
					:model-value="modelValue"
					:options="filter.options"
					:append-to-body="view !== 'side'"
					:clearable="!filter.isSimple()"
					:multiple="filter.isMultiple()"
					:reduce="option => option.key"
					@update:model-value="update"
				/>

				<!-- eslint-disable vue/no-v-html -->
				<div
					v-if="filter.additionalHtml"
					class="gui-sidebar-filter-additional"
					v-html="filter.additionalHtml"
				/>
				<!--eslint-enable-->
			</div>
		</collapse-transition>
	</div>
</template>