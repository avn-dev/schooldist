<script lang="ts">
import { defineComponent, ref, reactive, type Ref, type PropType } from 'vue'
import ButtonComponent from './ButtonComponent.vue'
import PasswordStrength from './PasswordStrength.vue'
import AlertMessage from './AlertMessage.vue'
import Passkey from './profile/Passkey.vue'
import Device from './profile/Device.vue'
import ModalFooter from './modal/ModalFooter.vue'
import { buildPrimaryColorContrastCssClass, buildPrimaryColorElementCssClasses } from '../utils/primarycolor'
import { safe } from '../utils/promise'
import { Alert } from '../types/interface'
import { ComponentApiInterface } from '../types/backend/router'
import { SelectOption, Passkey as PasskeyType, Device as DeviceType } from '../types/common'
import { browserSupportsWebAuthn, startRegistration, type PublicKeyCredentialCreationOptionsJSON } from "@simplewebauthn/browser"
import l10n from '../l10n'

type UserData = {
	firstname: string,
	lastname: string,
	email: string,
	phone: string,
	sex: number,
	birthday: string,
	street: string,
	city: string,
	zip: string,
	country: string,
	authentication: 'simple' | 'googletwofactor'
}

export default defineComponent({
	name: "MyProfile",
	components: { ModalFooter, Passkey, Device, AlertMessage, PasswordStrength, ButtonComponent },
	props: {
		api: { type: Object as PropType<ComponentApiInterface>, required: true },
		user: { type: Object as PropType<UserData>, required: true },
		sexes: { type: Array as PropType<SelectOption[]>, required: true },
		authentications: { type: Array as PropType<SelectOption[]>, required: true },
		passkeys: { type: Array as PropType<PasskeyType[]>, required: true },
		devices: { type: Array as PropType<DeviceType[]>, required: true },
	},
	setup(props) {
		const loading: Ref<boolean> = ref(false)
		const localPasskeys: Ref<PasskeyType[]> = ref([...props.passkeys])
		const localDevices: Ref<DeviceType[]> = ref([...props.devices])
		const alerts: Ref<Alert[]> = ref([])

		const navigation = [
			{ view: 'profile', text: l10n.translate('my_profile.tab.profile'), icon: 'fa fa-user-circle-o' },
			{ view: 'security', text: l10n.translate('my_profile.tab.security'), icon: 'fa fa-shield' },
		]
		const view: Ref<string> = ref(navigation[0].view)

		const form = reactive({
			firstname: props.user.firstname,
			lastname: props.user.lastname,
			email: props.user.email,
			phone: props.user.phone,
			sex: props.user.sex,
			birthday: props.user.birthday,
			street: props.user.street,
			city: props.user.city,
			zip: props.user.zip,
			country: props.user.country,
			authentication: props.user.authentication,
			current_password: '',
			password: '',
			password1: ''
		})


		const save = async (event: MouseEvent) => {

			if (loading.value !== false) {
				return
			}

			loading.value = true
			alerts.value = []

			/* eslint-disable @typescript-eslint/no-unused-vars */
			const [error, response] = await safe<{ success: boolean, messages: Alert[] }>(props.api.action('save', { method: 'post', data: form }))

			loading.value = false

			if (response) {
				if (response.success) {
					form.current_password = ''
					form.password = ''
					form.password1 = ''
				}

				alerts.value = response.messages

				if (response.messages.length > 0) {
					const scrollContainer = (event.target as HTMLElement)?.closest('.overflow-auto')
					if (scrollContainer) {
						scrollContainer.scrollTo({ top: 0, behavior: "smooth" })
					}
				}
			}
		}

		const createPasskey = async () => {
			const [, response] = await safe<PublicKeyCredentialCreationOptionsJSON>(props.api.action('createPasskey'))

			if (response) {
				const [, creation] = await safe(startRegistration({ optionsJSON: response }))

				if (creation) {
					const [, response] = await safe<{ success: true, passkey: PasskeyType } | { success: false, messages: Alert[] }>(props.api.action('savePasskey', { method: 'post', data: { passkey: JSON.stringify(creation)} }))

					if (response) {
						if (response.success === true) {
							localPasskeys.value.push(response.passkey)
						} else {
							alerts.value = response.messages
						}
					}
				}
			}
		}

		const updatePasskey = async (passkey: PasskeyType) => {
			const index = localPasskeys.value.findIndex((loop) => loop.value === passkey.value)
			if (index !== -1) {
				localPasskeys.value[index] = passkey
				await safe(props.api.action('updatePasskey', { method: 'post', data: { passkey } }))
			}
		}

		const deletePasskey = async (passkey: PasskeyType) => {
			localPasskeys.value = localPasskeys.value.filter((loop) => loop.value !== passkey.value)
			await safe(props.api.action('deletePasskey', { method: 'post', data: { id: passkey.value } }))
		}

		const deleteDevice = async (device: DeviceType) => {
			localDevices.value = localDevices.value.filter((loop) => loop.value !== device.value)
			await safe(props.api.action('deleteDevice', { method: 'post', data: { id: device.value } }))
		}

		return {
			alerts,
			view,
			navigation,
			form,
			localPasskeys,
			localDevices,
			save,
			buildPrimaryColorContrastCssClass,
			buildPrimaryColorElementCssClasses,
			browserSupportsWebAuthn,
			createPasskey,
			updatePasskey,
			deletePasskey,
			deleteDevice
		}
	}
})
</script>

<template>
	<div class="h-[80vh] text-sm pt-2">
		<div class="h-full flex flex-col gap-2">
			<div class="min-h-0 grow flex flex-row overflow-none">
				<nav class="flex-none h-full w-56 border-r border-gray-50">
					<ul
						role="list"
						class="p-2 space-y-1"
					>
						<li
							v-for="node in navigation"
							:key="node.view"
						>
							<button
								:class="[
									view === node.view ? buildPrimaryColorElementCssClasses() : 'text-gray-700 hover:bg-gray-50 hover:text-indigo-600 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white',
									'w-full group flex gap-x-3 items-center rounded-md p-2 text-sm font-semibold'
								]"
								@click="view = node.view"
							>
								<i
									:class="[node.icon, 'shrink-0']"
									aria-hidden="true"
								/>
								{{ node.text }}
							</button>
						</li>
					</ul>
				</nav>
				<div class="grow max-h-full grow py-2 px-4 overflow-auto">
					<div v-show="view === 'profile'">
						<div class="grid grid-cols-1 gap-4 sm:grid-cols-6">
							<div class="sm:col-span-3">
								<label
									for="firstname"
									class="block text-sm/6 font-medium"
								>
									{{ $l10n.translate('my_profile.label.firstname') }}*
								</label>
								<div class="mt-1">
									<input
										id="firstname"
										v-model="form.firstname"
										type="text"
										name="firstname"
										:class="[
											'block w-full rounded-md bg-white px-3 py-1.5 text-base outline outline-1 -outline-offset-1 outline-gray-100 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 sm:text-sm/6',
											buildPrimaryColorContrastCssClass('focus:outline', 'content')
										]"
									>
								</div>
							</div>
							<div class="sm:col-span-3">
								<label
									for="lastname"
									class="block text-sm/6 font-medium"
								>
									{{ $l10n.translate('my_profile.label.lastname') }}*
								</label>
								<div class="mt-1">
									<input
										id="lastname"
										v-model="form.lastname"
										type="text"
										name="lastname"
										:class="[
											'block w-full rounded-md bg-white px-3 py-1.5 text-base outline outline-1 -outline-offset-1 outline-gray-100 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 sm:text-sm/6',
											buildPrimaryColorContrastCssClass('focus:outline', 'content')
										]"
									>
								</div>
							</div>
							<div class="sm:col-span-3">
								<label
									for="email"
									class="block text-sm/6 font-medium"
								>
									{{ $l10n.translate('my_profile.label.email') }}*
								</label>
								<div class="mt-1">
									<input
										id="email"
										v-model="form.email"
										type="email"
										name="email"
										:class="[
											'block w-full rounded-md bg-white px-3 py-1.5 text-base outline outline-1 -outline-offset-1 outline-gray-100 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 sm:text-sm/6',
											buildPrimaryColorContrastCssClass('focus:outline', 'content')
										]"
									>
								</div>
							</div>
							<div class="sm:col-span-3">
								<label
									for="phone"
									class="block text-sm/6 font-medium"
								>
									{{ $l10n.translate('my_profile.label.phone') }}
								</label>
								<div class="mt-1">
									<input
										id="phone"
										v-model="form.phone"
										type="text"
										name="phone"
										:class="[
											'block w-full rounded-md bg-white px-3 py-1.5 text-base outline outline-1 -outline-offset-1 outline-gray-100 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 sm:text-sm/6',
											buildPrimaryColorContrastCssClass('focus:outline', 'content')
										]"
									>
								</div>
							</div>
							<div class="sm:col-span-3">
								<label
									for="sex"
									class="block text-sm/6 font-medium"
								>
									{{ $l10n.translate('my_profile.label.sex') }}
								</label>
								<div class="mt-1">
									<select
										id="sex"
										v-model="form.sex"
										name="sex"
										:class="[
											'col-start-1 row-start-1 w-full appearance-none rounded-md bg-white py-1.5 pl-3 text-base outline outline-1 -outline-offset-1 outline-gray-100 focus:outline focus:outline-2 focus:-outline-offset-2 sm:text-sm/6',
											buildPrimaryColorContrastCssClass('focus:outline', 'content')
										]"
									>
										<option
											v-for="option in sexes"
											:key="option.value"
											:value="option.value"
											:selected="option.value === user.sex"
										>
											{{ option.text }}
										</option>
									</select>
								</div>
							</div>
							<div class="sm:col-span-3">
								<label
									for="birthday"
									class="block text-sm/6 font-medium"
								>
									{{ $l10n.translate('my_profile.label.birthday') }}
								</label>
								<div class="mt-1">
									<input
										id="birthday"
										v-model="form.birthday"
										type="date"
										name="birthday"
										:class="[
											'block w-full rounded-md bg-white px-3 py-1.5 text-base outline outline-1 -outline-offset-1 outline-gray-100 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 sm:text-sm/6',
											buildPrimaryColorContrastCssClass('focus:outline', 'content')
										]"
									>
								</div>
							</div>
							<div class="col-span-full">
								<hr class="my-2 border-gray-100/50">
							</div>
							<div class="col-span-full">
								<h2 class="font-semibold">
									{{ $l10n.translate('my_profile.address.heading') }}
								</h2>
							</div>
							<div class="col-span-full">
								<label
									for="street"
									class="block text-sm/6 font-medium"
								>
									{{ $l10n.translate('my_profile.label.street') }}
								</label>
								<div class="mt-1">
									<input
										id="street"
										v-model="form.street"
										type="text"
										name="street"
										:class="[
											'block w-full rounded-md bg-white px-3 py-1.5 text-base outline outline-1 -outline-offset-1 outline-gray-100 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 sm:text-sm/6',
											buildPrimaryColorContrastCssClass('focus:outline', 'content')
										]"
									>
								</div>
							</div>
							<div class="sm:col-span-2 sm:col-start-1">
								<label
									for="city"
									class="block text-sm/6 font-medium"
								>
									{{ $l10n.translate('my_profile.label.city') }}
								</label>
								<div class="mt-1">
									<input
										id="city"
										v-model="form.city"
										type="text"
										name="city"
										:class="[
											'block w-full rounded-md bg-white px-3 py-1.5 text-base outline outline-1 -outline-offset-1 outline-gray-100 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 sm:text-sm/6',
											buildPrimaryColorContrastCssClass('focus:outline', 'content')
										]"
									>
								</div>
							</div>
							<div class="sm:col-span-2">
								<label
									for="zip"
									class="block text-sm/6 font-medium"
								>
									{{ $l10n.translate('my_profile.label.zip') }}
								</label>
								<div class="mt-1">
									<input
										id="zip"
										v-model="form.zip"
										type="text"
										name="zip"
										:class="[
											'block w-full rounded-md bg-white px-3 py-1.5 text-base outline outline-1 -outline-offset-1 outline-gray-100 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 sm:text-sm/6',
											buildPrimaryColorContrastCssClass('focus:outline', 'content')
										]"
									>
								</div>
							</div>
							<div class="sm:col-span-2">
								<label
									for="country"
									class="block text-sm/6 font-medium"
								>
									{{ $l10n.translate('my_profile.label.country') }}
								</label>
								<div class="mt-1">
									<input
										id="country"
										v-model="form.country"
										type="text"
										name="country"
										:class="[
											'block w-full rounded-md bg-white px-3 py-1.5 text-base outline outline-1 -outline-offset-1 outline-gray-100 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 sm:text-sm/6',
											buildPrimaryColorContrastCssClass('focus:outline', 'content')
										]"
									>
								</div>
							</div>
						</div>
					</div>
					<div v-show="view === 'security'">
						<div class="grid grid-cols-1 gap-4 sm:grid-cols-6">
							<div class="col-span-full">
								<div v-if="user.authentication !== 'passkeys_extern'">
									<label
										for="authentication"
										class="block text-sm/6 font-medium"
									>
										{{ $l10n.translate('my_profile.label.authentication') }}*
									</label>
									<div class="mt-1">
										<select
											id="authentication"
											v-model="form.authentication"
											name="authentication"
											:class="[
												'col-start-1 row-start-1 w-full appearance-none rounded-md bg-white py-1.5 pl-3 text-base outline outline-1 -outline-offset-1 outline-gray-100 focus:outline focus:outline-2 focus:-outline-offset-2 sm:text-sm/6',
												buildPrimaryColorContrastCssClass('focus:outline', 'content')
											]"
										>
											<option
												v-for="option in authentications"
												:key="option.value"
												:value="option.value"
												:selected="option.value === user.authentication"
											>
												{{ option.text }}
											</option>
										</select>
										<div
											v-show="form.authentication === 'passkeys'"
											class="mt-3 space-y-2"
										>
											<AlertMessage
												v-show="localPasskeys.length === 0"
												:message="$l10n.translate('my_profile.label.authentication.passkeys.empty')"
												type="warning"
												class="p-2 text-sm"
											/>
											<ul
												role="list"
												class="grid gap-2 grid-cols-1 lg:grid-cols-2"
											>
												<li
													v-for="passkey in localPasskeys"
													:key="passkey.value"
												>
													<Passkey
														:passkey="passkey"
														@update="(payload) => updatePasskey(payload)"
														@delete="deletePasskey(passkey)"
													/>
												</li>
												<li
													v-if="browserSupportsWebAuthn()"
													class="col-span-1 flex rounded-md shadow-xs dark:shadow-none"
												>
													<button
														type="button"
														class="relative block w-full rounded-lg border-2 border-dashed border-gray-100 p-1 text-center hover:border-gray-200 focus:outline-2 focus:outline-offset-2 focus:outline-indigo-600 dark:border-white/15 dark:hover:border-white/25 dark:focus:outline-indigo-500"
														@click="createPasskey"
													>
														<i class="fa fa-fingerprint mx-auto text-xl text-gray-400 dark:text-gray-500" />
														<span class="mt-1 block text-sm font-semibold text-gray-900 dark:text-white">
															{{ $l10n.translate('my_profile.label.authentication.passkeys.new') }}
														</span>
													</button>
												</li>
											</ul>
											<AlertMessage
												v-if="localPasskeys.length > 0"
												:message="$l10n.translate('my_profile.label.authentication.passkeys.existing')"
												type="info"
												class="p-2 text-sm"
											/>
										</div>
									</div>
								</div>
								<AlertMessage
									v-else
									:message="$l10n.translate('my_profile.label.authentication.passkeys.extern')"
									type="warning"
									class="mt-1 p-2 text-sm"
								/>
							</div>
							<div
								v-if="localDevices.length > 0"
								class="col-span-full"
							>
								<div class="col-span-full">
									<hr class="my-2 border-gray-100/50">
								</div>
								<div class="col-span-full">
									<h2 class="font-semibold">
										{{ $l10n.translate('my_profile.devices.heading') }}
									</h2>
									<p class="text-gray-400 text-sm">
										{{ $l10n.translate('my_profile.devices.description') }}
									</p>
								</div>
								<ul
									role="list"
									class="grid gap-2 grid-cols-1 lg:grid-cols-2 mt-3"
								>
									<li
										v-for="device in localDevices"
										:key="device.value"
									>
										<Device
											:device="device"
											@delete="deleteDevice(device)"
										/>
									</li>
								</ul>
							</div>
							<div class="col-span-full">
								<hr class="my-2 border-gray-100/50">
							</div>
							<div class="col-span-full">
								<h2 class="font-semibold">
									{{ $l10n.translate('my_profile.password.heading') }}
								</h2>
								<p class="text-gray-400 text-sm">
									{{ $l10n.translate('my_profile.password.description') }}
								</p>
							</div>
							<div class="col-span-full">
								<label
									for="current_password"
									class="block text-sm/6 font-medium"
								>
									{{ $l10n.translate('my_profile.label.current_password') }}
									<span v-show="form.password.length > 0">*</span>
								</label>
								<div class="mt-1">
									<input
										id="current_password"
										v-model="form.current_password"
										type="password"
										name="current_password"
										:class="[
											'block w-full rounded-md bg-white px-3 py-1.5 text-base outline outline-1 -outline-offset-1 outline-gray-100 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 sm:text-sm/6',
											buildPrimaryColorContrastCssClass('focus:outline', 'content')
										]"
									>
								</div>
							</div>
							<div class="col-span-full">
								<label
									for="password"
									class="block text-sm/6 font-medium"
								>
									{{ $l10n.translate('my_profile.label.password') }}
								</label>
								<div class="mt-1">
									<input
										id="password"
										v-model="form.password"
										type="password"
										name="password"
										:class="[
											'block w-full rounded-md bg-white px-3 py-1.5 text-base outline outline-1 -outline-offset-1 outline-gray-100 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 sm:text-sm/6',
											buildPrimaryColorContrastCssClass('focus:outline', 'content')
										]"
									>
									<PasswordStrength
										:password="form.password"
										:translations="{
											0: $l10n.translate('password.strength.very_week'),
											1: $l10n.translate('password.strength.week'),
											2: $l10n.translate('password.strength.sufficient'),
											3: $l10n.translate('password.strength.good'),
											4: $l10n.translate('password.strength.very_good')
										}"
										class="mt-2"
									/>
								</div>
							</div>
							<div class="col-span-full">
								<label
									for="password1"
									class="block text-sm/6 font-medium"
								>
									{{ $l10n.translate('my_profile.label.password_repeat') }}
									<span v-show="form.password.length > 0">*</span>
								</label>
								<div class="mt-1">
									<input
										id="password1"
										v-model="form.password1"
										type="password"
										name="password1"
										:class="[
											'block w-full rounded-md bg-white px-3 py-1.5 text-base outline outline-1 -outline-offset-1 outline-gray-100 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 sm:text-sm/6',
											buildPrimaryColorContrastCssClass('focus:outline', 'content')
										]"
									>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<ModalFooter
				:alerts="alerts"
				class="flex-none p-3"
			>
				<div />
				<div>
					<ButtonComponent
						type="button"
						color="primary"
						@click="save"
					>
						{{ $l10n.translate('my_profile.label.submit') }}
					</ButtonComponent>
				</div>
			</ModalFooter>
		</div>
	</div>
</template>
