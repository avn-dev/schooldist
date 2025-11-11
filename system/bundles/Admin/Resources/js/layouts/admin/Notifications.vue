<script lang="ts">
import { defineComponent, watch, ref, type Ref } from 'vue3'
import { ComponentSize, UserNotification } from '../../types/backend/app'
import { ContentType } from '../../types/backend/router'
import { useUser } from '../../composables/user'
import { useModals } from '../../composables/modals'
import AnnouncementSlider from './notifications/AnnouncementSlider.vue'
import ImportantNotifications from './notifications/ImportantNotifications.vue'
import ToastNotifications from './notifications/ToastNotifications.vue'
import l10n from '../../l10n'

export default defineComponent({
	name: "Notifications",
	components: { ToastNotifications, AnnouncementSlider },
	setup() {
		const { notifications } = useUser()
		const { openModal } = useModals()
		const modalOpen: Ref<boolean> = ref(false)

		watch(notifications, async (payload) => {
			const important = payload.find((notification: UserNotification) => (!notification.read && notification.type === 'Core\\Notifications\\PopupNotification'))
			if (important && !modalOpen.value) {
				modalOpen.value = true
				await openModal({
					title: l10n.translate('notifications.important'),
					content: { type: ContentType.component, payload: { component: ImportantNotifications } },
					size: ComponentSize.large,
					closable: false
				})
				modalOpen.value = false
			}
		}, { deep: true })

		return {}
	}
})
</script>

<template>
	<Teleport to="body">
		<ToastNotifications />
		<AnnouncementSlider />
	</Teleport>
</template>
