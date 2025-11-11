<script lang="ts">
// @ts-ignore
import { defineComponent, inject } from 'vue3'
import CollapseTransition from '@ivanv/vue-collapse-transition/src/CollapseTransition.vue'
import type { GuiInstance, EmitterType } from '../../types/gui'
import { FilterQuery } from '../../models/filter'
// @ts-ignore
import { buildPrimaryColorElementCssClasses } from "@Admin/utils/primarycolor"

export default defineComponent({
	components: {
		CollapseTransition
	},
	emits: ['update:query'],
	setup() {
		return {
			gui: inject('gui') as GuiInstance,
			emitter: inject('emitter') as EmitterType,
			buildPrimaryColorElementCssClasses
		}
	},
	data() {
		return {
			// @ts-ignore 2339
			filterQuery: this.gui.filterQuery, // ref in Vue wrappen
			// @ts-ignore 2339
			filterQueries: this.gui.filterQueries,
			errorName: false,
			name: '',
			visibility: 'all',
			saveAsNew: true,
			showSettings: false
		}
	},
	watch: {
		filterQuery: function (filterQuery: FilterQuery) {
			this.adoptQuery(filterQuery)
		}
	},
	mounted() {
		// Workaround für app.unmount()
		this.adoptQuery(this.filterQuery)
	},
	methods: {
		adoptQuery(filterQuery: FilterQuery) {
			this.saveAsNew = false
			this.name = filterQuery.name
			this.visibility = filterQuery.visibility

			if (!filterQuery.id) {
				this.name = ''
				this.saveAsNew = true
			}
		},
		change(queryId: string) {
			const query = this.filterQueries.find((q: FilterQuery) => q.id === parseInt(queryId))
			if (!query) {
				console.error('Query not found:', queryId)
				return
			}
			this.$emit('update:query', query)
		},
		save() {
			this.errorName = false
			if (this.name.trim() === '') {
				this.errorName = true
				return
			}

			const params = new URLSearchParams(this.gui.getFilterparam())
			params.append('task', 'request')
			params.append('action', 'saveFilterQuery')
			params.append('name', this.name)
			params.append('visibility', this.visibility)
			params.append('save_as_new', this.saveAsNew ? '1' : '0')

			this.request(params)
		},
		setDefault() {
			const params = new URLSearchParams(this.gui.getFilterparam())
			params.append('task', 'request')
			params.append('action', 'setDefaultFilterQuery')
			this.request(params)
		},
		deleteQuery() {
			if (this.filterQuery.id == 0) {
				console.error('No filter query selected')
				return
			}
			const translation = this.visibility === 'all' ? 'filter_delete_confirm_all' : 'filter_delete_confirm'
			if (confirm(this.gui.getTranslation(translation))) {
				const params = new URLSearchParams()
				params.append('task', 'request')
				params.append('action', 'deleteFilterQuery')
				params.append('filter_query_id', this.filterQuery.id)
				this.request(params)
			}
		},
		async request(params: URLSearchParams) {
			const data = await this.gui.request2(params)
			if (data.status) {
				this.emitter.emit(`notification:${this.gui.hash}`, {
					type: data.status,
					message: data.message
				})
			}
			if (data.status === 'success' && data.filter_query) {
				this.$emit('update:query', data.filter_query)
			}
		}
	}
})
</script>

<template>
	<ul class="flex flex-col gap-y-2 p-2">
		<li class="header">
			<div class="flex py-1 px-2 gap-x-2 rounded-md items-center font-semibold font-heading bg-gray-100/75 dark:bg-gray-900">
				<div
					:class="[
						'flex-none h-6 w-1 rounded-full inline-block',
						buildPrimaryColorElementCssClasses(),
					]"
				/>
				<div class="grow">
					{{ gui.getTranslation('filter_query_label') }}
				</div>
			</div>
		</li>
		<li>
			<select
				:value="filterQuery.id"
				class="w-full rounded ring-1 ring-inset ring-gray-100 p-2 bg-white"
				@change="change($event.target.value)"
			>
				<option
					v-for="query in filterQueries"
					:key="query.id"
					:value="query.id"
				>
					{{ query.name }}
				</option>
			</select>
			<span class="isolate inline-flex mt-2">
				<button
					type="button"
					class="relative inline-flex items-center gap-x-1.5 rounded-l-md bg-white px-3 py-2 text-xs font-heading font-semibold text-gray-900 ring-1 ring-inset ring-gray-100 hover:bg-gray-50 focus:z-10"
					:title="gui.getTranslation('filter_default_title')"
					@click="setDefault"
				>
					<i class="fa fa-star-o" />
					{{ gui.getTranslation('filter_default') }}
				</button>
				<button
					type="button"
					class="relative -ml-px inline-flex gap-x-1.5 items-center rounded-r-md bg-white px-3 py-2 text-xs font-heading font-semibold text-gray-900 ring-1 ring-inset ring-gray-100 hover:bg-gray-50 focus:z-10"
					:title="gui.getTranslation('filter_query_settings')"
					@click="showSettings = !showSettings"
				>
					<i class="fa fa-cog" />
					{{ gui.getTranslation('filter_query_settings') }}
				</button>
			</span>
		</li>
		<collapse-transition>
			<li
				v-show="showSettings"
				class="text-xs rounded-md bg-gray-50 p-2 flex flex-col gap-y-2"
			>
				<div
					class="form-group"
					:class="{ 'has-error': errorName }"
				>
					<label class="block leading-6 font-semibold font-heading text-gray-900">
						{{ gui.getTranslation('filter_query_name') }}
					</label>
					<input
						v-model="name"
						class="w-full p-2 rounded items-center relative ring-1 bg-white ring-gray-100 text-gray-500 hover:text-gray-600 hover:ring-gray-200 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 dark:hover:text-gray-100"
						@input="errorName = false"
					>
				</div>
				<div class="form-group">
					<label class="block font-semibold font-heading leading-6 text-gray-900">
						{{ gui.getTranslation('filter_visibility') }}
					</label>
					<div class="radio">
						<label>
							<input
								v-model="visibility"
								type="radio"
								value="all"
							>
							{{ gui.getTranslation('filter_visibility_all') }}
						</label>
					</div>
					<div class="radio">
						<label>
							<input
								v-model="visibility"
								type="radio"
								value="user"
							>
							{{ gui.getTranslation('filter_visibility_user') }}
						</label>
					</div>
				</div>
				<div class="checkbox">
					<label>
						<input
							v-model="saveAsNew"
							type="checkbox"
							:disabled="!filterQuery.id"
						>
						{{ gui.getTranslation('filter_save_as_new') }}
					</label>
				</div>
				<span class="isolate inline-flex">
					<button
						type="button"
						class="relative inline-flex items-center gap-x-1.5 rounded-l-md bg-white px-3 py-2 text-xs font-heading font-semibold text-gray-900 ring-1 ring-inset ring-gray-100 hover:bg-gray-50 focus:z-10"
						:title="gui.getTranslation('filter_save')"
						@click="save"
					>
						{{ gui.getTranslation('filter_save') }}
					</button>
					<button
						type="button"
						class="relative -ml-px inline-flex gap-x-1.5 items-center rounded-r-md bg-red-100 px-3 py-2 text-xs font-heading font-semibold text-red-700 ring-1 ring-inset ring-red-300 hover:bg-red-200 focus:z-10"
						:title="gui.getTranslation('filter_delete')"
						@click="deleteQuery"
					>
						{{ gui.getTranslation('filter_delete') }}
					</button>
				</span>
			</li>
		</collapse-transition>
	</ul>
</template>
