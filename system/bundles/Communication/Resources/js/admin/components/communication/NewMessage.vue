<script lang="ts">
import {
	defineComponent,
	inject,
	nextTick,
	onBeforeUnmount,
	onMounted,
	computed,
	reactive,
	ref,
	type PropType,
	type Ref
} from 'vue'
import { ComponentApiInterface, ContentType } from '@Admin/types/backend/router'
import { ComponentSize } from "@Admin/types/backend/app"
import { Alert } from "@Admin/types/interface"
import { SelectOption as SelectOptionType, SelectOptionValueType } from '@Admin/types/common'
import { useTooltip } from '@Admin/composables/tooltip'
import { useFileViewer } from '@Admin/composables/file_viewer'
import { GuiInstance } from "@Gui2/types/gui"
import {
	buildPrimaryColorCssClass,
	buildPrimaryColorElementCssClasses,
	getPrimaryColor,
	getPrimaryColorContrastShade
} from "@Admin/utils/primarycolor"
import { safe } from '@Admin/utils/promise'
import { formatFileSize } from '@Admin/utils/files'
import {
	CommunicationChannelConfig,
	CommunicationContact, CommunicationContentType,
	CommunicationMessage,
	CommunicationMessageAttachment, CommunicationMessageDirection,
	CommunicationMessageForm,
	CommunicationMessagesTransportResponse,
	CommunicationMessagesTransportStatus,
	CommunicationPreviewMessage,
	CommunicationRelatedMessageParams
} from '../../types/communication'
import { matchAttachmentSelection } from "../../utils/communication"
import ButtonComponent from '@Admin/components/ButtonComponent.vue'
import AlertMessage from '@Admin/components/AlertMessage.vue'
import UploadField from '@Admin/components/form/UploadField.vue'
import ModalFooter from '@Admin/components/modal/ModalFooter.vue'
import RecipientSelection from './message/RecipientSelection.vue'
import AttachmentSelection from './message/AttachmentSelection.vue'
import SendingPreview from './message/SendingPreview.vue'
import ConfirmSend from './message/ConfirmSend.vue'
import router from '@Admin/router'
import l10n from '@Admin/l10n'

type InterfaceResponse = {
	templates: SelectOptionType[],
	languages: string[],
	attachments: []
	flags: [],
	identities?: SelectOptionType[],
	message: Partial<CommunicationMessageForm>
	alerts?: Alert[]
}

type PrepareSendingResponse = InterfaceResponse & {
	messages: number,
	invalid_recipients: { to?: string[], cc?: string[], bcc?: string[] }
}

export default defineComponent({
	name: "NewMessage",
	components: { SendingPreview, ButtonComponent, AlertMessage, RecipientSelection, AttachmentSelection, UploadField, ModalFooter },
	props: {
		api: { type: Object as PropType<ComponentApiInterface>, required: true },
		channel: { type: String, required: true },
		config: { type: Object as PropType<CommunicationChannelConfig>, required: true },
		multiple: { type: Boolean, default: false },
		related: { type: Object as PropType<CommunicationRelatedMessageParams|null>, default: null },
		identities: { type: Array as PropType<SelectOptionType[]>, default: () => [] },
		templates: { type: Array as PropType<SelectOptionType[]>, default: () => [] },
		languages: { type: Array as PropType<string[]>, default: () => [] },
		contacts: { type: Object as PropType<CommunicationContact[]|null>, default: null },
		alerts: { type: Array as PropType<Alert[]>, default: () => [] },
		message: { type: Object as PropType<CommunicationMessageForm|null>, default: null },
		flags: { type: Array as PropType<SelectOptionType[]>, default: () => [] },
		attachments: { type: Array as PropType<CommunicationMessageAttachment[]>, default: () => [] },
		gui2: { type: Object as PropType<GuiInstance | null>, default: null }
	},
	emits: ['close'],
	setup(props) {
		const contentRef: Ref<HTMLElement|null> = ref(null)
		const loading: Ref<boolean> = ref(false)
		const sending: Ref<boolean> = ref(false)
		const messageCount: Ref<number> = ref(0)
		const ccEnabled: Ref<boolean> = ref((props.message?.cc ?? []).length > 0)
		const bccEnabled: Ref<boolean> = ref((props.message?.bcc ?? []).length > 0)
		const flagsOpen: Ref<boolean> = ref(false)
		const attachmentsOpen: Ref<boolean> = ref(false)
		const previewEnabled: Ref<boolean> = ref(false)
		const previewMessagesTotal: Ref<number> = ref(0)
		const previewMessages: Ref<CommunicationMessage[]> = ref([])
		const localAlerts: Ref<Alert[]> = ref(props.alerts)
		const localIdentities: Ref<SelectOptionType[]> = ref(props.identities)
		const localTemplates: Ref<SelectOptionType[]> = ref(props.templates)
		const localLanguages: Ref<string[]> = ref(props.languages)
		const localAttachments: Ref<CommunicationMessageAttachment[]> = ref(props.attachments)
		const localFlags: Ref<SelectOptionType[]> = ref(props.flags)
		const form = reactive<CommunicationMessageForm>({
			id: props.message?.id ?? '',
			direction: CommunicationMessageDirection.out,
			content_type: props.message?.content_type ?? CommunicationContentType.text,
			from: props.message?.from ?? 0,
			to: props.message?.to ?? [],
			cc: props.message?.cc ?? [],
			bcc: props.message?.bcc ?? [],
			subject: props.message?.subject ?? '',
			content: props.message?.content ?? '',
			flags: props.message?.flags ?? [],
			attachments: props.message?.attachments ?? [],
			template_id: 0,
			language: props.message?.language ?? 'en',
			send_individually: props.message?.send_individually ?? true,
			files: props.message?.files ?? [],
			confirmed_errors: []
		})

		const attachmentsCount = computed(() => selectedAttachments.value.length + form.files.length)
		const selectedAttachments = computed(() => {
			const attachmentKeys: SelectOptionValueType[] = form.attachments.map((loop: SelectOptionType) => loop.value)
			return matchAttachmentSelection(attachmentKeys, localAttachments.value)
		})

		let contentTinyMCE: string|null = null

		const primaryColor = getPrimaryColor()
		const { showTooltip } = useTooltip()
		const { openFile } = useFileViewer()
		const setError = inject<(error: Error) => void>('setError')
		const resetView = inject<() => void>('resetView')
		/* eslint-disable @typescript-eslint/no-explicit-any */
		const reloadMessages = inject<(params?: Record<string, any>, reset?: boolean) => void>('load')
		const openMessage = inject<(messageId: number, event?: MouseEvent, force?: boolean) => void>('openMessage')
		const addMessages = inject<(messages: CommunicationPreviewMessage[]) => void>('addMessages')
		const reloadGui2 = inject<() => void>('reloadGui2')

		if (!setError || !resetView || !reloadMessages || !openMessage || !addMessages || !reloadGui2) {
			console.warn('[NewMessage] missing injected variables')
			return {}
		}

		const init = async () => {
			if (form.content_type === 'html') {
				await initHtml()
			} else {
				await initText()
			}

			if (form.to.length > 0 || form.cc.length > 0 || form.bcc.length > 0) {
				await prepareSending()
			}
		}

		const initHtml = async () => {

			let editor

			if (contentTinyMCE !== null) {
				// @ts-ignore
				editor = window.tinymce.get(contentTinyMCE)
			} else if (contentRef.value) {
				// @ts-ignore
				const editors = await window.tinymce.init({
					target: contentRef.value.getElementsByTagName('textarea')[0],
					mode: "exact",
					theme: "modern",
					skin: "lightgray",
					plugins: [
						"advlist autolink lists link image charmap print preview hr anchor pagebreak",
						"searchreplace wordcount visualblocks visualchars code fullscreen",
						"insertdatetime media nonbreaking save table contextmenu directionality",
						"emoticons template paste textcolor colorpicker textpattern"
					],
					menubar: false,
					toolbar1: 'undo redo | styleselect | searchreplace pastetext visualblocks visualchars | bold italic underline | alignleft aligncenter alignright | forecolor | bullist numlist outdent indent | preview code fullscreen',
					toolbar2: "backcolor | link | charmap table",
					toolbar_items_size: 'small',
					forced_root_block: false,
					verify_html: false,
					convert_urls: false,
					remove_script_host: true,
					resize: false,
				})

				editor = editors[0] ?? null
			}

			if (editor) {
				contentTinyMCE = editor.id
				editor.setContent(form.content, { format : 'html' })

				setTimeout(() => resizeHtmlEditor(), 1)

			} else {
				console.error('Unable to init HTML editor')
			}

		}

		const initText = async () => {
			if (contentTinyMCE !== null) {
				// @ts-ignore
				const editor = window.tinymce.get(contentTinyMCE)
				editor.save()
				editor.remove()
				editor.destroy()
			}

			contentTinyMCE = null

			await nextTick()
		}

		const resizeHtmlEditor = async () => {

			if (form.content_type !== 'html' || !contentTinyMCE || !contentRef.value) {
				console.warn(`No HTML editor available for resizing`)
				return
			}

			await nextTick()

			const editorBody = contentRef.value.getElementsByTagName('iframe')[0]

			const fullHeight = contentRef.value.getBoundingClientRect().height
			const editorHeight = contentRef.value.getElementsByClassName('mce-tinymce')[0].getBoundingClientRect().height
			const editorBodyHeight = editorBody.getBoundingClientRect().height // iframe

			editorBody.style.height = `${fullHeight - editorHeight + editorBodyHeight - 1}px`
		}

		const toggleSendIndividually = async () => {
			form.send_individually = !form.send_individually

			await resizeHtmlEditor()
			await prepareSending()
		}

		const toggleFlagsContainer = async () => {
			if (flagsOpen.value === false && attachmentsOpen.value === true) {
				attachmentsOpen.value = false
			}

			flagsOpen.value = !flagsOpen.value
			await resizeHtmlEditor()
		}

		const toggleAttachmentsContainer = async () => {
			if (attachmentsOpen.value === false && flagsOpen.value === true) {
				flagsOpen.value = false
			}
			attachmentsOpen.value = !attachmentsOpen.value
			await resizeHtmlEditor()
		}

		const prepareSending = async () => {

			loading.value = true

			const formPayload = buildFormPayload(['to', 'cc', 'bcc', 'send_individually', 'template_id', 'language', 'content_type', 'content'])

			const [, response] = await safe<PrepareSendingResponse>(props.api.action('prepareSending', {
				method: 'post',
				data: {
					channel: props.channel,
					...buildRelatedMessageParameters(),
					...formPayload,
				}
			}))

			if (response) {
				messageCount.value = response.messages

				await setDefaultNewMessagePayload(response)

				Object.keys(response.invalid_recipients).forEach(field => {
					const typedField = field as keyof CommunicationMessageForm
					if (['to', 'cc', 'bcc'].includes(typedField) && form[typedField]) {
						// @ts-ignore TODO
						for (const [index, recipient] of Object.entries(form[typedField])) {
							const typedRecipient = recipient as SelectOptionType
							// @ts-ignore TODO
							form[typedField][index].error = response.invalid_recipients[typedField].includes(typedRecipient.value) || response.invalid_recipients[typedField].includes(typedRecipient.text)
						}
					}
				})
			} else {
				messageCount.value = 0
			}

			loading.value = false
		}

		const send = async () => {

			if (previewEnabled.value) {
				previewEnabled.value = false
			}

			const formData = buildFormDataPayload()

			let send: boolean|null = true
			if (form.subject.length === 0) {
				const message = l10n.translate('communication.message.send.confirm.empty_subject')

				send = await router.openModal<boolean|null>({
					title: l10n.translate('communication.message.send.confirm.title'),
					content: { type: ContentType.component, payload: { component: ConfirmSend, payload: { message } } },
					size: ComponentSize.small,
					closable: true
				})
			}

			if (send) {
				sending.value = true

				if (localAlerts.value.length > 0) {
					localAlerts.value = []
					await resizeHtmlEditor()
				}

				const [, response] = await safe<CommunicationMessagesTransportResponse>(props.api.action('send', {
					method: 'post',
					headers: {
						'Content-Type': 'multipart/form-data'
					},
					params: {
						channel: props.channel,
						...buildRelatedMessageParameters(),
					},
					data: formData
				}))

				sending.value = false

				if (response) {

					const newMessages: CommunicationPreviewMessage[] = response.messages
						.filter(loop => loop.messages && loop.messages.length > 0)
						// @ts-ignore
						.map(loop => loop.messages)
						.flat(1)

					if (newMessages.length > 0) {
						await addMessages(newMessages)
					}

					//await reloadMessages()
					reloadGui2()

					if (response.status === CommunicationMessagesTransportStatus.ALL_SENT && newMessages[0]) {
						openMessage(newMessages[0].id)
					} else {
						previewEnabled.value = false
						localAlerts.value = response.messages
							.filter(loop => loop.alerts && loop.alerts.length > 0)
							// @ts-ignore
							.map(loop => loop.alerts)
							.flat(1)

						await resizeHtmlEditor()
					}
				}
			}
		}

		const changeLanguage = async (language: string) => {
			form.language = language

			if (form.template_id > 0) {
				await loadTemplate()
			} else {
				await prepareSending()
			}
		}

		const reloadSignature = async () => {

			if (props.multiple || form.content_type === 'text') {
				return
			}

			loading.value = true

			const formPayload = buildFormPayload(['from', 'language', 'content_type', 'content'])

			const [, response] = await safe<{ content: string }>(props.api.action('reloadSignature', {
				method: 'post',
				data: {
					channel: props.channel,
					...formPayload,
					...buildRelatedMessageParameters()
				}
			}))

			if (response) {
				form.content = response.content
				await initHtml()
			}

			loading.value = false
		}

		const loadTemplate = async () => {
			loading.value = true

			if (localAlerts.value.length > 0) {
				localAlerts.value = []
				await resizeHtmlEditor()
			}

			form.to = form.to.filter((loop: SelectOptionType) => !loop.additional?.template)
			form.cc = form.cc.filter((loop: SelectOptionType) => !loop.additional?.template)
			form.bcc = form.bcc.filter((loop: SelectOptionType) => !loop.additional?.template)

			const formPayload = buildFormPayload(['from', 'to', 'cc', 'bcc', 'template_id', 'language', 'content_type'])

			const [error, response] = await safe<InterfaceResponse>(props.api.action('loadTemplate', {
				method: 'post',
				data: {
					channel: props.channel,
					...formPayload,
					...buildRelatedMessageParameters()
				}
			}))

			if (response) {
				form.attachments = form.attachments.filter((attachment: SelectOptionType) => attachment.additional?.source !== 'template')
				form.flags = []

				if (response.alerts) {
					localAlerts.value = response.alerts
				}

				await setDefaultNewMessagePayload(response)

				if (form.content_type === 'html') {
					await initHtml()
				} else {
					await initText()
				}
			} else {
				setError(error)
			}

			loading.value = false
		}

		const removeAttachment = async (attachment: CommunicationMessageAttachment) => {

			const existingIndex = form.attachments.findIndex((loop: SelectOptionType) => loop.value === attachment.key)

			if (existingIndex >= 0) {
				form.attachments.splice(existingIndex, 1)
			}

			await resizeHtmlEditor()
		}

		const removeFile = async (file: File, index: number) => {
			form.files.splice(index, 1)
			await resizeHtmlEditor()
		}

		const toggleFlag = async (flag: SelectOptionType) => {
			const flagKey = flag.value as string

			if (form.flags.includes(flagKey)) {
				const index = form.flags.indexOf(flagKey)
				form.flags.splice(index, 1)
			} else {
				form.flags.push(flagKey)
			}

			await resizeHtmlEditor()
		}

		const loadSendingPreview = async () => {
			previewMessagesTotal.value = 0
			previewMessages.value = []
			loading.value = true

			const formData = buildFormDataPayload()

			const [, response] = await safe<{ total: number, messages: CommunicationMessage[] }>(props.api.action('previewSending', {
				method: 'post',
				headers: {
					'Content-Type': 'multipart/form-data'
				},
				params: {
					channel: props.channel,
					...buildRelatedMessageParameters(),
				},
				data: formData
			}))

			if (response) {
				previewMessagesTotal.value = response.total
				previewMessages.value = response.messages
				previewEnabled.value = true
			}

			loading.value = false
		}

		const setDefaultNewMessagePayload = async (payload: InterfaceResponse) => {
			localTemplates.value = payload.templates
			localLanguages.value = payload.languages
			localFlags.value = payload.flags

			if (payload.identities) {
				localIdentities.value = payload.identities

				const existing = payload.identities.find((loop: SelectOptionType) => loop.value === form.from)
				if (!existing) {
					form.from = payload.identities[0].value
				}
			}

			await syncFormWithPayload(payload.message)

			const customAttachments = localAttachments.value.filter((loop: CommunicationMessageAttachment) => loop.key.substring(0, 8) === 'custom::')
			const newAttachments: CommunicationMessageAttachment[] = [...payload.attachments, ...customAttachments]

			const possibleAttachments = [
				'all',
				...newAttachments.map(attachment => attachment.groups).flat(),
				...newAttachments.map(attachment => attachment.key)
			]

			form.flags = form.flags.filter((key: string) => localFlags.value.findIndex((loop: SelectOptionType) => loop.value === key) !== -1)
			form.attachments = form.attachments.filter((attachment) => possibleAttachments.findIndex((loop) => loop === attachment.value) !== -1)

			localAttachments.value = newAttachments

			await resizeHtmlEditor()
		}

		// TODO Types
		const syncFormWithPayload = async (payload: Partial<CommunicationMessageForm>) => {
			let reloadTemplate = false
			let reloadPrepareSending = false

			Object.keys(payload).forEach((field) => {

				const typedField = field as keyof CommunicationMessageForm

				if (!reloadTemplate && ['language', 'template_id'].includes(typedField) && form[typedField] !== payload[typedField]) {
					reloadTemplate = form.template_id > 0
				}

				if (['to', 'cc', 'bcc'].includes(typedField)) {
					(payload[typedField] as SelectOptionType[]).forEach((recipient: SelectOptionType) => {
						const target = form[typedField] as SelectOptionType[]
						const existing = target.find((loop: SelectOptionType) => loop.value === recipient.value)
						if (!existing) {
							target.push({ value: (target.length + 1) * -1, text: recipient.text, additional: recipient.additional })
						}
					})

					reloadPrepareSending = true
				} else if (typedField === 'subject' && form.subject.length === 0) {
					form[typedField] = payload[typedField] ?? ''
				} else if (typedField === 'attachments') {
					(payload[typedField] as SelectOptionType[]).forEach((value: SelectOptionType) => {
						const target = form[typedField] as SelectOptionType[]
						const existing = target.find((loop: SelectOptionType) => loop.value === value.value)
						if (!existing) {
							target.push(value)
						}
					})
				} else if (typedField === 'flags') {
					(payload[typedField] as string[]).forEach((key: string) => {
						const existing = form.flags.find((loop: string) => loop === key)
						if (!existing) {
							form.flags.push(key)
						}
					})
				} else {
					// @ts-ignore TODO
					form[typedField] = payload[typedField]
				}
			})

			if (form.cc.length > 0 && ccEnabled.value === false) {
				ccEnabled.value = true
			} else if (form.cc.length === 0 && ccEnabled.value === true) {
				ccEnabled.value = false
			}

			if (form.bcc.length > 0 && bccEnabled.value === false) {
				bccEnabled.value = true
			} else if (form.bcc.length === 0 && bccEnabled.value === true) {
				bccEnabled.value = false
			}

			if (form.content_type === 'html') {
				await initHtml()
			} else {
				await initText()
			}

			if (reloadPrepareSending) {
				await prepareSending()
			}

			if (reloadTemplate) {
				await loadTemplate()
			}

			await resizeHtmlEditor()
		}

		const buildFormPayload = (keys?: (keyof CommunicationMessageForm)[]): Partial<Record<keyof CommunicationMessageForm, any>> => {
			if (!keys)  {
				keys = Object.keys(form) as (keyof CommunicationMessageForm)[]
			} else {
				keys = ['id', ...keys]
			}

			const payload: Partial<Record<keyof CommunicationMessageForm, any>> = {}
			for (const key of keys) {

				const typedKey = key as keyof CommunicationMessageForm

				if (['to', 'cc', 'bcc'].includes(key)) {
					payload[typedKey] = (form[typedKey] as SelectOptionType[]).map((option: SelectOptionType) => (option.value < 0) ? option.text : option.value)
				} else if (typedKey === 'attachments') {
					payload[typedKey] = form[typedKey].map((option: SelectOptionType) => (option.value < 0) ? option.text : option.value)
				} else if (typedKey === 'send_individually') {
					// @ts-ignore TODO
					payload[typedKey] = Number(form[typedKey])
				} else {
					if (typedKey === 'content' && contentTinyMCE) {
						// @ts-ignore TODO
						const editor = window.tinymce.get(contentTinyMCE)
						form.content = editor.getContent()
					}
					// @ts-ignore TODO
					payload[typedKey] = form[typedKey]
				}
			}

			return payload
		}

		const buildFormDataPayload = (keys?: (keyof CommunicationMessageForm)[]) => {

			const payload = buildFormPayload(keys)

			const formData = new FormData()
			Object.keys(payload).forEach(key => {
				const typedKey = key as keyof CommunicationMessageForm
				if (Array.isArray(payload[typedKey])) {
					/* eslint-disable @typescript-eslint/no-explicit-any */
					(payload[typedKey] as string[]).forEach(value => formData.append(`${typedKey}[]`, value))
				} else {
					/* eslint-disable @typescript-eslint/no-explicit-any */
					formData.append(typedKey, payload[typedKey])
				}
			})

			return formData
		}

		const buildRelatedMessageParameters = () => {
			if (!props.related) {
				return {}
			}

			return {
				message_id: props.related.message.id,
				action: props.related.action
			}
		}

		const toggleCC = () => {
			ccEnabled.value = !ccEnabled.value
			if (!ccEnabled.value) {
				form.cc = []
				prepareSending()
			}
		}

		const toggleBCC = () => {
			bccEnabled.value = !bccEnabled.value
			if (!bccEnabled.value) {
				form.bcc = []
				prepareSending()
			}
		}

		const discard = async () => {
			await resetView()
			if (props.related && props.related.message) {
				await openMessage(props.related.message.id)
			}
		}

		const confirmError = (key: string, confirmed: boolean) => {
			if (confirmed && !form.confirmed_errors.includes(key)) {
				form.confirmed_errors.push(key)
			} else if (!confirmed && form.confirmed_errors.includes(key)) {
				const index = form.confirmed_errors.indexOf(key)
				form.confirmed_errors.splice(index, 1)
			}
		}

		onMounted(() => init())
		onBeforeUnmount(async() => await initText())

		return {
			contentRef,
			loading,
			sending,
			previewMessagesTotal,
			previewMessages,
			previewEnabled,
			flagsOpen,
			attachmentsOpen,
			ccEnabled,
			bccEnabled,
			localAlerts,
			localIdentities,
			localTemplates,
			localLanguages,
			localAttachments,
			localFlags,
			form,
			messageCount,
			attachmentsCount,
			selectedAttachments,
			primaryColor,
			toggleSendIndividually,
			toggleCC,
			toggleBCC,
			toggleFlagsContainer,
			toggleAttachmentsContainer,
			prepareSending,
			loadSendingPreview,
			send,
			reloadSignature,
			changeLanguage,
			loadTemplate,
			removeAttachment,
			removeFile,
			toggleFlag,
			openFile,
			discard,
			confirmError,
			showTooltip,
			buildPrimaryColorElementCssClasses,
			buildPrimaryColorCssClass,
			getPrimaryColorContrastShade,
			formatFileSize,
			resetView
		}
	}
})
</script>

<template>
	<div class="flex-none h-full overflow-hidden">
		<transition
			enter-active-class="transition-all ease-in duration-100"
			enter-from-class="opacity-0 translate-y-6"
			enter-to-class="opacity-100 translate-y-0"
			leave-active-class="transition-all ease-out duration-100"
			leave-from-class="opacity-100"
			leave-to-class="opacity-0"
		>
			<SendingPreview
				v-if="previewEnabled"
				:channel="channel"
				:api="api"
				:total="previewMessagesTotal"
				:messages="previewMessages"
				class="h-full"
				@close="previewEnabled = false"
				@send="send"
			/>
		</transition>
		<div
			v-show="!previewEnabled"
			class="flex flex-col h-full gap-1"
		>
			<div class="grow overflow-hidden">
				<div class="h-full flex flex-col gap-1 bg-white p-1 rounded-md">
					<div class="flex-none font-medium text-gray-500 divide-y divide-gray-50 border-b border-gray-50">
						<div class="flex flex-row items-center gap-1 pb-1">
							<button
								type="button"
								class="lg:hidden size-7 rounded disabled:opacity-50 hover:bg-gray-100/50"
								:disabled="loading || sending"
								@mouseenter="showTooltip($l10n.translate('common.close'), $event, 'top')"
								@click="resetView"
							>
								<i class="fa fa-times" />
							</button>
							<div
								class="lg:hidden h-6 w-px bg-gray-50"
								aria-hidden="true"
							/>
							<button
								v-show="config.fields.cc"
								type="button"
								:class="[
									'size-7 rounded disabled:opacity-50',
									(ccEnabled) ? buildPrimaryColorElementCssClasses() : 'hover:bg-gray-100/50'
								]"
								:disabled="loading || sending"
								@click="toggleCC"
							>
								{{ $l10n.translate('communication.message.cc') }}
							</button>
							<button
								v-show="config.fields.bcc"
								type="button"
								:class="[
									'size-7 rounded disabled:opacity-50',
									(bccEnabled) ? buildPrimaryColorElementCssClasses() : 'hover:bg-gray-100/50'
								]"
								:disabled="loading || sending"
								@click="toggleBCC"
							>
								{{ $l10n.translate('communication.message.bcc') }}
							</button>
							<div
								v-show="config.fields.cc || config.fields.bcc"
								class="h-6 w-px bg-gray-50"
								aria-hidden="true"
							/>
							<button
								type="button"
								class="size-7 rounded hover:bg-gray-100/50 disabled:opacity-50"
								:disabled="loading || sending"
								@click="discard"
								@mouseenter="showTooltip($l10n.translate('communication.message.discard'), $event, 'top')"
							>
								<i class="far fa-trash-alt" />
							</button>
							<!--<div
								v-show="hasAction(config,'observe', form)"
								class="h-6 w-px bg-gray-50"
								aria-hidden="true"
							/>
							<button
								v-show="hasAction(config,'observe', form)"
								type="button"
								class="size-7 rounded hover:bg-gray-100/50 disabled:opacity-50"
								@mouseenter="showTooltip($l10n.translate('communication.message.observe'), $event, 'top')"
							>
								<i class="fas fa-bell" />
							</button>-->
						</div>
						<div class="flex flex-row items-center gap-2 p-1">
							<span class="w-10 text-right flex-none font-medium text-gray-500">
								{{ $l10n.translate('communication.message.from') }}
							</span>
							<div class="grow">
								<select
									v-model="form.from"
									class="bg-white text-gray-500 border border-gray-100/50 rounded h-6 px-1"
									@change="reloadSignature"
								>
									<option
										v-for="identity in localIdentities"
										:key="identity.value"
										:value="identity.value"
									>
										{{ identity.text }}
									</option>
								</select>
							</div>
						</div>
						<div
							v-show="config.fields.to"
							class="flex flex-row items-center gap-2 p-1"
						>
							<span class="w-10 text-right flex-none font-medium text-gray-500">
								{{ $l10n.translate('communication.message.to') }}
							</span>
							<div class="grow">
								<RecipientSelection
									v-model="form.to"
									:api="api"
									:channel="channel"
									:config="config.fields.to"
									:contacts="contacts"
									@change="prepareSending"
								/>
							</div>
							<div
								v-show="multiple"
								class="flex-none"
							>
								<button
									type="button"
									:class="[
										'h-6 w-8 rounded disabled:opacity-50',
										(!form.send_individually) ? buildPrimaryColorElementCssClasses() : 'bg-gray-50 text-gray-500 hover:bg-gray-100/50'
									]"
									:disabled="loading || sending"
									@click="toggleSendIndividually"
									@mouseenter="showTooltip($l10n.translate('communication.message.send_all'), $event, 'top')"
								>
									<i :class="(form.send_individually) ? 'fas fa-users-slash' : 'fas fa-users'" />
								</button>
							</div>
						</div>
						<div
							v-show="config.fields.cc && ccEnabled"
							class="flex flex-row items-center gap-2 p-1"
						>
							<span class="w-10 text-right flex-none font-medium text-gray-500">
								{{ $l10n.translate('communication.message.cc') }}
							</span>
							<div class="grow">
								<RecipientSelection
									v-model="form.cc"
									:api="api"
									:channel="channel"
									:config="config.fields.cc ?? {}"
									:contacts="contacts"
									@change="prepareSending"
								/>
							</div>
						</div>
						<div
							v-show="config.fields.bcc && bccEnabled"
							class="flex flex-row items-center gap-2 p-1"
						>
							<span class="w-10 text-right flex-none font-medium text-gray-500">
								{{ $l10n.translate('communication.message.bcc') }}
							</span>
							<div class="grow">
								<RecipientSelection
									v-model="form.bcc"
									:api="api"
									:channel="channel"
									:config="config.fields.bcc ?? {}"
									:contacts="contacts"
									@change="prepareSending"
								/>
							</div>
						</div>
						<div class="flex flex-row gap-2 items-center px-1">
							<div class="flex-none flex flex-row items-center gap-1 max-w-72">
								<div class="grow min-w-0">
									<select
										v-show="config.fields.template"
										v-model="form.template_id"
										class=" bg-white text-gray-500 border border-gray-100/50 rounded h-6 w-full px-1"
										@change="loadTemplate"
									>
										<option value="0">
											-- {{ $l10n.translate('communication.message.no_template') }} --
										</option>
										<option
											v-for="template in localTemplates"
											:key="template.value"
											:value="template.value"
										>
											{{ template.text }}
										</option>
									</select>
								</div>
								<div
									v-for="language in localLanguages"
									:key="language"
									:class="[
										'flex-none cursor-pointer inline-flex size-6 rounded items-center justify-center',
										(language === form.language) ? buildPrimaryColorElementCssClasses() : 'bg-gray-50 text-gray-500 hover:bg-gray-100/50'
									]"
									@click="changeLanguage(language)"
								>
									<img :src="`/admin/media/flag_${language.slice(0, 2)}.gif`">
								</div>
							</div>
							<i
								v-if="config.fields.subject && !config.fields.subject.reaches_recipient"
								class="flex-none fa fa-exclamation-circle text-yellow-400"
								@mouseenter="showTooltip($l10n.translate('communication.message.subject.intern'), $event, 'top')"
							/>
							<div
								v-show="config.fields.subject"
								class="grow flex flex-row items-center gap-2 py-1"
							>
								<input
									v-model="form.subject"
									type="text"
									class="w-full h-6 placeholder:text-gray-200/75 placeholder:font-light"
									:placeholder="$l10n.translate('communication.message.subject')"
								>
							</div>
							<div class="flex-none">
								<div class="flex flex-row items-center gap-1">
									<button
										v-show="config.fields.flags && localFlags.length > 0"
										type="button"
										:class="['relative h-6 w-8 rounded disabled:opacity-50', flagsOpen ? buildPrimaryColorElementCssClasses() : 'bg-gray-50 text-gray-500 hover:bg-gray-100/50']"
										:disabled="loading || sending"
										@mouseenter="showTooltip($l10n.translate('communication.message.add_flags'), $event, 'top')"
										@click="toggleFlagsContainer"
									>
										<i class="fas fa-thumbtack" />
										<span
											v-show="form.flags.length > 0"
											:class="['absolute -top-1 -right-1 size-4 items-center justify-center rounded-full', buildPrimaryColorElementCssClasses()]"
										>
											{{ form.flags.length }}
										</span>
									</button>
									<button
										v-show="config.fields.attachments"
										type="button"
										:class="['relative h-6 w-8 rounded disabled:opacity-50', attachmentsOpen ? buildPrimaryColorElementCssClasses() : 'bg-gray-50 text-gray-500 hover:bg-gray-100/50']"
										:disabled="loading || sending"
										@mouseenter="showTooltip($l10n.translate('communication.message.add_attachments'), $event, 'top')"
										@click="toggleAttachmentsContainer"
									>
										<i class="fas fa-paperclip" />
										<span
											v-show="attachmentsCount > 0"
											:class="['absolute -top-1 -right-1 size-4 items-center justify-center rounded-full', buildPrimaryColorElementCssClasses()]"
										>
											<span>{{ attachmentsCount }}</span>
										</span>
									</button>
								</div>
							</div>
						</div>
					</div>
					<AlertMessage
						v-if="multiple && !form.send_individually"
						type="warning"
						:message="$l10n.translate('communication.message.send_all.warning')"
						class="flex-none p-2 text-xs"
					/>
					<div
						v-show="flagsOpen"
						:class="['grid gap-1', (localFlags.length > 8) ? 'grid-cols-5' : 'grid-cols-4']"
					>
						<div
							v-for="flag in localFlags"
							:key="flag.value"
							:class="['group flex flex-row items-center gap-1 rounded p-1 cursor-pointer border border-gray-50', (form.flags.includes(flag.value)) ? 'bg-gray-50' : 'hover:bg-gray-50']"
							@mouseenter="showTooltip(flag.text, $event, 'top')"
							@click="toggleFlag(flag)"
						>
							<div :class="['flex-none inline-flex size-6 items-center justify-center rounded-full', (form.flags.includes(flag.value)) ? buildPrimaryColorElementCssClasses() : 'text-gray-500 bg-gray-50 group-hover:bg-gray-100']">
								<i :class="flag.icon" />
							</div>
							<div class="grow flex flex-col truncate">
								{{ flag.text }}
							</div>
						</div>
					</div>
					<div
						v-show="attachmentsOpen"
						class="flex flex-col gap-1"
					>
						<div
							v-if="localAttachments.length > 0"
							class="flex flex-row items-center gap-2 pb-1 px-1 text-gray-500 border-b border-gray-50"
						>
							<span class="w-10 text-right flex-none font-medium text-gray-500">
								<i class="fas fa-paperclip" />
							</span>
							<div class="grow">
								<AttachmentSelection
									v-model="form.attachments"
									:attachments="localAttachments"
								/>
							</div>
						</div>
					</div>
					<div :class="['grid gap-1', (selectedAttachments.length > 8) ? 'grid-cols-5' : 'grid-cols-4']">
						<div
							v-for="attachment in selectedAttachments"
							:key="attachment.key"
							class="group flex flex-row items-center gap-1 rounded p-1 cursor-pointer border border-gray-50 bg-gray-50"
							@mouseenter="showTooltip(attachment.file_name, $event, 'top')"
							@click="openFile(attachment.file_path)"
						>
							<div :class="['flex-none inline-flex size-6 items-center justify-center rounded-full', buildPrimaryColorElementCssClasses()]">
								<i :class="attachment.icon" />
							</div>
							<div class="grow flex flex-col truncate">
								<span class="truncate">{{ attachment.file_name }}</span>
								<span class="text-xs text-gray-300">{{ attachment.file_size }}</span>
							</div>
							<div
								class="flex-none inline-flex size-6 items-center justify-center rounded hover:bg-gray-100"
								@click.stop="removeAttachment(attachment)"
							>
								<i
									v-show="attachment.deletable"
									class="fa fa-trash text-gray-400"
								/>
							</div>
						</div>
						<div
							v-for="(file, fileIndex) in form.files"
							:key="`file.${file.name}`"
							class="group flex flex-row items-center gap-1 rounded p-1 cursor-pointer border border-gray-50 bg-gray-50"
							@mouseenter="showTooltip(file.name, $event, 'top')"
							@click="openFile(file)"
						>
							<div :class="['flex-none inline-flex size-6 items-center justify-center rounded-full', buildPrimaryColorElementCssClasses()]">
								<i class="fas fa-file-upload" />
							</div>
							<div class="grow flex flex-col truncate">
								<span class="truncate">{{ file.name }}</span>
								<span class="text-xs text-gray-300">{{ formatFileSize(file) }}</span>
							</div>
							<div class="flex-none">
								<i
									class="fa fa-trash inline-flex size-6 items-center justify-center rounded text-gray-400 hover:bg-gray-100"
									@click.stop="removeFile(file, fileIndex)"
								/>
							</div>
						</div>
						<UploadField
							v-if="attachmentsOpen"
							v-model="form.files"
							:multiple="true"
							:placeholder="$l10n.translate('communication.message.new.uploads.placeholder')"
							:class="['rounded text-gray-600 border-2 p-2', (selectedAttachments.length === 0 && form.files.length === 0) ? 'col-span-full bg-gray-50 border-gray-100 hover:border-gray-200' : 'border-gray-50 hover:border-gray-100']"
						/>
					</div>
					<div
						ref="contentRef"
						class="grow overflow-hidden rounded text-xs"
					>
						<textarea
							v-model="form.content"
							:class="['h-full w-full p-1 resize-none', {'hidden': loading}]"
						/>
					</div>
					<ModalFooter
						class="p-1"
						:alerts="localAlerts"
						@confirm="(key: string, confirmed: boolean) => confirmError(key, confirmed)"
					>
						<div />
						<div class="flex flex-row items-center gap-1">
							<ButtonComponent
								v-show="messageCount > 0"
								:disabled="loading || sending"
								@click="loadSendingPreview"
							>
								{{ $l10n.translate('communication.message.btn.preview') }}
							</ButtonComponent>
							<ButtonComponent
								color="primary"
								:disabled="loading || sending || messageCount === 0"
								@click="send"
							>
								<span
									v-if="!sending"
									:class="[
										'rounded px-1',
										buildPrimaryColorCssClass('bg', getPrimaryColorContrastShade(), 60),
										(primaryColor.base <= 100) ? 'text-black' : 'text-white'
									]"
								>
									{{ messageCount }}
								</span>
								<i
									v-else
									class="fa fa-spinner fa-spin"
								/>
								{{ $l10n.translate('communication.message.btn.send') }}
							</ButtonComponent>
						</div>
					</ModalFooter>
				</div>
			</div>
		</div>
	</div>
</template>