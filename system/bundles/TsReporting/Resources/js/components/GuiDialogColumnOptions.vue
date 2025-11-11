<script lang="ts">
import { defineComponent, PropType } from 'vue3'
import { EMITS, PROPS } from '@Gui2/composables/dialog/repeatable-section-component'
import type { RepeatableSectionValue, FieldDependency } from '@Gui2/types/dialog'

type Definitions = { [column: string]: Definition }
type Definition = Array<{ key: string, label: string, type: string, required: boolean, options?: Record<string, string> }>

enum FIELDS {
	AGGREGATED = 'aggregated',
	SUBTOTALS = 'subtotals'
}

export default defineComponent({
	inheritAttrs: false,
	props: {
		...PROPS,
		definitions: { type: Object as PropType<Definitions>, required: true },
		nestable: { type: Boolean, default: false },
		firstLayer: { type: Boolean, required: true },
		nestedLabel: { type: String, default: '' },
		nestedComponents: { type: Array, default: () => [] },
		labelSubtotals: { type: String, default: '' }
	},
	emits: EMITS,
	setup() {
		return {
			FIELDS,
			pivotFieldDependency: [{ type: 'visibility', field: 'visualization', values: ['pivot'] }] as FieldDependency[]
		}
	},
	computed: {
		// Verfügbare Felder/Einstellungen für das ausgewählte Objekt
		definition(): Definition {
			return this.definitions[this.sectionValue.object] ?? []
		},
		attributes() {
			const keys = [...Object.values(FIELDS), ...this.definition.map(d => d.key)]
			const attributes = this.firstLayer && this.modelValue ?
				JSON.parse(this.modelValue as string) :
				{ ...this.modelValue as RepeatableSectionValue }
			// Alle Keys löschen, die nicht mehr vorkommen (Definition geändert)
			Object.keys(attributes).forEach(k => !keys.includes(k) ? delete attributes[k] : null)
			return attributes
		},
	},
	watch: {
		// Falls beim Evaluieren von attributes Keys entfernt wurden: Direkt aktualisieren (z.B. Spalte geändert)
		attributes(value: unknown) {
			this.emit(value)
		}
	},
	methods: {
		change(key: string, value: unknown) {
			this.emit({ ...this.attributes, [key]: value })
		},
		emit(value: unknown) {
			this.$emit('update:modelValue', this.firstLayer ? JSON.stringify(value) : value)
		}
	}
})
</script>

<template>
	<select-field
		v-for="field in definition"
		:key="field.key"
		:model-value="attributes[field.key]"
		:name="field.key"
		:label="field.label"
		:required="field.required"
		:multiple="field.type === 'multiselect'"
		:options="field.options"
		:dependencies="field.dependencies"
		@update:model-value="change(field.key, $event)"
	/>

	<checkbox-field
		v-if="sectionKey === 'groupings' && attributes.pivot === 'row'"
		:model-value="attributes.subtotals"
		:name="FIELDS.SUBTOTALS"
		:label="labelSubtotals"
		:dependencies="pivotFieldDependency"
		@update:model-value="change(FIELDS.SUBTOTALS, $event)"
	/>

	<repeatable-section
		v-if="firstLayer && sectionValue.object === 'TsReporting\\Generator\\Groupings\\Aggregated'"
		:model-value="attributes.aggregated || []"
		:components="nestedComponents"
		:name="FIELDS.AGGREGATED"
		:label="nestedLabel"
		:min="0"
		sortable
		@update:model-value="change(FIELDS.AGGREGATED, $event); $forceUpdate()"
	/>
</template>
