<script lang="ts">
import { defineComponent, inject, ref } from 'vue3'
import FullCalendar from '@fullcalendar/vue3'
import { DatesSetArg, EventClickArg, EventMountArg, EventContentArg } from '@fullcalendar/core'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin, { DateClickArg } from '@fullcalendar/interaction'
import listPlugin from '@fullcalendar/list'
import timeGridPlugin from '@fullcalendar/timegrid'
import { GuiInstance } from '@Gui2/composables/gui-component'

export default defineComponent({
	components: {
		FullCalendar
	},
	emits: [
		'allocate',
		'date:click',
		'dates:set',
		'delete',
		'event:click'
	],
	expose: [
		'getDateRange',
		'refetchEvents'
	],
	setup() {
		return {
			calendar: ref<typeof FullCalendar>()
		}
	},
	data() {
		const gui = inject('gui') as GuiInstance
		return {
			gui,
			calendarOptions: {
				plugins: [ dayGridPlugin, interactionPlugin, listPlugin, timeGridPlugin ],
				initialView: 'timeGridWeek',
				locale: gui.options.locale,
				height: '100%',
				showNonCurrentDates: false,
				weekNumbers: true,
				weekNumberCalculation: 'ISO',
				allDaySlot: false,
				slotMinTime: gui.options.activity_starttime,
				slotMaxTime: gui.options.activity_endtime,
				expandRows: true,
				headerToolbar: {
					left: 'prev,next today,refresh',
					center: 'title',
					right: 'dayGridMonth,timeGridWeek,listWeek'
				},
				customButtons: {
					refresh: {
						text: gui.getTranslation('refresh'),
						click: this.refetchEvents
					}
				},
				eventSources: [
					{
						url: '/ts/activities/scheduling/events',
						type: 'POST'
					}
				],
				// Muss alles über methods passieren, da ansonsten this überschrieben wird
				eventDidMount: this.eventDidMount,
				dateClick: this.dateClick,
				datesSet: this.datesSet,
				eventClick: this.eventClick,
				buttonText: {
					today: gui.getTranslation('today'),
					month: gui.getTranslation('month'),
					week: gui.getTranslation('week'),
					day: gui.getTranslation('day'),
					list: gui.getTranslation('list')
				},
				eventContent: function( info: EventContentArg ) {

					let leaders = null
					if (info.event.extendedProps.activity_leaders != null) {
						leaders = info.event.extendedProps.activity_leaders
					} else {
						leaders = ''
					}

					let title = null
					if (info.view.type != 'dayGridMonth') {
						title = '<div class="fc-event-title-container"><div class="fc-event-title fc-sticky"><b>'+info.event.title+'</b>'
					} else {
						title = '<div class="fc-event-title"><b>'+info.event.title+'</b>'
					}

					let students = null
					students = info.event.extendedProps.students

					let timeString = null
					if (info.view.type != 'dayGridMonth') {
						// slice(0, -3) Um die Sekunden zu entfernen
						timeString = '<div class = "fc-event-time">'+info.event.extendedProps.start_time.slice(0, -3)+' - '+info.event.extendedProps.end_time.slice(0, -3)+'</div>'
					} else {
						// slice(0, -3) Um die Sekunden zu entfernen
						timeString = '<div class = "fc-event-time">'+info.event.extendedProps.start_time.slice(0, -3)+'</div>'
					}

					let mainFrame = null

					if (info.view.type != 'dayGridMonth') {
						mainFrame = '<div class="fc-event-main-frame">'+timeString+title
					} else {
						mainFrame = timeString+title
					}

					if (
						info.view.type == 'timeGridWeek' &&
						info.event.extendedProps.activity_duration < 120
					) {
						// Bei weniger Platz
						const commaPosition = leaders.indexOf(',')
						if (commaPosition != -1) {
							// Wenn es mehrere Begleiter gibt
							leaders = leaders.slice(0, commaPosition)+', ...'
						}
						return {
							html: mainFrame+' ('+students+') '+leaders+' </div></div></div>'
						}
					} else if (info.view.type == 'dayGridMonth') {
						return {
							html: '<div class="fc-daygrid-event-dot"></div>'+mainFrame+' ('+students+')</div></div></div>'
						}
					} else {
						// Bei mehr Platz
						if (leaders != '') {
							leaders = gui.getTranslation('leaders')+': '+leaders
						}
						return {
							html: mainFrame+'<br>'+gui.getTranslation('students')+': '+students+'<br>'+leaders+'</div></div></div>' }
					}
				}
			}
		}
	},
	methods: {
		eventDidMount(event: EventMountArg) {
			const span = Object.assign(document.createElement('span'), { className: 'fc-event-actions' })
			const deleteIcon = Object.assign(document.createElement('i'), { className: 'fa fa-trash' })
			const editIcon = Object.assign(document.createElement('i'), { className: 'fa fa-pencil' })
			span.appendChild(deleteIcon)
			span.appendChild(editIcon)
			this.getEventMountElement(event).appendChild(span)

			editIcon.addEventListener('click', () => {
				this.gui.selectedRowId = parseInt(event.event.id) // eslint-disable-line vue/no-mutating-props
				this.gui.useRowIdWithoutRows = true
				this.gui.prepareOpenDialog('edit', undefined, `&id=${event.event.id}`)
			})

			deleteIcon.addEventListener('click', () => {
				this.$emit('delete', event.event)
			})

			event.el.addEventListener('dragover', (e: DragEvent) => {
				if (
					e.dataTransfer && // Kein Zugriff auf dataTransfer.getData
					(e.dataTransfer.types as Array<string>).includes('application/json')
				) {
					e.preventDefault()
					e.dataTransfer.dropEffect = 'move'
				}
			})

			event.el.addEventListener('drop', (e: DragEvent) => {
				if (e.dataTransfer) {
					this.$emit('allocate', event.event, JSON.parse(e.dataTransfer.getData('application/json')))
				}
			})
		},
		dateClick(dateInfo: DateClickArg) {
			this.$emit('date:click', dateInfo)
		},
		datesSet(dateInfo: DatesSetArg) {
			this.$emit('dates:set', dateInfo)
		},
		eventClick(eventClickInfo: EventClickArg) {
			this.$emit('event:click', eventClickInfo)
		},
		refetchEvents() {
			if (this.calendar) {
				this.calendar.getApi().refetchEvents()
				this.$emit('dates:set')
			}
		},
		getEventMountElement(event: EventMountArg): HTMLElement {
			switch (event.view.type) {
				case 'timeGridWeek':
					return event.el.querySelector('.fc-event-time') ?? event.el
				case 'listWeek':
					return event.el.querySelector('.fc-event-title') ?? event.el
				default: // dayGridMonth
					return event.el
			}
		},
		getDateRange() {
			if (this.calendar) {
				return {
					// Nicht Date.toISOString, da formatIso die Zeitzone ohne weitere Lib korrekt anwendet
					start: this.calendar.getApi().formatIso(this.calendar.getApi().view.activeStart),
					end: this.calendar.getApi().formatIso(this.calendar.getApi().view.activeEnd)
				}
			}
		}
	}
})
</script>

<template>
	<FullCalendar
		ref="calendar"
		:options="calendarOptions"
	/>
</template>
