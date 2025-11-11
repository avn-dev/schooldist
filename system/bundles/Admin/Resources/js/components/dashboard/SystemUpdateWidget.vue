<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import { RouterAction } from '../../types/backend/router'
import { buildPrimaryColorElementCssClasses } from '../../utils/primarycolor'
import router from '../../router'

export default defineComponent({
	name: "SystemUpdateWidget",
	props: {
		updates: { type: Object as PropType<{label: string, version: string}>, required: true },
		action: { type: Object as PropType<RouterAction>, default: null }
	},
	setup(props) {
		const openUpdate = () => {
			if (props.action) {
				router.action(props.action)
			}
		}

		return {
			openUpdate,
			buildPrimaryColorElementCssClasses
		}
	}
})
</script>

<template>
	<div
		v-if="updates.length === 0"
		class="text-center py-6"
	>
		{{ $l10n.translate('dashboard.system_updates.empty') }}
	</div>
	<ul
		v-else
		role="list"
		class="text-xs flex flex-col gap-1"
	>
		<li
			v-for="update in updates"
			:key="update.version"
		>
			<div
				:class="[
					'flex items-center gap-1 p-2 gap-4 rounded-md text-gray-900 dark:text-gray-100',
					{'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-900': action !== null}
				]"
				@click="openUpdate"
			>
				<div :class="['text-xl py-2 px-3 rounded-md', buildPrimaryColorElementCssClasses()]">
					<i class="fas fa-download" />
				</div>
				<div class="min-w-0">
					<span class="font-semibold">{{ update.label }}</span><br>
					<span class="text-gray-400">{{ update.version }}</span>
				</div>
			</div>
		</li>
	</ul>
</template>
