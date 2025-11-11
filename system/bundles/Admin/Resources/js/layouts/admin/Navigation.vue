<script lang="ts">
import { defineComponent } from 'vue3'
import { useNavigation } from "../../composables/navigation"
import MobileNavigation from "./navigation/MobileNavigation.vue"
import BasicNavigation from "./navigation/BasicNavigation.vue"
import ExtendedNavigation from "./navigation/ExtendedNavigation.vue"

export default defineComponent({
	name: "Navigation",
	components: { ExtendedNavigation, BasicNavigation, MobileNavigation },
	setup() {
		const { navigation } = useNavigation()

		return {
			navigation
		}
	}
})
</script>

<template>
	<nav class="navigation bg-gray-800 text-white h-full">
		<MobileNavigation
			class="w-[17rem] lg:hidden"
		/>
		<ExtendedNavigation
			v-if="navigation.layout === 'extended'"
			class="hidden lg:block"
		/>
		<BasicNavigation
			v-else
			class="w-[16.5rem] hidden lg:block"
		/>
	</nav>
</template>
