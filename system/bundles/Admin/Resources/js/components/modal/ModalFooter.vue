<script lang="ts">
import { defineComponent, ref, type Ref, type PropType } from 'vue'
import { TransitionRoot as HeadlessUiTransitionRoot } from '@headlessui/vue'
import { Alert } from '../../types/interface'
import AlertMessage from '../AlertMessage.vue'

export default defineComponent({
	name: "ModalFooter",
	components: { AlertMessage, HeadlessUiTransitionRoot },
	props: {
		alerts: { type: Array as PropType<Alert[]>, default: () => [] },
	},
	emits: ['confirm'],
	setup() {
		const containerRef: Ref<HTMLDivElement | null> = ref(null)

		return {
			containerRef,
		}
	}
})
</script>

<template>
	<div
		ref="containerRef"
		class="relative flex-none rounded bg-gray-100/50 flex flex-row gap-1 items-center justify-between"
	>
		<div class="">
			<HeadlessUiTransitionRoot
				as="div"
				:show="alerts.length > 0"
				enter="transform transition duration-300"
				enter-from="translate-y-4 opacity-0"
				enter-to="translate-y-0 opacity-100"
				leave="transform transition duration-300"
				leave-from="translate-y-0 opacity-100"
				leave-to="translate-y-4 opacity-0"
				class="absolute bottom-full left-0 right-0 py-1 space-y-1 overflow-hidden"
			>
				<AlertMessage
					v-for="(alert, index) in alerts"
					:key="`modal-footer-alert-${index}`"
					v-bind="alert"
					class="p-2"
					@confirm="(key: string, confirmed: boolean) => $emit('confirm', key, confirmed)"
				/>
			</HeadlessUiTransitionRoot>
		</div>
		<slot />
	</div>
</template>
