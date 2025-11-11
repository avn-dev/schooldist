<script lang="ts">
import { defineComponent, inject, PropType } from 'vue3'
import { GuiInstance } from '@Gui2/composables/gui-component'
import { ModalMessageLines } from '../types'

export default defineComponent({
	props: {
		type: { type: String, required: true },
		title: { type: String, required: true },
		message: { type: String, required: true },
		messageLines: { type: Array as PropType<ModalMessageLines>, default: () => [] },
		visible: { type: Boolean, required: true },
		options: { type: Array as PropType<Array<[number, string]>>, default: () => [] }
	},
	emits: [
		'close'
	],
	setup() {
		return {
			gui: inject('gui') as GuiInstance
		}
	}
})
</script>

<template>
	<div
		class="modal fade in fixed inset-0 bg-gray-900/80 dark:bg-gray-950 dark:opacity-90 z-50 top-0 left-0 right-0 bottom-0"
		:style="{ 'display': visible ? 'block' : 'none' }"
	>
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header flex flex-row items-center">
					<h3 class="modal-title grow">
						{{ title }}
					</h3>
					<button
						type="button"
						class="close flex-none"
						:aria-label="gui.getTranslation('close')"
						@click="$emit('close')"
					>
						<span aria-hidden="true">×</span>
					</button>
				</div>
				<div class="modal-body relative p-4">
					<p v-if="message">
						{{ message }}
					</p>
					<ul
						v-if="messageLines.length"
						class="list-disc px-6"
					>
						<li
							v-for="(line, index) in messageLines"
							:key="index"
							:class="`text-${line[0]}`"
						>
							{{ line[1] }}
						</li>
					</ul>
					<div
						v-if="type === 'choose'"
						class="flex flex-col rounded-md border border-gray-100/50 divide-y divide-gray-100/50 mt-2"
					>
						<a
							v-for="option in options"
							:key="option[0]"
							class="px-4 py-2 cursor-pointer hover:bg-gray-50 text-gray-500 hover:text-gray-600"
							@click="$emit('close', option[0])"
						>
							{{ option[1] }}
						</a>
					</div>
				</div>
				<div class="modal-footer flex flex-row items-center border-t border-gray-50 p-2">
					<button
						type="button"
						class="btn btn-default pull-left"
						@click="$emit('close')"
					>
						{{ gui.getTranslation('close') }}
					</button>
					<div class="grow" />
					<button
						v-if="type === 'confirm'"
						type="button"
						class="btn btn-primary"
						@click="$emit('close', true)"
					>
						{{ gui.getTranslation('confirm') }}
					</button>
				</div>
			</div>
		</div>
	</div>
</template>
