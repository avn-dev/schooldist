<script lang="ts">
import { defineComponent, reactive, ref, type Ref, type PropType } from 'vue'
import { ComponentApiInterface, RouterActionStorePayload } from "@Admin/types/backend/router"
import { safe } from '@Admin/utils/promise'
import { SelectOption as SelectOptionType } from '@Admin/types/common'
import { Alert } from "@Admin/types/interface"
import InputField from '@Gui2/components/dialog/InputField.vue'
import SelectField from '@Admin/components/form/SelectField.vue'
import SelectOption from '@Admin/components/form/select/SelectOption.vue'
import UserAvatar from '@Admin/components/UserAvatar.vue'
import AlertMessage from '@Admin/components/AlertMessage.vue'
import ButtonComponent from '@Admin/components/ButtonComponent.vue'

export default defineComponent({
	name: "Inquiry",
	components: {
		AlertMessage,
		InputField,
		SelectField,
		SelectOption,
		UserAvatar,
		ButtonComponent
	},
	props: {
		api: { type: Object as PropType<ComponentApiInterface>, required: true },
		payloadStorable: { type: Object as PropType<RouterActionStorePayload|null>, default: null },
		form: { type: Object as PropType<{ firstname: string, lastname: string, email: string, phone: string }>, default: () => ({}) }
	},
	emits: ['close'],
	setup(props, { emit }) {
		const action: Ref<'new_enquiry'|'inquiry'|'enquiry'|null> = ref(null)
		const searchResult: Ref<SelectOptionType[]> = ref([])
		const errors: Ref<Alert[]> = ref([])
		const existing: Ref<SelectOptionType|null> = ref(null)
		const localForm = reactive({
			firstname: props.form.firstname ?? '',
			lastname: props.form.lastname ?? '',
			email: props.form.email ?? '',
			phone: props.form.phone ?? ''
		})

		const reset = () => {
			action.value = null
			existing.value = null
		}

		const search = async (query: string) => {

			searchResult.value = []

			if (!query || query.length === 0) {
				return []
			}

			const [, response] = await safe<SelectOptionType[]>(props.api.action('search', {
				method: 'post',
				params: {
					type: action.value,
					query
				}
			}))

			if (response) {
				searchResult.value = response
			}

		}

		const save = async () => {
			const body = (action.value === 'new_enquiry') ? localForm : { existing: existing.value?.value }
			errors.value = []

			const [, response] = await safe<{ success: true } | { success: false, errors: Alert[] }>(props.api.action('save', {
				method: 'post',
				params: {
					type: action.value,
					...body
				}
			}))

			if (response) {
				if (response.success === true) {
					emit('close', true)
				} else {
					errors.value = response.errors
				}
			}
		}

		return {
			errors,
			action,
			localForm,
			existing,
			searchResult,
			reset,
			search,
			save
		}
	}
})
</script>

<template>
	<div>
		<ul
			v-if="!action"
			role="list"
			class="divide-y divide-gray-50 overflow-hidden"
		>
			<li class="relative flex justify-between gap-x-6 px-2 py-3 hover:bg-gray-50 sm:px-6">
				<div
					class="group flex items-center gap-4 px-2 py-1 rounded-md hover:bg-gray-50 cursor-pointer"
					@click="action = 'new_enquiry'"
				>
					<div class="text-xl py-2 px-3 text-gray-300 group-hover:text-gray-400 bg-gray-50 group-hover:bg-gray-100 rounded-md">
						<i class="fa fa-plus" />
					</div>
					<div class="min-w-0">
						<p class="text-sm/6 font-semibold text-gray-900">
							{{ $l10n.translate('communication.allocate.action.enquiry.new.btn') }}
						</p>
						<p class="mt-1 text-xs/5 text-gray-500">
							{{ $l10n.translate('communication.allocate.action.enquiry.new.text') }}
						</p>
					</div>
				</div>
			</li>
			<li class="relative flex justify-between gap-x-6 px-2 py-3 hover:bg-gray-50 sm:px-6">
				<div
					class="group flex items-center gap-4 px-2 py-1 rounded-md hover:bg-gray-50 cursor-pointer"
					@click="action = 'enquiry'"
				>
					<div class="text-xl py-2 px-3 text-gray-300 group-hover:text-gray-400 bg-gray-50 group-hover:bg-gray-100 rounded-md">
						<i class="fas fa-user-plus" />
					</div>
					<div class="min-w-0">
						<p class="text-sm/6 font-semibold text-gray-900">
							{{ $l10n.translate('communication.allocate.action.enquiry.allocate.btn') }}
						</p>
						<p class="mt-1 text-xs/5 text-gray-500">
							{{ $l10n.translate('communication.allocate.action.enquiry.allocate.text') }}
						</p>
					</div>
				</div>
			</li>
			<li class="relative flex justify-between gap-x-6 px-2 py-3 hover:bg-gray-50 sm:px-6">
				<div
					class="group flex items-center gap-4 px-2 py-1 rounded-md hover:bg-gray-50 cursor-pointer"
					@click="action = 'inquiry'"
				>
					<div class="text-xl py-2 px-3 text-gray-300 group-hover:text-gray-400 bg-gray-50 group-hover:bg-gray-100 rounded-md">
						<i class="fas fa-user-plus" />
					</div>
					<div class="min-w-0">
						<p class="text-sm/6 font-semibold text-gray-900">
							{{ $l10n.translate('communication.allocate.action.inquiry.allocate.btn') }}
						</p>
						<p class="mt-1 text-xs/5 text-gray-500">
							{{ $l10n.translate('communication.allocate.action.inquiry.allocate.text') }}
						</p>
					</div>
				</div>
			</li>
		</ul>
		<div v-else>
			<div
				v-show="errors.length > 0"
				class="flex flex-col gap-1 pt-1"
			>
				<AlertMessage
					v-for="(error, index) in errors"
					:key="index"
					:message="error"
					type="error"
					class="p-2 text-xs"
				/>
			</div>
			<div class="py-4 px-3">
				<div
					v-if="action === 'new_enquiry'"
					class=""
				>
					<InputField
						v-model="localForm.firstname"
						:label="$l10n.translate('communication.allocate.form.firstname')"
					/>
					<InputField
						v-model="localForm.lastname"
						:label="$l10n.translate('communication.allocate.form.lastname')"
					/>
					<InputField
						v-model="localForm.email"
						:label="$l10n.translate('communication.allocate.form.email')"
					/>
					<InputField
						v-model="localForm.phone"
						:label="$l10n.translate('communication.allocate.form.phone')"
					/>
				</div>
				<div
					v-else
					class="relative text-xs text-gray-900 py-2 sm:grid sm:grid-cols-12 sm:gap-4"
				>
					<label class="block font-semibold leading-6 sm:col-span-4 sm:pt-1.5 sm:px-3 text-right">
						{{ $l10n.translate('communication.allocate.form.search') }}
					</label>
					<div class="relative sm:col-span-8 sm:items-center">
						<SelectField
							v-model="existing"
							:searchable="true"
							:placeholder="$l10n.translate('communication.allocate.form.search.placeholder')"
							:empty-options="$l10n.translate('communication.allocate.form.search.empty')"
							:options="search"
							class="text-gray-600 text-xs rounded border border-gray-100 p-2 bg-white placeholder:text-gray-200/75 placeholder:font-light"
						>
							<SelectOption
								v-for="result in searchResult"
								:key="result.value"
								v-slot="{ select, active }"
								:value="result.value"
								:text="result.text"
								class="text-xs text-gray-600"
							>
								<div
									:class="[
										'group flex items-center gap-3 p-1 rounded-md cursor-pointer',
										active(result.value) ? 'bg-gray-100/50 text-gray-800' : 'hover:bg-gray-50'
									]"
									@click.stop="select(result)"
								>
									<UserAvatar
										:user="{ icon: 'fas fa-plus' }"
										:class="[
											'flex-none size-6 text-xs',
											active(result.value) ? 'bg-gray-100 text-gray-500' : 'text-gray-400 bg-gray-50 group-hover:bg-gray-100'
										]"
									/>
									<div class="grow font-semibold truncate">
										{{ result.text }}
									</div>
								</div>
							</SelectOption>
						</SelectField>
					</div>
				</div>
			</div>
			<div class="bg-gray-100/50 dark:bg-gray-900 p-3 rounded-md">
				<div class="flex flex-col sm:flex-row gap-2">
					<div class="flex-none flex gap-2 flex-col sm:flex-row">
						<ButtonComponent @click="reset">
							{{ $l10n.translate('common.back') }}
						</ButtonComponent>
					</div>
					<div class="grow flex gap-2 flex-col sm:flex-row justify-end">
						<ButtonComponent
							color="primary"
							@click="save"
						>
							{{ $l10n.translate('communication.allocate.btn') }}
						</ButtonComponent>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>
