<script lang="ts">
import { defineComponent, inject, PropType } from 'vue3'
import { resolveComponent } from '../../util/util'
import type { GuiInstance } from '../../types/gui'
import type { DialogComponent, DialogValues } from '../../types/dialog'
// @ts-ignore
import ButtonComponent from '@Admin/components/ButtonComponent.vue'
import { EMITS, PROPS } from '../../composables/dialog/dialog-component'

export default defineComponent({
	components: { ButtonComponent },
	inheritAttrs: false,
	props: {
		...PROPS,
		components: {
			type: Array as PropType<DialogComponent[]>,
			required: true,
			validator(v: DialogComponent[]) {
				return !v.some(c => !c.key)
			}
		},
		label: {
			type: String,
			default: null
		},
		min: {
			type: Number,
			default: 0
		},
		sortable: {
			type: Boolean,
			default: false
		}
	},
	emits: EMITS,
	setup() {
		return {
			gui: inject('gui') as GuiInstance,
			resolveComponent
		}
	},
	computed: {
		value(): DialogValues[] {
			if (!Array.isArray(this.modelValue)) {
				console.error('Value of RepeatableSection is not an array', this)
				return []
			}
			if (!this.sortable) {
				return this.modelValue
			}

			const values = [...this.modelValue]
			let position = 0
			values.sort((a, b) => a.position - b.position)
			return values.map(value => {
				value.position = position++
				return value
			})
		}
	},
	mounted() {
		if (!this.value.length && this.min > 0) {
			this.add()
		}
	},
	methods: {
		add() {
			const entry = Object.fromEntries(this.components.map((c: DialogComponent) => [c.key, null]))
			this.$emit('update:modelValue', [...this.value, entry])
		},
		change(index: number, key: string, value: unknown) {
			const values = [...this.value]
			if (!(values[index] instanceof Object)) {
				console.error('Missing index for changing value of RepeatableSection', index, key, value, this)
				return
			}
			values[index][key] = value
			this.$emit('update:modelValue', values)
		},
		move(index: number, delta: number) {
			const values = [...this.value] as { [key: string]: unknown, position: number }[]
			values[index].position += delta
			values[index + delta].position -= delta
			this.$emit('update:modelValue', values)
		},
		remove(index: number) {
			const values = [...this.value]
			values.splice(index, 1)
			this.$emit('update:modelValue', values)
		}
	}
})
</script>

<template>
	<div class="flex flex-col gap-y-2 bg-gray-50 p-2 rounded-xl my-2">
		<div
			v-if="label"
			class="text-gray-500 text-sm font-heading font-semibold"
		>
			{{ label }}
		</div>
		<div
			v-for="(val, index) in value"
			:key="index"
			class="rounded-md p-2 bg-white"
		>
			<div class="flex flex-col gap-y-2">
				<component
					:is="resolveComponent(component.component)"
					v-for="component in components"
					:key="`${index}_${component.key}`"
					:model-value="val[component.key]"
					:name="component.key"
					:section-key="name"
					:section-value="val"
					:="component"
					@update:model-value="change(index, component.key, $event)"
				/>
				<div class="flex flex-row gap-1 items-stretch justify-end">
					<ButtonComponent
						v-if="sortable && index > 0"
						@click="move(index, -1)"
					>
						<i class="fa fa-arrow-up" />
					</ButtonComponent>
					<ButtonComponent
						v-if="sortable && index !== value.length - 1"
						@click="move(index, 1)"
					>
						<i class="fa fa-arrow-down" />
					</ButtonComponent>
					<ButtonComponent
						color="gray"
						class="flex flex-row gap-1 items-center"
						@click="remove(index)"
					>
						<i class="fa fa-remove" /> {{ gui.getTranslation('remove_entry') }}
					</ButtonComponent>
				</div>
			</div>
		</div>
		<div class="flex flex-row gap-1 items-stretch justify-end">
			<button
				type="button"
				class="btn btn-primary pull-right"
				@click="add"
			>
				<i class="fa fa-plus" /> {{ gui.getTranslation('add_entry') }}
			</button>
		</div>
	</div>
</template>
