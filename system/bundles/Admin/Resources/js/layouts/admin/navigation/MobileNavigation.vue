<script lang="ts">
import { defineComponent } from 'vue3'
import {
	Dialog as HeadlessUiDialog,
	DialogPanel as HeadlessUiDialogPanel,
	TransitionChild as HeadlessUiTransitionChild,
	TransitionRoot as HeadlessUiTransitionRoot
} from '@headlessui/vue'
import type { NavigationNode } from "../../../types/backend/app"
import { useNavigation } from "../../../composables/navigation"
import BasicNavigation from "./BasicNavigation.vue"

export default defineComponent({
	name: "MobileNavigation",
	components: {
		BasicNavigation,
		HeadlessUiDialog,
		HeadlessUiDialogPanel,
		HeadlessUiTransitionChild,
		HeadlessUiTransitionRoot
	},
	emits: ['action'],
	setup() {
		const { navigation, closeNavigation } = useNavigation()

		const nodeAction = (node: NavigationNode) => {
			if (node.action) {
				closeNavigation()
			}
		}

		return {
			navigation,
			closeNavigation,
			nodeAction
		}
	}
})
</script>

<template>
	<HeadlessUiTransitionRoot
		as="template"
		:show="!!navigation.open"
	>
		<HeadlessUiDialog
			as="div"
			class="relative z-50"
			@close="closeNavigation"
		>
			<HeadlessUiTransitionChild
				as="template"
				enter="transition-opacity ease-linear duration-300"
				enter-from="opacity-0"
				enter-to="opacity-100"
				leave="transition-opacity ease-linear duration-300"
				leave-from="opacity-100"
				leave-to="opacity-0"
			>
				<div class="fixed inset-0 bg-gray-700 opacity-80" />
			</HeadlessUiTransitionChild>

			<div class="h-full fixed inset-0 flex">
				<HeadlessUiTransitionChild
					as="template"
					enter="transition ease-in-out duration-300 transform"
					enter-from="-translate-x-full"
					enter-to="translate-x-0"
					leave="transition ease-in-out duration-300 transform"
					leave-from="translate-x-0"
					leave-to="-translate-x-full"
				>
					<HeadlessUiDialogPanel class="relative mr-16 flex w-full max-w-xs flex-1">
						<HeadlessUiTransitionChild
							as="template"
							enter="ease-in-out duration-300"
							enter-from="opacity-0"
							enter-to="opacity-100"
							leave="ease-in-out duration-300"
							leave-from="opacity-100"
							leave-to="opacity-0"
						>
							<div class="absolute left-full top-0 flex w-16 justify-center pt-5">
								<button
									type="button"
									class="-m-2.5 p-2.5 text-white"
									@click="closeNavigation"
								>
									<i class="fa fa-times h-6 w-6" />
								</button>
							</div>
						</HeadlessUiTransitionChild>
						<div class="flex grow flex-col gap-y-5 overflow-y-auto bg-gray-800 ring-1 ring-white/10">
							<BasicNavigation @node="nodeAction" />
						</div>
					</HeadlessUiDialogPanel>
				</HeadlessUiTransitionChild>
			</div>
		</HeadlessUiDialog>
	</HeadlessUiTransitionRoot>
</template>
