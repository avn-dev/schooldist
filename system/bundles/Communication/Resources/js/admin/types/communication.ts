import { type User } from '@Admin/types/backend/app'
import { type SelectOption, type SelectOptionValueType } from '@Admin/types/common'
import { Alert } from "@Admin/types/interface"

export enum CommunicationContentType { html = 'html', text = 'text' }
export enum CommunicationMessageDirection { in = 'in', out = 'out' }
export enum CommunicationMessagesTransportStatus { ALL_FAILED = 'ALL_MESSAGES_FAILED', SOME_FAILED = 'SOME_MESSAGES_FAILED', ALL_SENT = 'ALL_MESSAGES_SENT' }

export type CommunicationMessagesTransportResponse = {
	status: CommunicationMessagesTransportStatus,
	messages: Array<{ success: boolean, messages: CommunicationPreviewMessage[], alerts: Alert[] }>
}

export type CommunicationRecipientFieldConfig = {
	allow_custom: boolean
	routes_selection: boolean
}

export type CommunicationChannelConfig = {
	icon: string
	text: string
	content_types: CommunicationContentType[]
	fields: {
		to?: CommunicationRecipientFieldConfig
		cc?: CommunicationRecipientFieldConfig
		bcc?: CommunicationRecipientFieldConfig
		template?: object
		subject?: { reaches_recipient: boolean }
		flags?: object
		attachments?: object
	}
	actions: {
		reply?: { history: boolean }
		reply_all?: { history: boolean }
		forward?: object
		resend?: { direction: CommunicationMessageDirection }
		assign?: { direction: CommunicationMessageDirection }
		delete?: object
		observe?: object
	},
	new_message_disabled?: boolean
}

export type CommunicationContactGroup = { text: string, contacts: CommunicationContact[] }
export type CommunicationAttachmentGroup = { text: string, attachments: CommunicationMessageAttachment[] }

export type CommunicationContact = SelectOption & {
	user: User,
	routes: SelectOption[],
	groups: string[]
	model: string
	allSelection: boolean
}

type BaseCommunicationMessage = {
	id: number
	draft: boolean
	date: string
	subject: string
	direction: CommunicationMessageDirection
	content: string
	categories: number[]
	channel: string
	has_attachments: boolean,
	event?: string
}

export type CommunicationPreviewMessage = BaseCommunicationMessage & {
	contact: string
	unseen: boolean
	has_flags: boolean
	status?: { value: string, icon: string, text: string }
	group: string
}

export type CommunicationMessage = BaseCommunicationMessage & {
	from: string
	to: string
	cc: string
	bcc: string
	flags: string[]
	attachments: CommunicationMessageAttachment[]
	errors: string[]
}

export type CommunicationMessageAttachment = {
	key: string
	icon: string,
	file_name: string
	file_size: string
	groups: string[]
	model: string
	deletable: boolean
}

export type CommunicationMessageGroup = { text: string, messages: CommunicationPreviewMessage[] }

export type CommunicationMessageCategory = {
	id: number
	text: string
	color: string
}

export type CommunicationMessageStatus = {
	value: string
	text: string
	icon: string
}

export type CommunicationRelatedMessageParams = {
	message: BaseCommunicationMessage
	message_attachments?: number
	action: string
}

export type CommunicationNewMessageConfig = {
	channel?: string
	related?: CommunicationRelatedMessageParams,
}

export type CommunicationMessageForm = {
	id: string,
	direction: CommunicationMessageDirection,
	content_type: CommunicationContentType,
	from: SelectOptionValueType,
	to: SelectOption[],
	cc: SelectOption[],
	bcc: SelectOption[],
	template_id: number|string,
	language: string,
	send_individually: boolean,
	subject: string,
	content: string,
	flags: string[],
	attachments: SelectOption[],
	files: File[],
	confirmed_errors: string[]
}

export type CommunicationNoticeForm = {
	type: string
	direction: CommunicationMessageDirection,
	to: SelectOption[],
	content: string
}