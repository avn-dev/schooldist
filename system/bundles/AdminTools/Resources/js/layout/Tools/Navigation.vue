<script lang="ts">
import { defineComponent, PropType, ref } from 'vue3'
import { Dialog as VueDialog, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { Interface } from "../../types"
import { Link as InertiaLink } from '@inertiajs/vue3'

export default defineComponent({
	name: "Navigation",
	components: { InertiaLink, VueDialog, DialogPanel, TransitionChild, TransitionRoot },
	props: {
		interface: { type: Object as PropType<Interface>, required: true }
	},
	setup() {
		const sidebarOpen = ref(true)
		return {
			sidebarOpen
		}
	}
})
</script>

<template>
	<div>
		<TransitionRoot
			as="template"
			:show="sidebarOpen"
		>
			<VueDialog
				as="div"
				class="relative z-50 lg:hidden"
				@close="sidebarOpen = false"
			>
				<TransitionChild
					as="template"
					enter="transition-opacity ease-linear duration-300"
					enter-from="opacity-0"
					enter-to="opacity-100"
					leave="transition-opacity ease-linear duration-300"
					leave-from="opacity-100"
					leave-to="opacity-0"
				>
					<div class="fixed inset-0 bg-gray-800/80" />
				</TransitionChild>

				<div class="fixed inset-0 flex">
					<TransitionChild
						as="template"
						enter="transition ease-in-out duration-300 transform"
						enter-from="-translate-x-full"
						enter-to="translate-x-0"
						leave="transition ease-in-out duration-300 transform"
						leave-from="translate-x-0"
						leave-to="-translate-x-full"
					>
						<DialogPanel class="relative mr-16 flex w-full max-w-64 flex-1">
							<TransitionChild
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
										@click="sidebarOpen = false"
									>
										<span class="sr-only">
											Close sidebar
										</span>
										<i class="fa fa-times h-6 w-6" />
									</button>
								</div>
							</TransitionChild>

							<div
								class="flex grow flex-col gap-y-5 overflow-y-auto bg-gray-800 px-6 pb-2 ring-1 ring-white/10"
							>
								<div class="flex h-16 shrink-0 items-center" />
								<nav class="flex flex-1 flex-col">
									<ul
										role="list"
										class="-mx-2 flex-1 space-y-1"
									>
										<li
											v-for="(node, index) in interface.navigation.nodes"
											:key="index"
										>
											<InertiaLink
												:href="node.url"
												:class="[
													node.active
														? 'bg-gray-700 text-white'
														: 'text-gray-400 hover:text-white hover:bg-gray-700',
													'group flex items-center gap-x-3 rounded-md p-3 text-sm leading-6 font-semibold'
												]"
											>
												<i
													class="h-4 w-4 flex-shrink-0"
													:class="node.icon"
													aria-hidden="true"
												/>
												<span class="truncate">{{ node.text }}</span>
											</InertiaLink>
										</li>
									</ul>
									<div class="mt-auto -mx-2">
										<button
											type="button"
											class="w-full rounded-md p-3 text-sm font-semibold leading-6 text-gray-300 hover:bg-gray-700 hover:text-white flex items-center gap-x-3"
										>
											<span
												class="relative grid h-8 w-8 place-content-center rounded-full bg-primary-900 text-xs text-primary-500"
											>
												{{ interface.user.initials }}
											</span>
											<span class="truncate">{{ interface.user.name || 'Account' }}</span>
										</button>
									</div>
								</nav>
							</div>
						</DialogPanel>
					</TransitionChild>
				</div>
			</VueDialog>
		</TransitionRoot>

		<!-- Static sidebar for desktop -->
		<div class="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:z-50 lg:block lg:w-64 lg:bg-gray-800">
			<div class="flex h-full flex-col">
				<div class="flex h-16 shrink-0 items-center justify-center">
					<img
						src="/admin/assets/media/fidelo_signet_white.svg"
						class="h-8 w-auto"
					>
				</div>

				<nav class="mt-4 flex-1 min-h-0 flex flex-col px-6 pb-2">
					<ul
						role="list"
						class="-mx-2 flex-1 space-y-1 overflow-y-auto"
					>
						<li
							v-for="(node, index) in interface.navigation.nodes"
							:key="index"
						>
							<InertiaLink
								:href="node.url"
								:class="[
									node.active
										? 'bg-gray-700 text-white'
										: 'text-gray-400 hover:text-white hover:bg-gray-700',
									'group flex items-center gap-x-3 rounded-md p-3 text-sm leading-6 font-semibold'
								]"
							>
								<i
									class="h-4 w-4 flex-shrink-0"
									:class="node.icon"
									aria-hidden="true"
								/>
								<span class="truncate">{{ node.text }}</span>
							</InertiaLink>
						</li>
					</ul>

					<div class="mt-auto -mx-2">
						<button
							type="button"
							class="w-full rounded-md p-3 text-sm font-semibold leading-6 text-gray-300 hover:bg-gray-700 hover:text-white flex items-center gap-x-3"
						>
							<span
								class="relative grid h-8 w-8 place-content-center rounded-full bg-primary-900 text-xs text-primary-500"
							>
								{{ interface.user.initials }}
							</span>
							<span class="truncate">{{ interface.user.name || 'Account' }}</span>
						</button>
					</div>
				</nav>
			</div>
		</div>

		<div class="sticky top-0 z-40 flex items-center gap-x-6 bg-gray-800 px-4 py-4 shadow-sm sm:px-6 lg:hidden">
			<button
				type="button"
				class="-m-2.5 p-2.5 text-gray-400 lg:hidden"
				@click="sidebarOpen = true"
			>
				<span class="sr-only">
					Open sidebar
				</span>
				<i
					class="fa fa-bars h-4 w-4 shrink-0"
					aria-hidden="true"
				/>
			</button>
			<div class="flex-1" />
			<button
				type="button"
				class="flex items-center gap-x-4 text-sm font-semibold leading-6"
			>
				<span
					class="relative grid h-8 w-8 place-content-center rounded-full bg-primary-900 text-xs text-primary-500"
				>
					{{ interface.user.initials }}
				</span>
			</button>
		</div>
	</div>
</template>
