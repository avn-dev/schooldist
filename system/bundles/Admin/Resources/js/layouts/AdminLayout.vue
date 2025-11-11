<script lang="ts">
import { PropType, defineComponent, onMounted } from 'vue'
import { Head as InertiaHead } from '@inertiajs/vue3'
import { InterfaceBackend as Interface } from '../types/backend/app'
import { useTabs } from '../composables/tabs'
import { useInterface } from '../composables/interface'
import { useModals } from '../composables/modals'
import { useSlideOver } from '../composables/slideover'
import SlideOver from './admin/SlideOver.vue'
import Modal from './admin/Modal.vue'
import SearchBar from './admin/SearchBar.vue'
import Navigation from './admin/Navigation.vue'
import ContextMenu from '../components/ContextMenu.vue'
import Gui2Dialog from './admin/Gui2Dialog.vue'
import Notifications from './admin/Notifications.vue'
import Tooltip from '../components/Tooltip.vue'

export default defineComponent({
	name: 'AdminLayout',
	components: { Tooltip, Notifications, Gui2Dialog, ContextMenu, InertiaHead, SearchBar, Modal, SlideOver, Navigation },
	props: {
		interface: { type: Object as PropType<Interface>, required: true },
		title: { type: String, required: true },
	},
	setup() {
		const { fetchTabs } = useTabs()
		const ui = useInterface()
		const { modals } = useModals()
		const { panels } = useSlideOver()

		onMounted(() => fetchTabs())

		return {
			ui,
			modals,
			panels
		}
	}
})
</script>

<template>
	<div
		:data-mode="ui.colorScheme.value"
		class="h-screen overflow-hidden"
	>
		<InertiaHead>
			<title>{{ title }}</title>
		</InertiaHead>
		<div class="flex h-full text-sm bg-gray-800 text-gray-900 dark:text-gray-200">
			<Navigation class="flex-none" />
			<div class="grow overflow-hidden bg-gray-50 dark:bg-gray-900">
				<slot />
			</div>
			<SearchBar
				:data-mode="ui.colorScheme.value"
				class="text-gray-900"
			/>
			<SlideOver
				v-for="panel in panels"
				:key="panel.id"
				:panel="panel"
				:data-mode="ui.colorScheme.value"
				class="text-gray-900"
			/>
			<Modal
				v-for="modal in modals"
				:key="modal.id"
				:modal="modal"
				:data-mode="ui.colorScheme.value"
				class="text-gray-900"
			/>
			<Teleport to="body">
				<!-- ContextMenu -->
				<ContextMenu />
				<!-- Tooltip -->
				<Tooltip />
			</Teleport>
			<Gui2Dialog />
			<Notifications />
		</div>
	</div>
</template>