<script lang="ts">
import { defineComponent, inject, ref, type Ref, type PropType } from 'vue'
import { ComponentApiInterface, RouterAction } from '@Admin/types/backend/router'
import { useTooltip } from '@Admin/composables/tooltip'
import { useFileViewer } from '@Admin/composables/file_viewer'
import {
	CommunicationChannelConfig,
	CommunicationMessage, CommunicationMessagesTransportResponse, CommunicationMessagesTransportStatus,
	CommunicationNewMessageConfig, CommunicationPreviewMessage
} from '../../types/communication'
import { hasAction } from "../../utils/communication"
import { safe } from '@Admin/utils/promise'
import { Alert } from "@Admin/types/interface"
import AlertMessage from '@Admin/components/AlertMessage.vue'
import Badge from '@Admin/components/Badge.vue'
import router from '@Admin/router'

export default defineComponent({
	name: "MessagePreview",
	components: { AlertMessage, Badge },
	props: {
		api: { type: Object as PropType<ComponentApiInterface>, required: true },
		config: { type: Object as PropType<CommunicationChannelConfig>, required: true },
		message: { type: Object as PropType<CommunicationMessage>, required: true },
		assignable: { type: Boolean, default: false },
		showActions: { type: Boolean, default: true }
	},
	setup(props) {
		const loading: Ref<boolean> = ref(false)
		const sending: Ref<boolean> = ref(false)
		const alerts: Ref<Alert[]> = ref([])

		const { showTooltip } = useTooltip()
		const { openFile } = useFileViewer()

		const categoryColor = inject<(categoryId: number) => string>('categoryColor')
		const newMessage = inject<(event: MouseEvent, config?: CommunicationNewMessageConfig) => void>('newMessage')
		const resendMessages = inject<(messageIds: number[]) => Promise<[Error, null, []] | [null, CommunicationMessagesTransportResponse, CommunicationPreviewMessage[]]>>('resendMessages')
		const deleteMessages = inject<(messageIds: number[], force?: boolean) => boolean>('deleteMessages')
		/* eslint-disable @typescript-eslint/no-explicit-any */
		const loadMessages = inject<(params?: Record<string, any>, reset?: boolean) => void>('load')
		const openMessage = inject<(messageId: number, event?: MouseEvent, force?: boolean) => void>('openMessage')
		const resetView = inject<() => void>('resetView')

		if (!categoryColor || !newMessage || !deleteMessages || !loadMessages || !openMessage || !resendMessages || !resetView) {
			console.warn('[MessageView] missing injected variables')
			return {}
		}

		const openNewMessage = async (event: MouseEvent, action: string, channel?: string) => {
			newMessage(event, {
				channel: channel ?? props.message.channel,
				related: { action, message: props.message },
			})
		}

		const resend = async () => {
			alerts.value = []
			sending.value = true

			const [, response, newMessages] = await resendMessages([props.message.id])

			sending.value = false

			if (response) {
				if (response.status === CommunicationMessagesTransportStatus.ALL_SENT && newMessages[0]) {
					openMessage(newMessages[0].id)
				}else {
					alerts.value = response.messages
						.filter(loop => loop.alerts && loop.alerts.length > 0)
						// @ts-ignore
						.map(loop => loop.alerts)
						.flat(1)
				}
			}
		}

		const allocateMessage = async () => {
			const [, response] = await safe<{ action: RouterAction }>(props.api.action('allocateMessage', {
				method: 'get',
				params: {
					message_id: props.message.id
				}
			}))

			if (response) {
				const success = await router.action(response.action)
				if (success === true) {
					await openMessage(props.message.id, undefined, true)
				}
			}
		}

		return {
			loading,
			sending,
			alerts,
			categoryColor,
			hasAction,
			resend,
			showTooltip,
			openNewMessage,
			deleteMessages,
			allocateMessage,
			openFile,
			resetView
		}
	}
})
</script>

<template>
	<div class="flex flex-col gap-1 flex-none h-full">
		<AlertMessage
			v-for="(alert, index) in alerts"
			:key="index"
			v-bind="alert"
			class="flex-none p-2 text-xs"
		/>
		<div class="flex-grow rounded-t-md bg-white p-1">
			<div class="flex flex-col h-full">
				<div
					v-if="showActions"
					class="flex-none font-medium text-gray-500 flex flex-row items-center gap-1 border-b border-gray-50 pb-1"
				>
					<button
						type="button"
						class="lg:hidden size-7 rounded disabled:opacity-50 hover:bg-gray-100/50"
						:disabled="loading"
						@click="resetView"
						@mouseenter="showTooltip($l10n.translate('common.close'), $event, 'top')"
					>
						<i class="fa fa-times" />
					</button>
					<div
						class="lg:hidden h-6 w-px bg-gray-50"
						aria-hidden="true"
					/>
					<button
						v-show="hasAction(config, 'reply', message)"
						type="button"
						class="size-7 rounded hover:bg-gray-100/50 disabled:opacity-50"
						:disabled="loading"
						@click="openNewMessage($event, 'reply')"
						@mouseenter="showTooltip($l10n.translate('communication.message.reply'), $event, 'top')"
					>
						<i class="fas fa-reply" />
					</button>
					<button
						v-show="hasAction(config, 'reply_all', message)"
						type="button"
						class="size-7 rounded hover:bg-gray-100/50 disabled:opacity-50"
						:disabled="loading"
						@click="openNewMessage($event, 'reply_all')"
						@mouseenter="showTooltip($l10n.translate('communication.message.reply_all'), $event, 'top')"
					>
						<i class="fas fa-reply-all" />
					</button>
					<button
						v-show="hasAction(config, 'forward', message)"
						type="button"
						class="size-7 rounded hover:bg-gray-100/50 disabled:opacity-50"
						:disabled="loading"
						@click="openNewMessage($event, 'forward', 'mail')"
						@mouseenter="showTooltip($l10n.translate('communication.message.forward'), $event, 'top')"
					>
						<i class="fas fa-share" />
					</button>
					<div
						v-show="hasAction(config, 'resend', message)"
						class="h-6 w-px bg-gray-50"
						aria-hidden="true"
					/>
					<button
						v-show="hasAction(config, 'resend', message)"
						type="button"
						class="size-7 rounded hover:bg-gray-100/50 disabled:opacity-50"
						:disabled="loading"
						@mouseenter="showTooltip($l10n.translate((message.draft) ? 'communication.message.btn.send' : 'communication.message.resend'), $event, 'top')"
						@click="resend"
					>
						<i
							v-if="sending"
							class="fa fa-spinner fa-spin"
						/>
						<i
							v-else
							class="fas fa-paper-plane"
						/>
					</button>
					<div
						v-show="assignable"
						class="h-6 w-px bg-gray-50"
						aria-hidden="true"
					/>
					<button
						v-show="assignable"
						type="button"
						class="size-7 rounded hover:bg-gray-100/50 disabled:opacity-50"
						:disabled="loading"
						@click="allocateMessage(message.id)"
						@mouseenter="showTooltip($l10n.translate('communication.message.assign'), $event, 'top')"
					>
						<i class="fa fa-share-alt" />
					</button>
					<div
						v-show="assignable && hasAction(config, 'delete', message)"
						class="h-6 w-px bg-gray-50"
						aria-hidden="true"
					/>
					<button
						v-show="hasAction(config, 'delete', message)"
						type="button"
						class="size-7 rounded hover:bg-gray-100/50 disabled:opacity-50"
						:disabled="loading"
						@click="deleteMessages([message.id])"
						@mouseenter="showTooltip($l10n.translate('communication.message.delete'), $event, 'top')"
					>
						<i class="far fa-trash-alt" />
					</button>
					<!--<div
						v-show="hasAction(config, 'delete', message)"
						class="h-6 w-px bg-gray-50"
						aria-hidden="true"
					/>
					<button
						v-show="hasAction(config, 'observe', message)"
						type="button"
						class="size-7 rounded hover:bg-gray-100/50 disabled:opacity-50"
						:disabled="loading"
						@mouseenter="showTooltip($l10n.translate('communication.message.observe'), $event, 'top')"
					>
						<i class="fas fa-bell-slash" />
					</button>-->
				</div>
				<div class="grow overflow-hidden rounded">
					<div class="h-full flex flex-col gap-1">
						<div class="flex-none flex flex-row items-center gap-2 px-1 pt-1">
							<div class="flex-none">
								<span class="inline-flex size-8 items-center justify-center rounded-full text-gray-500 bg-gray-100">
									<span class="text-sm">
										<i class="fa fa-user" />
									</span>
								</span>
							</div>
							<div class="grow">
								<div class="">
									<span class="text-gray-800 font-semibold">
										{{ message.from }}
									</span>
								</div>
								<div class="text-gray-500">
									<span class="font-semibold">
										{{ $l10n.translate('communication.message.to') }}:
									</span>
									{{ message.to }}
								</div>
								<div
									v-if="message.cc"
									class="text-gray-500"
								>
									<span class="font-semibold">
										{{ $l10n.translate('communication.message.cc') }}:
									</span>
									{{ message.cc }}
								</div>
								<div
									v-if="message.bcc"
									class="text-gray-500"
								>
									<span class="font-semibold">
										{{ $l10n.translate('communication.message.bcc') }}:
									</span>
									{{ message.bcc }}
								</div>
							</div>
							<div class="flex-none place-self-start">
								<span class="flex flex-row items-center gap-1 text-gray-500">
									<i
										v-for="categoryId in message.categories"
										:key="categoryId"
										class="fas fa-flag"
										:style="{'color': categoryColor(categoryId)}"
									/>
									<i
										v-if="message.event"
										class="fas fa-link text-gray-400"
										@mouseenter="showTooltip(`${$l10n.translate('communication.message.event')}: ${message.event}`, $event, 'top')"
									/>
									<i :class="['text-gray-400', config.icon]" />
									{{ message.date }}
								</span>
							</div>
						</div>
						<div
							v-if="message.flags.length > 0"
							class="flex flex-row items-center gap-0.5"
						>
							<Badge
								v-for="flag in message.flags"
								:key="flag"
								color="primary"
								class="px-1 font-medium"
							>
								{{ flag }}
							</Badge>
						</div>
						<div
							v-if="['mail', 'notice'].includes(message.channel)"
							class="flex-none text-sm text-gray-800 border-b border-gray-50 p-1"
						>
							<span
								v-if="message.subject"
								class="font-medium"
							>
								<span
									v-if="message.draft"
									class="text-red-400"
								>
									[{{ $l10n.translate('communication.message.draft') }}]
								</span>
								{{ message.subject }}
							</span>
							<span
								v-else
								class="text-gray-300"
							>
								&#60;{{ $l10n.translate('communication.message.no_subject') }}&#62;
							</span>
						</div>
						<div class="grow rounded overflow-hidden">
							<div
								v-show="message.errors.length > 0"
								class="flex flex-col gap-1"
							>
								<AlertMessage
									v-for="(error, index) in message.errors"
									:key="index"
									v-bind="error"
									class="p-2 text-xs"
								/>
							</div>
							<iframe
								v-if="message.content"
								class="h-full w-full"
								:srcdoc="message.content"
								sandbox=""
							/>
							<span
								v-else-if="message.errors.length === 0"
								class="text-gray-300"
							>
								&#60;{{ $l10n.translate('communication.message.no_content') }}&#62;
							</span>
						</div>
					</div>
				</div>
				<div
					v-if="message.attachments.length > 0"
					class="flex-none rounded grid grid-cols-12 gap-1 mt-1"
				>
					<div
						v-for="attachment in message.attachments"
						:key="attachment.file"
						class="group col-span-3 rounded p-1 border border-gray-100/50 hover:bg-gray-100/50 cursor-pointer"
						@click="openFile(attachment.file)"
					>
						<div class="flex flex-row items-center gap-1.5">
							<span class="flex-none inline-flex size-8 items-center justify-center bg-gray-100/50 group-hover:bg-gray-200/50 rounded">
								<i :class="[attachment.icon, 'text-gray-400 text-base']" />
							</span>
							<div class="grow truncate">
								<div class="text-gray-800 font-medium truncate">
									{{ attachment.file_name }}
								</div>
								<div class="text-gray-500">
									{{ attachment.file_size }}
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>