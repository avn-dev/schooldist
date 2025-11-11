<script lang="ts">
// @ts-ignore
import {defineComponent, PropType} from 'vue3'
// @ts-ignore
import { Head as InertiaHead } from '@inertiajs/vue3'
import Navigation from "./Tools/Navigation.vue"
import {Interface} from "../types"
// @ts-ignore
import {ColorScheme} from "@Admin/types/interface"
// @ts-ignore
import {resolveColorScheme} from "@Admin/utils/interface"

export default defineComponent({
	name: "ToolsLayout",
	components: { InertiaHead, Navigation },
	props: {
		interface: { type: Object as PropType<Interface>, required: true }
	},
	setup(props) {
		const colorScheme: ColorScheme = resolveColorScheme(props.interface.color_scheme)

		return {
			colorScheme
		}
	}
})
</script>

<template>
	<InertiaHead :title="interface.title" />
	<div
		:data-mode="colorScheme"
		class="h-screen text-xs"
	>
		<div class="h-screen bg-gray-50 dark:bg-gray-900 dark:text-gray-50">
			<Navigation :interface="interface" />
			<main class="pl-16 lg:pl-64 overflow-hidden">
				<slot />
			</main>
		</div>
	</div>
</template>
