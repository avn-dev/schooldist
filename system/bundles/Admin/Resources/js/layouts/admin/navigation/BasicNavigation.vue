<script lang="ts">
import { defineComponent, computed, type ComputedRef} from 'vue3'
import { NavigationNode } from "../../../types/backend/app"
import { useNavigation } from '../../../composables/navigation'
import { useInterface } from '../../../composables/interface'
import { useTooltip } from '../../../composables/tooltip'
import ExpandableNode from "./components/ExpandableNode.vue"

export default defineComponent({
	name: "BasicNavigation",
	components: { ExpandableNode },
	emits: ['node'],
	setup() {
		const { logo } = useInterface()
		const { navigation } = useNavigation()
		const { server, version } = useInterface()
		const { showTooltip } = useTooltip()

		// @ts-ignore TODO
		const baseNodes: ComputedRef<NavigationNode[]> = computed(() => navigation.nodes.filter((loop: NavigationNode) => !loop.parent) as NavigationNode[])

		return {
			logo,
			baseNodes,
			server,
			version,
			showTooltip
		}
	}
})
</script>

<template>
	<div class="h-full lg:inset-y-0 lg:z-50 lg:flex lg:w-66 lg:flex-col">
		<!-- Sidebar component, swap this element with another sidebar if you like -->
		<div class="flex grow flex-col gap-y-3 px-6 pb-2 overflow-hidden">
			<div class="flex h-12 shrink-0 items-center">
				<a href="">
					<img
						:src="logo.framework"
						class="h-8 w-auto"
						alt=""
						@mouseenter="(version && server) ? showTooltip(`${server} / ${version}`, $event, 'right') : false"
					>
				</a>
			</div>
			<nav class="-mx-2 flex grow flex-col overflow-x-hidden overflow-y-auto">
				<ul
					role="list"
					class="space-y-1"
				>
					<li
						v-for="node in baseNodes"
						:key="node.id"
					>
						<ExpandableNode
							:node="node"
							@node="$emit('node', $event)"
						/>
					</li>
				</ul>
			</nav>
			<div
				v-if="server || version"
				class="-mx-2 mt-auto hidden lg:block gap-2 text-gray-500"
			>
				<div class="flex justify-between">
					<div v-if="server">
						{{ server }}
					</div>
					<div v-if="version">
						{{ version }}
					</div>
				</div>
			</div>
		</div>
	</div>
</template>
