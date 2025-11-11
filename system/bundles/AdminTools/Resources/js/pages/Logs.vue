<script lang="ts">
import {defineComponent, PropType, ref} from 'vue3'
import ToolsLayout from "../layout/ToolsLayout.vue"
import LogRow from "../components/LogRow.vue"
import {Interface} from "../types"

export default defineComponent({
	name: "Dashboard",
	components: {LogRow, ToolsLayout},
	props: {
		interface: { type: Object as PropType<Interface>, required: true },
		fileOptions: { type: Array as PropType<{ value: string, text: string }[]>, required: true }
	},
	setup(props) {

		const loading = ref(false)
		const active = ref(false)
		const file = ref(props.fileOptions[0]?.value)
		const from = ref(null)
		const until = ref(null)
		const lines = ref([])

		const load = (event: Event, limit: number) => {
			loading.value = true
			if (!limit) {
				lines.value = []
			}

			const body = {
				file: file.value,
				offset: lines.value.length,
				limit: limit ?? 0,
				from: from.value,
				until: until.value,
				// level: this.level
			}

			fetch('/admin/tools/log-viewer/load-log', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify(body)
			}).then(async resp => {
				const json = await resp.json()
				lines.value = lines.value.concat(json.lines)
			}).finally(() => {
				loading.value = false
			})
		}

		return {
			loading,
			active,
			file,
			lines,
			from,
			until,
			load
		}
	}
})
</script>

<template>
	<ToolsLayout :interface="interface">
		<div class="h-screen overflow-auto">
			<header class="flex flex-row items-stretch p-2 gap-x-2">
				<select
					v-model="file"
					class="flex block rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 bg-white ring-inset ring-gray-100 focus:ring-2 focus:ring-primary-600 sm:text-sm sm:leading-6 dark:bg-gray-800 dark:text-gray-200 dark:ring-gray-700"
					@change="load($event, null)"
				>
					<option
						v-for="option in fileOptions"
						:key="option.value"
						:value="option.value"
					>
						{{ option.text }}
					</option>
				</select>
				<input
					v-model="from"
					type="date"
					class="flex block rounded-md border-0 py-1 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-100 focus:ring-2 focus:ring-primary-600 sm:text-sm sm:leading-6 dark:bg-gray-800 dark:text-gray-200 dark:ring-gray-700"
					@change="load($event, null)"
				>
				<input
					v-model="until"
					type="date"
					class="flex block rounded-md border-0 py-1 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-100 focus:ring-2 focus:ring-primary-600 sm:text-sm sm:leading-6 dark:bg-gray-800 dark:text-gray-200 dark:ring-gray-700"
					@change="load($event, null)"
				>
				<button
					type="button"
					class="inline-flex items-center justify-center size-8 rounded-md bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400 hover:text-white"
					@click="load($event, null)"
				>
					<i class="fa fa-sync-alt flex" />
				</button>
			</header>
			<div class="p-2">
				<table class="min-w-full text-xs font-medium text-black dark:text-gray-200">
					<thead>
						<tr>
							<th style="width: 250px" />
							<th style="width: 100px" />
							<th style="width: 75px" />
							<th style="width: auto" />
							<th style="width: 50px" />
						</tr>
					</thead>
					<tbody>
						<tr v-if="!lines.length">
							<td
								colspan="5"
								class="text-center"
							>
								<span class="bg-gray-100/50 dark:bg-gray-800 rounded p-1">Please select file</span>
							</td>
						</tr>
						<LogRow
							v-for="line in lines"
							:key="line.key"
							:line="line"
						/>
					</tbody>
					<tfoot>
						<tr v-if="lines.length">
							<td
								colspan="5"
								class="text-center"
							>
								<div class="flex justify-center gap-x-2 mt-2">
									<button
										class="flex rounded-md bg-gray-500 text-gray-200 p-2 dark:bg-gray-800 dark:text-gray-400 hover:text-white"
										@click="load($event, 100)"
									>
										Load 100 more
									</button>
									<button
										class="flex rounded-md bg-gray-500 text-gray-200 p-2 dark:bg-gray-800 dark:text-gray-400 hover:text-white"
										@click="load($event, 1000)"
									>
										Load 1000 more
									</button>
								</div>
							</td>
						</tr>
					</tfoot>
				</table>
			</div>
		</div>
	</ToolsLayout>
</template>
