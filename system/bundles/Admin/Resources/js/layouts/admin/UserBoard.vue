<script lang="ts">
import { defineComponent, ref, type Ref, type PropType } from 'vue'
import {
	Switch as HeadlessUiSwitch,
	Menu as HeadlessUiMenu,
	MenuButton as HeadlessUiMenuButton,
	MenuItem as HeadlessUiMenuItem,
	MenuItems as HeadlessUiMenuItems,
	TransitionRoot as HeadlessUiTransitionRoot
} from '@headlessui/vue'
import { UserBoardTab, SystemButton } from "../../types/backend/app"
import { ComponentApiInterface, RouterAction } from "../../types/backend/router"
import { buildPrimaryColorElementCssClasses } from "../../utils/primarycolor"
import { sleep } from '../../utils/util'
import { safe } from '../../utils/promise'
import { useUser } from "../../composables/user"
import { useTooltip } from '../../composables/tooltip'
import router from "../../router"
import UserAvatar from "../../components/UserAvatar.vue"
import AlertMessage from "../../components/AlertMessage.vue"
import RoundedBox from "../../components/RoundedBox.vue"
import ButtonComponent from '../../components/ButtonComponent.vue'
import UserNotifications from './userboard/UserNotifications.vue'

export default defineComponent({
	name: "UserBoard",
	components: {
		ButtonComponent,
		RoundedBox,
		AlertMessage,
		UserAvatar,
		UserNotifications,
		HeadlessUiSwitch,
		HeadlessUiMenu,
		HeadlessUiMenuButton,
		HeadlessUiMenuItem,
		HeadlessUiMenuItems,
		HeadlessUiTransitionRoot
	},
	props: {
		api: { type: Object as PropType<ComponentApiInterface>, required: true },
		profile: { type: Object as PropType<RouterAction>, required: true },
		tabs: { type: Array as PropType<UserBoardTab[]>, required: true },
		buttons: { type: Array as PropType<SystemButton[]>, required: true },
	},
	emits: ['close'],
	setup(props, { emit }) {
		const { user } = useUser()
		const { showTooltip } = useTooltip()
		const loading: Ref<boolean> = ref(false)
		const localButtons: Ref<SystemButton[]> = ref([...props.buttons])
		const buttonMessage: Ref<{ type: string, message: string } | null> = ref(null)
		const executingButton: Ref<string | null> = ref(null)

		const openProfile = () => {
			emit('close')
			router.action(props.profile)
		}

		// eslint-disable-next-line
		const buttonAction = async (button: SystemButton, option?: any) => {
			if (button.options && button.options.length > 0 && typeof option === 'undefined') {
				return false
			}

			executingButton.value = button.key

			try {
				const params = { button: button.key, option: null }
				const url: URL = new URL(`/admin/interface/buttons/${button.key}`, document.baseURI)
				if (typeof option !== 'undefined') {
					url.searchParams.append('option', option)
					params.option = option
				}

				/* eslint-disable @typescript-eslint/no-unused-vars */
				const [error, response] = await safe<{ button: SystemButton, message?: { type: string, message: string } }>(props.api.action('button', { params: params }))

				if (response) {
					const index = localButtons.value.findIndex((loop: SystemButton) => loop.key === response.button.key)
					if (index !== -1) {
						localButtons.value[index] = response.button
					}
					if (response.message) {
						buttonMessage.value = response.message
						sleep(5000).then(() => buttonMessage.value = null)
					}
				}

			} catch (e) {
				console.error(e)
			}

			executingButton.value = null
		}

		return {
			user,
			loading,
			executingButton,
			buttonMessage,
			localButtons,
			openProfile,
			buttonAction,
			showTooltip,
			buildPrimaryColorElementCssClasses
		}
	}
})
</script>

<template>
	<div class="h-full flex flex-col gap-y-2">
		<RoundedBox class="flex-1">
			<div class="h-full flex flex-col">
				<div class="flex-none">
					<div class="flex w-full items-center gap-x-4 leading-6 p-4">
						<div class="flex-none">
							<UserAvatar
								:user="user"
								:class="[
									'text-base size-14',
									buildPrimaryColorElementCssClasses()
								]"
							/>
						</div>
						<div class="flex-col grow text-left">
							<div class="text-gray-800 font-semibold text-sm dark:text-gray-50">
								{{ user.name }}
							</div>
							<div class="text-gray-600 text-sm dark:text-gray-300">
								{{ user.email }}
							</div>
						</div>
						<button
							class="text-lg text-gray-400 hover:text-gray-600 dark:text-gray-200 dark:hover:text-gray-400"
							@click="openProfile"
							@mouseenter="showTooltip($l10n.translate('userboard.btn.my'), $event, 'left')"
						>
							<i class="fa fa-cog" />
						</button>
					</div>
				</div>
				<!-- h-64 damit das overflow richtig funktioniert -->
				<div class="h-64 grow max-h-full">
					<div
						v-if="loading"
						class="text-center p-4 text-gray-300"
					>
						<i class="fa fa-spinner fa-spin" />
					</div>
					<div
						v-else
						class="h-full flex flex-col pb-2"
					>
						<div
							v-if="tabs.length > 1"
							class="flex-none"
						>
							<nav class="-mb-px flex p-1.5">
								<button
									v-for="tab in tabs"
									:key="tab.key"
									:class="[
										'w-1/3 py-1 px-0.5 text-center text-sm rounded-md',
										tab.active
											? buildPrimaryColorElementCssClasses()
											: 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 hover:border-gray-300 dark:hover:text-gray-100'
									]"
									:aria-current="tab.active ? 'page' : undefined"
								>
									{{ tab.text }}
								</button>
							</nav>
						</div>
						<div class="grow max-h-full overflow-auto">
							<div
								v-for="tab in tabs"
								:key="tab.key"
								class="max-h-full"
							>
								<component
									:is="tab.component"
									v-if="tab.active"
									@close="$emit('close')"
								/>
							</div>
						</div>
					</div>
				</div>
			</div>
		</RoundedBox>
		<RoundedBox class="flex-none">
			<div class="p-2">
				<HeadlessUiTransitionRoot
					:show="buttonMessage !== null"
					enter="transform transition duration-300"
					enter-from="translate-y-4 opacity-0"
					enter-to="translate-y-0 opacity-100"
					leave="transform transition duration-300"
					leave-from="translate-y-0 opacity-100"
					leave-to="translate-y-4 opacity-0"
				>
					<AlertMessage
						v-if="buttonMessage"
						:type="buttonMessage.type"
						:message="buttonMessage.message"
						class="border p-4 text-sm mb-2"
					/>
				</HeadlessUiTransitionRoot>
				<ul
					role="list"
					class="grid grid-cols-4 gap-2"
				>
					<HeadlessUiMenu
						v-for="button in localButtons"
						:key="button.key"
						as="li"
						class="col-span-1 flex flex-col divide-y divide-gray-200"
					>
						<div class="relative">
							<HeadlessUiMenuButton
								as="div"
								:class="[
									'group flex flex-1 flex-col p-2 rounded-lg text-center justify-items-center cursor-pointer',
									(button.active) ? buildPrimaryColorElementCssClasses() : 'bg-gray-50 hover:bg-gray-100 text-gray-600 group-hover:text-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-950 dark:hover:text-gray-300'
								]"
								@click="() => buttonAction(button)"
							>
								<div
									:class="[
										'text-xl p-2',
										(!button.active) ? 'text-gray-300 group-hover:text-gray-400 dark:text-gray-400' : '',
									]"
								>
									<i
										v-if="executingButton === button.key"
										class="fa fa-spinner fa-spin"
									/>
									<i
										v-else
										:class="button.icon"
									/>
								</div>
								<div class="text-xs font-medium w-full truncate">
									{{ button.text }}
								</div>
							</HeadlessUiMenuButton>
							<transition
								v-if="button.options.length > 0"
								enter-active-class="transition ease-out duration-100"
								enter-from-class="transform opacity-0 scale-95"
								enter-to-class="transform opacity-100 scale-100"
								leave-active-class="transition ease-in duration-75"
								leave-from-class="transform opacity-100 scale-100"
								leave-to-class="transform opacity-0 scale-95"
							>
								<HeadlessUiMenuItems
									class="absolute right-0 bottom-20 py-1 z-10 mt-2 w-56 origin-top-right divide-y divide-gray-100 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
								>
									<HeadlessUiMenuItem
										v-for="(option, index) in button.options"
										:key="index"
										v-slot="{ active }"
									>
										<button
											type="button"
											:class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'text-left w-full block px-4 py-2 text-sm']"
											@click="() => buttonAction(button, option.value)"
										>
											{{ option.text }}
										</button>
									</HeadlessUiMenuItem>
								</HeadlessUiMenuItems>
							</transition>
						</div>
					</HeadlessUiMenu>
				</ul>
			</div>
		</RoundedBox>
	</div>
</template>
