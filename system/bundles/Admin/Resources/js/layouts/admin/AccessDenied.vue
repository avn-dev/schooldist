<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import { ComponentApiInterface } from '../../types/backend/router'
import RoundedBox from '../../components/RoundedBox.vue'

export default defineComponent({
	name: "AccessDenied",
	components: { RoundedBox },
	props: {
		api: { type: Object as PropType<ComponentApiInterface>, required: true }
	},
})
</script>

<template>
	<RoundedBox>
		<div class="sm:flex sm:items-start px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
			<div class="mx-auto flex size-12 shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:size-10">
				<i class="fa fa-exclamation-triangle text-red-600" />
			</div>
			<div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
				<h3 class="text-base font-semibold text-gray-900">
					{{ $l10n.translate('access.denied.heading') }}
				</h3>
				<div class="mt-2">
					<p class="text-sm text-gray-500">
						{{ $l10n.translate('access.denied.text') }}
					</p>
				</div>
			</div>
		</div>
	</RoundedBox>
</template>
