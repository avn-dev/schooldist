<script lang="ts">
import { defineComponent, PropType, provide, ref } from 'vue3'
import axios from 'axios'
import { Emitter } from 'mitt'
import { GuiInstance } from '@Gui2/composables/gui-component'
import SchedulingAllocated from './SchedulingAllocated.vue'
import SchedulingCalendar from './SchedulingCalendar.vue'
import SchedulingModal from './SchedulingModal.vue'
import SchedulingUnallocated from './SchedulingUnallocated.vue'
import { EventApi, EventClickArg } from '@fullcalendar/common' // Muss nach SchedulingCalendar
import { DateClickArg } from '@fullcalendar/interaction'
import { ActivityFilter, ModalMessageLines, ModalMessageLine, Student } from '../types'

export default defineComponent({
	components: {
		SchedulingAllocated,
		SchedulingCalendar,
		SchedulingModal,
		SchedulingUnallocated
	},
	props: {
		gui: { type: Object as PropType<GuiInstance>, required: true },
		emitter: { type: Object as PropType<Emitter<Record<string, unknown>>>, required: true }
	},
	setup(props) {
		provide('gui', props.gui)
		return {
			calendar: ref<typeof SchedulingCalendar>()
		}
	},
	data() {
		return {
			selectedBlock: null as EventApi|null,
			studentsUnallocated: [],
			studentsAllocated: [],
			filter: { ...this.gui.options.filters as ActivityFilter },
			modal: {
				type: 'error',
				title: this.gui.getTranslation('allocate_students'),
				message: '',
				messageLines: [] as ModalMessageLines,
				visible: false,
				options: [],
				promise: (data: any) => {} // eslint-disable-line
			}
		}
	},
	mounted() {
		// SchedulingGuiClass Dialog
		this.emitter.on('refetchEvents', this.calendar.refetchEvents)
	},
	methods: {
		openModal(type: string, message: string, messageLines?: ModalMessageLines) {
			this.modal.type = type
			this.modal.message = message
			this.modal.visible = true
			this.modal.messageLines = messageLines ?? []
			return new Promise(resolve => {
				this.modal.promise = resolve
			})
		},
		closeModal(data?: string) {
			if (this.modal.promise) {
				this.modal.promise(data)
			}
			this.modal.visible = false
		},
		openCreate(info: DateClickArg) {
			this.gui.selectedRowId = 0 // eslint-disable-line vue/no-mutating-props
			this.gui.prepareOpenDialog('new', undefined, `&date=${encodeURIComponent(info.dateStr)}`)
		},
		async allocate(event: InstanceType<typeof EventApi>, students: Array<Student>) {
			const noActivityStudents = students.filter(s => !s.activity_id)

			// Schüler ohne Aktivität wird auf Block mit mehreren Aktivitäten gezogen
			let activityChosen = null
			if (event.extendedProps.activity_ids.length > 1 && noActivityStudents.length) {
				this.modal.options = event.extendedProps.activity_ids.map((id: number) => [id, (this.gui.options.activities as Record<number, string>)[id] ?? id])
				activityChosen = await this.openModal('choose', this.gui.getTranslation('allocate_select_activity'))
				if (!activityChosen) {
					return
				}
			}

			// Schüler ohne gebuchte Aktivität bestätigen
			if (noActivityStudents.length) {
				const confirm = await this.openModal('confirm', this.gui.getTranslation('allocate_book'), noActivityStudents.map(s => ['info', s.name]))
				if (!confirm) {
					return
				}
			}

			// eslint-disable-next-line
			const send = (activityId: any, date: string, blockId: string, students: Array<Student>, confirm: Array<string> = []) => {
				axios.post('/ts/activities/scheduling/allocate', {
					activity_id: activityId,
					date: date,
					block_id: blockId,
					confirm: confirm,
					students
				}).then(async res => {
					const studentsNeedsConfirm = res.data.needsConfirm ?? []
					if (studentsNeedsConfirm.length > 0) {
						const confirm = await this.openModal('confirm', '', res.data.messages)
						if (confirm) {
							// Error-Codes die ignoriert werden sollen
							const confirm = res.data.messages.filter((line: ModalMessageLine) => line[0] === 'warning')
								.map((line: ModalMessageLine, index: number) => (line[2]) ? line[2] : `warning_${index}`)

							send(activityId, date, blockId, studentsNeedsConfirm, confirm)
							return
						}
					} else {
						this.openModal('alert', '', res.data.messages)
					}

					this.loadUnallocated()
					if (this.selectedBlock instanceof EventApi) {
						this.loadBlock(this.selectedBlock)
						this.calendar.refetchEvents()
					}
				})
			}

			send(activityChosen, event.startStr, event.id, students)
		},
		async deleteAllocation(student: Student) {
			const confirm = await this.openModal('confirm', this.gui.getTranslation('delete_allocation'), [['info', student.name]])
			if (confirm) {
				await axios.delete('/ts/activities/scheduling/deleteAllocation', { data: student })
				this.loadUnallocated()

				if (this.selectedBlock instanceof EventApi) {
					this.loadBlock(this.selectedBlock)
					this.calendar.refetchEvents()
				}
			}
		},
		async deleteBlock(event: EventApi) {
			const confirm = await this.openModal('confirm', this.gui.getTranslation('delete_block'))
			if (confirm) {
				this.selectedBlock = null
				await axios.delete('/ts/activities/scheduling/deleteBlock', { data: { block_id: event.id }})
				this.calendar.refetchEvents()
			}
		},
		openCommunication() {
			if (!this.selectedBlock) return
			const ids = this.studentsAllocated.map((s: Student) => [`&id[]=${s.block_traveller_id}`])
			this.gui.selectedRowId = 0 // eslint-disable-line vue/no-mutating-props
			this.gui.request(`&task=request&action=communication&additional=activity&block_id=${this.selectedBlock.id}&date=${this.selectedBlock.startStr}${ids.join('')}`)
			//this.gui.prepareOpenDialog('communication', 'activity', `&block_id=${this.selectedBlock.id}&date=${this.selectedBlock.startStr}${ids.join('')}`)
		},
		async exportBlock() {
			if (!this.selectedBlock) return
			axios.get(`/ts/activities/scheduling/exportBlock?block_id=${this.selectedBlock.id}&date=${encodeURIComponent(this.selectedBlock.startStr)}`, { responseType: 'blob' }).then(res => {
				const blob = URL.createObjectURL(res.data)
				const link = document.createElement('a')
				link.style.display = 'none'
				link.href = blob
				link.download = res.headers['content-disposition'].match('filename="(.+)"')?.[1] ?? ''
				document.body.appendChild(link)
				link.click()
				document.body.removeChild(link)
				setTimeout(() => URL.revokeObjectURL(blob), 100)
			})
		},
		updateFilter(filter: ActivityFilter) {
			this.filter = filter
			this.loadUnallocated()
		},
		loadUnallocated() {
			const dates = this.calendar.getDateRange()
			this.studentsUnallocated = []
			axios.post('/ts/activities/scheduling/unallocated', {
				...dates,
				filter: this.filter,
			}).then(res => {
				this.studentsUnallocated = res.data.students
			})
		},
		selectBlock(eventClickInfo: EventClickArg) {
			document.querySelectorAll('.fc-event').forEach(el => el.classList.remove('selected'))
			eventClickInfo.el.classList.add('selected')
			this.loadBlock(eventClickInfo.event)
		},
		loadBlock(event: EventApi) {
			this.selectedBlock = event
			this.studentsAllocated = []
			axios.post('/ts/activities/scheduling/allocated', {
				block_id: event.id,
				date: event.startStr
			}).then(res => {
				this.studentsAllocated = res.data.students
			})
		}
	}
})
</script>

<template>
	<SchedulingModal
		v-bind="modal"
		@close="closeModal"
	/>
	<div class="grid grid-cols-12 grid-flow-col gap-2 text-sm p-2">
		<div class="col-span-3 col-students flex flex-col">
			<SchedulingUnallocated
				class="col-span-3 col-students row-span-6"
				:filter="filter"
				:students="studentsUnallocated"
				@update:filter="updateFilter"
			/>
			<SchedulingAllocated
				class="col-span-3 col-students row-span-6"
				:block="selectedBlock"
				:students="studentsAllocated"
				@communication="openCommunication"
				@delete:allocation="deleteAllocation"
				@export="exportBlock"
			/>
		</div>
		<div class="col-span-9 col-calendar">
			<SchedulingCalendar
				ref="calendar"
				@allocate="allocate"
				@date:click="openCreate"
				@dates:set="loadUnallocated"
				@delete="deleteBlock"
				@event:click="selectBlock"
			/>
		</div>
	</div>
</template>
