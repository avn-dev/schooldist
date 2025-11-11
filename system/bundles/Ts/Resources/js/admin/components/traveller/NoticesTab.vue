<script lang="ts">
import { PropType } from "vue3"
import type { Traveller, Inquiry } from "../../types"
// @ts-ignore
import UserAvatar from "@Admin/components/UserAvatar.vue"

export default {
	name: "NoticesTab",
	components: {UserAvatar},
	props: {
		student: { type: Object as PropType<Traveller>, required: true },
		inquiry: { type: Object as PropType<Inquiry>, required: true },
	},
}
</script>

<template>
	<div
		v-if="!inquiry.notices || inquiry.notices.length === 0"
		class="text-sm/6 flex-auto rounded-md mt-2 py-6 bg-gray-50 text-gray-500 text-center"
	>
		{{ $l10n.translate('ts.traveller.inquiry.label.no_notices') }}
	</div>
	<ul
		v-else
		role="list"
		class="space-y-2"
	>
		<li
			v-for="(notice, index) in inquiry.notices"
			:key="notice.id"
			class="relative flex gap-x-4"
		>
			<div :class="[index === inquiry.notices.length - 1 ? 'h-6' : '-bottom-6', 'absolute left-0 top-0 flex w-8 justify-center']">
				<div class="w-px bg-gray-100" />
			</div>
			<UserAvatar
				:user="notice.author"
				:class="[
					'text-xs bg-gray-50 relative mt-3 size-8'
				]"
			/>
			<div class="flex-auto rounded-md p-3 bg-gray-50">
				<div class="flex justify-between gap-x-4">
					<div class="py-0.5 text-xs/5 text-gray-500">
						<span class="font-medium text-gray-900">{{ notice.author.name }}</span>
					</div>
					<time class="flex-none py-0.5 text-xs/5 text-gray-500">{{ notice.created }}</time>
				</div>
				<!-- eslint-disable vue/no-v-html -->
				<p
					class="text-sm/6 text-gray-500"
					v-html="notice.text "
				/>
			</div>
		</li>
	</ul>
</template>
