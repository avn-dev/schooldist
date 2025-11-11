<script lang="ts">
import { defineComponent, type PropType } from 'vue3'
import { buildPrimaryColorContrastCssClass } from "../../../utils/primarycolor"
import { ContentLoadingState } from '../../../types/backend/router'

export default defineComponent({
	name: "FailedContent",
	props: {
		state: { type: Object as PropType<ContentLoadingState>, required: true }
	},
	setup() {
		return {
			buildPrimaryColorContrastCssClass
		}
	}
})
</script>

<template>
	<div class="grid min-h-full place-items-center px-6 py-24 sm:py-32 lg:px-8">
		<div class="text-center">
			<p
				:class="[
					'text-base font-semibold',
					buildPrimaryColorContrastCssClass('text', 'text')
				]"
			>
				{{ state.text }}
			</p>
			<h1 class="mt-4 text-3xl font-bold tracking-tight text-gray-900 sm:text-5xl dark:text-gray-50">
				{{ $l10n.translate(`interface.loading.status.${state.state}.title`) }}
			</h1>
			<p class="mt-6 text-base leading-7 text-gray-600 dark:text-gray-200">
				{{ $l10n.translate(`interface.loading.status.${state.state}.text`) }}
			</p>
		</div>
	</div>
</template>
