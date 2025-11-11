<script lang="ts">
import { defineComponent, PropType } from 'vue3'
import { Head as InertiaHead } from '@inertiajs/vue3'
import { ColorSchemeSetting, Alert } from "../types/interface"
import { InterfaceAuth } from "../types/auth/app"
import { resolveColorScheme } from "../utils/interface"
import AlertMessage from "../components/AlertMessage.vue"
import ImageSource from '../components/ImageSource.vue'

export default defineComponent({
	name: "AuthLayout",
	components: { ImageSource, InertiaHead, AlertMessage },
	props: {
		interface: { type: Object as PropType<InterfaceAuth>, required: true },
		messages: { type: Array<Alert[]>, default: [] },
		title: { type: String, required: true },
	},
	setup(props) {
		const colorScheme: ColorSchemeSetting = resolveColorScheme(props.interface.color_scheme)

		return {
			colorScheme
		}
	}
})
</script>

<template>
	<div
		:data-mode="colorScheme"
		class="h-screen"
	>
		<InertiaHead>
			<title>{{ title }}</title>
		</InertiaHead>
		<div class="flex min-h-full">
			<div class="bg-white flex flex-1 flex-col justify-center px-4 py-8 sm:px-6 lg:flex-none lg:px-20 xl:px-24 dark:bg-gray-800">
				<div class="mx-auto w-full max-w-sm lg:w-96">
					<div>
						<img
							:src="(colorScheme === 'dark') ? interface.logo.dark : interface.logo.light"
							class="h-10 w-auto"
							alt=""
						>
					</div>
					<div
						v-if="messages.length > 0"
						class="mt-4 space-y-2"
					>
						<AlertMessage
							v-for="(message, index) in messages"
							:key="index"
							:type="message.type"
							:message="message.message"
							class="p-4 text-sm"
						/>
					</div>
					<div>
						<slot />
					</div>
					<div class="w-full mt-4 text-center text-sm text-gray-500 dark:text-gray-300">
						&copy; {{ interface.copyright }}
					</div>
				</div>
			</div>
			<div class="relative hidden bg-gray-50 w-0 flex-1 lg:block m-2 rounded-xl overflow-hidden">
				<ImageSource
					src="/admin/login/image"
					class="absolute h-full w-full text-gray-500"
					alt=""
				/>
			</div>
		</div>
	</div>
</template>