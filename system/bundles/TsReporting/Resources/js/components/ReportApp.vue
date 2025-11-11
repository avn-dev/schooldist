<script lang="ts">
import { defineComponent, PropType } from 'vue3'
import type { Report } from '../types'
import IndexView from './views/Index.vue'
import ShowView from './views/Show.vue'

export default defineComponent({
	components: {
		IndexView,
		ShowView
	},
	inject: ['translations'],
	props: {
		reports: { type: Array as PropType<Array<Report>>, required: true }
	},
	data() {
		return {
			report: null as Report|null,
			title: '',
			info: ''
		}
	},
	methods: {
		setTitle(title: string, info: string) {
			this.title = title
			this.info = info
		}
	}
})
</script>

<template>
	<div class="reporting-app box box-default">
		<div class="box-header with-border">
			<h1 class="box-title">
				{{ title }}
				<span
					v-if="info"
					:title="info"
				>
					&nbsp;<i class="fa fa-info-circle" />
				</span>
			</h1>
		</div>
		<div class="box-body">
			<index-view
				v-if="!report"
				:reports="reports"
				@load:report="report = $event"
				@title="setTitle"
			/>
			<show-view
				v-if="report"
				:report="report"
				@unload:report="report = null"
				@title="setTitle"
			/>
		</div>
	</div>
</template>
