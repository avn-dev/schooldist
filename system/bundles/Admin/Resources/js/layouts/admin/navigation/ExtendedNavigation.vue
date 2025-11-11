<script lang="ts">
import { defineComponent, ref, watchEffect, computed, type ComputedRef, type Ref } from 'vue3'
import {
	TransitionRoot as HeadlessUiTransitionRoot,
	TransitionChild as HeadlessUiTransitionChild
} from '@headlessui/vue'
import type { NavigationNode } from "../../../types/backend/app"
import { buildPrimaryColorColorSchemeCssClass } from "../../../utils/primarycolor"
import { useNavigation } from "../../../composables/navigation"
import { useInterface } from '../../../composables/interface'
import { useTooltip } from '../../../composables/tooltip'
import ExpandableNode from "./components/ExpandableNode.vue"
import router from "../../../router"

export default defineComponent({
	name: "ExtendedNavigation",
	components: { ExpandableNode, HeadlessUiTransitionRoot, HeadlessUiTransitionChild },
	emits: ['action'],
	setup() {
		const { logo } = useInterface()
		const { navigation, getNavigationNodeChilds, handleNavigationNodeClick } = useNavigation()
		const { showTooltip } = useTooltip()

		const childNodes: Ref<NavigationNode[]> = ref([])
		// @ts-ignore TODO
		const baseNodes: ComputedRef<NavigationNode[]> = computed(() => navigation.nodes.filter((loop: NavigationNode) => !loop.parent) as NavigationNode[])
		const activeNode: Ref<NavigationNode|null> = ref(null)

		watchEffect(async () => {
			// @ts-ignore TODO
			const activeBaseNode = navigation.nodes.find((loop: NavigationNode) => !loop.parent && loop.active)
			if (activeBaseNode) {
				// @ts-ignore TODO
				activeNode.value = activeBaseNode
				if (activeBaseNode.active === true && !activeBaseNode.action) {
					childNodes.value = await getNavigationNodeChilds(activeBaseNode.id)
				}
			}
		})

		const nodeAction = async (node: NavigationNode) => {
			await handleNavigationNodeClick(node.id)
		}

		return {
			logo,
			baseNodes,
			activeNode,
			childNodes,
			buildPrimaryColorColorSchemeCssClass,
			showTooltip,
			openUserBoard: router.openUserBoard,
			nodeAction
		}
	}
})
</script>

<template>
	<div class="h-full lg:inset-y-0 lg:z-50 lg:flex">
		<div class="h-full w-16 flex grow flex-col gap-y-5 px-4 pb-4 z-40">
			<div class="flex h-16 shrink-0 items-center">
				<img
					:src="logo.framework_small"
					class="h-8 w-auto"
					alt=""
				>
			</div>
			<nav class="-mx-1 flex grow flex-col overflow-y-auto">
				<ul
					role="list"
					class="space-y-1"
				>
					<li
						v-for="node in baseNodes"
						:key="node.id"
					>
						<button
							type="button"
							:class="[
								'w-full place-content-center flex flex-nowrap rounded-md p-3 text-sm leading-6 font-semibold hover:bg-gray-700 group relative',
								node.active ? 'text-gray-100 bg-gray-700' : 'text-gray-300'
							]"
							@click="nodeAction(node)"
							@mouseenter="showTooltip(node.text, $event, 'right')"
						>
							<div
								v-if="node.active"
								class="absolute flex h-full w-2 left-1 top-0 items-center"
							>
								<span
									:class="[
										'h-6 w-1 rounded-full',
										buildPrimaryColorColorSchemeCssClass('bg', 'dark'),
									]"
								/>
							</div>
							<i :class="node.icon" />
							<!--<i
								v-if="!node.action"
								class="absolute -right-2 fa fa-chevron-right text-gray-600 text-xs ml-2"
							/>
							<span class="z-50 pointer-events-none absolute top-0 left-0 rounded-md bg-gray-900 px-2 py-1 text-sm font-medium text-gray-50 opacity-0 shadow transition-opacity group-hover:opacity-100">
								{{ node.text }}
							</span>-->
						</button>
					</li>
				</ul>
			</nav>
			<!--<div
				v-if="user"
				class="-mx-2 mt-auto hidden lg:inline-flex"
			>
				<button
					type="button"
					class="flex w-full items-center gap-x-4 px-2 py-1 leading-6 rounded-md hover:bg-gray-700"
					@click="openUserBoard"
				>
					<div class="flex">
						<UserAvatar
							:user="user"
							class="text-xs h-8 w-8 relative"
							:class="[
								generatePrimaryColorCssClass('bg', 1),
								generatePrimaryColorCssClass('text', -8)
							]"
						>
							<span
								class="animate-ping absolute -right-0 -top-0 rounded text-xs h-2 w-2"
								:class="[
									generatePrimaryColorCssClass('bg', -5),
								]"
							/>
							<span
								class="absolute -right-0 -top-0 rounded text-xs h-2 w-2"
								:class="[
									generatePrimaryColorCssClass('bg', -5),
								]"
							/>
						</UserAvatar>
					</div>
				</button>
			</div>-->
		</div>
		<HeadlessUiTransitionRoot
			as="template"
			:show="!!(activeNode && !activeNode.action)"
		>
			<HeadlessUiTransitionChild
				as="template"
				enter="transition ease-in-out duration-300 transform"
				enter-from="-translate-x-full"
				enter-to="translate-x-0"
				leave="transition ease-in-out duration-300 transform"
				leave-from="translate-x-0"
				leave-to="-translate-x-full"
			>
				<div class="flex flex-col w-56 h-full p-4 border-l border-gray-700 dark:border-gray-900 z-30">
					<div
						v-if="activeNode.state === 'loading'"
						class="p-2 text-center"
					>
						<i class="fa fa-spinner fa-spin" />
					</div>
					<div v-else>
						<div class="flex shrink-0 mb-2 text-lg items-center">
							<i :class="['text-gray-500 mr-2', activeNode.icon]" />
							<span class="text-gray-100">
								{{ activeNode.text }}
							</span>
						</div>
						<nav class="-mx-2 flex grow flex-col overflow-x-hidden overflow-y-auto">
							<ul
								role="list"
								class="space-y-1"
							>
								<li
									v-for="node in childNodes"
									:key="node.id"
								>
									<ExpandableNode :node="node" />
								</li>
							</ul>
						</nav>
					</div>
				</div>
			</HeadlessUiTransitionChild>
		</HeadlessUiTransitionRoot>
	</div>
</template>
