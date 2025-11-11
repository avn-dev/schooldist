<script lang="ts">
import { defineComponent, onMounted } from 'vue'
import Tooltip from '../components/Tooltip.vue'
import ContextMenu from '../components/ContextMenu.vue'
import Modal from '../layouts/admin/Modal.vue'
import { useModals } from '../composables/modals'
import { useInterface } from '../composables/interface'

export default defineComponent({
	name: "AppSlim",
	components: { Modal, ContextMenu, Tooltip },
	setup() {
		const { modals } = useModals()
		const { colorScheme, initScope } = useInterface()

		onMounted(initScope)

		return {
			modals,
			colorScheme
		}
	}
})
</script>

<template>
	<Teleport to="body">
		<Modal
			v-for="modal in modals"
			:key="modal.id"
			:modal="modal"
			:data-mode="colorScheme.value"
			class="text-gray-900"
		/>
		<!-- ContextMenu -->
		<ContextMenu />
		<!-- Tooltip -->
		<Tooltip />
	</Teleport>
</template>
