<script lang="ts">
import { defineComponent, PropType, inject } from 'vue3'
import type { Report, Translations } from '../../types'

export default defineComponent({
	props: {
		reports: { type: Array as PropType<Array<Report>>, required: true }
	},
	emits: [
		'title',
		'load:report'
	],
	setup() {
		return {
			translations: inject('translations') as Translations
		}
	},
	beforeMount() {
		this.$emit('title', this.translations.overview)
	}
})
</script>

<template>
	<div class="list-group">
		<button
			v-for="report in reports"
			:key="report.id"
			type="button"
			class="list-group-item"
			@click="$emit('load:report', report)"
		>
			{{ report.name }}
			<span
				v-if="report.description"
				:title="report.description"
			>
				&nbsp;<i class="fa fa-info-circle" />
			</span>
		</button>
	</div>
</template>
