<script lang="ts">
import { defineComponent, inject } from 'vue3'
import * as debounce from 'debounce-promise'
import { GuiInstance } from '@Gui2/composables/gui-component'
import StudentTable from './StudentTable.vue'

export default defineComponent({
	components: {
		StudentTable
	},
	props: {
		filter: { type: Object, required: true },
		students: { type: Array, required: true }
	},
	emits: [
		'update:filter'
	],
	setup() {
		const gui = inject('gui') as GuiInstance
		return {
			gui,
			hasInboxes: Object.values(gui.options.inboxes as Record<string, string>).length > 1
		}
	},
	data() {
		return {
			debounce: debounce((filter: string, event: InputEvent) => {
				this.updateFilter(filter, event)
			}, 900)
		}
	},
	methods: {
		updateFilter(filter: string, event: InputEvent) {
			const value: string|null = (event.target as HTMLInputElement).value
			// if (event.target instanceof HTMLSelectElement && event.target.selectedIndex === 0) {
			// 	value = null
			// }
			this.$emit('update:filter', {
				...this.filter,
				[filter]: value
			})
		}
	}
})
</script>

<template>
	<div class="box box-default bg-white">
		<div class="box-separator" />
		<div class="box-header">
			<h2 class="box-title">
				{{ gui.getTranslation('unallocated_students') }}
			</h2>
		</div>
		<div
			class="box-header"
			style="padding: 4px 0; border: none"
		>
			<div class="grid grid-cols-12 gap-1">
				<div class="col-span-8">
					<input
						:value="filter.search"
						:placeholder="gui.getTranslation('filter_search')"
						type="text"
						class="w-full rounded border border-gray-100/75 hover:border-gray-200 px-1.5 py-1 bg-white text-xs"
						@keyup="debounce('search', $event)"
					>
				</div>
				<div class="col-span-4">
					<select
						:value="filter.booking_state"
						class="w-full rounded border border-gray-100/75 hover:border-gray-200 text-gray-500 px-1.5 py-1 bg-white text-xs"
						@input="updateFilter('booking_state', $event)"
					>
						<option value="">
							-- {{ gui.getTranslation('booking_state') }} --
						</option>
						<option value="unallocated">
							{{ gui.getTranslation('booking_state_unallocated') }}
						</option>
						<option value="booked">
							{{ gui.getTranslation('booking_state_activity') }}
						</option>
						<option value="not_booked">
							{{ gui.getTranslation('booking_state_no_activity') }}
						</option>
					</select>
				</div>
				<div :class="hasInboxes ? 'col-span-4' : 'col-span-8'">
					<select
						:value="filter.activity"
						class="w-full rounded border border-gray-100/75 hover:border-gray-200 text-gray-500 px-1.5 py-1 bg-white text-xs"
						@input="updateFilter('activity', $event)"
					>
						<option value="">
							-- {{ gui.getTranslation('activity') }} --
						</option>
						<option
							v-for="(label, key) in gui.options.activities"
							:key="key"
							:value="key"
						>
							{{ label }}
						</option>
					</select>
				</div>
				<div
					v-if="hasInboxes"
					class="col-span-4"
				>
					<select
						:value="filter.inbox"
						class="w-full rounded border border-gray-100/75 hover:border-gray-200 text-gray-500 px-1.5 py-1 bg-white text-xs"
						@input="updateFilter('inbox', $event)"
					>
						<option value="">
							-- {{ gui.getTranslation('inbox') }} --
						</option>
						<option
							v-for="(label, key) in gui.options.inboxes"
							:key="key"
							:value="key"
						>
							{{ label }}
						</option>
					</select>
				</div>
				<div class="col-span-4">
					<select
						:value="filter.student_status"
						class="w-full rounded border border-gray-100/75 hover:border-gray-200 text-gray-500 px-1.5 py-1 bg-white text-xs"
						@input="updateFilter('student_status', $event)"
					>
						<option value="">
							-- {{ gui.getTranslation('student_status') }} --
						</option>
						<option
							v-for="(label, key) in gui.options.student_status"
							:key="key"
							:value="key"
						>
							{{ label }}
						</option>
					</select>
				</div>
			</div>
		</div>
		<div class="box-body p-0">
			<StudentTable
				:students="students"
				view="unallocated"
			/>
		</div>
	</div>
</template>
