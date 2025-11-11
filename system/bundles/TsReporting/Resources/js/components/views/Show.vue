<script lang="ts">
import { defineComponent, markRaw, ref } from 'vue3'
import { default as ky, HTTPError } from 'ky'
import type { QueryPayload, QueryConfig, QueryFilter, QueryRows } from '../../types'
import { Component } from '../../composables/visualization'
import { Definition } from '../../composables/visualization'
import FilterElement from '../filter/Element.vue'
import PivotVisualization from '../visualization/Pivot.vue'
import TableVisualization from '../visualization/Table.vue'
import ModalView from './Modal.vue'

export default defineComponent({
	components: {
		FilterElement,
		PivotVisualization,
		TableVisualization,
		ModalView
	},
	inject: ['debugmode', 'translations'],
	props: {
		report: { type: Object, required: true }
	},
	emits: [
		'title',
		'unload:report'
	],
	setup() {
		return {
			exportAnchor: ref<HTMLAnchorElement>(),
			visualization: ref<typeof Component>()
		}
	},
	data() {
		return {
			loading: false,
			loadingExport: false,
			config: {} as QueryConfig,
			definitions: [] as Definition[],
			rows: [] as QueryRows,
			filters: [] as QueryFilter[],
			filtersOpen: false,
			filterElements: [],
			debug: false,
			exportBlob: undefined as string|undefined,
			exportFilename: undefined as string|undefined
		}
	},
	mounted() {
		this.$emit('title', this.report.name, this.report.description)
		this.query()
	},
	methods: {
		createPayload() {
			return { id: this.report.id, filters: this.filters, debug: this.debug }
		},
		async query() {
			this.loading = true
			this.config = {}
			this.definitions = []
			this.rows = []
			try {
				const data = await ky.post('/ts/reports/query', { json: this.createPayload(), timeout: 120000 }).json() as QueryPayload
				this.config = markRaw(data.config)
				this.definitions = markRaw(data.definitions.map((d, i) => new Definition(d, i)))
				this.filters = data.filters
				this.rows = markRaw(data.rows)
			} catch (e) {
				console.error('Reporting query error', e)
				if (e instanceof Error) {
					this.config = { message: e.message }
				}
				if (e instanceof HTTPError && this.debug) {
					this.config.debug = await e.response.text()
				}
			}
			this.loading = false
		},
		async queryFilters() {
			const body = { ...this.createPayload(), filter_dependency: true }
			const data = await ky.post('/ts/reports/query', { json: body }).json() as QueryPayload
			if (data.filters) this.filters = data.filters
		},
		updateFilter(filter: QueryFilter, value: unknown) {
			filter.value = value
			if (this.filters.some(f => f.dependencies.includes(filter.key))) {
				this.queryFilters()
			}
		},
		applyFilterModal() {
			this.filtersOpen = false
			this.query()
		},
		async handleExport() {
			if (!this.visualization) {
				return
			}
			this.loadingExport = true
			const body = { ...this.createPayload(), ...this.visualization.export() }
			ky.post('/ts/reports/export', { json: body, timeout: 30000 }).then(async resp => {
				this.exportBlob = URL.createObjectURL(await resp.blob())
				this.exportFilename = resp.headers.get('Content-Disposition')?.match(/filename="(.+?)"/)?.[1]

				this.$nextTick(() => {
					this.exportAnchor?.click()
					setTimeout(() => {
						URL.revokeObjectURL(this.exportBlob as string)
						this.exportBlob = undefined
					}, 100)
				})
			}).then(() => {
				this.loadingExport = false
			})
		}
	}
})
</script>

<template>
	<ul class="actions-bar">
		<li>
			<a
				class="btn btn-sm btn-default"
				@click="$emit('unload:report')"
			>
				<i class="fa fa-chevron-left" />
				{{ translations.back }}
			</a>
		</li>
		<li>
			<a
				class="btn btn-sm btn-default"
				@click="filtersOpen = true"
			>
				<i class="fa fa-filter" />
				{{ translations.filter }}
			</a>
		</li>
		<template
			v-for="filter in filters"
			:key="filter.key"
		>
			<filter-element
				v-if="filter.required || filter.value !== null"
				:filter="filter"
				@update="updateFilter(filter, $event)"
			/>
		</template>
		<li class="btn-group">
			<a
				class="btn btn-sm btn-default"
				@click="query"
			>
				<i class="fa fa-refresh" />
				{{ translations.refresh }}
			</a>
			<a
				v-if="debugmode"
				class="btn btn-sm btn-default"
				:class="[debug ? 'active' : '']"
				@click="debug = !debug"
			>
				<i class="fa fa-bug" />
			</a>
		</li>
		<li>
			<a
				class="btn btn-sm btn-default"
				:class="{ disabled: !definitions.length || loadingExport }"
				@click="handleExport"
			>
				<span>
					<i class="fa fa-file-excel" />
					{{ translations.export }}
				</span>
				<span v-if="loadingExport">
					&nbsp;<i class="fas fa-sync fa-spin" />
				</span>
			</a>
		</li>
	</ul>

	<div
		v-if="loading || config.message || config.debug"
		class="view-not-loaded fa-2x"
	>
		<div>
			<i
				v-if="loading"
				class="fa fa-spin fa-sync"
			/>
		</div>
		<div v-if="config.message">
			{{ config.message }}
		</div>
		<pre v-if="config.debug">
			{{ config.debug }}
		</pre>
	</div>

	<div
		v-if="!loading && definitions.length && rows.length"
		class="view-loaded"
	>
		<component
			:is="`${config.visualization}-visualization`"
			ref="visualization"
			:config="config"
			:definitions="definitions"
			:rows="rows"
		/>
	</div>

	<modal-view
		v-model="filtersOpen"
		:title="translations.filter"
	>
		<template #default>
			<ul class="list-unstyled">
				<filter-element
					v-for="filter in filters"
					:key="filter.key"
					:filter="filter"
					@update="updateFilter(filter, $event)"
				/>
			</ul>
		</template>
		<template #footer>
			<button
				type="button"
				class="btn btn-primary"
				@click="applyFilterModal"
			>
				{{ translations.apply_filters }}
			</button>
		</template>
	</modal-view>

	<a
		ref="exportAnchor"
		:href="exportBlob"
		:download="exportFilename"
	/>
</template>
