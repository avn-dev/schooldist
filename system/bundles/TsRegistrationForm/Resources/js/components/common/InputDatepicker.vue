<template>
	<!-- Do NOT use v-model -->
	<date-picker
		:locale="locale"
		:popover="popoverOptions"
		v-bind="$attrs"
		v-on="$listeners"
	>
		<template #default="slotProps">
			<div class="input-group">
				<div class="input-group-prepend">
					<div class="input-group-text"><i :class="$icon('calendar')"></i></div>
				</div>
				<input
					:id="id"
					:name="name"
					v-model="slotProps.inputValue"
					type="text"
					class="form-control"
					:class="{ 'is-invalid': isInvalid }"
					:disabled="disabled"
					autocomplete="off"
					v-on="slotProps.inputEvents"
				>
				<div class="invalid-feedback">
					{{ errors }}
				</div>
			</div>
		</template>
	</date-picker>
</template>

<script>
	import DatePicker from '@fidelo-software/v-calendar/lib/components/date-picker.umd';

	export default {
		inheritAttrs: false,
		components: {
			DatePicker
		},
		props: {
			id: { type: String, required: true },
			name: String,
			disabled: Boolean,
			isInvalid: Boolean,
			errors: String
		},
		data() {
			return {
				locale: {
					id: this.$s('datepicker').locale,
					firstDayOfWeek: 2,
					masks: {
						L: this.$s('datepicker').format
					}
				}
			};
		},
		computed: {
			popoverOptions() {
				return {
					placement: 'bottom',
					// Datepicker won't be shown with hidden; there is no disabled
					visibility: this.disabled ? 'hidden' : 'click'
				};
			}
		}
	}
</script>
