<script lang="ts">
import { defineComponent, computed, type PropType } from 'vue3'
import { ComponentSize } from '../types/backend/app'
import { buildPrimaryColorElementCssClasses } from '../utils/primarycolor'

// TODO mode: filled siehe Badge
export default defineComponent({
	name: "ButtonComponent",
	props: {
		type: { type: String as PropType<'submit' | 'button'>, default: 'button' },
		color: { type: String as PropType<'primary' | 'gray' | 'dark_gray' | 'default'> , default: 'default' },
		size: { type: String as PropType<ComponentSize>, default: ComponentSize.medium }
	},
	setup(props) {

		const sizeCss = computed(() => {
			if (props.size) {
				if (props.size === ComponentSize.extra_large) {
					return 'rounded-md lg:ax-w-7xl'
				} else if (props.size === ComponentSize.large) {
					return 'rounded-md lg:max-w-3xl'
				} else if (props.size === ComponentSize.small) {
					return 'rounded text-xs px-1.5 py-1'
				}
			}
			return 'rounded-md text-sm px-2.5 py-1.5'
		})

		return {
			sizeCss,
			buildPrimaryColorElementCssClasses
		}
	}
})
</script>

<template>
	<button
		:type="type"
		:class="[
			'font-body font-medium disabled:cursor-not-allowed',
			sizeCss,
			(color === 'primary') ? buildPrimaryColorElementCssClasses() : '',
			(color === 'gray') ? 'bg-gray-100 text-gray-800 hover:bg-gray-200 hover:text-gray-800 dark:bg-gray-950 dark:text-gray-200 dark:hover:bg-gray-800 dark:hover:text-gray-950' : '',
			(color === 'dark_gray') ? 'bg-gray-500 dark:bg-gray-950 text-gray-50 dark:text-gray-600 hover:bg-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-800' : '',
			(color === 'default') ? 'bg-white ring-1 ring-inset text-gray-800 ring-gray-200/75 hover:bg-gray-100 dark:ring-0 dark:bg-gray-900 dark:text-gray-200 dark:ring-gray-900 dark:hover:bg-gray-950' : '',
		]"
	>
		<slot />
	</button>
</template>
