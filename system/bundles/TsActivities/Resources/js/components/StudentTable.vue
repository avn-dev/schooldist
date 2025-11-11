<script lang="ts">
import { defineComponent, inject } from 'vue3'
import { VTooltip } from 'floating-vue'
import { GuiInstance } from '@Gui2/composables/gui-component'

export default defineComponent({
	directives: {
		VTooltip
	},
	props: {
		students: { type: Array, required: true },
		view: { type: String, required: true }
	},
	emits: [
		'delete'
	],
	setup() {
		return {
			gui: inject('gui') as GuiInstance
		}
	},
	data() {
		return {
			checkboxes: [] as boolean[],
			allCheckbox: false
		}
	},
	watch: {
		students: {
			immediate: true,
			handler() {
				this.toggleAll()
			}
		}
	},
	methods: {
		toggleAll() {
			this.checkboxes = [] as boolean[]
			this.students.forEach((s, i) => this.checkboxes[i] = this.allCheckbox)
		},
		dragStart(e: DragEvent, student: Record<string, string>) {
			if (e.dataTransfer) {
				let students = this.students.filter((s, i) => this.checkboxes[i])
				if (students.length < 2) {
					students = [student]
				}
				e.dataTransfer.effectAllowed = 'move'
				e.dataTransfer.setData('application/json', JSON.stringify(students))
			}
		}
	}
})
</script>

<template>
	<div :class="['student-table', view]">
		<table class="table">
			<thead>
				<tr>
					<th
						v-if="view === 'unallocated'"
						class="col-checkbox"
					>
						<input
							v-model="allCheckbox"
							type="checkbox"
							@change="toggleAll"
						>
					</th>
					<th class="col-group">
						<i class="fa fa-users" />
					</th>
					<th class="col-name">
						{{ gui.getTranslation('name') }}
					</th>
					<th
						v-if="view === 'unallocated'"
						class="col-blocks-booked"
						:title="gui.getTranslation('blocks_booked')"
					>
						{{ gui.getTranslation('blocks_booked_short') }}
					</th>
					<th
						v-if="view === 'unallocated'"
						class="col-blocks-allocated"
						:title="gui.getTranslation('blocks_allocated')"
					>
						{{ gui.getTranslation('blocks_allocated_short') }}
					</th>
					<th class="col-activity">
						{{ gui.getTranslation('activity') }}
					</th>
					<th class="col-actions" />
				</tr>
			</thead>
			<tbody>
				<tr
					v-for="(student, index) in students"
					:key="index"
					:draggable="view === 'unallocated'"
					@dragstart="dragStart($event, student)"
				>
					<td
						v-if="view === 'unallocated'"
						class="col-checkbox"
					>
						<input
							v-model="checkboxes[index]"
							type="checkbox"
						>
					</td>
					<td
						class="col-group"
						:title="student.group_name"
					>
						{{ student.group_short }}
					</td>
					<td class="col-name">
						{{ student.name }}
					</td>
					<td
						v-if="view === 'unallocated'"
						class="col-blocks-booked"
					>
						{{ student.blocks_count }}
					</td>
					<td
						v-if="view === 'unallocated'"
						class="col-blocks-allocated"
					>
						{{ student.blocks_allocated_count }}
					</td>
					<td
						class="col-activity"
						:title="student.activity_name"
					>
						{{ student.activity_short }}
					</td>
					<td class="col-actions">
						<a
							v-if="view === 'allocated'"
							@click="$emit('delete', student)"
						>
							<i class="fa fa-trash" />
						</a>
						<i
							v-if="view === 'unallocated' && student.comment"
							v-v-tooltip="student.comment"
							class="fa fa-info-circle"
						/>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</template>
