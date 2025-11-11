<script lang="ts">
import { defineComponent, ref } from 'vue3'
import CollapseTransition from '@ivanv/vue-collapse-transition/src/CollapseTransition.vue'
import { EMITS, EXPOSE, PROPS, focus, useDefault } from '../../../composables/filter-element'
import GuiFilterBarElementLabel from './ElementLabel.vue'

export default defineComponent({
	components: {
		// TODO Funktioniert nicht mit Composable/setup()
		CollapseTransition,
		GuiFilterBarElementLabel
	},
	props: PROPS,
	emits: EMITS,
	expose: EXPOSE,
	setup(props) {
		const inputFrom = ref<HTMLInputElement>()
		const inputUntil = ref<HTMLInputElement>()
		return {
			...useDefault(props),
			inputFrom,
			inputUntil
		}
	},
	computed: {
		from() {
			return this.modelValue[0]
		},
		until() {
			return this.modelValue[1]
		}
	},
	mounted() {
		this.gui.prepareCalendar(this.inputFrom as HTMLInputElement)
		this.gui.prepareCalendar(this.inputUntil as HTMLInputElement)

		// @change funktioniert nicht; jQuery scheint das wohl über sich selbst zu machen
		// @ts-ignore
		window.jQuery([this.inputFrom, this.inputUntil]).on('change', () => {
			this.$emit('update:modelValue', [this.inputFrom?.value, this.inputUntil?.value])
		})
	},
	methods: {
		focus() {
			focus(this.inputFrom as HTMLInputElement)
		}
	}
})
</script>

<template>
	<div :class="['flex flex-col gap-y-2 px-2 py-1 rounded hover:bg-gray-50', (!collapsed) ? 'py-2 bg-gray-50': 'py-1']">
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
				class="flex flex-col gap-y-2 mx-0.5"
			>
				<div class="flex flex-row items-center bg-white pl-2 rounded items-center ring-1 ring-gray-100 text-gray-500 hover:text-gray-600 hover:ring-gray-200 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 dark:hover:text-gray-100">
					<i class="fa fa-calendar" />
					<input
						ref="inputFrom"
						:value="from"
						type="text"
						class="grow rounded ml-1 p-1 bg-white prototypejs-is-dead"
					>
				</div>
				<div class="flex flex-row items-center bg-white pl-2 rounded items-center ring-1 ring-gray-100 text-gray-500 hover:text-gray-600 hover:ring-gray-200 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 dark:hover:text-gray-100">
					<i class="fa fa-calendar" />
					<input
						ref="inputUntil"
						:value="until"
						type="text"
						class="grow rounded ml-1 p-1 bg-white prototypejs-is-dead"
					>
				</div>
			</div>
		</collapse-transition>
	</div>
</template>