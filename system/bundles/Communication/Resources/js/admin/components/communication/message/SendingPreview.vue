<script lang="ts">
import { defineComponent, inject, computed, ref, type Ref, type ComputedRef, type PropType } from 'vue'
import { ComponentApiInterface } from '@Admin/types/backend/router'
import { buildPrimaryColorElementCssClasses, buildPrimaryColorCssClass, getPrimaryColorContrastShade, getPrimaryColor } from '@Admin/utils/primarycolor'
import ButtonComponent from '@Admin/components/ButtonComponent.vue'
import { CommunicationMessage } from '../../../types/communication'
import MessageView from '../MessageView.vue'

export default defineComponent({
	name: "SendingPreview",
	components: { MessageView, ButtonComponent },
	props: {
		api: { type: Object as PropType<ComponentApiInterface>, required: true },
		channel: { type: String, required: true },
		total: { type: Number, required: true },
		messages: { type: Array as PropType<CommunicationMessage[]>, required: true },
	},
	emits: ['close', 'send'],
	setup(props) {
		const currentMessageIndex: Ref<number> = ref(1)

		const currentMessage: ComputedRef<CommunicationMessage> = computed(() => props.messages[(currentMessageIndex.value - 1)])

		const channelConfig = inject('channelConfig')

		const primaryColor = getPrimaryColor()

		return {
			primaryColor,
			currentMessageIndex,
			currentMessage,
			channelConfig,
			buildPrimaryColorElementCssClasses,
			buildPrimaryColorCssClass,
			getPrimaryColorContrastShade
		}
	}
})
</script>

<template>
	<div class="flex flex-col gap-1">
		<nav class="flex-none flex flex-row gap-1 flex-wrap">
			<button
				v-for="index in messages.length"
				:key="index"
				:class="[
					'rounded-md px-2 py-1 text-xs font-medium whitespace-nowrap',
					(index === currentMessageIndex) ?
						buildPrimaryColorElementCssClasses() :
						'text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 hover:text-gray-500',
				]"
				@click="currentMessageIndex = index"
			>
				{{ $l10n.translate('communication.message.preview.message_label') }} {{ index }}
			</button>
			<div
				v-show="total > messages.length"
				class="font-semibold rounded-md px-2 py-1 text-xs font-medium whitespace-nowrap text-gray-700 bg-gray-100"
			>
				+{{ total - messages.length }}
			</div>
		</nav>
		<div class="grow bg-white p-1 rounded-md">
			<MessageView
				:api="api"
				:config="channelConfig(channel)"
				:message="currentMessage"
				:show-actions="false"
			/>
		</div>
		<div class="flex-none bg-white p-1 rounded-md">
			<div class="flex-none rounded bg-gray-100/50 p-1 flex flex-row gap-1 items-center justify-between">
				<div>
					<ButtonComponent
						@click="$emit('close')"
					>
						{{ $l10n.translate('communication.message.btn.back') }}
					</ButtonComponent>
				</div>
				<div class="flex flex-row items-center gap-1">
					<ButtonComponent
						color="primary"
						@click="$emit('send')"
					>
						<span
							:class="[
								'rounded px-1',
								buildPrimaryColorCssClass('bg', getPrimaryColorContrastShade(), 60),
								(primaryColor.base <= 100) ? 'text-black' : 'text-white'
							]"
						>
							{{ total }}
						</span>
						{{ $l10n.translate('communication.message.btn.send') }}
					</ButtonComponent>
				</div>
			</div>
		</div>
	</div>
</template>