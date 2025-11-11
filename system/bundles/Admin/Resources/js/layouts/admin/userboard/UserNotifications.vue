<script lang="ts">
import { computed, defineComponent, ref, type Ref, type ComputedRef } from 'vue3'
import { type UserNotificationGroup, UserNotificationView } from "../../../types/backend/app"
import { buildPrimaryColorContrastCssClass, buildPrimaryColorElementCssClasses } from "../../../utils/primarycolor"
import { groupNotifications } from "../../../utils/backend/notifications"
import { useUser } from "../../../composables/user"
import { useInterface } from '../../../composables/interface'
import Notification from "./notifications/Notification.vue"
import NotificationGroup from "./notifications/NotificationGroup.vue"
import l10n from "../../../l10n"
import router from '../../../router'

export default defineComponent({
	name: "UserNotifications",
	components: { NotificationGroup, Notification },
	emits: ['close'],
	setup() {
		const { notificationsTotal, notifications, deleteNotifications } = useUser()
		const { colorScheme } = useInterface()
		// TODO Einstellung speichern
		const view: Ref<UserNotificationView> = ref(UserNotificationView.list)

		const groupedNotifications: ComputedRef<UserNotificationGroup[]> = computed(() => {
			if (view.value === UserNotificationView.group) {
				return groupNotifications(notifications.value)
			}
			return []
		})

		const deleteAllMessages = async () => {
			if (await router.confirm(l10n.translate('userboard.notification.delete_all.confirm.title'), l10n.translate('userboard.notification.delete_all.confirm.text'))) {
				await deleteNotifications('all')
			}
		}

		return {
			colorScheme,
			view,
			notificationsTotal,
			notifications,
			groupedNotifications,
			UserNotificationView,
			deleteAllMessages,
			buildPrimaryColorContrastCssClass,
			buildPrimaryColorElementCssClasses
		}
	}
})
</script>

<template>
	<div
		v-if="notifications.length === 0"
		class="text-xs text-center p-4 text-gray-200"
	>
		- {{ $l10n.translate('userboard.notification.empty') }} -
	</div>
	<div v-else>
		<div class="flex flex-row-reverse justify-items-end gap-x-1 px-1.5 mb-1.5">
			<div class="isolate inline-flex rounded-md shadow-sm">
				<button
					type="button"
					:class="[
						'text-xs relative -ml-px inline-flex items-center rounded-l-md px-2 py-2 ring-1 ring-inset ring-gray-400/50 focus:z-10 dark:ring-0',
						(view === UserNotificationView.list) ? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-200' : 'bg-white text-gray-400 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-950'
					]"
					@click="view =UserNotificationView.list"
				>
					<i class="fa fa-bars w-4" />
				</button>
				<button
					type="button"
					:class="[
						'text-xs relative -ml-px inline-flex items-center rounded-r-md px-2 py-2 ring-1 ring-inset ring-gray-400/50 focus:z-10 dark:ring-0',
						(view === UserNotificationView.group) ? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-200' : 'bg-white text-gray-400 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-950'
					]"
					@click="view = UserNotificationView.group"
				>
					<i class="fa fa-th-large w-4" />
				</button>
			</div>
			<button
				type="button"
				:class="[
					'text-xs items-center rounded-md px-2 py-1.5 focus:z-10 hover:bg-gray-50 dark:hover:bg-gray-700 hidden sm:inline-flex',
					buildPrimaryColorContrastCssClass('text', 'text')
				]"
				@click="deleteAllMessages()"
			>
				<!--<i class="fa fa-trash w-4"/>-->
				{{ $l10n.translate('userboard.notification.delete_all') }}
			</button>
			<div class="grow">
				<span
					:class="[
						'rounded-md text-xs p-1.5 truncate',
						buildPrimaryColorElementCssClasses()
					]"
				>
					{{ $l10n.translate('userboard.notification.count').replace('%d', notifications.length).replace('%d', notificationsTotal) }}
				</span>
			</div>
		</div>
		<div
			v-if="view === UserNotificationView.group"
			class="flex flex-col gap-y-1 px-1.5"
		>
			<NotificationGroup
				v-for="group in groupedNotifications"
				:key="group.text"
				:group="group"
				@close="$emit('close')"
			/>
		</div>
		<div
			v-else
			class="flex flex-col gap-y-1 px-1.5"
		>
			<Notification
				v-for="notification in notifications"
				:key="notification.id"
				:notification="notification"
				@close="$emit('close')"
			/>
		</div>
	</div>
</template>
