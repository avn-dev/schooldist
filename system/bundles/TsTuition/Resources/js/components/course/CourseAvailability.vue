<script lang="ts">
import { defineComponent, ref, type PropType, type Ref } from 'vue3'
import type { GuiInstance, EmitterType } from '@Gui2/types/gui'
import { Calendar } from 'v-calendar'

type StartDate = { date: string, label: string, weeks: string, popover?: { label: string } }
type DisabledDate = { from: string, until: string, popover?: { label: string } }
type Holidays = { from: string, until: string }
type PublicHolidays = { date: string, popover: { label: string } }

/**
 * TODO Daten über eigenen Request?
 */
export default defineComponent({
	name: "CourseAvailability",
	components: {
		Calendar
	},
	props: {
		gui: { type: Object as PropType<GuiInstance>, required: true },
		emitter: { type: Object as PropType<EmitterType>, required: true },
		container: { type: String, required: true },
		courseId: { type: Number, required: true },
		locale: { type: String, required: true },
		label: { type: String, required: true },
		minDate: { type: [String, null] as PropType<string|null>, required: true },
		maxDate: { type: [String, null] as PropType<string|null>, required: true },
		dates: { type: Array as PropType<StartDate[]>, required: true },
		disabledDates: { type: Array as PropType<DisabledDate[]>, required: true },
		holidays: { type: Array as PropType<Holidays[]>, required: true },
		publicHolidays: { type: Array as PropType<PublicHolidays[]>, required: true }
	},
	setup(props) {
		const open: Ref<boolean> = ref(false)

		const selectDate = (date: { id: string }) => {
			const availableDate = props.dates.find((d: StartDate) => d.date === date.id)
			if (availableDate) {
				try {
					props.emitter.emit(`course:start:${props.gui.hash}:${props.container}`, availableDate)
				} catch (e) {
					console.error(e)
				}

				open.value = false
			}
		}

		const convertDate = (date: string) => {
			const [year, month, day] = date.split('-')
			return Date.UTC(parseInt(year), parseInt(month) - 1, parseInt(day), 0, 0, 0)
		}

		const disabledDatesAttribute = props.disabledDates.map((date: DisabledDate) => ({ start: convertDate(date.from), end: convertDate(date.until)}))

		const attributes = []
		props.dates.forEach((date: StartDate) => {
			const popover = (date.popover)
				? { label: date.popover }
				: null

			attributes.push({
				dates: convertDate(date.date),
				popover: popover,
				highlight: true
			})
		})

		if (props.holidays.length > 0) {
			attributes.push({
				dates: props.holidays.map((date: Holidays) => [convertDate(date.from), convertDate(date.until)]),
				highlight: {
					start: { fillMode: 'solid', color: 'gray' },
					base: { fillMode: 'light', color: 'gray' },
					end: { fillMode: 'solid', color: 'gray' }
				}
			})
		}

		props.publicHolidays.forEach((date: PublicHolidays) => {
			attributes.push({
				dates: convertDate(date.date),
				popover: { label: date.popover },
				dot: { color: 'gray' },
			})
		})

		return {
			open,
			attributes,
			disabledDatesAttribute,
			selectDate,
			convertDate
		}
	}
})
</script>

<template>
	<div style="position: relative; height: 30px">
		<div v-if="attributes.length == 0">
			{{ label }}
		</div>
		<div
			v-else
			class="w-full rounded border border-gray-100 p-2 bg-white cursor-pointer"
			:style="[
				open ? 'position: absolute; z-index: 100; width: 760px;' : ''
			]"
		>
			<div
				class="flex flex-row items-center"
				@click="() => open = !open"
			>
				<div class="grow m-0">
					{{ label }}
				</div>
				<div class="flex-none">
					<button
						type="button"
					>
						<i :class="['fa', open ? 'fa-angle-up' : 'fa-angle-down']" />
					</button>
				</div>
			</div>
			<div
				v-if="open"
				class="p-0"
			>
				<Calendar
					view="monthly"
					timezone="UTC"
					:columns="3"
					:rows="2"
					:locale="locale"
					:attributes="attributes"
					:min-date="convertDate(minDate)"
					:max-date="convertDate(maxDate)"
					:disabled-dates="disabledDatesAttribute"
					transparent
					borderless
					expanded
					@dayclick="selectDate"
				/>
			</div>
		</div>
	</div>
</template>
