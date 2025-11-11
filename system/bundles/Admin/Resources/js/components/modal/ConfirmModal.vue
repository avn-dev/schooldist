<script lang="ts">
import { defineComponent } from 'vue'
import ButtonComponent from '../ButtonComponent.vue'

export default defineComponent({
	name: "ConfirmModal",
	components: { ButtonComponent },
	props: {
		message: { type: String, default: null },
	},
	emits: ['close'],
})
</script>

<template>
	<div class="p-2 space-y-2">
		<p
			v-if="message"
			class="text-sm py-2"
		>
			{{ message }}
		</p>
		<div class="flex flex-row items-center gap-2 justify-end">
			<ButtonComponent @click="$emit('close', false)">
				{{ $l10n.translate('common.cancel') }}
			</ButtonComponent>
			<ButtonComponent
				color="primary"
				@click="$emit('close', true)"
			>
				{{ $l10n.translate('common.confirm') }}
			</ButtonComponent>
		</div>
	</div>
</template>
