<script lang="ts">
import { defineComponent, computed, ref, type Ref } from 'vue3'
import { UserNotification } from '../../../types/backend/app'
import { buildPrimaryColorElementCssClasses } from '../../../utils/primarycolor'
import { useUser } from '../../../composables/user'
import ButtonComponent from '../../../components/ButtonComponent.vue'

export default defineComponent({
	name: "ImportantNotifications",
	components: { ButtonComponent },
	emits: ['close'],
	setup(props, { emit }) {
		const { notifications, markNotificationAsSeen } = useUser()
		const currentIndex: Ref<number> = ref(0)

		const importantNotifications = computed(() => {
			return notifications.value.filter((notification: UserNotification) => (!notification.read && notification.type === 'Core\\Notifications\\PopupNotification'))
		})

		const nav = (steps: number) => {
			currentIndex.value += steps
			if (currentIndex.value > (importantNotifications.value.length - 1)) {
				currentIndex.value = (importantNotifications.value.length - 1)
			} else if (currentIndex.value < 0) {
				currentIndex.value = 0
			}
		}

		const close = () => {
			importantNotifications.value.forEach((notification) => markNotificationAsSeen(notification))
			emit('close')
		}

		return {
			importantNotifications,
			currentIndex,
			nav,
			close,
			buildPrimaryColorElementCssClasses
		}
	}
})
</script>

<template>
	<div class="flex flex-col py-4 px-3 ">
		<div
			v-for="(notification, index) in importantNotifications"
			:key="notification.id"
			:class="[
				'text-sm',
				{'invisible absolute left-0 right-0': currentIndex !== index}
			]"
		>
			<h4 class="font-bold">
				{{ notification.subject }}
			</h4>
			<!-- eslint-disable vue/no-v-html -->
			<p
				class="mt-4 text-black dark:text-gray-50"
				v-html="notification.message"
			/>
		</div>
		<div
			v-if="importantNotifications.length > 1"
			class="flex flex-row items-center justify-center gap-x-2 mt-4"
		>
			<div
				v-for="(notification, index) in importantNotifications"
				:key="notification.id"
				:class="[
					'h-1.5 w-1.5 rounded-full',
					(currentIndex === index) ? buildPrimaryColorElementCssClasses() : 'bg-gray-100'
				]"
			/>
		</div>
	</div>
	<div class="bg-gray-100/50 dark:bg-gray-900 p-3 rounded-md">
		<div class="flex flex-col sm:flex-row gap-2">
			<div class="flex-none flex gap-2 flex-col sm:flex-row">
				<ButtonComponent
					v-if="currentIndex > 0"
					@click="nav(-1)"
				>
					{{ $l10n.translate('notifications.important.previous') }}
				</ButtonComponent>
			</div>
			<div class="grow flex gap-2 flex-col sm:flex-row justify-end">
				<ButtonComponent
					v-if="currentIndex < (importantNotifications.length - 1)"
					@click="nav(1)"
				>
					{{ $l10n.translate('notifications.important.next') }}
				</ButtonComponent>
				<ButtonComponent
					v-if="currentIndex === (importantNotifications.length - 1)"
					color="primary"
					@click="close"
				>
					{{ $l10n.translate('notifications.important.accept') }}
				</ButtonComponent>
			</div>
		</div>
	</div>
</template>
