<script lang="ts">
import { defineComponent } from 'vue'
import { buildPrimaryColorColorSchemeCssClass } from "@Admin/utils/primarycolor"
import ButtonComponent from "@Admin/components/ButtonComponent.vue"

export default defineComponent({
	name: "TakeOverAttachments",
	components: { ButtonComponent },
	emits: ['close'],
	setup() {
		return {
			buildPrimaryColorColorSchemeCssClass
		}
	}
})
</script>

<template>
	<div>
		<div class="sm:flex sm:items-start p-3 ">
			<p class="text-sm text-black dark:text-gray-50">
				{{ $l10n.translate('communication.message.new.attachments.take_over.text') }}
			</p>
		</div>
		<div class="bg-gray-100/50 dark:bg-gray-900 p-3 rounded-md">
			<div class="flex flex-col sm:flex-row gap-2">
				<div class="flex-none flex gap-2 flex-col sm:flex-row">
					<ButtonComponent @click="$emit('close')">
						{{ $l10n.translate('communication.message.new.attachments.take_over.no') }}
					</ButtonComponent>
				</div>
				<div class="grow flex gap-2 flex-col sm:flex-row justify-end">
					<ButtonComponent
						color="primary"
						@click="$emit('close', true)"
					>
						{{ $l10n.translate('communication.message.new.attachments.take_over.yes') }}
					</ButtonComponent>
				</div>
			</div>
		</div>
	</div>
</template>