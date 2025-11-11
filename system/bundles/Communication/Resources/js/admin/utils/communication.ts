import { SelectOptionValueType } from '@Admin/types/common'
import {
	CommunicationChannelConfig,
	CommunicationContact,
	CommunicationContactGroup,
	CommunicationAttachmentGroup,
	CommunicationMessage,
	CommunicationMessageGroup,
	CommunicationPreviewMessage, CommunicationMessageAttachment,
} from '../types/communication'

export const groupMessages = (messages: CommunicationPreviewMessage[]): CommunicationMessageGroup[] => {
	const grouped: Record<string, CommunicationMessageGroup> = {}
	messages.forEach((message: CommunicationPreviewMessage) => {
		if (!grouped[message.group]) {
			grouped[message.group] = { text: message.group, messages: [] }
		}
		grouped[message.group].messages.push(message)
	})
	return Object.values(grouped)
}

export const groupContacts = (contacts: CommunicationContact[]): CommunicationContactGroup[] => {
	const grouped: Record<string, CommunicationContactGroup> = {}
	contacts.forEach((contact: CommunicationContact) => {
		if (!grouped[contact.model]) {
			grouped[contact.model] = { text: contact.model, contacts: [] }
		}
		grouped[contact.model].contacts.push(contact)
	})
	return Object.values(grouped)
}

export const groupAttachments = (attachments: CommunicationMessageAttachment[]): CommunicationAttachmentGroup[] => {
	const grouped: Record<string, CommunicationAttachmentGroup> = {}
	attachments.forEach((attachment: CommunicationMessageAttachment) => {
		if (!grouped[attachment.model]) {
			grouped[attachment.model] = { text: attachment.model, attachments: [] }
		}
		grouped[attachment.model].attachments.push(attachment)
	})
	return Object.values(grouped)
}

export const matchAttachmentSelection = (keys: SelectOptionValueType[], attachments: CommunicationMessageAttachment[]): CommunicationMessageAttachment[] => {
	const groups = [...new Set(attachments.map(attachment => attachment.groups).flat())]

	const matched: CommunicationMessageAttachment[] = []
	keys.forEach(key => {
		if (key === 'all') {
			attachments.forEach(attachment => {
				attachment.deletable = false
				matched.push(attachment)
			})
		} else if (groups.includes(key as string)) {
			attachments
				.filter(attachment => attachment.groups.includes(key as string))
				.forEach(attachment => {
					attachment.deletable = false
					matched.push(attachment)
				})
		} else {
			const attachment = attachments.find((loop: CommunicationMessageAttachment) => loop.key === key)
			if (attachment) {
				attachment.deletable = true
				matched.push(attachment)
			}
		}
	})
	return matched

}

export const hasAction = (config: CommunicationChannelConfig, actions: string[], message: CommunicationMessage) => {
	if (config && config.actions) {
		if (!Array.isArray(actions)) {
			actions = [actions]
		}

		for (const action of actions) {
			if (
				Object.keys(config.actions).includes(action) &&
				// @ts-ignore
				(!config.actions[action].direction || message.direction === config.actions[action].direction)
			) {
				return true
			}
		}
	}

	return false
}