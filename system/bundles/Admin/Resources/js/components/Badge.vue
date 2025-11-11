<script lang="ts">
import { defineComponent, type PropType } from 'vue3'
import { buildPrimaryColorContrastCssClass, buildPrimaryColorElementCssClasses } from '../utils/primarycolor'

export default defineComponent({
	name: "Badge",
	props: {
		color: { type: String as PropType<'primary'|'default'|'custom'>, default: 'default' },
		filled: { type: Boolean, default: false },
	},
	setup() {
		return {
			buildPrimaryColorContrastCssClass,
			buildPrimaryColorElementCssClasses
		}
	}
})
</script>

<template>
	<span
		:class="[
			'rounded flex flex-row items-center place-content-center',
			(color === 'primary' && !filled) ? ['border', buildPrimaryColorContrastCssClass('border'), buildPrimaryColorContrastCssClass('text')].join(' ') : '',
			(color === 'primary' && filled) ? buildPrimaryColorElementCssClasses() : '',
			(color === 'default' && !filled) ? 'border border-gray-100 text-gray-900 dark:text-gray-50' : '',
			(color === 'default' && filled) ? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-50' : '',
		]"
	>
		<slot />
	</span>
</template>
