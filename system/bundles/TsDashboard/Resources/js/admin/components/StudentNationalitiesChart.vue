<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import { Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, Tooltip, ArcElement, CategoryScale, LinearScale, ChartOptions, ChartData } from 'chart.js'

ChartJS.register(Tooltip, ArcElement, CategoryScale, LinearScale)

export default defineComponent({
	name: 'StudentNationalitiesChart',
	components: { Doughnut },
	props: {
		options: { type: Object as PropType<ChartOptions>, required: true },
		data: { type: Object as PropType<ChartData>, required: true }
	}
})
</script>

<template>
	<Doughnut
		:options="options"
		:data="data"
		class="w-full h-full"
	/>
</template>