<script>
import { inject } from 'vue3'

export default {
	props: { line: { type: Object, required: true } },
	data() {
		const levelColors = inject('level_colors')
		return {
			context: null,
			showDetails: false,
			levelColor: levelColors[this.line.level]
		}
	},
	computed: {
		faCaret() {
			return 'fa-caret-' + (!this.showDetails ? 'down' : 'up')
		}
	},
	methods: {
		toggleDetails() {
			this.showDetails = !this.showDetails
		},
		formatContext() {
			this.context = JSON.stringify(JSON.parse(this.line.context), null, 4)
		}
	}
}
</script>

<template>
	<tr>
		<td>{{ line.date }}</td>
		<td>{{ line.logger }}</td>
		<td :class="levelColor">
			{{ line.level }}
		</td>
		<td>{{ line.message }}</td>
		<td class="context-actions">
			<a
				v-if="line.context"
				class="btn btn-default btn-sm"
				@click="toggleDetails"
			>
				<i :class="['fa', faCaret]" />
			</a>
		</td>
	</tr>
	<tr
		v-if="showDetails"
		class="context-row"
	>
		<td colspan="5">
			<button
				v-if="!context"
				type="button"
				class="btn btn-info btn-xs pull-right"
				@click="formatContext"
			>
				Format
			</button>
			<div :class="{'pre': context}">
				{{ context || line.context }}
			</div>
		</td>
	</tr>
</template>