<script lang="ts">
import { defineComponent, PropType, ref, reactive, type Ref } from 'vue3'
import { router } from '@inertiajs/vue3'
import { Alert } from "../../types/interface"
import { InterfaceAuth } from "../../types/auth/app"
import { buildPrimaryColorElementCssClasses } from "../../utils/primarycolor"
import AuthLayout from "../../layouts/AuthLayout.vue"
import ButtonComponent from '../../components/ButtonComponent.vue'

export default defineComponent({
	name: "ForgotPassword",
	components: { ButtonComponent, AuthLayout },
	props: {
		interface: { type: Object as PropType<InterfaceAuth>, required: true },
		title: { type: String, required: true },
		messages: { type: Array as PropType<Alert[]>, default: () => [] },
		l10n: { type: Object, required: true },
	},
	setup() {
		const loading: Ref<boolean> = ref(false)
		const form = reactive({
			email: '',
		})

		const attempt = async () => {
			loading.value = true
			await router.post('/admin/forgot-password/request', form)
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
					<div>
						<label
							for="email"
							class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-200"
						>
							{{ l10n.field.email }}
						</label>
						<div class="mt-2">
							<input
								v-model="form.email"
								class="block w-full rounded-md border-0 p-1.5 shadow-sm focus:outline-none ring-1 ring-inset ring-gray-200 focus:ring-inset focus:ring-primary-300 sm:text-sm sm:leading-6 dark:bg-gray-700 dark:text-gray-200 dark:ring-gray-600"
								:disabled="loading"
							>
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
							@click="router.visit('/admin/login')"
						>
							{{ l10n.btn.cancel }}
						</ButtonComponent>
					</div>
				</form>
			</div>
		</div>
	</AuthLayout>
</template>
