import {
	UserNotification,
	UserNotificationGroup,
	UserNotificationToast,
	UserNotificationToastGroup
} from "../../types/backend/app"

const groupNotifications = (notifications: UserNotification[]): UserNotificationGroup[] => {
	const grouped: Record<string, UserNotification[]> = {}
	notifications.forEach((notification) => {
		if (!grouped[notification.group]) {
			grouped[notification.group] = []
		}
		grouped[notification.group].push(notification)
	})

	const cleanArray: UserNotificationGroup[] = []
	Object.keys(grouped).forEach((group: string) => {
		cleanArray.push({ text: group, notifications: grouped[group] } as UserNotificationGroup)
	})

	return cleanArray
}

const groupToastNotifications = (notifications: UserNotificationToast[]): UserNotificationToastGroup[] => {
	const grouped: Record<string, UserNotificationToast[]> = {}
	notifications.forEach((notification: UserNotificationToast) => {
		const type = notification.alert ?? 'info'
		if (!grouped[type]) {
			grouped[type] = []
		}
		grouped[type].push(notification)
	})

	const orderedTypes = ['danger', 'warning', 'success', 'info']
	const cleanArray: UserNotificationToastGroup[] = []

	orderedTypes.forEach(type => {
		if (grouped[type]) {
			// @ts-ignore
			cleanArray.push({ type: type, notifications: grouped[type] })
		}
	})

	return cleanArray
}

export {
	groupNotifications,
	groupToastNotifications
}