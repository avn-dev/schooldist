<script lang="ts">
import { defineComponent, computed, inject, type ComputedRef, type PropType } from 'vue'
import { Emitter } from 'mitt'
import { ChatState } from '../../types/backend/app'
import { ComponentApiInterface } from "../../types/backend/router"
import { Events } from '../../utils/backend/app'
import { useSupport } from '../../composables/support'
import RoundedBox from "../../components/RoundedBox.vue"

export default defineComponent({
	name: "UserBoard",
	components: { RoundedBox },
	props: {
		api: { type: Object as PropType<ComponentApiInterface>, required: true }
	},
	emits: ['close'],
	setup(props, { emit }) {
		const { features, chatState } = useSupport()

		const chatAvailable: ComputedRef<boolean> = computed(() => [ChatState.online, ChatState.away].includes(chatState.value))
		const emitter: Emitter<Events> | undefined = inject('emitter')

		const openHelpDesk = (url: string) => {
			window.open(url)
		}

		const openSupportChat = () => {
			if (emitter && chatAvailable.value) {
				emit('close')
				emitter.emit('support.chat.open')
			}
		}

		return {
			ChatState,
			chatAvailable,
			features,
			chatState,
			openHelpDesk,
			openSupportChat
		}
	}
})
</script>

<template>
	<div class="">
		<RoundedBox>
			<ul
				role="list"
				class="flex flex-col gap-4 p-2"
			>
				<li v-if="features.helpdesk">
					<div
						class="group flex items-center gap-4 px-2 py-1 rounded-md hover:bg-gray-50 cursor-pointer"
						@click="openHelpDesk(features.helpdesk)"
					>
						<div class="text-xl py-2 px-3 text-gray-300 group-hover:text-gray-400 bg-gray-50 group-hover:bg-gray-100 rounded-md">
							<i class="fas fa-life-ring" />
						</div>
						<div class="min-w-0">
							<p class="text-sm/6 font-semibold text-gray-900">
								{{ $l10n.translate('support.label.help_center') }}
							</p>
							<p class="mt-1 text-xs/5 text-gray-500">
								{{ $l10n.translate('support.text.help_center') }}
							</p>
						</div>
					</div>
				</li>
				<li v-if="features.support_chat">
					<div
						:class="['group flex items-center gap-4 px-2 py-1 rounded-md', { 'hover:bg-gray-50 cursor-pointer': chatAvailable }]"
						@click="openSupportChat"
					>
						<div
							:class="[
								'text-xl py-2 px-3 text-gray-300 bg-gray-50 rounded-md',
								{ 'group-hover:text-gray-400 group-hover:bg-gray-100': chatAvailable }
							]"
						>
							<i class="fas fa-comments" />
						</div>
						<div class="min-w-0">
							<p class="text-sm/6 font-semibold text-gray-900">
								{{ $l10n.translate('support.label.chat') }}
								<span
									:class="{
										'text-green-500': chatState === ChatState.online,
										'text-yellow-500': chatState === ChatState.away,
										'text-red-500': chatState === ChatState.offline
									}"
								>
									({{ $l10n.translate(`interface.support.chat.${chatState}`) }})
								</span>
							</p>
							<p class="mt-1 text-xs/5 text-gray-500">
								{{ $l10n.translate('support.text.chat') }}
							</p>
						</div>
					</div>
				</li>
			</ul>
		</RoundedBox>
	</div>
</template>
