<script lang="ts">
import { defineComponent, ref, type Ref, type PropType } from 'vue'
import { DashboardWidget } from '../../types/backend/app'
import RoundedBox from '../RoundedBox.vue'
import ButtonComponent from '../ButtonComponent.vue'


export default defineComponent({
	name: "UnusedWidgets",
	components: { ButtonComponent, RoundedBox },
	props: {
		widgets: { type: Array as PropType<DashboardWidget[]>, required: true },
	},
	emits: ['close'],
	setup(props, { emit }) {
		const selection: Ref<DashboardWidget[]> = ref([])

		const toggleWidget = (widget: DashboardWidget) => {
			if (selection.value.includes(widget)) {
				selection.value = selection.value.filter(loop => loop !== widget)
			} else {
				selection.value.push(widget)
			}
		}

		const addWidgets = () => {
			emit('close', selection.value)
			selection.value = []
		}

		return {
			selection,
			toggleWidget,
			addWidgets
		}
	}
})
</script>

<template>
	<RoundedBox class=" h-full">
		<div class="flex flex-col gap-2 p-2">
			<div
				v-for="widget in widgets"
				:key="widget.key"
				class="flex flex-row items-center gap-1 p-2 rounded-md font-semibold text-sm bg-gray-50 hover:bg-gray-100 cursor-pointer"
			>
				<input
					type="checkbox"
					@change="toggleWidget(widget)"
				>
				{{ widget.title }}
			</div>
			<ButtonComponent
				v-show="selection.length > 0"
				color="primary"
				@click="addWidgets"
			>
				<i class="fas fa-plus" />
				{{ $l10n.translate('dashboard.widgets.add') }}
			</ButtonComponent>
		</div>
	</RoundedBox>
</template>
