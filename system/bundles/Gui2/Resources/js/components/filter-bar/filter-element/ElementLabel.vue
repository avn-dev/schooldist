<script lang="ts">
import { defineComponent, inject, PropType, ref } from 'vue3'
import { GuiInstance } from '../../../composables/gui-component'
import FilterModel from '../../../models/filter'

export default defineComponent({
	props: {
		filter: { type: Object as PropType<FilterModel>, required: true },
		collapsible: { type: Boolean, default: false },
		collapsed: { type: Boolean, default: false },
		view: { type: String, default: 'side' }
	},
	emits: [
		'collapse'
	],
	setup() {
		const infoIcon = ref<HTMLInputElement>()
		return {
			gui: inject('gui') as GuiInstance,
			infoIcon
		}
	},
	watch: {
		// jQuery-Mist ist nicht reactive
		'filter.helpText'() {
			this.initTooltip()
		}
	},
	mounted() {
		// watch.immediate funktioniert wegen ref auch nicht
		this.initTooltip()
	},
	methods: {
		initTooltip() {
			// .attr(): https://github.com/twbs/bootstrap/issues/14769
			if (this.filter.helpText) {
				console.log(this.view)
				// @ts-ignore
				window.jQuery(this.infoIcon).bootstrapTooltip({
					html: true,
					placement: this.view === 'contextmenu' ? 'bottom' : 'left',
					title: this.filter.helpText
				}).attr('data-original-title', this.filter.helpText)
			}
		},
		openHelp() {
			this.gui.openHelp()
		},
		openInfoIconDialog() {
			if (this.gui.options.info_icon_edit_mode) {
				this.gui.openInfoIconDialog(`${this.gui.hash}.${this.gui.options.info_icon_filter_key}.${this.filter.key}`)
			}
		}
	}
})
</script>

<template>
	<label
		:class="{
			'flex flex-row items-center cursor-pointer': (view === 'side' || view === 'contextmenu'),
			'text-gray-700': view === 'bar'
		}"
		@click="$emit('collapse')"
	>
		<span :class="['grow text-xs', {'font-heading font-semibold': !collapsed}]">
			{{ filter.label }}
		</span>
		<span class="flex-none">
			<i
				v-if="filter.helpText || gui.options.info_icon_edit_mode"
				ref="infoIcon"
				class="fa fa-info-circle gui-info-icon prototypejs-is-dead mr-1"
				:class="{ inactive: !filter.helpText && gui.options.info_icon_edit_mode, editable: gui.options.info_icon_edit_mode }"
				@click.stop="openInfoIconDialog"
			/>
			<i
				v-if="collapsible"
				class="fa"
				:class="collapsed ? 'fa-caret-down' : 'fa-caret-up'"
			/>
		</span>
	</label>
</template>
