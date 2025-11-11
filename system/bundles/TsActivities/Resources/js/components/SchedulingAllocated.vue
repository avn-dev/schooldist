<script lang="ts">
import { defineComponent, inject, PropType } from 'vue3'
import { EventApi } from '@fullcalendar/core'
import { GuiInstance } from '@Gui2/composables/gui-component'
import { Student } from '../types'
import StudentTable from './StudentTable.vue'

export default defineComponent({
	components: {
		StudentTable
	},
	props: {
		block: { type: Object as PropType<EventApi>, default: null },
		students: { type: Array as PropType<Array<Student>>, required: true }
	},
	emits: [
		'communication',
		'delete:allocation',
		'export'
	],
	setup() {
		return {
			gui: inject('gui') as GuiInstance
		}
	}
})
</script>

<template>
	<div class="box box-default bg-white">
		<div class="box-separator" />
		<div class="box-header">
			<h2 class="box-title">
				{{ gui.getTranslation('allocated_students') }}
			</h2>
			<div class="box-tools pull-right">
				<button
					type="button"
					class="btn btn-box-tool"
					:title="gui.getTranslation('communication')"
					:disabled="!students.length"
					@click="$emit('communication')"
				>
					<i class="fa fa-envelope" />
				</button>
				<button
					type="button"
					class="btn btn-box-tool"
					:title="gui.getTranslation('export')"
					:disabled="!students.length"
					@click="$emit('export')"
				>
					<i class="fa fa-table" />
				</button>
			</div>
		</div>
		<div class="box-body">
			<h3
				v-if="block === null"
				class="h4 p-1"
			>
				{{ gui.getTranslation('select_activity') }}
			</h3>
			<p v-if="block && !students.length">
				{{ gui.getTranslation('no_students') }}
			</p>
			<StudentTable
				v-if="block && students.length"
				:students="students"
				view="allocated"
				@delete="$emit('delete:allocation', $event)"
			/>
		</div>
	</div>
</template>
