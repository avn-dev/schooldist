<script lang="ts">
import { defineComponent, ref, watchEffect, type Ref, type PropType } from 'vue3'
import { TransitionRoot as HeadlessUiTransitionRoot } from '@headlessui/vue'
import { NavigationNode } from "../../../../types/backend/app"
import { LoadingState } from "../../../../types/backend/router"
import { buildPrimaryColorColorSchemeCssClass } from "../../../../utils/primarycolor"
import { useNavigation } from "../../../../composables/navigation"

export default defineComponent({
	name: "ExpandableNode",
	components: { HeadlessUiTransitionRoot },
	props: {
		node: { type: Object as PropType<NavigationNode>, required: true },
		parent: { type: Object as PropType<NavigationNode>, default: null }
	},
	emits: ['node'],
	setup(props, { emit }) {
		const { handleNavigationNodeClick, getNavigationNodeChilds } = useNavigation()
		const childNodes: Ref<NavigationNode[]> = ref([])

		watchEffect(async () => {
			if (props.node.active === true && !props.node.action) {
				childNodes.value = await getNavigationNodeChilds(props.node.id)
			}
		})

		const nodeAction = async (node: NavigationNode) => {
			await handleNavigationNodeClick(node.id)
			emit('node', node)
		}

		return {
			LoadingState,
			childNodes,
			nodeAction,
			buildPrimaryColorColorSchemeCssClass
		}
	}
})
</script>

<template>
	<!-- Node with action -->
	<button
		v-if="node.action"
		type="button"
		:class="[
			'flex items-center w-full text-left rounded-md px-2 py-1.5 gap-x-2 text-sm leading-6 font-body',
			{
				'text-gray-50 bg-gray-700': (!parent && node.active),
				'text-gray-200 hover:bg-gray-700': (!parent && !node.active),
				'text-gray-50 bg-gray-600': (parent && node.active),
				'text-gray-200 hover:bg-gray-600 hover:text-gray-50': (parent && !node.active)
			}
		]"
		@click.stop="nodeAction(node)"
	>
		<i
			:class="[
				'w-4 shrink-0',
				node.icon,
				(node.active) ? buildPrimaryColorColorSchemeCssClass('text', 'dark') : 'text-gray-500'
			]"
		/>
		{{ node.text }}
	</button>
	<!-- Node with childs -->
	<div v-else>
		<button
			:class="[
				'flex items-center w-full text-left rounded-md px-2 py-1.5 gap-x-2 text-sm leading-6 font-body',
				(node.active || (node.state && node.state.state === LoadingState.loading)) ? 'bg-gray-700' : '',
				(parent && !node.active) ? 'text-gray-200 hover:bg-gray-600 hover:text-gray-50' : 'text-gray-200 hover:bg-gray-700'
			]"
			@click.stop="nodeAction(node)"
		>
			<i
				v-if="node.state && node.state.state === LoadingState.loading"
				:class="[
					'fa fa-spinner fa-spin',
					buildPrimaryColorColorSchemeCssClass('text', 'dark')
				]"
			/>
			<i
				v-else
				:class="[
					'w-4 shrink-0',
					node.icon,
					(node.active) ? buildPrimaryColorColorSchemeCssClass('text', 'dark') : 'text-gray-500'
				]"
			/>
			{{ node.text }}
			<i
				:class="[
					'text-gray-500 ml-auto shrink-0 text-xs',
					node.active ? 'fa fa-chevron-down' : 'fa fa-chevron-left',
					(!parent) ? 'mr-2' : ''
				]"
			/>
		</button>
		<div
			v-show="node.active || (node.state && node.state.state === LoadingState.loading)"
			:class="[
				'-mt-2 pl-4 rounded-b-md',
				{
					'bg-gray-700': node.active || (node.state && node.state.state === LoadingState.loading),
					'pr-2 pb-2': !parent
				}
			]"
		>
			<div
				v-if="node.state && node.state.state === LoadingState.failed"
				class="pt-2"
			>
				<div class="rounded-md bg-red-400/10 px-2 py-1 text-red-400 ring-1 ring-inset ring-red-400/20">
					<div class="flex items-center">
						<div class="flex-none">
							<i class="fa fa-times-circle text-red-400" />
						</div>
						<div class="grow ml-2">
							<div class="text-sm font-medium text-red-400 text-wrap">
								{{ $l10n.translate('navigation.node.loading_failed') }}
							</div>
						</div>
					</div>
				</div>
			</div>
			<HeadlessUiTransitionRoot
				as="ul"
				:show="!!(node.active && node.state && node.state.state === LoadingState.loaded)"
				enter="transition-opacity duration-500"
				enter-from="opacity-0"
				enter-to="opacity-100"
				leave="transition-opacity duration-500"
				leave-from="opacity-100"
				leave-to="opacity-0"
				:class="[
					'flex flex-col pt-2 space-y-0.5',
					(node.active) ? 'block' : 'hidden'
				]"
			>
				<li
					v-for="childNode in childNodes"
					:key="childNode.id"
				>
					<ExpandableNode
						:node="childNode"
						:parent="node"
						@node="$emit('node', $event)"
					/>
				</li>
			</HeadlessUiTransitionRoot>
		</div>
	</div>
</template>
