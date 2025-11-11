<script lang="ts">
import { defineComponent, onMounted, ref, type Ref, type PropType } from 'vue'
import { buildPrimaryColorElementCssClasses } from '../utils/primarycolor'

export default defineComponent({
	name: "LoadingBarMs",
	props: {
		ms: { type: Number, required: true },
		color: { type: String as PropType<'primary' | 'default'> , default: 'default' },
	},
	emits: ['start', 'finish'],
	setup(props, { emit }) {
		const progress: Ref<number> = ref(0)

		onMounted(() => {
			const startTime = Date.now()
			emit('start')

			const interval = setInterval(() => {
				const elapsedTime = Date.now() - startTime
				progress.value = Math.min((elapsedTime / props.ms) * 100, 100)

				if (progress.value >= 100) {
					clearInterval(interval)
					emit('finish')
				}
			}, 16)
		})

		return {
			progress,
			buildPrimaryColorElementCssClasses
		}
	}
})
</script>

<template>
	<div class="bg-gray-50 rounded-full">
		<div
			:class="[
				'h-full rounded-full',
				(color === 'primary') ? buildPrimaryColorElementCssClasses() : '',
				(color === 'default') ? 'bg-gray-200' : '',
				(color !== '') ? color : '',
			]"
			:style="{'width': `${progress}%`}"
		/>
	</div>
</template>

<style scoped>

</style>