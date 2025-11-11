<script lang="ts">
import { defineComponent, PropType } from 'vue3'
import {
	Combobox as HeadlessUiCombobox,
	ComboboxOptions as HeadlessComboboxOptions,
	ComboboxOption as HeadlessUiComboboxOptions,
} from '@headlessui/vue'
import { buildPrimaryColorElementCssClasses} from "../../utils/primarycolor"
import { useUser } from '../../composables/user'
import { Bookmark } from "../../types/backend/app"
import { ComponentApiInterface } from "../../types/backend/router"

export default defineComponent({
	name: "Bookmarks",
	components: { HeadlessUiCombobox, HeadlessComboboxOptions, HeadlessUiComboboxOptions },
	props: {
		api: { type: Object as PropType<ComponentApiInterface>, required: true },
		nodes: { type: Array, required: true }
	},
	emits: ['close'],
	setup(props, { emit }) {
		const { bookmarks, deleteBookmark, openBookmark } = useUser()

		const nodeAction = async (node: Bookmark) => {
			emit('close')
			await openBookmark(node)
		}

		return {
			bookmarks,
			nodeAction,
			deleteBookmark,
			buildPrimaryColorElementCssClasses
		}
	}
})
</script>

<template>
	<!-- eslint-disable vue/v-on-event-hyphenation -->
	<HeadlessUiCombobox
		@update:modelValue="nodeAction"
	>
		<div>
			<HeadlessComboboxOptions
				v-if="nodes.length > 0"
				static
				class="max-h-80 scroll-py-2 divide-y divide-gray-100 dark:divide-gray-700 overflow-y-auto"
			>
				<li class="p-2">
					<ul class="grid grid-cols-2 gap-2 sm:grid-cols-2 sm:gap-2 sm:grid-cols-4 lg:grid-cols-4 px-3">
						<HeadlessUiComboboxOptions
							v-for="(node, index) in nodes"
							:key="index"
							v-slot="{ active }"
							:value="node"
							as="template"
						>
							<li
								:class="[
									'col-span-1 flex flex-col p-2 ring-inset rounded-md text-center cursor-pointer',
									(active)
										? [buildPrimaryColorElementCssClasses(), 'ring-0'].join(' ')
										: 'ring-1 ring-gray-100 text-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:ring-gray-800 dark:hover:bg-gray-600'
								]"
							>
								<i
									:class="[
										'flex-none p-2 text-2xl',
										(node.icon) ? node.icon : 'fa fa-bookmark',
										active ? '' : 'text-gray-400'
									]"
									aria-hidden="true"
								/>
								<span class="font-medium text-sm flex-auto truncate">
									{{ node.text.join(' / ') }}
								</span>
							</li>
						</HeadlessUiComboboxOptions>
					</ul>
				</li>
			</HeadlessComboboxOptions>
			<HeadlessComboboxOptions
				static
				class="max-h-[30rem] min-h-60 mb-2 scroll-py-4 divide-y divide-gray-100 dark:divide-gray-700 overflow-y-auto "
			>
				<li class="p-2">
					<h2 class="mb-2 px-1.5 text-xs font-semibold text-gray-500 dark:text-gray-200">
						{{ $l10n.translate('bookmarks.my') }}
					</h2>
					<div
						v-if="bookmarks.length === 0"
						class="text-center text-xs p-4 text-gray-500 dark:text-gray-200"
					>
						{{ $l10n.translate('bookmarks.my.empty') }}
					</div>
					<ul
						v-else
						class="text-sm text-gray-700 dark:text-gray-200"
					>
						<li
							v-for="(node, index) in bookmarks"
							:key="index"
							class="flex py-0.5 gap-x-1"
						>
							<HeadlessUiComboboxOptions
								v-slot="{ active }"
								:value="node"
								as="div"
								class="grow"
							>
								<div
									:class="[
										'flex select-none items-center rounded-md px-3 py-1',
										(active)
											? buildPrimaryColorElementCssClasses()
											: 'bg-gray-50 dark:bg-gray-900 dark:text-gray-200 dark:ring-gray-800 dark:hover:bg-gray-600'
									]"
								>
									<i
										:class="[
											'flex-none w-5',
											(node.icon) ? node.icon : 'fa fa-bookmark',
											active ? '' : 'text-gray-400'
										]"
										aria-hidden="true"
									/>
									<span class="ml-3 flex-auto truncate">
										{{ node.text.join(' / ') }}
									</span>
									<i
										v-if="active"
										:class="[
											'fa fa-chevron-right ml-3 flex-none'
										]"
									/>
								</div>
							</HeadlessUiComboboxOptions>
							<button
								type="button"
								:class="[
									'p-1 rounded-md text-gray-400 bg-gray-50 flex items-center dark:bg-gray-900 dark:text-gray-400 dark:ring-gray-800 dark:hover:bg-gray-600',
									buildPrimaryColorElementCssClasses('hover:')
								]"
								@click="deleteBookmark(node)"
							>
								<i
									class="fa fa-trash w-5"
									aria-hidden="true"
								/>
							</button>
						</li>
					</ul>
				</li>
			</HeadlessComboboxOptions>
		</div>
	</HeadlessUiCombobox>
</template>
