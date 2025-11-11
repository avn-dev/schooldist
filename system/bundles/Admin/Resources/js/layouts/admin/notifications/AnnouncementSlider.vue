<script lang="ts">
import { defineComponent, ref, computed, type Ref, type ComputedRef } from 'vue3'
import { UserNotification, UserNotificationAnnouncement } from '../../../types/backend/app'
import { buildPrimaryColorElementCssClasses } from '../../../utils/primarycolor'
import { useUser } from '../../../composables/user'
import Announcement from './Announcement.vue'
import RoundedBox from '../../../components/RoundedBox.vue'

const INTERVAL = 10000

export default defineComponent({
	name: "AnnouncementSlider",
	components: { RoundedBox, Announcement },
	setup() {
		const { notifications, markNotificationAsSeen } = useUser()
		const currentIndex: Ref<number> = ref(0)
		let interval: number | null = null

		const announcements: ComputedRef<UserNotificationAnnouncement[]> = computed(() => {
			const tmp = notifications.value.filter((notification: UserNotification) => (!notification.read && notification.type === 'Core\\Notifications\\AnnouncementNotification'))

			if (tmp.length > 1 && !interval) {
				interval = window.setInterval(() => nav(1), INTERVAL)
			} else if (interval) {
				window.clearInterval(interval)
				interval = null
			}

			return tmp
		})

		const nav = (steps: number) => {
			currentIndex.value += steps
			if (currentIndex.value > (announcements.value.length - 1)) {
				currentIndex.value = 0
			} else if (currentIndex.value < 0) {
				currentIndex.value = (announcements.value.length - 1)
			}

			if (interval) {
				clearInterval(interval)
				interval = window.setInterval(() => nav(1), INTERVAL)
			}
		}

		const close = () => {
			announcements.value.forEach((notification) => markNotificationAsSeen(notification))
			if (interval) {
				window.clearInterval(interval)
				interval = null
			}
		}

		return {
			currentIndex,
			announcements,
			nav,
			close,
			buildPrimaryColorElementCssClasses
		}
	}
})
</script>

<template>
	<RoundedBox
		v-if="announcements.length > 0"
		ref="elementRef"
		mode="dark"
		class="absolute max-h-full max-w-full sm:w-[32rem] left-2 bottom-2 right-2 sm:left-auto shadow-lg"
		:style="{'z-index': 99999}"
	>
		<div class="relative">
			<button
				class="absolute top-1 right-1 bg-white rounded-full px-1.5"
				@click="close"
			>
				<i class="fa fa-times" />
			</button>
			<Announcement
				v-for="(announcement, index) in announcements"
				:key="announcement.id"
				:announcement="announcement"
				:class="{'invisible absolute left-0 right-0': currentIndex !== index}"
			/>
			<div
				v-if="announcements.length > 1"
				class="flex flex-row items-center justify-center gap-x-2 pb-2"
			>
				<i
					class="fa fa-arrow-circle-left cursor-pointer text-gray-300 hover:text-gray-500"
					@click="nav(-1)"
				/>
				<div
					v-for="(announcement, index) in announcements"
					:key="announcement.id"
					:class="[
						'h-1.5 w-1.5 rounded-full',
						(currentIndex === index) ? buildPrimaryColorElementCssClasses() : 'bg-gray-100'
					]"
				/>
				<i
					class="fa fa-arrow-circle-right cursor-pointer text-gray-300 hover:text-gray-500"
					@click="nav(1)"
				/>
			</div>
		</div>
	</RoundedBox>
</template>
