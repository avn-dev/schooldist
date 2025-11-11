<script lang="ts">
import { defineComponent, computed, ref, type PropType } from 'vue'

export default defineComponent({
	name: "UploadField",
	props: {
		modelValue: { type: Array as PropType<File[]>, default: () => [] },
		multiple: { type: Boolean, default: false },
		placeholder: { type: String, required: true },
	},
	emits: ['update:modelValue', 'change'],
	setup(props, { emit }) {
		const fileInput = ref(null)
		const fieldValue = computed({
			get() {
				if (props.modelValue === null) return []
				return !Array.isArray(props.modelValue) ? [props.modelValue] : props.modelValue
			},
			set(value) {
				if (value === null) value = []
				const final = !Array.isArray(value) ? [value] : value
				emit('update:modelValue', props.multiple ? final : final[0] ?? null)
				emit('change', props.multiple ? final : final[0] ?? null)
			}
		})

		function handleFileSelect(event: MouseEvent) {
			const selectedFiles = Array.from((event.target as HTMLInputElement).files ?? [])
			fieldValue.value = [...fieldValue.value, ...selectedFiles]
		}

		function handleDrop(event: DragEvent) {
			const droppedFiles = Array.from(event.dataTransfer?.files ?? [])
			fieldValue.value = [...fieldValue.value, ...droppedFiles]
		}

		function removeFile(index: number) {
			fieldValue.value.splice(index, 1)
		}

		return {
			fieldValue,
			fileInput,
			handleFileSelect,
			handleDrop,
			removeFile
		}
	}
})
</script>

<template>
	<div
		class="border-dashed text-center transition-colors"
		@dragover.prevent
		@drop.prevent="handleDrop"
		@click="fileInput.click()"
	>
		<p>
			{{ placeholder }}
		</p>
		<input
			ref="fileInput"
			type="file"
			:multiple="multiple"
			class="hidden"
			@change="handleFileSelect"
		>
		<!--<ul v-if="fieldValue.length > 0" class="mt-2 space-y-1 text-left">
			<li
				v-for="(file, index) in fieldValue"
				:key="index"
				class="flex items-center justify-between bg-gray-50 px-2 py-1 rounded border border-gray-100 shadow-sm"
			>
				<div class="text-gray-500 truncate max-w-xs">
					{{ file.name }}
					<span class="text-gray-300 text-xs ml-2">({{ formatSize(file.size) }})</span>
				</div>
				<button
					@click="removeFile(index)"
					class="text-sm hover:underline"
				>
					<i class="fa fa-trash" />
				</button>
			</li>
		</ul>-->
	</div>
</template>
