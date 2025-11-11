<script lang="ts">
import { defineComponent, ref, watch, type PropType } from 'vue'

const SCORE_MAPPING = {
	0: { width: 25, bg: 'bg-red-400', text: 'text-red-800' },
	1: { width: 30, bg: 'bg-red-400', text: 'text-red-800'  },
	2: { width: 50, bg: 'bg-yellow-400', text: 'text-yellow-800'  },
	3: { width: 75, bg: 'bg-blue-400', text: 'text-blue-800'  },
	4: { width: 100, bg: 'bg-green-400', text: 'text-green-800'  },
}

export default defineComponent({
	name: "PasswordStrength",
	props: {
		password: { type: String, required: true },
		translations: { type: Object as PropType<Record<number, string>>, default: () => ({}) },
	},
	setup(props) {
		const score = ref(null)

		// @ts-ignore
		watch(() => props.password, (value: string) => score.value = (value.length > 0) ? window.zxcvbn(value).score : null)

		return {
			score,
			SCORE_MAPPING
		}
	}
})
</script>

<template>
	<div class="h-2 w-full rounded-full bg-gray-100/50">
		<div
			v-if="score !== null"
			:class="['relative h-full rounded-full transition-all', SCORE_MAPPING[score]?.bg]"
			:style="{'width': `${SCORE_MAPPING[score]?.width}%`}"
		>
			<div
				v-if="translations[score]"
				:class="['absolute -top-1 rounded right-0 text-xs px-0.5 text-nowrap font-medium', SCORE_MAPPING[score]?.bg, SCORE_MAPPING[score]?.text]"
			>
				{{ translations[score] }}
			</div>
		</div>
	</div>
</template>
