import { AxiosResponse } from 'axios'
import {
	Gui2DialogPayload,
	ModalPayload,
	PagePayload,
	SlideOverPayload,
	TabPayload,
	UserNotification
} from '../../types/backend/app'
import { AdminMessageEvent, EventAction, RouterAction, RouterTarget } from '../../types/backend/router'
import { useUser } from '../../composables/user'
import { useTabs } from '../../composables/tabs'
import { useModals } from '../../composables/modals'
import { useSlideOver } from '../../composables/slideover'
import { useGui2Dialog } from '../../composables/gui2_dialog'
import { checkOrigin } from '../util'
import l10n from '../../l10n'

export const handleInterfaceResponse = async <T>(response: AxiosResponse): Promise<AxiosResponse<T>> => {
	const isAdmin: boolean = response.headers['x-admin-response'] === '1'
	const contentType: string = response.headers['content-type'] ?? ''

	if (isAdmin && contentType.includes('application/json')) {
		const body = response.data
		const actions: RouterAction[] = body._actions ?? []
		const notifications: UserNotification[] = body._notifications ?? []

		await l10n.addTranslations(body._l10n ?? {})

		if (notifications.length > 0) {
			useUser().addNotifications(notifications)
		}

		actions.map((action: RouterAction) => handleRouterAction(action).then())
		//const promises = actions.map((action: RouterAction) => handleRouterAction(action))
		//await Promise.all(promises)

		response.data = {
			...body.data ?? {},
			...(body._date_as_of) ? ({ date_as_of: body._date_as_of }) : {}
		}
	}

	return response
}

export const handleRouterAction = async <T>(action: RouterAction): Promise<T | null> => {

	const target: RouterTarget = action.target

	if (action._l10n) {
		await l10n.addTranslations(action._l10n ?? {})
	}

	switch (target) {
		case RouterTarget.tab:
			return await useTabs().addTab(action.payload as TabPayload, action.payload_storable)
		case RouterTarget.modal:
			return await useModals().openModal<T>(action.payload as ModalPayload, action.payload_storable ?? null, action.payload_additional ?? null)
		case RouterTarget.slideOver:
			return await useSlideOver().openSlideOver<T>(action.payload as SlideOverPayload, action.payload_storable ?? null, action.payload_additional ?? null)
		case RouterTarget.gui2Dialog:
			return await useGui2Dialog().openPreview<T>((action.payload as Gui2DialogPayload))
		case RouterTarget.page:
			/*InertiaRouter.visit(action.payload.url, {replace: true,preserveState: false})*/
			location.href = (action.payload as PagePayload).url
	}

	return null
}

export const handleMessageEvent = async (e: MessageEvent) => {

	if (
		!checkOrigin(e.origin) ||
		typeof e.data !== 'object' || !e.data['action'] || !e.data['payload']
	) {
		return
	}

	const event = e.data as AdminMessageEvent

	switch (event.action) {
		case EventAction.ACTION:
			await handleRouterAction(event.payload)
			break
		case EventAction.CLOSE: {
			const source = event.payload.source

			if (source === RouterTarget.tab) {
				await useTabs().removeTab(event.payload.payload.id)
			} else if (source === RouterTarget.modal) {
				await useModals().closeModal(event.payload.payload.id)
			} else if (source === RouterTarget.slideOver) {
				await useSlideOver().closeSlideOver(event.payload.payload.id)
			} else if (source === RouterTarget.gui2Dialog) {
				const { gui2, closePreview } = useGui2Dialog()

				if (
					event.payload.payload.other_dialogs === 0 &&
					gui2.value?.hash === event.payload.payload.hash &&
					gui2.value?.instance_hash === event.payload.payload.instance_hash
				) {
					await closePreview(EventAction.CLOSE)
				}
			}

			break
		}
		default:
			console.warn('Unhandled event', event)
	}

}