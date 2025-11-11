<script lang="ts">
import { defineComponent, inject, ref, type Ref, type PropType } from 'vue'
import { buildPrimaryColorColorSchemeCssClass, buildPrimaryColorCssClass, buildPrimaryColorElementCssClasses , getPrimaryColorContrastShade } from "@Admin/utils/primarycolor"
import { useTooltip } from '@Admin/composables/tooltip'
import {
	CommunicationMessagesTransportResponse,
	CommunicationMessagesTransportStatus,
	CommunicationPreviewMessage
} from '../../types/communication'
import { Alert } from "@Admin/types/interface"
import MessagePreview from './MessagePreview.vue'
import AlertMessage from '@Admin/components/AlertMessage.vue'

export default defineComponent({
	name: "MessageSelection",
	components: { MessagePreview, AlertMessage },
	props: {
		messages: { type: Array as PropType<CommunicationPreviewMessage[]>, required: true },
	},
	emits: ['close'],
	setup(props) {
		const action: Ref<string|null> = ref(null)
		const alerts: Ref<Alert[]> = ref([])

		const resendMessages = inject<(messageIds: number[]) => Promise<[Error, null, []] | [null, CommunicationMessagesTransportResponse, CommunicationPreviewMessage[]]>>('resendMessages')
		const deleteMessages = inject<(messageIds: number[], force?: boolean) => boolean>('deleteMessages')
		const openMessage = inject<(messageId: number, event?: MouseEvent, force?: boolean) => void>('openMessage')

		if (!deleteMessages || !openMessage || !resendMessages) {
			console.warn('[MessageSelection] missing injected variables')
			return {}
		}

		const { showTooltip } = useTooltip()

		const resendAll = async () => {
			action.value = 'resend'
			alerts.value = []

			const [, response, newMessages] = await resendMessages(props.messages.map(message => message.id))

			action.value = null

			if (response) {
				if (response.status === CommunicationMessagesTransportStatus.ALL_SENT && newMessages[0]) {
					openMessage(newMessages[0].id)
				} else {
					alerts.value = response.messages
						.filter(loop => loop.alerts && loop.alerts.length > 0)
						// @ts-ignore
						.map(loop => loop.alerts)
						.flat(1)
				}
			}
		}

		const deleteAll = async () => {
			action.value = 'delete'
			await deleteMessages(props.messages.map(message => message.id))
			action.value = null
		}

		return {
			action,
			alerts,
			resendAll,
			deleteAll,
			showTooltip,
			buildPrimaryColorColorSchemeCssClass,
			buildPrimaryColorCssClass,
			buildPrimaryColorElementCssClasses,
			getPrimaryColorContrastShade
		}
	}
})
</script>

<template>
	<div class="flex flex-col gap-1">
		<div
			v-show="alerts.length > 0"
			class="flex-none space-y-1"
		>
			<AlertMessage
				v-for="(alert, index) in alerts"
				:key="index"
				v-bind="alert"
				class="flex-none p-2 text-xs"
			/>
		</div>
		<div class="grow flex flex-col gap-1 p-1 rounded-md bg-white">
			<div :class="['font-medium rounded py-1 px-2 flex justify-between items-center', buildPrimaryColorElementCssClasses()]">
				{{ $l10n.translate('communication.messages.selection').replace('%d', messages.length) }}
				<div class="flex flex-row gap-1 items-center">
					<button
						type="button"
						:class="[
							'rounded size-6',
							buildPrimaryColorCssClass('bg', getPrimaryColorContrastShade(), 30),
							buildPrimaryColorCssClass('hover:bg', getPrimaryColorContrastShade(), 40)
						]"
						@click="resendAll()"
						@mouseenter="showTooltip($l10n.translate('communication.message.resend'), $event, 'top')"
					>
						<i :class="[action === 'resend' ? 'fa fa-spinner fa-spin' : 'fas fa-paper-plane']" />
					</button>
					<button
						type="button"
						:class="[
							'rounded size-6',
							buildPrimaryColorCssClass('bg', getPrimaryColorContrastShade(), 30),
							buildPrimaryColorCssClass('hover:bg', getPrimaryColorContrastShade(), 40)
						]"
						@click="deleteAll()"
						@mouseenter="showTooltip($l10n.translate('communication.message.delete'), $event, 'top')"
					>
						<i :class="[action === 'delete' ? 'fa fa-spinner fa-spin' : 'fa fa-trash']" />
					</button>
				</div>
			</div>
			<div class="grow overflow-scroll flex flex-col gap-1">
				<MessagePreview
					v-for="message in messages"
					:key="`selected-${message.id}`"
					:message="message"
					class="border border-gray-100/50"
				/>
			</div>
		</div>
	</div>
</template>