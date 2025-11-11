<script lang="ts">
import { defineComponent, computed, type PropType } from 'vue3'
import {
	Dialog as HeadlessUiDialog,
	DialogPanel as HeadlessUiDialogPanel,
	TransitionRoot as HeadlessUiTransitionRoot,
	TransitionChild as HeadlessUiTransitionChild
} from '@headlessui/vue'
import { ComponentSize, SlideOverPanel } from "../../types/backend/app"
import { RouterTarget, LoadingState } from '../../types/backend/router'
import { useSlideOver } from "../../composables/slideover"
import { useInterface } from '../../composables/interface'
import ContentLoad from "./ContentLoad.vue"

export default defineComponent({
	name: "SlideOver",
	components: { ContentLoad, HeadlessUiDialog, HeadlessUiDialogPanel, HeadlessUiTransitionRoot, HeadlessUiTransitionChild },
	props: {
		panel: { type: Object as PropType<SlideOverPanel>, required: true },
	},
	setup(props) {
		const { closeSlideOver, setSlideOverState } = useSlideOver()
		const { levelZIndex } = useInterface()

		const size = computed(() => {
			if (props.panel.payload) {
				if (props.panel.payload.size === ComponentSize.extra_large) {
					return 'lg:max-w-4xl'
				} else if (props.panel.payload.size === ComponentSize.large) {
					return 'lg:max-w-xl'
				} else if (props.panel.payload.size === ComponentSize.medium) {
					return 'lg:max-w-md'
				}
			}
			return 'lg:max-w-md'
		})

		const handleOutsideClosing = () => {
			if (
				props.panel.payload.outer_closable !== false &&
				props.panel.level >= levelZIndex.value
			) {
				// TODO not ideal, prevents the slideover from being closed from a higher level
				closeSlideOver(props.panel.id)
			}
		}

		return {
			RouterTarget,
			LoadingState,
			size,
			setSlideOverState,
			closeSlideOver,
			handleOutsideClosing
		}
	}
})
</script>

<template>
	<HeadlessUiTransitionRoot
		as="template"
		:show="panel.active"
	>
		<HeadlessUiDialog
			as="div"
			class="relative"
			:style="{ 'z-index': panel.level }"
			@close="handleOutsideClosing"
		>
			<HeadlessUiTransitionChild
				as="template"
				class="opacity-0"
				enter="ease-in-out duration-500"
				enter-from="opacity-0"
				enter-to="opacity-100"
				leave="ease-in-out duration-500"
				leave-from="opacity-100"
				leave-to="opacity-0"
			>
				<div class="fixed inset-0 bg-gray-900 opacity-80 transition-opacity dark:bg-gray-950 dark:opacity-90" />
			</HeadlessUiTransitionChild>
			<div class="fixed inset-0">
				<div class="absolute inset-0">
					<div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
						<HeadlessUiTransitionChild
							as="template"
							class="translate-x-full"
							enter="transform transition ease-in-out duration-500 sm:duration-700"
							enter-from="translate-x-full"
							enter-to="translate-x-0"
							leave="transform transition ease-in-out duration-500 sm:duration-700"
							leave-from="translate-x-0"
							leave-to="translate-x-full"
						>
							<HeadlessUiDialogPanel
								:class="[
									'pointer-events-auto relative w-screen',
									size
								]"
							>
								<div class="absolute left-0 top-0 -ml-6 flex pr-1 pt-4 lg:-ml-8 lg:pr-4">
									<button
										v-if="panel.payload.closable && panel.state && panel.state.state === LoadingState.loaded"
										type="button"
										class="relative text-gray-200 hover:text-white"
										@click="closeSlideOver(panel.id)"
									>
										<i
											class="fa fa-times"
											aria-hidden="true"
										/>
									</button>
								</div>
								<div class="h-full p-2">
									<!-- eslint-disable @typescript-eslint/no-explicit-any -->
									<ContentLoad
										:source="{ source: RouterTarget.slideOver, payload: { id: panel.id } }"
										:current-state="panel.state"
										:content="panel.payload.content"
										:payload-storable="panel.payload_storable"
										@state="(state) => setSlideOverState(panel.id, state)"
										@close="(data, force) => closeSlideOver(panel.id, data, force)"
									/>
								</div>
							</HeadlessUiDialogPanel>
						</HeadlessUiTransitionChild>
					</div>
				</div>
			</div>
		</HeadlessUiDialog>
	</HeadlessUiTransitionRoot>
</template>
