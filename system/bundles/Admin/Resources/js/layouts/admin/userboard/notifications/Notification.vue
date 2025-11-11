<script lang="ts">
import { defineComponent, PropType } from 'vue'
import { UserNotification, UserNotificationButton } from "../../../../types/backend/app"
import { buildPrimaryColorElementCssClasses } from "../../../../utils/primarycolor"
import { safe } from '../../../../utils/promise'
import { useUser } from "../../../../composables/user"
import { useFileViewer } from '../../../../composables/file_viewer'
import ViewPort from "@Core/components/ViewPort.vue"
import ButtonComponent from '../../../../components/ButtonComponent.vue'
import UserAvatar from '../../../../components/UserAvatar.vue'
import router from '../../../../router'

const COLORS = {
	'success': { bg: 'bg-green-200', text: 'text-green-500' },
	'danger': { bg: 'bg-red-200', text: 'text-red-500' },
	'warning': { bg: 'bg-yellow-200', text: 'text-yellow-500' },
	'info': { bg: 'bg-blue-200', text: 'text-blue-500' },
	'default': { bg: 'bg-gray-100 dark:bg-gray-950', text: 'text-gray-500 dark:text-gray-600' },
}

export default defineComponent({
	name: "Notification",
	components: { UserAvatar, ButtonComponent, ViewPort },
	props: {
		notification: { type: Object as PropType<UserNotification>, required: true },
	},
	emits: ['close'],
	setup(props) {
		const { user, markNotificationAsSeenDelayed, deleteNotification } = useUser()
		const { openFile } = useFileViewer()

		const buttonAction = async (button: UserNotificationButton) => {
			//emit('close')
			await safe(router.get(`/admin/user/notifications/${props.notification.id}/action/${button.key}`))
			/*
			// Execute with a delay so that the closing process is completed
			setTimeout(
				() => to(router.get(`/admin/user/notifications/${props.notification.id}/action/${button.key}`)),
				// duration-500
				2000
			)*/
		}

		return {
			COLORS,
			user,
			buildPrimaryColorElementCssClasses,
			markNotificationAsSeenDelayed,
			deleteNotification,
			openFile,
			buttonAction
		}
	}
})
</script>

<template>
	<div
		:class="[
			'rounded-md flex flex-row items-stretch gap-x-2 group relative py-1.5 px-1 sm:py-3 sm:px-2 text-sm bg-gray-50 hover:bg-gray-100/50 dark:bg-gray-900',
			(!notification.read) ? 'text-black dark:text-white bg-gray-100/50' : 'text-gray-600 hover:text-black dark:text-gray-300 dark:hover:text-white'
		]"
	>
		<div
			v-if="!notification.read"
			:class="[
				'flex-none w-1 rounded-full',
				buildPrimaryColorElementCssClasses()
			]"
		/>
		<div class="flex-none">
			<UserAvatar
				v-if="notification.sender"
				:class="[
					'text-xs size-8',
					[COLORS[notification.alert].bg, COLORS[notification.alert].text].join(' '),
				]"
				:user="notification.sender"
			/>
			<span
				v-else
				:class="[
					'grid items-center place-content-center rounded-full font-semibold text-xs size-8',
					[COLORS[notification.alert].bg, COLORS[notification.alert].text].join(' '),
				]"
			>
				<i :class="notification.icon ? notification.icon : 'fa fa-bell'" />
			</span>
		</div>
		<div class="grow">
			<ViewPort @enter="markNotificationAsSeenDelayed(notification)">
				<!-- eslint-disable vue/no-v-html -->
				<div
					class="text-xs max-w-[20.5rem] max-h-80 overflow-auto"
					v-html="notification.message"
				/>
				<div
					v-if="notification.attachments && notification.attachments.length > 0"
					class="flex flex-row flex-wrap gap-0.5 my-1.5"
				>
					<button
						v-for="(attachment, index) in notification.attachments"
						:key="index"
						class="group rounded cursor-pointer text-xs p-0.5 font-medium text-gray-600 truncate border border-gray-100 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800"
						type="button"
						@click="openFile(attachment.file)"
					>
						<i :class="['text-gray-500 group-hover:text-gray-600 p-1', attachment.icon]" />
						{{ attachment.name }}
					</button>
				</div>
				<div
					v-if="notification.buttons && notification.buttons.length > 0"
					class="flex flex-row flex-wrap gap-0.5 my-1.5"
				>
					<ButtonComponent
						v-for="(button, index) in notification.buttons"
						:key="index"
						class="font-medium"
						color="gray"
						size="small"
						@click="buttonAction(button)"
					>
						{{ button.text }}
					</ButtonComponent>
				</div>
				<div class="flex text-gray-200 text-xs mt-1 dark:text-gray-600 gap-x-1">
					<div>
						<i class="far fa-clock" />
						{{ notification.date_formatted }}
					</div>
					<div class="font-bold hidden sm:inline">
						&centerdot;
					</div>
					<div
						class="truncate hidden sm:inline"
						style="max-width: 150px"
					>
						{{ notification.group }}
					</div>
				</div>
			</ViewPort>
		</div>
		<div class="flex-none">
			<div class="flex flex-row items-center h-full">
				<button
					:class="[
						'p-1 flex-none opacity-0 group-hover:opacity-100 rounded-md text-gray-400 flex items-center',
						buildPrimaryColorElementCssClasses('hover:')
					]"
					type="button"
					@click="deleteNotification(notification)"
				>
					<i class="fa w-5 h-5 fa-trash text-xs" />
				</button>
			</div>
		</div>
	</div>
</template>
