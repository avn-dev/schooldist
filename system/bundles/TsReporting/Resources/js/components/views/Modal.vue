<script lang="ts">
import { defineComponent } from 'vue3'

// TODO Genereller umsetzen, aber kein Use Case
// TODO Transition
export default defineComponent({
	props: {
		modelValue: { type: Boolean, default: false },
		title: { type: String, default: '' }
	},
	emits: [
		'update:model-value'
	],
	methods: {
		close() {
			this.$emit('update:model-value', false)
		}
	}
})
</script>

<template>
	<div
		class="modal-backdrop fade in"
		:style="!modelValue ? 'display: none' : ''"
	/>
	<div
		:class="['modal', 'fade', modelValue ? 'in' : '']"
		tabindex="-1"
		:style="modelValue ? 'display: block' : ''"
		@click="close"
	>
		<div
			class="modal-dialog"
			@click.stop=""
		>
			<div class="modal-content">
				<div class="modal-header">
					<button
						type="button"
						class="close"
						@click="close"
					>
						<i class="fa fa-times" />
					</button>
					<h4 class="modal-title">
						{{ title }}
					</h4>
				</div>
				<div class="modal-body">
					<slot />
				</div>
				<div class="modal-footer">
					<slot name="footer" />
					<!--<button
						type="button"
						class="btn btn-default"
						@click="close"
					>
						Close
					</button>
					<button
						type="button"
						class="btn btn-primary"
					>
						Save changes
					</button>-->
				</div>
			</div>
		</div>
	</div>
</template>
