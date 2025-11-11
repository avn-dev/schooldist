<script lang="ts">
import { defineComponent, onMounted, ref, type Ref } from 'vue'
import axios from 'axios'
import { safe } from '../utils/promise'
export default defineComponent({
	name: "ImageSource",
	props: {
		src: { type: String, required: true },
		alt: { type: String, required: true },
	},
	setup(props) {
		const loading: Ref<boolean> = ref(true)
		const base64: Ref<string|null> = ref(null)
		const source: Ref<string|null> = ref(null)

		const load = async () => {
			/* eslint-disable @typescript-eslint/no-unused-vars */
			const [error, response] = await safe<{ data: { image: string, source: string|null }}>(axios.get(props.src))
			loading.value = false

			if (response) {
				base64.value = response.data.image
				source.value = response.data.source
			}
		}

		onMounted(load)

		return {
			loading,
			base64,
			source
		}
	}
})
</script>

<template>
	<div class="h-full w-full inline-flex items-center justify-center overflow-hidden">
		<i
			v-if="loading"
			class="fa fa-spinner fa-spin"
		/>
		<div
			v-else
			class="h-full w-full relative"
		>
			<img
				:src="base64"
				class="h-full w-full object-cover inset-0"
				:alt="alt"
			>
			<!-- eslint-disable vue/no-v-html -->
			<div
				v-if="source"
				class="text-xs text-gray-950 px-2 py-1 absolute right-2 bottom-2 rounded bg-gray-100/50"
				v-html="source"
			/>
		</div>
	</div>
</template>
