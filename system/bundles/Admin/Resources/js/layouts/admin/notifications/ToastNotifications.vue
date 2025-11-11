<script lang="ts">
import { defineComponent, computed, ref, type Ref, type ComputedRef } from 'vue3'
import { UserNotification, UserNotificationToast } from '../../../types/backend/app'
import { useUser } from '../../../composables/user'
import { useContextMenu } from '../../../composables/contextmenu'
import { groupToastNotifications } from '../../../utils/backend/notifications'
import Toast from './Toast.vue'
import ListItems from '../../../components/contextmenu/ListItems.vue'
import l10n from '../../../l10n'

export default defineComponent({
	name: "ToastNotifications",
	components: { Toast },
	setup() {
		const { notifications, markNotificationAsSeen } = useUser()
		const { openContextMenu } = useContextMenu()
		const view: Ref<'list'|'group'> = ref('list')

		const toasts: ComputedRef<UserNotificationToast[]> = computed(() => {
			return notifications.value.filter((notification: UserNotification) => (!notification.read && notification.type === 'Core\\Notifications\\ToastrNotification')) as UserNotificationToast[]
		})

		const groupedToasts = computed(() => groupToastNotifications(toasts.value))

		const contextMenu = async (toast: UserNotificationToast, event: MouseEvent) => {

			const groupLabel = (view.value === 'group') ? 'notifications.toast.ungroup' : 'notifications.toast.group'
			const closeAllLabel = (view.value === 'group') ? 'notifications.toast.close_group' : 'notifications.toast.close_all'

			const action = await openContextMenu<string>(event, {
				component: ListItems,
				payload: {
					items: [
						[
							{text: l10n.translate(groupLabel), action: 'group', icon: 'fas fa-layer-group'},
							{text: l10n.translate(closeAllLabel), action: 'close_all', icon: 'fa fa-times'}
						],
						[
							{text: l10n.translate('notifications.toast.close'), action: 'close', icon: 'fa fa-times'}
						],
					]
				}
			})

			if (action === 'close') {
				await markNotificationAsSeen(toast)
			} else if (action === 'close_all') {
				if (view.value === 'list') {
					toasts.value.forEach((notification) => markNotificationAsSeen(notification))
				} else {
					toasts.value
						.filter((notification) => notification.data.type === toast.data.type)
						.forEach((notification) => markNotificationAsSeen(notification))
				}
			} else if (action === 'group') {
				view.value = (view.value === 'group') ? 'list' : 'group'
			}
		}

		return {
			view,
			toasts,
			contextMenu,
			groupedToasts
		}
	}
})
</script>

<template>
	<div
		v-if="toasts.length > 0"
		class="max-h-screen absolute top-0 right-0 z-40 p-2 overflow-y-auto pb-6"
	>
		<div
			v-if="view === 'list'"
			class="flex flex-col gap-y-3"
		>
			<Toast
				v-for="toast in toasts"
				:key="toast.id"
				:toast="toast"
				@contextmenu.prevent="contextMenu(toast, $event)"
			/>
		</div>
		<div
			v-else
			class="flex flex-col gap-y-3"
		>
			<div
				v-for="group in groupedToasts"
				:key="group"
				class="flex flex-col gap-y-3"
			>
				<Toast
					v-for="toast in group.notifications"
					:key="toast.id"
					:toast="toast"
					@contextmenu.prevent="contextMenu(toast, $event)"
				/>
			</div>
		</div>
	</div>
</template>
