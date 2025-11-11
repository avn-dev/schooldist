<script lang="ts">
import { App, defineComponent, PropType, provide, reactive, readonly } from 'vue3'
import { resolveComponent} from '../util/util'
import { PROPS, useDefault } from '../composables/gui-component'
import type { DialogComponent, DialogValues } from '../types/dialog'
import ContentHeading from './dialog/ContentHeading.vue'
import CheckboxField from './dialog/CheckboxField.vue'
import HtmlContent from './dialog/HtmlContent.vue'
import InputField from './dialog/InputField.vue'
import RepeatableSection from './dialog/RepeatableSection.vue'
import SelectField from './dialog/SelectField.vue'
import TextareaField from './dialog/TextareaField.vue'

type DialogRequest = {
	task: string,
	action: string,
	vue: { components: DialogComponent[] }
	values: DialogValues
}

// Global in der App setzen, damit Komponenten auch in verschachtelten Komponenten verfügbar sind
export function setup(app: App<Element>) {
	app.component('ContentHeading', ContentHeading)
	app.component('CheckboxField', CheckboxField)
	app.component('HtmlContent', HtmlContent)
	app.component('InputField', InputField)
	app.component('RepeatableSection', RepeatableSection)
	app.component('SelectField', SelectField)
	app.component('TextareaField', TextareaField)
}

export default defineComponent({
	props: {
		...PROPS,
		data: { type: Object as PropType<DialogRequest>, required: true }
	},
	setup(props) {
		const values = reactive(props.data.values)
		provide('values', readonly(values))
		return {
			...useDefault(props),
			values
		}
	},
	methods: {
		change(key: string, value: string) {
			this.values[key] = value
		},
		resolveComponent(component: string) {
			return resolveComponent(component)
		},
		/**
		 * Aufruf erfolgt von gui2.js prepareSaveDialog()
		 */
		submit(data: DialogRequest) {
			const body = {
				task: data.task,
				action: data.action,
				id: this.gui.selectedRowId,
				save: this.values
			} as Record<string, unknown>

			// Als neuen Eintrag speichern
			if (this.gui.bSaveAsNewEntry !== false) {
				body.clonedata = true
				body.save_as_new_from = [this.gui.bSaveAsNewEntry]
			}

			this.gui.request2(body)
				.then((data) => this.gui.requestCallback(data))
		}
	}
})
</script>

<template>
	<component
		:is="resolveComponent(component.component)"
		v-for="component in data.vue.components"
		:key="component.key"
		:model-value="values[component.key]"
		:name="component.key"
		:="component"
		@update:model-value="change(component.key, $event)"
	/>
</template>
