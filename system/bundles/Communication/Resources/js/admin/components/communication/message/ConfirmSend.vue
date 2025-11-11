<script lang="ts">
import { defineComponent } from 'vue'
import ButtonComponent from '@Admin/components/ButtonComponent.vue'

export default defineComponent({
	name: "ConfirmSend",
	components: { ButtonComponent },
	props: {
		message: { type: String, required: true },
	},
	emits: ['close'],
})
</script>

<template>
	<div>
		<div class="sm:flex sm:items-start py-6 px-3 ">
			<div class="mt-2">
				<p class="text-sm text-black dark:text-gray-50">
					{{ message }}
				</p>
			</div>
		</div>
		<div class="bg-gray-100/50 dark:bg-gray-900 p-3 rounded-md">
			<div class="flex flex-col sm:flex-row gap-2">
				<div class="flex-none flex gap-2 flex-col sm:flex-row">
					<ButtonComponent @click="$emit('close', false)">
						{{ $l10n.translate('common.cancel') }}
					</ButtonComponent>
				</div>
				<div class="grow flex gap-2 flex-col sm:flex-row justify-end">
					<ButtonComponent
						color="primary"
						@click="$emit('close', true)"
					>
						{{ $l10n.translate('communication.message.btn.send') }}
					</ButtonComponent>
				</div>
			</div>
		</div>
	</div>
</template>