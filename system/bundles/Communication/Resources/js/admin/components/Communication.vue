<script lang="ts">
import { defineComponent, nextTick, toRaw, onMounted, onBeforeUnmount, provide, reactive, ref, computed, type Ref, type ComputedRef, type PropType } from 'vue'
import * as debounce from 'debounce-promise'
import {
	buildPrimaryColorColorSchemeCssClass,
	buildPrimaryColorElementCssClasses,
	buildPrimaryColorCssClass,
	getPrimaryColorContrastShade
} from "@Admin/utils/primarycolor"
import { ComponentApiInterface, ComponentContentPayload, ContentType } from '@Admin/types/backend/router'
import { SelectOption } from '@Admin/types/common'
import { ComponentSize } from "@Admin/types/backend/app"
import { GuiInstance } from "@Gui2/types/gui"
import {
	CommunicationPreviewMessage,
	CommunicationMessageCategory,
	CommunicationMessageStatus,
	CommunicationNewMessageConfig,
	CommunicationMessageGroup,
	CommunicationChannelConfig,
	CommunicationMessage,
	CommunicationMessageForm,
	CommunicationMessagesTransportResponse
} from '../types/communication'
import { groupMessages } from '../utils/communication'
import { useContextMenu } from '@Admin/composables/contextmenu'
import { useTooltip } from '@Admin/composables/tooltip'
import { useLocalStorage } from '@Admin/composables/localstorage'
import { useInterface } from '@Admin/composables/interface'
import { safe } from '@Admin/utils/promise'
import { formatNumber as formatNumberUtil } from '@Admin/utils/util'
import ButtonComponent from '@Admin/components/ButtonComponent.vue'
import ListItems from '@Admin/components/contextmenu/ListItems.vue'
import DateField from '@Admin/components/form/DateField.vue'
import ComponentContent from '@Admin/layouts/admin/content/ComponentContent.vue'
import NewMessage from './communication/NewMessage.vue'
import MessagePreview from './communication/MessagePreview.vue'
import MessageView from './communication/MessageView.vue'
import ViewPort from "@Core/components/ViewPort.vue"
import FailedContent from '@Admin/layouts/admin/content/FailedContent.vue'
import LoadingContent from '@Admin/layouts/admin/content/LoadingContent.vue'
import TakeOverAttachments from './communication/TakeOverAttachments.vue'
import MessageSelection from './communication/MessageSelection.vue'
import l10n from '@Admin/l10n'
import router from '@Admin/router'

const DEFAULT_WIDTH = 384 //px -> w-96
const MIN_WIDTH = 250 //px
const MAX_WIDTH = 800 //px

type LoadResponse = {
	total: number|null,
	messages: CommunicationPreviewMessage[]
}

type LoadMessageResponse = {
	assignable: boolean,
	message: CommunicationMessage
}

type NewMessageResponse = {
	identities: SelectOption[]
	templates: SelectOption[]
	message?: CommunicationMessageForm
}

type PingMessageResponse = {
	status: { message_id: number, status: { value: string, icon: string, text: string }}[]
}

type ToggleFilterKey = 'date' | 'direction' | 'flags' | 'categories' | 'status' | 'channels' | 'account'
type EnabledFilterKey = `${ToggleFilterKey}_enabled`
type Filters = {
	search: string,
	direction: string,
	date_start: string|null,
	date_end: string|null,
	unseen: number,
	drafts: number,
	flags: string,
	attachments: number,
	channels: string[],
	categories: number[],
	status: string[],
	account: number|string,
	direction_enabled: boolean,
	date_enabled: boolean,
	flags_enabled: boolean,
	categories_enabled: boolean,
	status_enabled: boolean,
	channels_enabled: boolean
	account_enabled: boolean
}

/*type NewNoticeResponse = {
	// TODO
	test: string
}*/

export default defineComponent({
	name: "Communication",
	components: { MessageSelection, LoadingContent, MessagePreview, ComponentContent, ButtonComponent, ViewPort, DateField },
	props: {
		api: { type: Object as PropType<ComponentApiInterface>, required: true },
		multiple: { type: Boolean, default: false },
		pingInterval: { type: Number, default: 2000 },
		maxLoading: { type: Number, default: 200 },
		dateFormat: { type: String, default: 'MM/DD/YYYY' },
		numberFormat: { type: Object as PropType<{ decimal: string, thousands: string }>, default: () => ({ decimal: ',', thousands: '.' }) },
		total: { type: Number, default: 0 },
		channels: { type: Object as PropType<Record<string, CommunicationChannelConfig>>, default: () => ({}) },
		messages: { type: Array as PropType<CommunicationPreviewMessage[]>, default: () => [] },
		categories: { type: Array as PropType<CommunicationMessageCategory[]>, default: () => [] },
		status: { type: Array as PropType<CommunicationMessageStatus[]>, default: () => [] },
		flags: { type: Array as PropType<SelectOption[]>, default: () => [] },
		accounts: { type: Array as PropType<SelectOption[]>, default: () => [] },
		gui2: { type: Object as PropType<GuiInstance | null>, default: null }
	},
	setup(props) {
		const loading: Ref<boolean> = ref(false)
		const loadingContent: Ref<boolean> = ref(false)
		const sidebarWidth: Ref<number> = ref(DEFAULT_WIDTH)
		const filtersOpen: Ref<boolean> = ref(false)
		const messagesTotal: Ref<number> = ref(props.total)
		const localMessages: Ref<CommunicationPreviewMessage[]> = ref(props.messages)
		const collapsedGroups: Ref<string[]> = ref([])
		const selectedMessages: Ref<number[]> = ref([])
		const component: Ref<ComponentContentPayload|null> = ref(null)
		const resizingStart: Ref<{ clientX: number, clientY: number }|null> = ref(null)
		let pingInterval: number|null = null
		const filters = reactive<Filters>({
			search: '',
			direction: '',
			date_start: null,
			date_end: null,
			unseen: 0,
			drafts: 0,
			flags: '',
			attachments: 0,
			channels: [],
			categories: [],
			status: [],
			account: '',
			direction_enabled: false,
			flags_enabled: false,
			categories_enabled: false,
			status_enabled: false,
			channels_enabled: false,
			account_enabled: false,
			date_enabled: false,
		})

		const { scope, language } = useInterface()
		const { openContextMenu } = useContextMenu()
		const { showTooltip } = useTooltip()
		const { getStoredValue, setStoredValue } = useLocalStorage()

		onMounted(() => {
			// Set initial sidebar width from local storage or default value
			sidebarWidth.value = getStoredValue('communication.sidebar.width', DEFAULT_WIDTH)
			startPing()
		})

		onBeforeUnmount(() => stopPing())

		const messagesTotalFormatted = computed(() => formatNumber(messagesTotal.value, 0))

		/* eslint-disable @typescript-eslint/no-explicit-any */
		const setFilterValueDebounce = debounce((key: keyof Filters, value: any) => setFilterValue(key, value), 200)

		/* eslint-disable @typescript-eslint/no-explicit-any */
		const setFilterValue = <K extends keyof Filters>(key: K, value: Filters[K]) => {
			const target = filters[key]
			if (Array.isArray(target)) {
				const index = (target as (string | number)[]).indexOf(value as string | number)
				index === -1 ? (target as (string | number)[]).push(value as string | number) : (target as (string | number)[]).splice(index, 1)
			} else if (value instanceof Date) {
				// @ts-ignore TODO
				filters[key] = value.toISOString().split('T')[0]
			} else {
				filters[key] = value
			}

			load()
		}

		const toggleFilters = () => {
			filtersOpen.value = !filtersOpen.value
			if (!filtersOpen.value) {
				filters.date_start = null
				filters.date_end = null
				filters.search = ''
				filters.unseen = 0
				filters.attachments = 0
				filters.account = ''
				toggleFilterCategory('date', false, false)
				toggleFilterCategory('direction', false, false)
				toggleFilterCategory('flags', false, false)
				toggleFilterCategory('categories', false, false)
				toggleFilterCategory('status', false, false)
				toggleFilterCategory('channels', false, false)
				toggleFilterCategory('account', false, true)
			}
		}

		const toggleFilterCategory = (filter: ToggleFilterKey, value: boolean, loadMessages = true) => {
			const enabledKey = `${filter}_enabled` as EnabledFilterKey

			filters[enabledKey] = value

			if (filters[enabledKey] === false) {
				if (filter === 'date') {
					filters.date_start = null
					filters.date_end = null
				} else if (Array.isArray(filters[filter])) {
					// @ts-ignore
					filters[filter] = [] as any
				} else {
					// @ts-ignore
					filters[filter] = '' as any
				}

				if (loadMessages) {
					load()
				}
			}
		}

		const groupedMessages: ComputedRef<CommunicationMessageGroup[]> = computed(() => groupMessages(localMessages.value))

		const selectedMessageObjects: ComputedRef<CommunicationPreviewMessage[]> = computed(() => {
			return selectedMessages.value
				.map((id: number) => localMessages.value.find((loop: CommunicationPreviewMessage) => loop.id === id))
				.filter((message): message is CommunicationPreviewMessage => typeof message !== 'undefined')
		})

		/* eslint-disable @typescript-eslint/no-explicit-any */
		const load = async (params: Record<string, any> = {}, reset = true) => {
			loading.value = true

			params = {...params, ...filters }

			/* eslint-disable @typescript-eslint/no-unused-vars */
			const [error, response] = await safe<LoadResponse>(props.api.action('loadMessages', { params }))

			if (response) {
				if (response.total !== null) {
					messagesTotal.value = response.total
				}

				if (reset) {
					localMessages.value = response.messages
				} else {
					localMessages.value = [
						...localMessages.value,
						...response.messages
					]
				}

				await nextTick()

				startPing()

			} else {
				await setError(error)
			}

			loading.value = false
		}

		const infiniteScroll = async () => {
			if (
				loading.value ||
				localMessages.value.length >= props.total ||
				localMessages.value.length >= props.maxLoading
			) {
				return
			}

			const lastId = localMessages.value[localMessages.value.length - 1]?.id

			await load({ last_id: lastId }, false)
		}

		const newMessage = async (event: MouseEvent, config?: CommunicationNewMessageConfig) => {
			if (!config || !config.channel) {
				const elementRect = (event.target as HTMLElement)?.getBoundingClientRect()
				if (!elementRect) {
					return
				}

				if (!config) {
					config = {}
				}

				config.channel = await openContextMenu<string>(event,
					{
						component: ListItems,
						payload: {
							items: [
								[
									...Object.keys(props.channels)
										.filter(loop => !props.channels[loop].new_message_disabled)
										.map(loop => {
											const channel = props.channels[loop]
											return { text: channel.text, action: loop, icon: channel.icon }
										})
								],
								[
									{ text: l10n.translate('common.cancel'), action: null, icon: 'fa fa-times' }
								],
							]
						}
					},
					elementRect.x,
					elementRect.y + elementRect.height + 5
				)
			}

			if (!config.channel) {
				return
			}

			component.value = null

			await nextTick()

			if (!config.related) {
				selectedMessages.value = []
			} else {
				selectedMessages.value = [config.related.message.id]

				if (
					config.related.action === 'forward' &&
					typeof config.related.message_attachments === 'undefined' &&
					config.related.message.has_attachments
				) {
					const useAttachments = await router.openModal<boolean>({
						title: l10n.translate('communication.message.new.attachments.take_over.heading'),
						content: { type: ContentType.component, payload: { component: TakeOverAttachments } },
						size: ComponentSize.small,
						closable: true
					})

					if (useAttachments) {
						config.related.message_attachments = 1
					}
				}
			}

			loadingContent.value = true

			if (config.channel === 'notice') {
				/*const [error, response] = await safe<NewNoticeResponse>(props.api.action('newNotice'))

				if (response) {
					component.value = { component: NewNotice, payload: {
						api: props.api,
						...response
					}}
				} else {
					await setError(error)
					return
				}*/
			} else {
				const relatedParams = (config.related) ? {
					action: config.related.action,
					message_id: config.related.message.id,
					message_attachments: config.related.message_attachments ?? 0,
				} : {}

				const [error, response] = await safe<NewMessageResponse>(props.api.action('newMessage', { params: {
					channel: config.channel,
					...relatedParams
				}}))

				if (response) {
					component.value = { component: NewMessage, payload: {
						api: props.api,
						channel: config.channel,
						config: channelConfig(config.channel),
						related: config.related,
						...response
					}}
				} else {
					await setError(error)
					return
				}
			}

			loadingContent.value = false
		}

		const openMessage = async (messageId: number, event?: MouseEvent, force = false) => {

			let openMessage = true

			// Multiple selection
			if (event && event.ctrlKey) {
				if (selectedMessages.value.includes(messageId)) {
					selectedMessages.value = selectedMessages.value.filter(loop => loop !== messageId)
				} else {
					selectedMessages.value.push(messageId)
				}

				if (selectedMessages.value.length === 0 || selectedMessages.value.length > 1) {
					component.value = null
					await nextTick()
					openMessage = false
				}
			} else if (
				!force &&
				selectedMessages.value.length === 1 &&
				selectedMessages.value.includes(messageId)
			) {
				return
			}

			if (force || openMessage) {
				await resetView()

				selectedMessages.value = [messageId]

				const message = localMessages.value.find((loop: CommunicationPreviewMessage) => loop.id === messageId)
				if (message) {
					message.unseen = false
				}

				const [error, response] = await safe<LoadMessageResponse>(props.api.action('viewMessage', { params: { message_id: messageId }}))

				if (response) {
					component.value = {
						component: MessageView,
						payload: {
							api: props.api,
							config: channelConfig(response.message.channel),
							...response
						}
					}
				} else {
					await setError(error)
				}

				loadingContent.value = false
			}

		}

		const resetView = async () => {
			component.value = null
			selectedMessages.value = []
			await nextTick()
		}

		const resendMessages = async (messageIds: number[], force = false): Promise<[Error, null, CommunicationPreviewMessage[]] | [null, CommunicationMessagesTransportResponse, CommunicationPreviewMessage[]]> => {

			let newMessages: CommunicationPreviewMessage[] = []

			if (force || await router.confirm(l10n.translate('communication.message.resend.confirm.title'), l10n.translate('communication.message.resend.confirm.text'))) {
				const [error, response] = await safe<CommunicationMessagesTransportResponse>(props.api.action('resend', { params: { message_ids: messageIds } }))

				if (response) {
					newMessages = response.messages
						.filter(loop => loop.messages && loop.messages.length > 0)
						.map(loop => loop.messages)
						.flat(1)

					if (newMessages.length > 0) {
						await addMessages(newMessages)
					}

					reloadGui2()

					return [null, response, newMessages]
				}

				return [error, null, newMessages]
			}

			return [new Error('Aborted'), null, newMessages]
		}

		const addMessages = async (messages: CommunicationPreviewMessage[]) => {
			localMessages.value = [
				...messages,
				...localMessages.value
			]

			await nextTick()

			startPing()
		}

		const deleteMessages = async (messageIds: number[], force = false) => {

			if (force || await router.confirm(l10n.translate('communication.message.delete.confirm.title'), l10n.translate('communication.message.delete.confirm.text'))) {
				const [, response] = await safe(props.api.action('delete', { params: { message_ids: messageIds } }))

				if (response) {
					messagesTotal.value -= messageIds.length

					localMessages.value = localMessages.value.filter((loop: CommunicationPreviewMessage) => !messageIds.includes(loop.id))
					selectedMessages.value = selectedMessages.value.filter((loop: number) => !messageIds.includes(loop))

					if (selectedMessages.value.length === 0) {
						component.value = null
					}

					return true
				}
			}

			return false
		}

		const categorizeMessages = async (messageIds: number[], categoryId: number) => {
			const messages: CommunicationPreviewMessage[] = messageIds
				.map(id => localMessages.value.find((loop: CommunicationPreviewMessage) => loop.id === id))
				.filter((message): message is CommunicationPreviewMessage => typeof message !== 'undefined')

			if (messages.length === 0) {
				return false
			}

			const categoryAlreadyAssigned = messages.filter((message: CommunicationPreviewMessage) => message.categories.includes(categoryId))

			const action = (categoryAlreadyAssigned.length === messages.length) ? 'remove' : 'add'
			messages.forEach((message) => {
				if (action === 'remove') {
					message.categories = message.categories.filter((loop) => loop !== categoryId)
				} else if (!message.categories.includes(categoryId)) {
					message.categories.push(categoryId)
				}
			})

			const [error] = await safe(props.api.action('categorize', {
				params: {
					action,
					category_id: categoryId,
					message_ids: messages.map((message: CommunicationPreviewMessage) => message.id)
				}
			}))

			return (!error)
		}

		const showMessageContextMenu = async (message: CommunicationPreviewMessage, event: MouseEvent) => {
			let messageIds = [message.id]
			let heading = null

			if (selectedMessages.value.length > 1 && selectedMessages.value.includes(message.id)) {
				heading = (l10n.translate('communication.messages.selection') as string).replace('%d', String(selectedMessages.value.length))
				messageIds = selectedMessages.value
			}

			const messages: CommunicationPreviewMessage[] = messageIds
				.map((id) => localMessages.value.find((loop: CommunicationPreviewMessage) => loop.id === id))
				.filter((msg): msg is CommunicationPreviewMessage => msg != null)

			const categoryItems = props.categories.map((category: CommunicationMessageCategory) => {
				const categoryAssigned = messages.filter((message) => message.categories.includes(category.id))
				return {
					text: category.text,
					action: `category_${category.id}`,
					icon: 'fa fa-flag',
					icon_color: categoryColor(category.id),
					highlight: categoryAssigned.length === messages.length
				}
			})

			const action = await openContextMenu<string>(event, { component: ListItems, payload: {
				heading: heading,
				items: [
					categoryItems,
					[
						{ text: l10n.translate('communication.message.reply'), action: 'reply', icon: 'fas fa-reply' },
						{ text: l10n.translate('communication.message.reply_all'), action: 'reply_all', icon: 'fas fa-reply-all' },
						{ text: l10n.translate('communication.message.forward'), action: 'forward', icon: 'fas fa-share' },
					],
					[
						{ text: l10n.translate('communication.message.delete'), action: 'delete', icon: 'fa fa-trash' }
					],
				]
			}})

			if (action) {
				if (action === 'delete') {
					return await deleteMessages(messageIds)
				} else if (['reply', 'reply_all', 'forward'].includes(action)) {
					return await newMessage(event, { channel: message.channel, related: { action, message }})
				} else if (action.substring(0, 9) === 'category_') {
					return await categorizeMessages(messageIds, parseInt(action.replace('category_', '')))
				}
			}

		}

		const toggleGroup = (group: string) => {
			if (collapsedGroups.value.includes(group)) {
				collapsedGroups.value = collapsedGroups.value.filter((loop) => loop !== group)
			} else {
				collapsedGroups.value.push(group)
			}
		}

		const channelConfig = (channelKey: string) => {
			return props.channels[channelKey] ?? null
		}

		const categoryColor = (categoryId: number) => {
			const category = props.categories.find((loop) => loop.id === categoryId)
			return category?.color ?? ''
		}

		const startPing = () => {

			stopPing()

			pingInterval = window.setInterval(() => {
				const sendingMessages = localMessages.value.filter((message: CommunicationPreviewMessage) => message.status && message.status.value === 'sending')
				if (sendingMessages.length > 0) {
					safe<PingMessageResponse>(props.api.action('pingMessageStatus', { params: { message_ids: sendingMessages.map(message => message.id) } })).then(payload => {
						const [, response] = payload
						if (response && response.status) {
							response.status.forEach(message => {
								const index = localMessages.value.findIndex((loop: CommunicationPreviewMessage) => loop.id === message.message_id)
								if (index !== -1) {
									localMessages.value[index].status = message.status
								}
							})
						} else {
							stopPing()
						}
					})
				} else {
					stopPing()
				}
			}, props.pingInterval)
		}

		const stopPing = () => {
			if (pingInterval !== null) {
				window.clearInterval(pingInterval)
			}
		}

		const startResizing = (event: MouseEvent) => {
			resizingStart.value = { clientX: event.clientX, clientY: event.clientY }
			document.addEventListener('mousemove', resize)
			document.addEventListener('mouseup', stopResizing)
		}

		const resize = (event: MouseEvent) => {
			if (!resizingStart.value) {
				return
			}

			const diffX = event.clientX - resizingStart.value.clientX
			const newWidth = sidebarWidth.value + diffX

			if (newWidth >= MIN_WIDTH && newWidth <= MAX_WIDTH) {
				sidebarWidth.value = newWidth
				resizingStart.value = { clientX: event.clientX, clientY: event.clientY }
			}
		}

		const stopResizing = () => {
			if (!resizingStart.value) {
				return
			}
			resizingStart.value = null
			document.removeEventListener('mousemove', resize)
			document.removeEventListener('mouseup', stopResizing)

			setStoredValue('communication.sidebar.width', sidebarWidth.value)
		}

		const setError = async (error: Error) => {
			component.value = null

			await nextTick()

			component.value = { component: FailedContent, payload: { message: error.message }}
		}

		const reloadGui2 = () => {
			if (!props.gui2) {
				console.warn('No gui2 instance given')
				return
			}

			// toRaw() - otherwise it is a proxy object and the filters will not work (this.filters?.value?)
			toRaw(props.gui2).loadTable(true)
		}

		const formatNumber = (value: number | string, decimals = 2) => {
			return formatNumberUtil(value, decimals, props.numberFormat.thousands, props.numberFormat.decimal)
		}

		provide('load', load)
		provide('channelConfig', channelConfig)
		provide('categoryColor', categoryColor)
		provide('openMessage', openMessage)
		provide('newMessage', newMessage)
		provide('resetView', resetView)
		provide('addMessages', addMessages)
		provide('deleteMessages', deleteMessages)
		provide('resendMessages', resendMessages)
		provide('setError', setError)
		provide('formatNumber', formatNumber)
		provide('reloadGui2', reloadGui2)

		return {
			scope,
			language,
			loading,
			sidebarWidth,
			filtersOpen,
			filters,
			setFilterValue,
			setFilterValueDebounce,
			toggleFilterCategory,
			toggleFilters,
			messagesTotal,
			messagesTotalFormatted,
			localMessages,
			groupedMessages,
			collapsedGroups,
			selectedMessages,
			selectedMessageObjects,
			loadingContent,
			component,
			load,
			toggleGroup,
			infiniteScroll,
			newMessage,
			openMessage,
			resendMessages,
			deleteMessages,
			showMessageContextMenu,
			startResizing,
			categoryColor,
			showTooltip,
			buildPrimaryColorElementCssClasses,
			buildPrimaryColorColorSchemeCssClass,
			buildPrimaryColorCssClass,
			getPrimaryColorContrastShade,
		}
	}
})
</script>

<template>
	<div class="h-[80vh] p-1 text-xs bg-gray-50 text-gray-900">
		<div class="h-full flex flex-col lg:flex-row gap-1 items-stretch dark:text-gray-50">
			<div
				:class="[
					'flex-none  flex flex-col gap-1 lg:w-96 bg-white rounded-md p-1',
					(component !== null) ? 'h-1/6 lg:h-full' : 'h-full'
				]"
				:style="[ scope.width < 1024 ? `width: 100%` : `width: ${sidebarWidth}px`]"
				:data-debug="scope.width"
			>
				<div class="flex-none flex flex-row lg:flex-col gap-1">
					<div class="grow bg-gray-100 text-gray-950 rounded p-1 font-medium  divide-gray-200/75">
						<div :class="['flex justify-between items-center', { 'pb-1': filtersOpen }]">
							{{ messagesTotalFormatted }} {{ $l10n.translate('communication.messages') }}
							<div class="flex flex-row gap-1 items-center">
								<i
									v-show="loading"
									class="fa fa-spinner fa-spin text-gray-500"
								/>
								<button
									type="button"
									class="size-6 rounded text-gray-500 hover:bg-gray-200 hover:text-gray-700"
									@click="load({ sync: 1 })"
									@mouseenter="showTooltip($l10n.translate('communication.refresh'), $event, 'top')"
								>
									<i class="fa fa-refresh" />
								</button>
								<button
									type="button"
									:class="['size-6 rounded', filtersOpen ? 'bg-gray-200 text-gray-700' : 'text-gray-500 hover:bg-gray-200 hover:text-gray-700']"
									@click="toggleFilters"
									@mouseenter="showTooltip($l10n.translate('communication.filters'), $event, 'top')"
								>
									<i class="fas fa-sliders-h" />
								</button>
							</div>
						</div>
						<div
							v-show="filtersOpen"
							class="flex flex-col gap-1"
						>
							<div class="flex flex-col gap-1">
								<input
									type="text"
									class="rounded p-1 border border-gray-200/75"
									:placeholder="$l10n.translate('communication.filters.search')"
									@keyup="setFilterValueDebounce('search', $event.target.value)"
								>
								<div class="flex flex-row flex-wrap items-center gap-1">
									<button
										type="button"
										:class="['size-6 rounded', filters.unseen ? buildPrimaryColorElementCssClasses() : 'text-gray-500 bg-gray-200 text-gray-700']"
										@click="setFilterValue('unseen', filters.unseen ? 0 : 1)"
										@mouseenter="showTooltip($l10n.translate('communication.filters.unseen'), $event, 'top')"
									>
										<i class="fas fa-envelope-open-text" />
									</button>
									<button
										type="button"
										:class="['size-6 rounded', filters.date_enabled ? buildPrimaryColorElementCssClasses() : 'text-gray-500 bg-gray-200 text-gray-700']"
										@click="toggleFilterCategory('date', !filters.date_enabled)"
										@mouseenter="showTooltip($l10n.translate('communication.filters.period'), $event, 'top')"
									>
										<i class="fas fa-calendar-alt" />
									</button>
									<button
										type="button"
										:class="['size-6 rounded', filters.direction_enabled ? buildPrimaryColorElementCssClasses() : 'text-gray-500 bg-gray-200 text-gray-700']"
										@click="toggleFilterCategory('direction', !filters.direction_enabled)"
										@mouseenter="showTooltip($l10n.translate('communication.filters.direction'), $event, 'top')"
									>
										<i class="fas fa-exchange-alt" />
									</button>
									<button
										type="button"
										:class="['size-6 rounded', filters.drafts ? buildPrimaryColorElementCssClasses() : 'text-gray-500 bg-gray-200 text-gray-700']"
										@click="setFilterValue('drafts', filters.drafts ? 0 : 1)"
										@mouseenter="showTooltip($l10n.translate('communication.filters.drafts'), $event, 'top')"
									>
										<i class="fas fa-paper-plane" />
									</button>
									<button
										type="button"
										:class="['size-6 rounded', filters.categories_enabled ? buildPrimaryColorElementCssClasses() : 'text-gray-500 bg-gray-200 text-gray-700']"
										@click="toggleFilterCategory('categories', !filters.categories_enabled)"
										@mouseenter="showTooltip($l10n.translate('communication.filters.categorized'), $event, 'top')"
									>
										<i class="fas fa-flag" />
									</button>
									<button
										type="button"
										:class="['size-6 rounded', filters.flags_enabled ? buildPrimaryColorElementCssClasses() : 'text-gray-500 bg-gray-200 text-gray-700']"
										@click="toggleFilterCategory('flags', !filters.flags_enabled)"
										@mouseenter="showTooltip($l10n.translate('communication.filters.flags'), $event, 'top')"
									>
										<i class="fas fa-thumbtack" />
									</button>
									<button
										type="button"
										:class="['size-6 rounded', filters.attachments ? buildPrimaryColorElementCssClasses() : 'text-gray-500 bg-gray-200 text-gray-700']"
										@click="setFilterValue('attachments', filters.attachments ? 0 : 1)"
										@mouseenter="showTooltip($l10n.translate('communication.filters.attachments'), $event, 'top')"
									>
										<i class="fas fa-paperclip" />
									</button>
									<button
										type="button"
										:class="['size-6 rounded', filters.status_enabled ? buildPrimaryColorElementCssClasses() : 'text-gray-500 bg-gray-200 text-gray-700']"
										@click="toggleFilterCategory('status', !filters.status_enabled)"
										@mouseenter="showTooltip($l10n.translate('communication.filters.status'), $event, 'top')"
									>
										<i class="fas fa-tasks" />
									</button>
									<button
										type="button"
										:class="['size-6 rounded', filters.channels_enabled ? buildPrimaryColorElementCssClasses() : 'text-gray-500 bg-gray-200 text-gray-700']"
										@click="toggleFilterCategory('channels', !filters.channels_enabled)"
										@mouseenter="showTooltip($l10n.translate('communication.filters.channel'), $event, 'top')"
									>
										<i class="fas fa-share-alt" />
									</button>
									<button
										v-show="accounts.length > 1"
										type="button"
										:class="['size-6 rounded', filters.account_enabled ? buildPrimaryColorElementCssClasses() : 'text-gray-500 bg-gray-200 text-gray-700']"
										@click="toggleFilterCategory('account', !filters.account_enabled)"
										@mouseenter="showTooltip($l10n.translate('communication.filters.account'), $event, 'top')"
									>
										<i class="fas fa-key" />
									</button>
								</div>
							</div>
							<div
								v-show="filters.date_enabled || filters.direction_enabled || filters.flags_enabled || filters.categories_enabled || filters.status_enabled || filters.channels_enabled || filters.account_enabled"
								class="flex flex-col gap-1 p-1 rounded-md bg-gray-200"
							>
								<select
									v-show="filters.direction_enabled"
									class="rounded p-1 bg-white border border-gray-300/75"
									@change="setFilterValue('direction', $event.target.value)"
								>
									<option value="">
										-- {{ $l10n.translate('communication.filters.direction') }} --
									</option>
									<option
										value="in"
										:selected="filters.direction === 'in'"
									>
										{{ $l10n.translate('communication.message.direction.in') }}
									</option>
									<option
										value="out"
										:selected="filters.direction === 'out'"
									>
										{{ $l10n.translate('communication.message.direction.out') }}
									</option>
								</select>
								<div
									v-show="filters.date_enabled"
									class="flex flex-col gap-1"
								>
									<DateField
										v-model="filters.date_start"
										class="p-1 border border-gray-200/75"
										:locale="language.substring(0, 2)"
										:date-format="dateFormat"
										@change="(date: Date|null) => setFilterValue('date_start', date)"
									/>
									<DateField
										v-model="filters.date_end"
										class="p-1 border border-gray-200/75"
										:locale="language.substring(0, 2)"
										:date-format="dateFormat"
										@change="(date: Date|null) => setFilterValue('date_end', date)"
									/>
								</div>
								<select
									v-show="filters.flags_enabled"
									class="rounded p-1 bg-white border border-gray-300/75"
									@change="setFilterValue('flags', $event.target.value)"
								>
									<option value="">
										-- {{ $l10n.translate('communication.filters.flags.empty') }} --
									</option>
									<option
										v-for="flag in flags"
										:key="flag.value"
										:value="flag.value"
										:selected="filters.flags.includes(flag.value)"
									>
										{{ flag.text }}
									</option>
								</select>
								<div
									v-show="filters.categories_enabled"
									class="flex flex-row flex-wrap items-center gap-1"
								>
									<span class="font-medium">{{ $l10n.translate('communication.filters.categorized') }}:</span>
									<button
										v-for="category in categories"
										:key="category.id"
										type="button"
										:class="['size-6 rounded', filters.categories.includes(category.id) ? buildPrimaryColorElementCssClasses() : 'text-gray-500 bg-gray-300 text-gray-800']"
										:style="{'color': categoryColor(category.id)}"
										@click="setFilterValue('categories', category.id)"
										@mouseenter="showTooltip(category.text, $event, 'top')"
									>
										<i class="fa fa-flag" />
									</button>
								</div>
								<div
									v-show="filters.status_enabled"
									class="flex flex-row flex-wrap items-center gap-1"
								>
									<span class="font-medium">{{ $l10n.translate('communication.filters.status') }}:</span>
									<button
										v-for="loop in status"
										:key="loop.value"
										type="button"
										:class="['size-6 rounded', filters.status.includes(loop.value) ? buildPrimaryColorElementCssClasses() : 'text-gray-500 bg-gray-300 text-gray-800']"
										@click="setFilterValue('status', loop.value)"
										@mouseenter="showTooltip(loop.text, $event, 'top')"
									>
										<i :class="loop.icon.replace(/\bfa-spin\b/g, '').trim()" />
									</button>
								</div>
								<div
									v-show="filters.channels_enabled"
									class="flex flex-row flex-wrap items-center gap-1"
								>
									<span class="font-medium">{{ $l10n.translate('communication.filters.channel') }}:</span>
									<button
										v-for="channel in Object.keys(channels)"
										:key="channel"
										type="button"
										:class="['size-6 rounded', filters.channels.includes(channel) ? buildPrimaryColorElementCssClasses() : 'text-gray-500 bg-gray-300 text-gray-800']"
										@click="setFilterValue('channels', channel)"
										@mouseenter="showTooltip(channels[channel].text, $event, 'top')"
									>
										<i :class="channels[channel].icon" />
									</button>
								</div>
								<select
									v-show="filters.account_enabled"
									class="rounded p-1 bg-white border border-gray-300/75"
									@change="setFilterValue('account', $event.target.value)"
								>
									<option
										value=""
										:selected="filters.account === ''"
									>
										-- {{ $l10n.translate('communication.filters.account') }} --
									</option>
									<option
										v-for="account in accounts"
										:key="`account-${account.value}`"
										:value="account.value"
										:selected="filters.account === account.value"
									>
										{{ account.text }}
									</option>
								</select>
							</div>
						</div>
					</div>
					<ButtonComponent
						color="primary"
						class="flex-none lg:w-full"
						@click="newMessage($event)"
					>
						<i class="fa fa-plus" />
						{{ $l10n.translate('communication.message.new') }}
					</ButtonComponent>
				</div>
				<!-- h-64 damit das overflow richtig funktioniert -->
				<div class="h-64 grow flex flex-col gap-1 overflow-x-hidden overflow-x-auto">
					<div
						v-if="localMessages.length === 0"
						class="p-2 text-center text-gray-500"
					>
						{{ $l10n.translate('communication.messages.empty') }}
					</div>
					<div
						v-for="group in groupedMessages"
						:key="group.text"
						class="flex flex-col gap-1"
					>
						<div
							class="bg-gray-100/75 hover:bg-gray-100 text-gray-950 rounded p-1.5 font-medium flex flex-row gap-1 items-center cursor-pointer"
							@click="toggleGroup(group.text)"
						>
							<i :class="(collapsedGroups.includes(group.text)) ? 'fas fa-angle-right' : 'fas fa-angle-down'" />
							{{ group.text }}
						</div>
						<div
							v-if="!collapsedGroups.includes(group.text)"
							class="flex flex-col gap-1"
						>
							<MessagePreview
								v-for="message in group.messages"
								:key="`message-${message.id}`"
								:message="message"
								:selected="selectedMessages.includes(message.id)"
								:class="['cursor-pointer', selectedMessages.includes(message.id) ? 'bg-gray-100/75' : 'bg-gray-50 hover:bg-gray-100/50']"
								@click="openMessage(message.id, $event)"
								@contextmenu.prevent="showMessageContextMenu(message, $event)"
							/>
						</div>
					</div>
					<div v-if="groupedMessages.length > 0 && localMessages.length < messagesTotal && !collapsedGroups.includes(groupedMessages[groupedMessages.length - 1].text) && localMessages.length < maxLoading">
						<ViewPort @enter="infiniteScroll()">
							<div class="text-center p-4">
								<i class="fa fa-spinner fa-spin" />
							</div>
						</ViewPort>
					</div>
					<div
						v-else-if="localMessages.length >= maxLoading"
						class="p-2 text-center text-gray-500"
					>
						{{ $l10n.translate('communication.messages.loading_limit_reached') }}
					</div>
				</div>
			</div>
			<div class="flex-none hidden lg:block h-full grid place-content-center">
				<div
					class="bg-gray-100 rounded w-1 h-10 cursor-col-resize"
					@mousedown.prevent="startResizing($event)"
				/>
			</div>
			<div
				:class="[
					'grow h-3/4 lg:h-full flex flex-col gap-1 rounded-md overflow-hidden',
					(component !== null) ? 'h-5/6 lg:h-full' : 'h-0 lg:h-full'
				]"
			>
				<LoadingContent v-if="loadingContent" />
				<ComponentContent
					v-else-if="component"
					:component="component.component"
					:payload="component.payload"
				/>
				<MessageSelection
					v-else-if="selectedMessages.length > 1"
					:messages="selectedMessageObjects"
					class="h-full"
				/>
				<div
					v-else
					class="grid min-h-full place-items-center"
				>
					<div class="text-center">
						<p :class="['text-4xl', buildPrimaryColorColorSchemeCssClass('text')]">
							<i class="far fa-envelope" />
						</p>
						<p class="mt-4 text-pretty text-lg font-medium text-gray-500 sm:text-sm/8">
							{{ $l10n.translate('communication.messages.no_selection') }}
						</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>
