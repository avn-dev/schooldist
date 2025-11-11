<script lang="ts">
import { defineComponent, ref, type Ref, type PropType } from 'vue3'
import { UserNotificationToast } from '../../../types/backend/app'
import { useUser } from '../../../composables/user'
import LoadingBarMs from '../../../components/LoadingBarMs.vue'

const COLORS = {
	'success': { bg: 'bg-green-400', text: 'text-green-400'},
	'danger': { bg: 'bg-red-400', text: 'text-red-400' },
	'warning': { bg: 'bg-yellow-400', text: 'text-yellow-400' },
	'info': { bg: 'bg-blue-400', text: 'text-blue-400' },
}

export default defineComponent({
	name: "Toast",
	components: { LoadingBarMs },
	props: {
		toast: { type: Object as PropType<UserNotificationToast>, required: true },
	},
	setup(props) {
		const zIndex: Ref<number> = ref(0)

		const { markNotificationAsSeen } = useUser()

		const close = () => {
			markNotificationAsSeen(props.toast)
		}

		return {
			COLORS,
			zIndex,
			close
		}
	}
})
</script>

<template>
	<div
		aria-live="assertive"
		class="w-96 pointer-events-none inset-0 flex items-end sm:items-start"
	>
		<div class="flex w-full flex-col items-center space-y-4 sm:items-end">
			<!-- Notification panel, dynamically insert this into the live region when it needs to be displayed -->
			<transition
				enter-active-class="transform ease-out duration-300 transition"
				enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
				enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
				leave-active-class="transition ease-in duration-100"
				leave-from-class="opacity-100"
				leave-to-class="opacity-0"
			>
				<div
					v-if="toast"
					class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5"
				>
					<div class="p-4">
						<div class="flex items-start">
							<div class="flex-shrink-0">
								<i
									v-if="toast.alert === 'success'"
									:class="['far fa-check-circle', COLORS[toast.alert].text]"
								/>
								<i
									v-if="toast.alert === 'danger'"
									:class="['far fa-times-circle', COLORS[toast.alert].text]"
								/>
								<i
									v-if="toast.alert === 'warning'"
									:class="['fas fa-exclamation-triangle', COLORS[toast.alert].text]"
								/>
								<i
									v-if="toast.alert === 'info'"
									:class="['fas fa-info-circle', COLORS[toast.alert].text]"
								/>
							</div>
							<div class="ml-3 w-0 flex-1 pt-0.5 text-xs">
								<p
									v-if="toast.subject"
									class="font-medium text-gray-900 mb-1"
								>
									{{ toast.subject }}
								</p>
								<!-- eslint-disable vue/no-v-html -->
								<p
									class="max-h-80 overflow-auto text-gray-500"
									v-html="toast.message"
								/>
							</div>
							<div class="ml-4 flex flex-shrink-0">
								<button
									type="button"
									class="inline-flex rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
									@click="close"
								>
									<i class="fa fa-times" />
								</button>
							</div>
						</div>
					</div>
					<div
						v-if="toast.data.timeout > 0"
						class="p-2"
					>
						<LoadingBarMs
							:ms="toast.data.timeout"
							class="h-1"
							:color="COLORS[toast.alert].bg"
							@finish="close"
						/>
					</div>
				</div>
			</transition>
		</div>
	</div>
</template>
