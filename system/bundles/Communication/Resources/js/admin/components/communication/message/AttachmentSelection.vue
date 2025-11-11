<script lang="ts">

import { defineComponent, computed, type ComputedRef, type PropType } from 'vue'
import { type SelectOption as SelectOptionType } from '@Admin/types/common'
import {
	CommunicationAttachmentGroup,
	CommunicationMessageAttachment
} from "../../../types/communication"
import { groupAttachments } from '../../../utils/communication'
import { buildPrimaryColorContrastCssClass } from '@Admin/utils/primarycolor'
import SelectField from '@Admin/components/form/SelectField.vue'
import SelectOption from '@Admin/components/form/select/SelectOption.vue'
import UserAvatar from '@Admin/components/UserAvatar.vue'
import Badge from '@Admin/components/Badge.vue'

export default defineComponent({
	name: "AttachmentSelection",
	components: { SelectField, SelectOption, UserAvatar, Badge },
	props: {
		attachments: { type: Array as PropType<CommunicationMessageAttachment[]>, default: () => [] },
		groups: { type: Array as PropType<SelectOptionType[]>, default: () => [] },
	},
	emits: ['update:modelValue'],
	setup(props) {
		const sourceGroups: ComputedRef<CommunicationAttachmentGroup[]> = computed(() => groupAttachments(props.attachments))
		const attachmentGroups: ComputedRef<string[]> = computed(() => {
			const allGroups = props.attachments.map(attachment => attachment.groups).flat()
			const withMultiOccurrences = allGroups.filter((item, index, self) => self.indexOf(item) !== index)
			return [...new Set(withMultiOccurrences)]
		})

		return {
			sourceGroups,
			attachmentGroups,
			buildPrimaryColorContrastCssClass
		}
	}
})
</script>

<template>
	<SelectField
		:multiple="true"
		:searchable="true"
		:placeholder="$l10n.translate('communication.message.attachments.placeholder')"
		:empty-options="$l10n.translate('communication.message.attachments.empty')"
		:create-custom="false"
		:show-tags="false"
		class="flex flex-row items-center w-full min-h-6 placeholder:text-gray-200/75 placeholder:font-light"
		@update:model-value="$emit('update:modelValue', $event)"
	>
		<SelectOption
			v-if="sourceGroups.length > 0"
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
			v-for="group in attachmentGroups"
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
			v-for="source in sourceGroups"
			:key="source.text"
		>
			<SelectOption
				:value="source.text"
				:text="source.text"
				class="text-xs text-gray-600"
			>
				<div
					v-if="source.text.length > 0"
					:class="['px-1 py-0.5 border-b', buildPrimaryColorContrastCssClass('text'), buildPrimaryColorContrastCssClass('border')]"
				>
					{{ source.text }}
				</div>
			</SelectOption>
			<SelectOption
				v-for="attachment in source.attachments"
				:key="attachment.key"
				v-slot="{ select, active }"
				:value="attachment.key"
				:text="[attachment.file_name, ...attachment.groups].join(' ')"
				class="text-xs text-gray-600"
			>
				<div
					:class="[
						'group flex items-center gap-3 p-1 rounded-md  cursor-pointer',
						active(['all', attachment.key, ...attachment.groups]) ? 'bg-gray-100/50' : 'hover:bg-gray-50'
					]"
					@click="select({ value: attachment.key, text: attachment.file_name })"
				>
					<UserAvatar
						:user="{ icon: attachment.icon }"
						:class="[
							'flex-none size-6 text-xs',
							active(['all', attachment.key, ...attachment.groups]) ? 'bg-gray-100 text-gray-500' : 'text-gray-400 bg-gray-50 group-hover:bg-gray-100'
						]"
					/>
					<div class="grow truncate flex flex-col gap-1">
						<div class="font-semibold">
							{{ attachment.file_name }}
						</div>
						<div class="flex flex-row items-center gap-1 text-gray-300">
							{{ attachment.file_size }}
						</div>
					</div>
					<div class="flex-none">
						<Badge
							v-for="group in attachment.groups"
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