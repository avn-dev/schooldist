<script lang="ts">
import { defineComponent, PropType, onMounted, reactive, ref, type Ref } from 'vue3'
import { Link as InertiaLink, router } from '@inertiajs/vue3'
import axios from 'axios'
import { Alert } from "../../types/interface"
import { InterfaceAuth } from "../../types/auth/app"
import { buildPrimaryColorContrastCssClass, buildPrimaryColorElementCssClasses } from "../../utils/primarycolor"
import AuthLayout from "../../layouts/AuthLayout.vue"
import ButtonComponent from '../../components/ButtonComponent.vue'
import { browserSupportsWebAuthn, startAuthentication, type PublicKeyCredentialRequestOptionsJSON } from "@simplewebauthn/browser"
import { safe } from '../../utils/promise'

export default defineComponent({
	name: "Login",
	components: { ButtonComponent, InertiaLink, AuthLayout },
	props: {
		interface: { type: Object as PropType<InterfaceAuth>, required: true },
		title: { type: String, required: true },
		force: { type: Boolean, default: false },
		sso: { type: String, default: '' },
		messages: { type: Array as PropType<Alert[]>, default: () => [] },
		l10n: { type: Object, required: true },
		languages: { type: Array<string>, required: true },
		defaultLanguage: { type: String, required: true },
	},
	setup(props) {
		const loading: Ref<boolean> = ref(false)
		const usernameRef: Ref<HTMLInputElement|null> = ref(null)
		const localMessages: Ref<Alert[]> = ref([])
		const form = reactive({
			force: props.force,
			login: 'ok',
			username: '',
			password: '',
			language: props.defaultLanguage,
			passkey: ''
		})

		const changeLanguage = async () => {
			loading.value = true
			await router.post('/admin/login/language/change', form)
			loading.value = false
		}

		const login = async () => {
			localMessages.value = []
			loading.value = true
			await router.post('/admin/login/attempt', form)
			loading.value = false
		}

		const loginPasskey = async () => {
			localMessages.value = []
			loading.value = true

			const [, response] = await safe<{ data: { success: true, challenge: PublicKeyCredentialRequestOptionsJSON } }| { data: { success: false, errors: Alert[] } }>(axios.post('/admin/login/passkeys/challenge', { username: form.username }))

			if (response) {
				if (response.data.success === true) {
					const [, answer] = await safe(startAuthentication({ optionsJSON: response.data.challenge }))

					if (answer) {
						form.passkey = JSON.stringify(answer)
						await login()
					}
				} else {
					localMessages.value = response.data.errors
				}
			}

			loading.value = false
		}

		onMounted(() => usernameRef.value?.focus())

		return {
			usernameRef,
			loading,
			form,
			localMessages,
			login,
			loginPasskey,
			changeLanguage,
			browserSupportsWebAuthn,
			buildPrimaryColorElementCssClasses,
			buildPrimaryColorContrastCssClass
		}
	}
})
</script>

<template>
	<AuthLayout
		:interface="interface"
		:title="title"
		:messages="[...messages, ...localMessages]"
	>
		<div class="mt-10">
			<div>
				<form
					class="space-y-6"
					@submit.prevent="login"
				>
					<div>
						<label
							class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-200"
							for="username"
						>
							{{ l10n.field.username }}
						</label>
						<div class="mt-2">
							<input
								id="username"
								ref="usernameRef"
								v-model="form.username"
								:disabled="loading"
								class="block w-full rounded-md border-0 p-1.5 shadow-sm focus:outline-none ring-1 ring-inset ring-gray-200 focus:ring-inset focus:ring-primary-300 sm:text-sm sm:leading-6 dark:bg-gray-700 dark:text-gray-200 dark:ring-gray-600"
							>
						</div>
					</div>
					<div>
						<label
							class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-200"
							for="password"
						>
							{{ l10n.field.password }}
						</label>
						<div class="mt-2">
							<input
								id="password"
								v-model="form.password"
								:disabled="loading"
								class="block w-full rounded-md border-0 p-1.5 shadow-sm focus:outline-none ring-1 ring-inset ring-gray-200 focus:ring-inset focus:ring-primary-300 sm:text-sm sm:leading-6 dark:bg-gray-700 dark:text-gray-200 dark:ring-gray-600"
								type="password"
							>
						</div>
					</div>
					<div>
						<label
							class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-200"
							for="language"
						>
							{{ l10n.field.language }}
						</label>
						<div class="mt-2">
							<select
								id="language"
								v-model="form.language"
								:disabled="loading"
								class="bg-white block w-full rounded-md border-0 p-2 shadow-sm focus:outline-none ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-inset focus:ring-primary-300 sm:text-sm sm:leading-6 dark:bg-gray-700 dark:text-gray-200 dark:ring-gray-600"
								@change="changeLanguage"
							>
								<option
									v-for="language in languages"
									:key="language.value"
									:value="language.value"
								>
									{{ language.text }}
								</option>
							</select>
						</div>
					</div>
					<div class="flex items-center justify-between">
						<div class="flex items-center" />
						<div class="text-sm leading-6">
							<InertiaLink
								:class="[
									'font-semibold text-primary-500 hover:text-primary-600',
									buildPrimaryColorContrastCssClass('text', 'text')
								]"
								href="/admin/forgot-password"
							>
								{{ l10n.btn.forgot }}
							</InertiaLink>
						</div>
					</div>
					<div>
						<ButtonComponent
							class="w-full font-semibold"
							color="primary"
							type="submit"
							:disabled="loading"
						>
							<i
								v-if="loading"
								class="fa fa-spinner fa-spin"
							/>
							{{ l10n.btn.submit }}
						</ButtonComponent>
					</div>
				</form>
			</div>
			<div
				class="mt-10"
			>
				<div class="relative">
					<div
						aria-hidden="true"
						class="absolute inset-0 flex items-center"
					>
						<div class="w-full border-t border-gray-200 dark:border-gray-700" />
					</div>
					<div class="relative flex justify-center text-sm font-medium leading-6">
						<span class="bg-white px-6 text-gray-900 dark:bg-gray-800 dark:text-gray-100">
							{{ l10n.alternatives }}
						</span>
					</div>
				</div>
				<div
					v-if="browserSupportsWebAuthn() || sso"
					class="mt-6 flex flex-col"
				>
					<button
						class="w-full mt-2 text-center rounded-md bg-gray-500 px-3 py-1.5 text-sm font-semibold leading-6 text-gray-50 shadow-sm hover:bg-gray-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-600 disabled:cursor-not-allowed"
						:disabled="loading"
						@click="loginPasskey"
					>
						<i class="fa fa-fingerprint mr-2" />
						{{ l10n.btn.passkey }}
					</button>
				</div>
				<div
					v-if="sso"
					class="mt-6 flex flex-col"
				>
					<a
						:href="sso"
						class="w-full mt-2 text-center rounded-md bg-gray-500 px-3 py-1.5 text-sm font-semibold leading-6 text-gray-50 shadow-sm hover:bg-gray-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-600"
					>
						{{ l10n.btn.sso }}
					</a>
				</div>
			</div>
		</div>
	</AuthLayout>
</template>
