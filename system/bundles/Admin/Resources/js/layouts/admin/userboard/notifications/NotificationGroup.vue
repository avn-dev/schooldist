<script lang="ts">
import { defineComponent, PropType, ref, type Ref } from 'vue'
import { UserNotificationGroup } from "../../../../types/backend/app"
import { buildPrimaryColorElementCssClasses } from "../../../../utils/primarycolor"
import { useUser } from "../../../../composables/user"
import Notification from "./Notification.vue"

export default defineComponent({
	name: "NotificationGroup",
	components: { Notification },
	props: {
		group: { type: Object as PropType<UserNotificationGroup>, required: true },
	},
	emits: ['close'],
	setup() {
		const { markNotificationAsSeenDelayed, deleteNotifications, deleteNotification } = useUser()
		const open: Ref<boolean> = ref(false)

		return {
			open,
			buildPrimaryColorElementCssClasses,
			markNotificationAsSeenDelayed,
			deleteNotification,
			deleteNotifications
		}
	}
})
</script>

<template>
	<div class="text-sm flex py-1 px-2 gap-x-2 items-center font-semibold font-heading bg-gray-100/50 dark:bg-gray-900 rounded-md">
		<div
			:class="[
				'flex-none h-6 w-1 rounded-full inline-block',
				buildPrimaryColorElementCssClasses(),
			]"
		/>
		<div
			class="text-gray-950 dark:text-gray-100 flex-grow cursor-pointer"
			@click="open = !open"
		>
			{{ group.text }}
			<!--<span
				class="inline-flex items-center rounded-md bg-gray-200 px-1 py-0.5 text-xs font-medium text-gray-50 ring-1 ring-gray-300 dark:bg-gray-950 dark:ring-gray-950 dark:text-gray-600"
			>
				{{ group.notifications.length }}
			</span>-->
		</div>
		<div class="flex-none">
			<div class="flex gap-x-1">
				<button
					type="button"
					class="p-1 text-gray-600"
					@click="open = !open"
				>
					<i
						:class="[
							'fa w-5',
							(open) ? 'fa-caret-up' : 'fa-caret-down'
						]"
					/>
				</button>
				<button
					type="button"
					:class="[
						'p-1 rounded-md text-gray-600',
						buildPrimaryColorElementCssClasses('hover:')
					]"
					@click="deleteNotifications(group.notifications)"
				>
					<i class="fa fa-trash w-5" />
				</button>
			</div>
		</div>
	</div>
	<div
		v-show="open"
		class="flex flex-col gap-y-1"
	>
		<Notification
			v-for="notification in group.notifications"
			:key="notification.id"
			:notification="notification"
			@close="$emit('close')"
		/>
	</div>
</template>
