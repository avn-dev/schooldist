<script lang="ts">
import {defineComponent, ref, type Ref, type PropType} from 'vue3'
import { default as ky } from 'ky'
import ToolsLayout from "../layout/ToolsLayout.vue"
import {Interface} from "../types"
import Box from '../components/Box.vue'
import AlertMessage from '../../../../Admin/Resources/js/components/AlertMessage.vue'

export default defineComponent({
	name: "Dashboard",
	components: { AlertMessage, Box, ToolsLayout},
	props: {
		interface: { type: Object as PropType<Interface>, required: true },
		debugMode: { type: Number, required: true},
		debugIp: { type: Boolean, required: true},
		licence: { type: String, required: true},
		version: { type: String, required: true},
		server: { type: String, required: true},
		actions: { type: Object as PropType<Record<string, Record<string, string>>>, required: true},
		indexes: { type: Object as PropType<Record<string, string>>, required: true}
	},
	setup(props) {
		const messages: Ref<{ type: 'error' | 'success', message: string }[]> = ref([])
		const executing: Ref<string|null> = ref(null)
		const localDebugMode = ref(props.debugMode)
		const localDebugIp = ref(props.debugIp)
		const action = ref(0)
		const actionValue = ref('')
		const index = ref(0)
		const indexValue = ref(0)

		const toggleDebugMode = async () => {
			executing.value = 'debug-mode'
			const response = await ky.get('/admin/tools/debug-mode').json<{ success: boolean, value: number}>()
			executing.value = null

			if (response.success) {
				localDebugMode.value = response.value
			}
		}

		const toggleDebugIp = async () => {
			executing.value = 'debug-ip'
			const response = await ky.get('/admin/tools/debug-ip').json<{ success: boolean, value: boolean}>()
			executing.value = null

			if (response.success) {
				localDebugIp.value = response.value
			}
		}

		const buttonAction = async (button: string) => {
			executing.value = button
			const response = await ky.post('/admin/tools/button', { json: { button: button } }).json<{ success: boolean, messages: { type: 'error' | 'success', message: string }[] }>()
			executing.value = null

			if (response) {
				messages.value = response.messages
				if (response.success) {
					setTimeout(() => messages.value = [], 5000)
				}
			}
		}

		const selectAction = async (type: 'index'|'action') => {
			const input = (type === 'index') ? index.value : action.value
			const value = (type === 'index') ? indexValue.value : actionValue.value

			if (input != 0 && value !== 0 && value !== '') {
				executing.value = type
				const response = await ky.post('/admin/tools/action', { json: { type, action: input, value } }).json<{ success: boolean, messages: { type: 'error' | 'success', message: string }[] }>()
				executing.value = null

				if (response) {
					messages.value = response.messages
					if (response.success) {
						setTimeout(() => messages.value = [], 5000)

						if (type === 'index') {
							action.value = 0
							actionValue.value = ''
						}
						if (type === 'index') {
							index.value = 0
							indexValue.value = 0
						}
					}
				}
			}
		}

		return {
			messages,
			executing,
			localDebugMode,
			localDebugIp,
			action,
			actionValue,
			index,
			indexValue,
			toggleDebugMode,
			toggleDebugIp,
			buttonAction,
			selectAction
		}
	}
})
</script>

<template>
	<ToolsLayout :interface="interface">
		<AlertMessage
			v-for="(message, i) in messages"
			:key="i"
			:message="message.message"
			:type="message.type"
			class="border m-2 p-4 text-sm"
		/>
		<div class="grid grid-cols-12 gap-4 p-4">
			<Box class="col-span-4">
				<table class="w-full text-xs">
					<tr class="even:bg-gray-200 dark:even:bg-gray-700">
						<th class="font-semibold text-left p-2">
							Lizenz
						</th>
						<td class="text-gray-500">
							{{ licence }}
						</td>
					</tr>
					<tr class="even:bg-gray-100 dark:even:bg-gray-700">
						<th class="font-semibold text-left p-2">
							Version
						</th>
						<td class="text-gray-500">
							{{ version }}
						</td>
					</tr>
					<tr class="even:bg-gray-100 dark:even:bg-gray-700">
						<th class="font-semibold text-left p-2">
							Server
						</th>
						<td class="text-gray-500">
							{{ server }}
						</td>
					</tr>
				</table>
			</Box>
			<Box class="col-span-8 grid-rows-1">
				<div class="grid gap-2 grid-cols-6 lg:grid-cols-12">
					<div
						:class="[
							'col-span-2 group flex flex-1 flex-col col-span-1 p-2 rounded-lg text-center justify-items-center cursor-pointer',
							(localDebugMode > 0) ? 'bg-primary-500 text-primary-50 dark:bg-primary-900 dark:text-primary-500' : 'bg-gray-100 hover:bg-gray-200/75 dark:bg-gray-700 dark:hover:bg-gray-600'
						]"
						@click="toggleDebugMode"
					>
						<div :class="['text-xl p-2', (localDebugMode > 0) ? 'text-primary-300 group-hover:text-primary-200 dark:text-primary-400 dark:group-hover:text-primary-300' : 'text-gray-500 dark:text-gray-300 group-hover:text-gray-400']">
							<i
								v-if="executing === 'debug-mode'"
								class="fa fa-spinner fa-spin"
							/>
							<i
								v-else
								class="fa fa-bug"
							/>
						</div>
						<div class="text-xs w-full truncate font-semibold">
							Debug mode
						</div>
					</div>
					<div
						:class="[
							'col-span-2 group flex flex-1 flex-col col-span-1 p-2 rounded-lg text-center justify-items-center cursor-pointer',
							(localDebugIp) ? 'bg-primary-500 text-primary-50 dark:bg-primary-900 dark:text-primary-500' : 'bg-gray-100 hover:bg-gray-200/75 dark:bg-gray-700 dark:hover:bg-gray-600'
						]"
						@click="toggleDebugIp"
					>
						<div :class="['text-xl p-2', (localDebugIp) ? 'text-primary-300 group-hover:text-primary-200 dark:text-primary-400 dark:group-hover:text-primary-300' : 'text-gray-500 dark:text-gray-300 group-hover:text-gray-400']">
							<i
								v-if="executing === 'debug-ip'"
								class="fa fa-spinner fa-spin"
							/>
							<i
								v-else
								class="fa fa-bug"
							/>
						</div>
						<div class="text-xs w-full truncate font-semibold">
							Debug IP
						</div>
					</div>
					<div
						class="col-span-2 group flex flex-1 flex-col col-span-1 bg-gray-100 hover:bg-gray-200/75 dark:bg-gray-700 dark:hover:bg-gray-600 p-2 rounded-lg text-center justify-items-center cursor-pointer"
						@click="buttonAction('clear_cache')"
					>
						<div class="text-xl p-2 text-gray-500 dark:text-gray-300 group-hover:text-gray-400">
							<i
								v-if="executing === 'clear_cache'"
								class="fa fa-spinner fa-spin"
							/>
							<i
								v-else
								class="fa fa-trash"
							/>
						</div>
						<div class="text-xs w-full truncate font-semibold">
							Clear cache
						</div>
					</div>
					<div
						class="col-span-2 group flex flex-1 flex-col col-span-1 bg-gray-100 hover:bg-gray-200/75 dark:bg-gray-700 dark:hover:bg-gray-600 p-2 rounded-lg text-center justify-items-center cursor-pointer"
						@click="buttonAction('refresh_routing')"
					>
						<div class="text-xl p-2 text-gray-500 dark:text-gray-300 group-hover:text-gray-400">
							<i
								v-if="executing === 'refresh_routing'"
								class="fa fa-spinner fa-spin"
							/>
							<i
								v-else
								class="fa fa-sync"
							/>
						</div>
						<div class="text-xs w-full truncate font-semibold">
							Routing Refresh
						</div>
					</div>
					<div
						class="col-span-2 group flex flex-1 flex-col col-span-1 bg-gray-100 hover:bg-gray-200/75 dark:bg-gray-700 dark:hover:bg-gray-600 p-2 rounded-lg text-center justify-items-center cursor-pointer"
						@click="buttonAction('refresh_bundles')"
					>
						<div class="text-xl p-2 text-gray-500 dark:text-gray-300 group-hover:text-gray-400">
							<i
								v-if="executing === 'refresh_bundles'"
								class="fa fa-spinner fa-spin"
							/>
							<i
								v-else
								class="fa fa-cubes"
							/>
						</div>
						<div class="text-xs w-full truncate font-semibold">
							Bundle Refresh
						</div>
					</div>
					<div
						class="col-span-2 group flex flex-1 flex-col col-span-1 bg-gray-100 hover:bg-gray-200/75 dark:bg-gray-700 dark:hover:bg-gray-600 p-2 rounded-lg text-center justify-items-center cursor-pointer"
						@click="buttonAction('refresh_db_functions')"
					>
						<div class="text-xl p-2 text-gray-500 dark:text-gray-300 group-hover:text-gray-400">
							<i
								v-if="executing === 'refresh_db_functions'"
								class="fa fa-spinner fa-spin"
							/>
							<i
								v-else
								class="fa fa-database"
							/>
						</div>
						<div class="text-xs w-full truncate font-semibold">
							DB Functions Refresh
						</div>
					</div>
				</div>
			</Box>
			<h2 class="col-span-12 text-gray-500 text-xl">
				Actions
			</h2>
			<Box class="col-span-12 grid-rows-1">
				<div class="flex flex-row items-center gap-2 p-2">
					<label class="flex-none font-semibold w-40">Action</label>
					<select
						v-model="action"
						class="grow bg-gray-100 dark:bg-gray-700 rounded p-2"
					>
						<option value="0" />
						<optgroup
							v-for="(items, group) in actions"
							:key="group"
							:label="group"
						>
							<option
								v-for="(label, key) in items"
								:key="key"
								:value="key"
							>
								{{ label }}
							</option>
						</optgroup>
					</select>
				</div>
				<div class="flex flex-row items-center gap-2 p-2">
					<label class="flex-none font-semibold w-40">Value</label>
					<input
						v-model="actionValue"
						class="grow bg-gray-100 dark:bg-gray-700 rounded p-2"
					>
				</div>
				<div class="flex-none flex justify-end mx-2">
					<button
						type="button"
						class="rounded font-semibold text-primary-50 bg-primary-500 text-primary-200 dark:bg-primary-900 dark:text-primary-500 px-3 py-2"
						@click="selectAction('action')"
					>
						<i
							v-if="executing === 'action'"
							class="fa fa-spinner fa-spin"
						/>
						Ausführen
					</button>
				</div>
			</Box>
			<h2 class="col-span-12 text-gray-500 text-xl">
				Index
			</h2>
			<Box class="col-span-12 grid-rows-1">
				<div class="flex flex-row items-center gap-2 p-2">
					<label class="flex-none font-semibold w-40">Index</label>
					<select
						v-model="index"
						class="grow bg-gray-100 dark:bg-gray-700 rounded p-2"
					>
						<option value="0" />
						<option
							v-for="(label, key) in indexes"
							:key="key"
							:value="key"
						>
							{{ label }}
						</option>
					</select>
				</div>
				<div class="flex flex-row items-center gap-2 p-2">
					<label class="flex-none font-semibold w-40">Action</label>
					<select
						v-model="indexValue"
						class="grow bg-gray-100 dark:bg-gray-700 rounded p-2"
					>
						<option />
						<option value="refresh">
							Refresh + Fill Stack
						</option>
						<option value="reset">
							Reset + Fill Stack
						</option>
						<option value="reset_no_stack">
							Reset
						</option>
					</select>
				</div>
				<div class="flex-none flex justify-end mx-2">
					<button
						type="button"
						class="rounded font-semibold text-primary-50 bg-primary-500 text-primary-200 dark:bg-primary-900 dark:text-primary-500 px-3 py-2"
						@click="selectAction('index')"
					>
						<i
							v-if="executing === 'index'"
							class="fa fa-spinner fa-spin"
						/>
						Ausführen
					</button>
				</div>
			</Box>
		</div>
	</ToolsLayout>
</template>
