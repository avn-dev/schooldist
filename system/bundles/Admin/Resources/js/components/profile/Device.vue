<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import { type Device } from '../../types/common'
import { buildPrimaryColorElementCssClasses, buildPrimaryColorContrastCssClass } from '../../utils/primarycolor'

export default defineComponent({
	name: "Device",
	props: {
		device: { type: Object as PropType<Device>, required: true },
	},
	emits: ['update', 'delete'],
	setup() {
		return {
			buildPrimaryColorElementCssClasses,
			buildPrimaryColorContrastCssClass
		}
	}
})
</script>

<template>
	<div class="flex rounded-md shadow-xs dark:shadow-none h-full">
		<div class="bg-gray-100/50 flex w-16 shrink-0 items-center justify-center rounded-l-md text-sm font-medium text-white">
			<i class="fa fa-desktop text-gray-500 text-xl" />
		</div>
		<div class="flex flex-1 items-center justify-between truncate rounded-r-md border-t border-r border-b border-gray-100/50 bg-white dark:border-white/10 dark:bg-gray-800/50">
			<div class="flex-1 truncate px-4 py-2 text-sm">
				<div class="font-medium hover:text-gray-600 dark:text-white dark:hover:text-gray-200">
					{{ device.text }}
					<span
						v-if="device.current"
						:class="buildPrimaryColorContrastCssClass('text', 'text')"
					>
						&centerdot; {{ $l10n.translate('my_profile.device.current') }}
					</span>
				</div>
				<p class="text-xs text-gray-400">
					{{ device.created }}
					<span v-if="device.last_login">
						&centerdot; {{ $l10n.translate('my_profile.last_login') }}: {{ device.last_login }}
					</span>
					<span v-if="device.standard">
						&centerdot; {{ $l10n.translate('my_profile.device.standard') }}
					</span>
				</p>
			</div>
			<div class="shrink-0 pr-2">
				<button
					v-if="!device.current && !device.standard"
					:class="[
						'inline-flex size-8 items-center justify-center rounded-md text-gray-400 hover:text-gray-500 focus:outline-2 focus:outline-offset-2 focus:outline-indigo-600 dark:hover:text-white dark:focus:outline-white',
						buildPrimaryColorElementCssClasses('hover:')
					]"
					type="button"
					@click="$emit('delete')"
				>
					<i class="fa w-5 h-5 fa-trash text-sm" />
				</button>
			</div>
		</div>
	</div>
</template>
