<script lang="ts">
import {defineComponent, ref, PropType} from 'vue3'
import type { Ref } from 'vue3'

interface Line {
	date: string,
	logger: string,
	level: string,
	message: string,
	context?: string
}

interface Levels {
	DEBUG: string
	INFO: string
	NOTICE: string
	WARNING: string
	ERROR: string
	CRITICAL: string
	ALERT: string
	EMERGENCY: string
}

export default defineComponent({
	name: "LogRow",
	components: {},
	props: {
		line: { type: Object as PropType<Line>, required: true }
	},
	setup(props) {
		const levelColors: Levels = {
			DEBUG: 'bg-purple-50 text-purple-700 ring-purple-700/10 dark:bg-purple-400/10 dark:text-purple-400 dark:ring-purple-400/30',
			INFO: 'bg-blue-50 text-blue-700 ring-blue-700/20 dark:bg-blue-400/10 dark:text-blue-400 dark:ring-blue-400/30',
			NOTICE: 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20',
			WARNING: 'bg-yellow-50 text-yellow-800 ring-yellow-600/20 dark:bg-yellow-400/10 dark:text-yellow-500 dark:ring-yellow-400/20',
			ERROR: 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-400/10 dark:text-red-400 dark:ring-red-400/20',
			CRITICAL: 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-400/10 dark:text-red-400 dark:ring-red-400/20',
			ALERT: 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-400/10 dark:text-red-400 dark:ring-red-400/20',
			EMERGENCY: 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-400/10 dark:text-red-400 dark:ring-red-400/20'
		}
		const context: Ref<string|null> = ref(null)
		const showDetails: Ref<boolean> = ref(false)
		const levelColor: string = levelColors[props.line.level as keyof Levels] ?? levelColors['DEBUG']

		const formatContext = () => {
			if (props.line.context) {
				context.value = JSON.stringify(JSON.parse(props.line.context), null, 4).toString()
			}
		}

		return {
			levelColor,
			context,
			showDetails,
			formatContext
		}
	}
})
</script>

<template>
	<tr class="even:bg-gray-100/50 dark:even:bg-gray-800 group">
		<td class="text-gray-600 dark:text-gray-400 p-2">
			{{ line.date }}
		</td>
		<td class="text-gray-600 dark:text-gray-400 p-2">
			{{ line.logger }}
		</td>
		<td class="px-2">
			<span
				class="inline-flex items-center gap-x-0.5 rounded-md px-2 py-1 text-xs font-medium ring-inset ring-1"
				:class="levelColor"
			>
				{{ line.level }}
			</span>
		</td>
		<td class="p-2">
			{{ line.message }}
		</td>
		<td class="p-2">
			<button
				v-if="line.context"
				type="button"
				class="flex rounded-md py-1.5 px-2 bg-gray-200 text-gray-600 dark:bg-gray-800 dark:group-even:bg-gray-900 hover:text-gray-800 dark:text-gray-400"
				@click="showDetails = !showDetails"
			>
				<i
					v-if="!showDetails"
					class="fa fa-caret-down"
				/>
				<i
					v-if="showDetails"
					class="fa fa-caret-up"
				/>
			</button>
		</td>
	</tr>
	<tr
		v-if="showDetails"
		:class="['my-2 ring-1 rounded-md', levelColor]"
	>
		<td
			colspan="5"
			class="p-2 rounded-md"
		>
			<div class="flex rounded-md">
				<div
					class="flex-grow"
					:class="{'whitespace-pre': context}"
				>
					{{ context || line.context }}
				</div>
				<div class="flex-none">
					<button
						v-if="!context"
						type="button"
						class="text-xs rounded-md py-1.5 px-2 bg-gray-500 text-white dark:bg-gray-900 dark:group-even:bg-gray-900 dark:text-gray-400 dark:hover:text-white"
						@click="formatContext"
					>
						Format
					</button>
				</div>
			</div>
		</td>
	</tr>
</template>
