<script lang="ts">
import { defineComponent, inject, type PropType } from 'vue'
import { CommunicationPreviewMessage } from '../../types/communication'
import { useTooltip } from '@Admin/composables/tooltip'
import { buildPrimaryColorColorSchemeCssClass, buildPrimaryColorElementCssClasses } from "@Admin/utils/primarycolor"

export default defineComponent({
	name: "MessagePreview",
	props: {
		message: { type: Object as PropType<CommunicationPreviewMessage>, required: true },
		selected: { type: Boolean, default: false },
	},
	setup() {
		const { showTooltip } = useTooltip()

		const channelConfig = inject('channelConfig')
		const categoryColor = inject('categoryColor')

		return {
			categoryColor,
			channelConfig,
			showTooltip,
			buildPrimaryColorColorSchemeCssClass,
			buildPrimaryColorElementCssClasses
		}
	}
})
</script>

<template>
	<div class="flex flex-row items-stretch gap-x-2 rounded-md py-1 px-2 text-gray-950">
		<div
			v-if="message.unseen"
			:class="[
				'flex-none w-1 my-1 rounded-full',
				buildPrimaryColorElementCssClasses()
			]"
		/>
		<div class="grow truncate">
			<div class="flex justify-between items-center">
				<span class="font-medium text-sm truncate">
					<span
						v-if="message.draft"
						class="text-red-400"
					>
						[{{ $l10n.translate('communication.message.draft') }}]
					</span>
					{{ message.contact }}
				</span>
				<span :class="['text-nowrap', selected ? 'text-gray-300' : 'text-gray-200']">
					{{ message.date }}
				</span>
			</div>
			<div :class="['truncate', selected ? 'text-gray-700' : 'text-gray-600', {'font-semibold': message.unseen }]">
				<span v-if="message.subject">
					{{ message.subject }}
				</span>
				<i v-else>
					&#60;{{ $l10n.translate('communication.message.no_subject') }}&#62;
				</i>
			</div>
			<div class="flex flex-row gap-1 items-center">
				<div :class="['grow truncate', selected ? 'text-gray-300' : 'text-gray-200', {'font-semibold': message.unseen }]">
					<span v-if="message.content">
						{{ message.content }}
					</span>
					<i v-else>
						&#60;{{ $l10n.translate('communication.message.no_content') }}&#62;
					</i>
				</div>
				<div class="flex-none flex flex-row gap-1">
					<i
						v-for="categoryId in message.categories"
						:key="categoryId"
						class="fas fa-flag"
						:style="{'color': categoryColor(categoryId)}"
					/>
					<i
						v-if="message.status"
						:class="['text-gray-400', message.status.icon]"
						@mouseenter="showTooltip(message.status.text, $event, 'top')"
					/>
					<i
						v-if="message.event"
						class="fas fa-link text-gray-400"
						@mouseenter="showTooltip(`${$l10n.translate('communication.message.event')}: ${message.event}`, $event, 'top')"
					/>
					<i
						v-if="message.has_flags"
						class="fas fa-thumbtack text-gray-400"
					/>
					<i
						v-if="message.has_attachments"
						class="fas fa-paperclip text-gray-400"
					/>
					<i
						:class="['text-gray-400', channelConfig(message.channel).icon]"
						@mouseenter="showTooltip(channelConfig(message.channel).text, $event, 'top')"
					/>
					<i
						:class="(message.direction === 'in') ? ['fas fa-arrow-alt-circle-left', buildPrimaryColorColorSchemeCssClass('text')].join(' ') : 'fas fa-arrow-alt-circle-right text-gray-400'"
						@mouseenter="showTooltip($l10n.translate((message.direction === 'in') ? 'communication.message.direction.in' : 'communication.message.direction.out'), $event, 'top')"
					/>
				</div>
			</div>
		</div>
	</div>
</template>