<script lang="ts">
import { defineComponent, nextTick, provide, computed, reactive, ref, type Ref, type PropType, type VNode } from 'vue'
import * as debounce from 'debounce-promise'
import { SelectOption, SelectOptionValueType } from '@Admin/types/common'
import { safe } from '@Admin/utils/promise'
import { sleep } from '../../utils/util'

type SelectOptionWithVNode = SelectOption & { element: VNode }

export default defineComponent({
	name: "SelectField",
	props: {
		modelValue: { type: Object as PropType<SelectOption | SelectOption[] | null>, default: null },
		placeholder: { type: String, default: '' },
		emptyOptions: { type: String, default: '' },
		multiple: { type: Boolean, default: false },
		searchable: { type: Boolean, default: false },
		createCustom: { type: Boolean, default: false },
		options: { type: Function as PropType<(query: string) => Promise<void>>, default: null },
		showTags: { type: Boolean, default: true },
	},
	emits: ['update:modelValue', 'change'],
	setup(props, { emit, attrs }) {
		const containerRef: Ref<HTMLDivElement|null> = ref(null)
		const optionsRef: Ref<HTMLDivElement|null> = ref(null)
		const searchRef: Ref<HTMLDivElement|null> = ref(null)
		const searching: Ref<boolean> = ref(false)
		const positioning: { width: number, top: number, left: number } = reactive({ width: 0, top: 0, left: 0 })
		const collapsed: Ref<boolean> = ref(true)
		const query: Ref<string> = ref('')
		const allOptions: Ref<SelectOptionWithVNode[]> = ref([])

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

		const localOptions = computed(() => {
			if (!props.options && query.value.length > 0) {
				return allOptions.value.filter((option: SelectOptionWithVNode) => option.text.toLowerCase().includes(query.value.toLowerCase()))
			}
			return allOptions.value
		})

		const open = async <E extends MouseEvent | PointerEvent | FocusEvent>(event: E) => {
			if (
				collapsed.value === false ||
				(!props.multiple && props.options !== null && fieldValue.value.length > 0)
			) {
				return
			}

			calcPositioning()

			if (props.searchable) {
				searchRef.value?.focus()
			}

			await loadOptions()

			collapsed.value = false

			const eventsWindow: HTMLElement|null = document.querySelector('.events-window')

			window.addEventListener('click', close)
			window.addEventListener('blur', close) // Iframes

			if (eventsWindow) {
				eventsWindow.addEventListener('click', close)
				eventsWindow.addEventListener('blur', close) // Iframes
			}

			event?.stopPropagation()

		}

		const close = <E extends MouseEvent | PointerEvent | FocusEvent>(event?: E) => {
			const target = (event) ? event.relatedTarget ?? event.target : null

			if (target instanceof HTMLElement && (containerRef.value?.contains(target) || optionsRef.value?.contains(target))) {
				return
			}

			collapsed.value = true
			query.value = ''

			const eventsWindow: HTMLElement|null = document.querySelector('.events-window')

			window.removeEventListener('click', close)
			window.removeEventListener('blur', close) // Iframes

			if (eventsWindow) {
				eventsWindow.removeEventListener('click', close)
				eventsWindow.removeEventListener('blur', close) // Iframes
			}
		}

		const toggle = (event: MouseEvent) => {
			if (!collapsed.value) {
				open(event)
			} else {
				close(event)
			}
		}

		const select = async (option: SelectOption) => {
			if (hasValue(option.value)) {
				if (props.multiple) {
					await unselect(option.value)
				}
				return
			}

			if (props.multiple) {
				if (fieldValue.value.length > 0) {
					fieldValue.value = [...fieldValue.value, option]
				} else {
					fieldValue.value = [option]
				}
			} else {
				fieldValue.value = [option]
				close()
			}

			query.value = ''

			await loadOptions()
		}

		const unselect = async (value: SelectOptionValueType) => {
			const newValue = fieldValue.value.filter((loop: SelectOption) => loop.value !== value)
			if (newValue.length !== fieldValue.value.length) {
				fieldValue.value = newValue
				await nextTick()

				calcPositioning()
			}
		}

		const reset = async () => {
			fieldValue.value = []

			if (props.searchable) {
				searchRef.value?.focus()
			}
		}

		const hasValue = (value: SelectOptionValueType|SelectOptionValueType[]) => {
			if (!Array.isArray(value)) {
				value = [value]
			}

			const selectedValues = fieldValue.value.map(loop => loop.value)

			for (const loop of value) {
				if (selectedValues.includes(loop)) {
					return true
				}
			}

			return false
		}

		const search = debounce(async (event: KeyboardEvent) => {

			if (collapsed.value) {
				collapsed.value = false
			}

			let queryString = query.value.replace(/\s/g, '')

			if (props.createCustom) {
				const creationKeyCodes = [' ', 'Enter', ';', ',']

				if (creationKeyCodes.includes(event.key) && queryString.length > 0) {
					if ([';', ','].includes(queryString.slice(-1))) {
						queryString = queryString.slice(0, -1)
					}
					await select({ value: (fieldValue.value.length + 1) * -1, text: queryString })
					query.value = ''
				} else if (
					event.key === 'Backspace' && queryString.length === 0 &&
					fieldValue.value.length > 0
				) {
					const lastValue = fieldValue.value.at(-1)
					if (lastValue) {
						await unselect(lastValue.value)
					}
				}
			}

			await loadOptions()
		}, 200)

		const addOption = (option: SelectOptionWithVNode) => {
			allOptions.value.push(option)
		}

		const removeOption = (value: SelectOptionValueType) => {
			allOptions.value = allOptions.value.filter((loop: SelectOptionWithVNode) => loop.value !== value)
		}

		const loadOptions = async () => {
			if (!props.options) {
				return
			}

			searching.value = true

			await safe(props.options(query.value))

			await sleep(100)

			searching.value = false
		}

		const calcPositioning = () => {
			if (!containerRef.value) {
				return
			}

			const rect = containerRef.value.getBoundingClientRect()
			positioning.width = rect.width
			positioning.top = rect.top + rect.height
			positioning.left = rect.left
		}

		const placeholderCssClasses = computed(() => {
			return (attrs.class)
				? (attrs.class as string).split(' ').filter((cssClass: string) => cssClass.includes('placeholder:'))
				: ''
		})

		provide('addOption', addOption)
		provide('removeOption', removeOption)
		provide('select', select)
		provide('hasValue', hasValue)

		return {
			containerRef,
			optionsRef,
			searchRef,
			query,
			searching,
			collapsed,
			positioning,
			placeholderCssClasses,
			localOptions,
			open,
			close,
			toggle,
			unselect,
			reset,
			search,
			fieldValue
		}
	}
})
</script>

<template>
	<div
		ref="containerRef"
		tabindex="0"
		class=""
		@focus="open"
	>
		<div class="h-full w-full flex flex-row items-center gap-2">
			<div class="grow flex flex-row items-center gap-1 flex-wrap">
				<div
					v-for="(selected, index) in fieldValue"
					v-show="showTags"
					:key="index"
					:class="[
						'flex-none rounded px-1 py-0.5 flex items-center gap-1 text-nowrap',
						{
							'bg-gray-50': multiple && !selected.error,
							'bg-red-500 text-white': multiple && selected.error,
						}
					]"
				>
					<i
						v-if="selected.error"
						class="fas fa-exclamation-circle text-red-800"
					/>
					{{ selected.text }}
					<i
						v-show="multiple"
						class="fa fa-times cursor-pointer"
						@click.stop="unselect(selected.value)"
					/>
				</div>
				<div class="grow">
					<span
						v-show="!searchable && fieldValue.length === 0"
						:class="['w-full', ...placeholderCssClasses.map((css: string) => css.replace('placeholder:', ''))]"
					>
						{{ placeholder }}
					</span>
					<input
						v-show="searchable && (multiple || fieldValue.length === 0)"
						ref="searchRef"
						v-model="query"
						type="text"
						:class="['w-full', ...placeholderCssClasses]"
						tabindex="-1"
						:placeholder="(fieldValue.length === 0 || !showTags) ? placeholder : ''"
						@focus="open"
						@keydown="search"
					>
				</div>
			</div>
			<i
				v-show="searching"
				class="flex-none fa fa-spinner fa-spin"
			/>
			<div
				v-show="!multiple && fieldValue.length > 0"
				class="flex-none"
			>
				<i
					class="fas fa-times-circle cursor-pointer"
					@click="reset"
				/>
			</div>
			<div
				v-show="!options"
				class="flex-none"
			>
				<i
					:class="['fas', collapsed ? 'fa-chevron-down' : 'fa-chevron-up']"
					@click.stop="toggle"
				/>
			</div>
		</div>

		<Teleport to="body">
			<transition
				enter-active-class="transition ease-out duration-100"
				enter-from-class="transform opacity-0 scale-95"
				enter-to-class="transform opacity-100 scale-100"
				leave-active-class="transition ease-in duration-75"
				leave-from-class="transform opacity-100 scale-100"
				leave-to-class="transform opacity-0 scale-95"
			>
				<div
					v-show="!collapsed"
					ref="optionsRef"
					class="absolute p-1 bg-white rounded shadow-sm overflow-y-auto max-h-96"
					:style="{ width: `${positioning.width}px`, top: `${positioning.top}px`, left: `${positioning.left}px`, zIndex: 999999 }"
				>
					<div class="hidden">
						<slot />
					</div>
					<div
						v-show="localOptions.length > 0"
						class="flex flex-col gap-1"
					>
						<component
							:is="option.element"
							v-for="(option, index) in localOptions"
							:key="index"
						/>
					</div>
					<div
						v-show="localOptions.length === 0"
						class="text-gray-600 text-xs"
					>
						{{ emptyOptions }}
					</div>
				</div>
			</transition>
		</Teleport>
	</div>
</template>
