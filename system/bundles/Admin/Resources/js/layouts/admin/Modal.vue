<script lang="ts">
import { defineComponent, nextTick, watch, onMounted, computed, ref, type Ref, type PropType } from 'vue3'
import { Dialog as HeadlessUiDialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { ComponentSize, Modal } from "../../types/backend/app"
import { ContentLoadingState, RouterTarget } from '../../types/backend/router'
import { buildPrimaryColorContrastCssClass } from "../../utils/primarycolor"
import { useModals } from "../../composables/modals"
import { useInterface } from '../../composables/interface'
import ContentLoad from "./ContentLoad.vue"
import RoundedBox from '../../components/RoundedBox.vue'

export default defineComponent({
	name: "Modal",
	components: {
		RoundedBox,
		HeadlessUiDialog,
		DialogPanel,
		DialogTitle,
		TransitionChild,
		TransitionRoot,
		ContentLoad
	},
	props: {
		modal: { type: Object as PropType<Modal>, required: true },
	},
	setup(props) {
		const modalRef: Ref<HTMLDivElement|null> = ref(null)
		const visible: Ref<boolean> = ref(false)
		// Moving modal
		const pos = ref({ x: 0, y: 0 })
		const moving: Ref<boolean> = ref(false)
		let dragStartPos = { x: 0, y: 0 }

		const { closeModal, setModalLoadingState } = useModals()
		const { scope, levelZIndex } = useInterface()

		const setLoadingState = async (state: ContentLoadingState) => {
			setModalLoadingState(props.modal.id, state)
			await centerModal()
		}

		const size = computed(() => {
			if (props.modal.payload) {
				if (props.modal.payload.size === ComponentSize.extra_large) {
					return 'w-full lg:max-w-7xl'
				} else if (props.modal.payload.size === ComponentSize.large) {
					return 'w-full lg:max-w-3xl'
				} else if (props.modal.payload.size === ComponentSize.medium) {
					return 'w-full lg:max-w-xl'
				}
			}
			return 'w-full lg:max-w-md'
		})

		const centerModal = async () => {

			await nextTick()

			let x = 0
			let y = 0
			if (modalRef.value) {
				const rect = modalRef.value.getBoundingClientRect()
				x = Math.round((scope.width - rect?.width ?? 0) / 2)
				y = Math.round((scope.height - rect?.height ?? 0) / 2)

				if (x < 0) x = 0
				if (y < 0) y = 0
			}

			pos.value = { x, y }

			await nextTick()

			if (!visible.value) {
				visible.value = true
			}
		}


		const moveStart = (event: MouseEvent) => {
			dragStartPos = { x: event.clientX, y: event.clientY }
			moving.value = true
			window.addEventListener('mousemove', movingModal)
			window.addEventListener('mouseup', moveEnd)
		}

		const movingModal = (event: MouseEvent) => {
			const x = pos.value.x - (dragStartPos.x - event.clientX)
			const y = pos.value.y - (dragStartPos.y - event.clientY)

			pos.value = { x, y }
			dragStartPos = { x: event.clientX, y: event.clientY }

			event.preventDefault()
		}

		const moveEnd = () => {
			dragStartPos = { x: 0, y: 0 }
			moving.value = false
			window.removeEventListener('mousemove', movingModal)
			window.removeEventListener('mouseup', moveEnd)
		}

		const handleOutsideClosing = () => {
			if (
				props.modal.payload.outer_closable !== false &&
				// TODO not ideal, prevents the modal from being closed from a higher level
				props.modal.level >= levelZIndex.value
			) {
				closeModal(props.modal.id)
			}
		}

		onMounted(centerModal)
		watch(scope, centerModal)

		return {
			RouterTarget,
			modalRef,
			size,
			pos,
			visible,
			moving,
			closeModal,
			handleOutsideClosing,
			moveStart,
			setLoadingState,
			buildPrimaryColorContrastCssClass,
		}
	}
})
</script>

<template>
	<TransitionRoot
		as="template"
		:show="true"
	>
		<HeadlessUiDialog
			as="div"
			class="relative"
			:style="{ 'z-index': modal.level }"
			@close="handleOutsideClosing"
		>
			<TransitionChild
				as="template"
				enter="ease-out duration-300"
				enter-from="opacity-0"
				enter-to="opacity-100"
				leave="ease-in duration-200"
				leave-from="opacity-100"
				leave-to="opacity-0"
			>
				<div class="fixed inset-0 bg-gray-900 opacity-80 transition-opacity dark:bg-gray-950 dark:opacity-90" />
			</TransitionChild>
			<div
				class="fixed inset-0 w-screen"
				:style="{ 'z-index': (modal.level + 1) }"
			>
				<div class="flex min-h-full justify-center text-center">
					<TransitionChild
						as="template"
						enter="ease-out"
						enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
						enter-to="opacity-100 translate-y-0 sm:scale-100"
						leave="ease-in"
						leave-from="opacity-100 translate-y-0 sm:scale-100"
						leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
					>
						<DialogPanel
							:class="[
								'absolute transform text-left transition-transform max-h-[90vh] overflow-hidden events-window',
								{'opacity-50': moving},
								size
							]"
							:style="{ left: `${pos.x}px`, top: `${pos.y}px` }"
						>
							<RoundedBox class="shadow-xl">
								<div
									ref="modalRef"
									:class="['rounded-md bg-white dark:bg-gray-800 p-1', {'opacity-0': !visible}]"
								>
									<div class="max-h-full flex flex-col">
										<div class="flex-none">
											<div :class="['h-1 rounded-full', buildPrimaryColorContrastCssClass('bg')]" />
											<div
												class="flex px-2 py-2 sm:px-3 items-center text-black dark:text-gray-50"
											>
												<DialogTitle
													as="h3"
													:class="[
														'grow text-base font-heading font-semibold leading-6 text-gray-900 dark:text-gray-200',
														{'cursor-move': modal.payload.moveable}
													]"
													@mousedown="moveStart($event)"
												>
													{{ (Array.isArray(modal.payload.title)) ? modal.payload.title.join(' / ') : modal.payload.title }}
												</DialogTitle>
												<div class="flex-none">
													<div class="flex gap-x-1">
														<button
															v-if="modal.payload.closable"
															type="button"
															class="px-1.5 py-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-900 dark:text-gray-200 flex items-center"
															@click.stop="closeModal(modal.id)"
														>
															<i class="fa fa-times" />
														</button>
													</div>
												</div>
											</div>
										</div>
										<div class="grow bg-white dark:bg-gray-800 border-t border-gray-50 dark:border-gray-700 rounded-b-md overflow-hidden">
											<ContentLoad
												:source="{source: RouterTarget.modal, payload: { id: modal.id }}"
												:current-state="modal.state"
												:content="modal.payload.content"
												:payload-storable="modal.payload_storable"
												:payload-additional="modal.payload_additional"
												@state="setLoadingState"
												@close="closeModal(modal.id, $event, true)"
											/>
										</div>
									</div>
								</div>
							</RoundedBox>
						</DialogPanel>
					</TransitionChild>
				</div>
			</div>
		</HeadlessUiDialog>
	</TransitionRoot>
</template>
