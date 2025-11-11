<script lang="ts">

import { defineComponent, computed, ref, type Ref, type ComputedRef, type PropType } from 'vue'
import { ComponentApiInterface } from '@Admin/types/backend/router'
import { SelectOption as SelectOptionType } from '@Admin/types/common'
import SelectField from '@Admin/components/form/SelectField.vue'
import SelectOption from '@Admin/components/form/select/SelectOption.vue'
import UserAvatar from '@Admin/components/UserAvatar.vue'
import Badge from '@Admin/components/Badge.vue'
import {
	CommunicationRecipientFieldConfig,
	CommunicationContact,
	CommunicationContactGroup
} from "../../../types/communication"
import { groupContacts } from '../../../utils/communication'
import { buildPrimaryColorContrastCssClass } from '@Admin/utils/primarycolor'

export default defineComponent({
	name: "RecipientSelection",
	components: { SelectField, SelectOption, UserAvatar, Badge },
	props: {
		api: { type: Object as PropType<ComponentApiInterface>, required: true },
		channel: { type: String, required: true },
		config: { type: Object as PropType<CommunicationRecipientFieldConfig>, required: true },
		contacts: { type: Array as PropType<CommunicationContact[]>, default: () => [] }
	},
	emits: ['update:modelValue'],
	setup(props) {
		const localContacts: Ref<CommunicationContact[]> = ref(props.contacts)

		const groupedContacts: ComputedRef<CommunicationContactGroup[]> = computed(() => groupContacts(localContacts.value))
		const contactGroups: ComputedRef<string[]> = computed(() => {
			const allGroups = localContacts.value.map(contact => contact.groups).flat()
			const withMultiOccurences = allGroups.filter((item, index, self) => self.indexOf(item) !== index)
			return [...new Set(withMultiOccurences)]
		})

		const selectContact = (select: (option: SelectOptionType) => void, contact: CommunicationContact) => {
			if (props.config.routes_selection) {
				const routes = contact.routes.map((item: SelectOptionType) => item.text).join(', ')
				select({ value: contact.value, text: `${contact.text} <${routes}>` })
			} else {
				select({ value: contact.value, text: contact.text })
			}
		}

		/*const searchContacts = async (query: string) => {
			localContacts.value = []
			localGroups.value = []

			await nextTick()

			const [error, response] = await safe<{ contacts: CommunicationContact[], groups: SelectOptionType[] }>(props.api.action('searchContacts', {
				params: {
					channel: props.channel,
					...props.related,
					query
				}})
			)

			if (response) {
				localContacts.value = response.contacts
				localGroups.value = response.groups
			} else {
				throw error
			}
		}*/

		return {
			localContacts,
			groupedContacts,
			contactGroups,
			selectContact,
			//searchContacts
			buildPrimaryColorContrastCssClass
		}
	}
})
</script>

<template>
	<SelectField
		:multiple="true"
		:searchable="true"
		:placeholder="$l10n.translate('communication.message.recipient.placeholder')"
		:empty-options="$l10n.translate('communication.message.recipient.empty')"
		:create-custom="config.allow_custom"
		class="flex flex-row items-center w-full min-h-6 placeholder:text-gray-200/75 placeholder:font-light"
		@update:model-value="$emit('update:modelValue', $event)"
	>
		<SelectOption
			v-if="localContacts.length > 0"
			v-slot="{ select, active }"
			:value="all"
			:text="$l10n.translate('communication.message.recipient.all')"
			class="text-xs text-gray-600"
		>
			<div
				:class="[
					'group flex items-center gap-3 p-1 rounded-md  cursor-pointer',
					active('all') ? 'bg-gray-100/50' : 'hover:bg-gray-50'
				]"
				@click="select({ value: 'all', text: $l10n.translate('communication.message.recipient.all') })"
			>
				<UserAvatar
					:user="{ icon: 'fas fa-plus' }"
					:class="[
						'flex-none size-6 text-xs',
						active('all') ? 'bg-gray-100 text-gray-500' : 'text-gray-400 bg-gray-50 group-hover:bg-gray-100'
					]"
				/>
				<div class="grow truncate">
					{{ $l10n.translate('communication.message.recipient.select_all') }}
				</div>
			</div>
		</SelectOption>
		<SelectOption
			v-for="group in contactGroups"
			:key="`group_${group}`"
			v-slot="{ select, active }"
			:value="group"
			:text="$l10n.translate('communication.message.recipient.group_selection').replace('%s', group)"
			class="text-xs text-gray-600"
		>
			<div
				:class="[
					'group flex items-center gap-3 p-1 rounded-md cursor-pointer',
					active(group) ? 'bg-gray-100/50' : 'hover:bg-gray-50'
				]"
				@click="select({ value: group, text: group })"
			>
				<UserAvatar
					:user="{ icon: 'fas fa-plus' }"
					:class="[
						'flex-none size-6 text-xs',
						active(group) ? 'bg-gray-100 text-gray-500' : 'text-gray-400 bg-gray-50 group-hover:bg-gray-100'
					]"
				/>
				<div class="grow truncate">
					{{ $l10n.translate('communication.message.recipient.group_selection').replace('%s', group) }}
				</div>
			</div>
		</SelectOption>
		<template
			v-for="contactGroup in groupedContacts"
			:key="contactGroup.text"
		>
			<SelectOption
				:value="contactGroup.text"
				:text="contactGroup.text"
				class="text-xs text-gray-600"
			>
				<div
					v-if="contactGroup.text.length > 0"
					:class="['px-1 py-0.5 border-b', buildPrimaryColorContrastCssClass('text'), buildPrimaryColorContrastCssClass('border')]"
				>
					{{ contactGroup.text }}
				</div>
			</SelectOption>
			<SelectOption
				v-for="contact in contactGroup.contacts"
				:key="contact.value"
				v-slot="{ select, active }"
				:value="contact.value"
				:text="[contact.text, ...contact.routes.map(route => route.text), ...contact.groups].join(' ')"
				class="text-xs text-gray-600"
			>
				<div
					:class="[
						'group flex items-center gap-3 p-1 rounded-md  cursor-pointer',
						active([...(contact.allSelection) ? ['all'] : [], contact.value, ...contact.groups, ...contact.routes.map(route => route.value)]) ? 'bg-gray-100/50' : 'hover:bg-gray-50'
					]"
					@click="selectContact(select, contact)"
				>
					<UserAvatar
						:user="{ icon: 'fas fa-user' }"
						:class="[
							'flex-none size-6 text-xs',
							active(['all', contact.value, ...contact.groups, ...contact.routes.map(route => route.value)]) ? 'bg-gray-100 text-gray-500' : 'text-gray-400 bg-gray-50 group-hover:bg-gray-100'
						]"
					/>
					<div class="grow truncate flex flex-col gap-1">
						<div class="font-semibold">
							{{ contact.text }}
						</div>
						<div
							v-if="config.routes_selection"
							class="flex flex-row items-center gap-1"
						>
							<span
								v-if="contact.routes.length === 1"
								class="text-gray-400"
							>
								{{ contact.routes[0].text }}
							</span>
							<Badge
								v-for="route in contact.routes"
								v-else
								:key="route"
								:title="route.text"
								:class="[
									'gap-1 text-xs px-0.5 font-normal hover:bg-gray-200 hover:text-gray-800',
									active([contact.value, route.value]) ? 'bg-gray-300 text-gray-700' : 'bg-gray-100/75'
								]"
								color="custom"
								@click.stop="select({ value: route.value, text: `${contact.text} <${route.text}>` })"
							>
								{{ route.text }}
							</Badge>
						</div>
					</div>
					<div class="flex-none flex flex-row items-center gap-1">
						<Badge
							v-for="group in contact.groups"
							:key="group"
							:title="group"
							class="gap-1 text-xs px-1 py-0.5 truncate"
							color="primary"
						>
							{{ group }}
						</Badge>
					</div>
				</div>
			</SelectOption>
		</template>
	</SelectField>
</template>