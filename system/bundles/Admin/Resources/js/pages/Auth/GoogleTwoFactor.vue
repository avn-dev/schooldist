<script lang="ts">
import { defineComponent, PropType, ref, reactive, type Ref } from 'vue3'
import { router } from '@inertiajs/vue3'
import { Alert } from "../../types/interface"
import { InterfaceAuth } from "../../types/auth/app"
import { buildPrimaryColorElementCssClasses } from "../../utils/primarycolor"
import AuthLayout from "../../layouts/AuthLayout.vue"
import ButtonComponent from '../../components/ButtonComponent.vue'

export default defineComponent({
	name: "Login",
	components: { ButtonComponent, AuthLayout },
	props: {
		interface: { type: Object as PropType<InterfaceAuth>, required: true },
		title: { type: String, required: true },
		messages: { type: Array as PropType<Alert[]>, default: () => [] },
		l10n: { type: Object, required: true },
		qrCode: { type: String, default: null }
	},
	setup() {
		const loading: Ref<boolean> = ref(false)
		const form = reactive({
			otp: '',
			remember_device: false
		})

		const attempt = async () => {
			loading.value = true
			await router.post('/admin/login/attempt', form)
			loading.value = false
		}

		return {
			loading,
			form,
			router,
			attempt,
			buildPrimaryColorElementCssClasses
		}
	}
})
</script>

<template>
	<AuthLayout
		:interface="interface"
		:title="title"
		:messages="messages"
	>
		<div class="mt-10">
			<div>
				<form
					class="space-y-10"
					@submit.prevent="attempt"
				>
					<div
						v-if="qrCode"
						class="text-center"
					>
						<a
							href="http://www.google.com/support/a/bin/answer.py?hl=en&answer=1037451"
							class="text-sm font-medium text-gray-900 dark:text-gray-200"
							target="_blank"
						>
							{{ l10n.qr_code }}
						</a>
						<div class="flex place-content-center">
							<a
								:href="qrCode"
								class="flex mt-4"
							>
								<img :src="qrCode">
							</a>
						</div>
					</div>
					<div>
						<label
							for="otp"
							class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-200"
						>
							{{ l10n.field.code }}
						</label>
						<div class="mt-2">
							<input
								id="otp"
								v-model="form.otp"
								class="block w-full rounded-md border-0 p-1.5 shadow-sm focus:outline-none ring-1 ring-inset ring-gray-200 focus:ring-inset focus:ring-primary-300 sm:text-sm sm:leading-6 dark:bg-gray-700 dark:text-gray-200 dark:ring-gray-600"
								:disabled="loading"
							>
						</div>
					</div>
					<div class="flex items-center justify-between">
						<div class="flex items-center">
							<input
								id="remember-me"
								v-model="form.remember_device"
								type="checkbox"
								class="h-4 w-4 rounded border-gray-300"
							>
							<label
								for="remember-me"
								class="ml-3 block text-sm leading-6 text-gray-900 dark:text-gray-400"
							>
								{{ l10n.remember_device }}
							</label>
						</div>
					</div>
					<div class="flex flex-col gap-2">
						<ButtonComponent
							type="submit"
							color="primary"
							class="w-full font-semibold"
						>
							<i
								v-if="loading"
								class="fa fa-spinner fa-spin"
							/>
							{{ l10n.btn.submit }}
						</ButtonComponent>
						<ButtonComponent
							color="dark_gray"
							class="w-full mt-2 font-semibold"
							@click="router.visit('/admin/logout')"
						>
							{{ l10n.btn.cancel }}
						</ButtonComponent>
					</div>
				</form>
			</div>
		</div>
	</AuthLayout>
</template>