<script lang="ts">
import { defineComponent, PropType, ref, reactive, type Ref } from 'vue3'
import { router } from '@inertiajs/vue3'
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
		force: { type: Boolean, default: false },
		l10n: { type: Object, required: true }
	},
	setup(props) {
		const loading: Ref<boolean> = ref(false)
		const form = reactive({
			force: props.force,
			overwrite_session: 1
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
	>
		<div class="mt-10">
			<div>
				<form
					class="space-y-6"
					@submit.prevent="attempt"
				>
					<div class="mt-10 rounded-md bg-yellow-50 p-4 dark:bg-yellow-400/10">
						<div class="flex">
							<div class="flex-shrink-0">
								<i class="fa fa-exclamation-triangle text-yellow-400" />
							</div>
							<!-- eslint-disable vue/no-v-html -->
							<div
								class="ml-3 text-sm text-yellow-800 dark:text-yellow-500"
								v-html="l10n.message"
							/>
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