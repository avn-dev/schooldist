<script lang="ts">
import { defineComponent } from 'vue3'
import { buildPrimaryColorElementCssClasses } from "../../../utils/primarycolor"
import { TabOpening } from "../../../types/backend/app"
import ButtonComponent from '../../../components/ButtonComponent.vue'
import ModalFooter from '../../../components/modal/ModalFooter.vue'

export default defineComponent({
	name: "ExistingTab",
	components: { ModalFooter, ButtonComponent },
	emits: ['close'],
	setup() {

		return {
			TabOpening,
			buildPrimaryColorElementCssClasses
		}
	}
})
</script>

<template>
	<div>
		<div class="sm:flex sm:items-start py-6 px-3">
			<div class="mt-2">
				<p class="text-sm text-black dark:text-gray-50">
					{{ $l10n.translate('tabs.existing.text') }}
				</p>
			</div>
		</div>
		<ModalFooter class="p-3">
			<div class="flex-none flex gap-2 flex-col sm:flex-row">
				<ButtonComponent @click="$emit('close')">
					{{ $l10n.translate('common.cancel') }}
				</ButtonComponent>
			</div>
			<div class="grow flex gap-2 flex-col sm:flex-row justify-end">
				<ButtonComponent @click="$emit('close', TabOpening.new)">
					{{ $l10n.translate('tabs.existing.open_new') }}
				</ButtonComponent>
				<ButtonComponent @click="$emit('close', TabOpening.reload)">
					{{ $l10n.translate('tabs.existing.reload_existing') }}
				</ButtonComponent>
				<ButtonComponent
					color="primary"
					@click="$emit('close', TabOpening.existing)"
				>
					{{ $l10n.translate('tabs.existing.open_existing') }}
				</ButtonComponent>
			</div>
		</ModalFooter>
	</div>
</template>
