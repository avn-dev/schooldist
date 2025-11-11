<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import { Bar } from 'vue-chartjs'
import { Chart as ChartJS, Tooltip, BarElement, CategoryScale, LinearScale, ChartOptions, ChartData } from 'chart.js'

ChartJS.register(Tooltip, BarElement, CategoryScale, LinearScale)

export default defineComponent({
	name: 'EnquiriesInquiriesChart',
	components: { Bar },
	props: {
		options: { type: Object as PropType<ChartOptions>, required: true },
		data: { type: Object as PropType<ChartData>, required: true }
	}
})
</script>

<template>
	<Bar
		:options="options"
		:data="data"
		class="w-full h-full"
	/>
</template>