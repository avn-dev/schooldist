import { ref, readonly, computed, type Ref } from 'vue3'
import type { Bookmark, User, UserNotification } from '../types/backend/app'
import { RouterAction, RouterActionStorePayload } from '../types/backend/router'
import { getGui2Instances, buildRequestBodyForGui2Instances } from '../utils/backend/gui'
import { safe } from '../utils/promise'
import { sleep } from '../utils/util'
import router from '../router'

type PingResponse = {
	global_checks?: boolean
	messages?: UserNotification[]
}

const user: Ref<User | null> = ref(null)
const notificationsTotal: Ref<number> = ref(0)
const notifications: Ref<UserNotification[]> = ref([])
const bookmarks: Ref<Bookmark[]> = ref([])
const pingInterval: Ref<number | null> = ref(null)
const pinging: Ref<boolean> = ref(false)

const init = async (payload: User, pingIntervall: number) => {
	user.value = payload
	if (pingIntervall > 0) {
		pingInterval.value = window.setInterval(() => ping(), pingIntervall)
	}

	// No 'await', information can be loaded in the background
	fetch().then()

	return true
}

const ping = async () => {
	const url: URL = new URL('/admin/ping', document.baseURI)
	let skipSession = false
	if (pinging.value === true) {
		url.searchParams.append('skip_session', '1')
		skipSession = true
	} else {
		pinging.value = true
	}

	const gui2Instances = getGui2Instances(window)

	const [, response] = await safe<PingResponse>(router.post(url.toString(), { guis: buildRequestBodyForGui2Instances(gui2Instances) }))

	if (response) {
		if (response.global_checks && response.global_checks === true) {
			router.visit('/admin')
		} else if (response.messages) {
			addNotifications(response.messages)
		}
	}

	if (!skipSession) {
		pinging.value = false
	}
}

const sortNotificationsDesc = (notifications: UserNotification[]): UserNotification[] => {
	return notifications.sort((n1: UserNotification, n2: UserNotification) => n2.sort_key - n1.sort_key)
}

const addNotifications = (newNotifications: UserNotification[], addToTotalCount = true) => {

	const originalLength = notifications.value.length

	const temp = notifications.value

	newNotifications.forEach((notification: UserNotification) => {
		const existing = temp.find((existingNotification: UserNotification) => existingNotification.id === notification.id)
		if (!existing || notification.id === 0) {
			temp.push(notification)
		}
	})

	if (addToTotalCount) {
		notificationsTotal.value = notificationsTotal.value + (temp.length - originalLength)
	}

	notifications.value = sortNotificationsDesc(temp)
}

const markNotificationAsSeen = async (notification: UserNotification) => {

	if (!notification.read) {
		const index = notifications.value.indexOf(notification)
		if (index !== -1) {

			notifications.value[index].read = true

			if (notification.id > 0) {
				const [error, ] = await safe(router.get(`/admin/user/notifications/${notification.id}`))
				if (error) {
					return false
				}
			}
		}
	}

	return true
}

const markNotificationAsSeenDelayed = (notification: UserNotification) => {
	if (notification.read || notification.id === 0) {
		return
	}

	sleep(1000).then(() => markNotificationAsSeen(notification))
}

const deleteNotifications = async (payload: UserNotification[]|'all') => {

	if (payload !== 'all') {
		const ids = payload.map((notification: UserNotification) => notification.id)
		notifications.value = notifications.value.filter((existingNotification: UserNotification) => ids.indexOf(existingNotification.id) === -1)
		notifications.value = sortNotificationsDesc(notifications.value)

		await router.delete(`/admin/user/notifications`, { params: { id: ids } })
	} else {
		notifications.value = []
		await router.delete(`/admin/user/notifications`)
	}

	return await fetch()
}

const deleteNotification = async (notification: UserNotification) => {
	await deleteNotifications([notification])
}

const addBookmark = async (routerAction: RouterAction): Promise<boolean> => {

	const [, response] = await safe<{ success: true, bookmark: Bookmark }|{ success: false, notification?: UserNotification }>(router.put('/admin/user/bookmark', { action: routerAction }))

	if (response) {
		if (response.success) {
			bookmarks.value.push(response.bookmark)
			return true
		} else if (response.notification) {
			addNotifications([response.notification], false)
		}
	}

	return false
}

const toggleBookmark = async (routerAction: RouterActionStorePayload): Promise<boolean> => {

	/* eslint-disable @typescript-eslint/no-unused-vars */
	const [error, response] = await safe<{ success: true, active: boolean, bookmark: Bookmark }|{ success: false, notification?: UserNotification }>(router.post('/admin/user/bookmark', { action: routerAction }))

	if (response) {
		if (response.success) {
			if (response.active) {
				bookmarks.value.push(response.bookmark)
			} else {
				bookmarks.value = bookmarks.value.filter((loop: Bookmark) => loop.id !== response.bookmark.id)
			}
		} else if (response.notification) {
			addNotifications([response.notification], false)
		}

		return response.success
	}

	return false
}

const deleteBookmark = async (bookmark: Bookmark) => {

	/* eslint-disable @typescript-eslint/no-unused-vars */
	const [error, response] = await safe<{ success: boolean }>(router.delete('/admin/user/bookmark', { params: { key: bookmark.id } }))

	if (response && response.success) {
		bookmarks.value = bookmarks.value.filter((loop: Bookmark) => loop.id !== bookmark.id)
		return true
	}

	return false
}

const openBookmark = async (bookmark: Bookmark) => {
	return await router.action(bookmark.action)
}

const hasBookmark = (payload: RouterActionStorePayload): boolean => {
	if (!payload) {
		return false
	}
	const found = bookmarks.value.find((loop: Bookmark) => loop.id === payload.key)
	return typeof found !== 'undefined'
}

const fetch = async () => {
	const [, response] = await safe<{ notifications_total: number, notifications: UserNotification[], bookmarks: Bookmark[] }>(router.get('/admin/user/load'))

	if (response) {
		notificationsTotal.value = response.notifications_total
		addNotifications(response.notifications, false)
		bookmarks.value = response.bookmarks
	}
}

const unseenNotifications = computed(() =>  notifications.value.filter((notification: UserNotification) => !notification.read).length)

export function useUser() {
	return {
		user: readonly(user),
		bookmarks: readonly(bookmarks),
		notificationsTotal: readonly(notificationsTotal),
		notifications,
		unseenNotifications,
		init,
		fetch,
		addNotifications,
		markNotificationAsSeenDelayed,
		markNotificationAsSeen,
		deleteNotification,
		deleteNotifications,
		addBookmark,
		toggleBookmark,
		deleteBookmark,
		openBookmark,
		hasBookmark
	}
}