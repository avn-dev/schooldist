<script lang="ts">
import { defineComponent } from 'vue'
import {
	Combobox as HeadlessUiCombobox,
	ComboboxOptions as HeadlessUiComboboxOptions,
	ComboboxOption as HeadlessUiComboboxOption,
	Dialog as HeadlessUiDialog,
	DialogPanel as HeadlessUiDialogPanel,
	TransitionRoot as HeadlessUiTransitionRoot,
	TransitionChild as HeadlessUiTransitionChild
} from '@headlessui/vue'
import { useSearch, SearchNode } from "../../composables/search"
import { buildPrimaryColorElementCssClasses, highlightText } from "../../utils/primarycolor"
import router from "../../router"
import LoadingContent from './content/LoadingContent.vue'
import ButtonComponent from '../../components/ButtonComponent.vue'
import RoundedBox from '../../components/RoundedBox.vue'

export default defineComponent({
	name: "SearchBar",
	components: {
		RoundedBox,
		ButtonComponent,
		LoadingContent,
		HeadlessUiCombobox,
		HeadlessUiComboboxOptions,
		HeadlessUiComboboxOption,
		HeadlessUiDialog,
		HeadlessUiDialogPanel,
		HeadlessUiTransitionRoot,
		HeadlessUiTransitionChild
	},
	setup() {
		const { menu, open, loading, query, searching, result, closeSearch, resetSearch, search, storeRecentResult, changeInstance } = useSearch()

		const action = async (node: SearchNode) => {
			storeRecentResult(node).then()
			await closeSearch()
			await router.action(node.action)
		}

		const outputHighlightedResult = (text: string[], matches: [], separator: string) => {
			if (query.value.length > 0) {
				text = text.map((value: string) => highlightText(value, matches))
			}
			return text.join(separator)
		}

		return {
			open,
			loading,
			query,
			searching,
			menu,
			result,
			action,
			closeSearch,
			resetSearch,
			search,
			outputHighlightedResult,
			buildPrimaryColorElementCssClasses,
			changeInstance
		}
	}
})
</script>

<template>
	<HeadlessUiTransitionRoot
		:show="open"
		as="template"
		appear
		@after-leave="resetSearch"
	>
		<HeadlessUiDialog
			as="div"
			class="relative z-10"
			@close="closeSearch"
		>
			<HeadlessUiTransitionChild
				as="template"
				enter="ease-out duration-300"
				enter-from="opacity-0"
				enter-to="opacity-100"
				leave="ease-in duration-200"
				leave-from="opacity-100"
				leave-to="opacity-0"
			>
				<div
					class="fixed inset-0 bg-gray-900 opacity-80 transition-opacity dark:bg-gray-950 dark:opacity-90"
				/>
			</HeadlessUiTransitionChild>
			<div class="fixed inset-0 z-10 w-screen overflow-y-auto p-4 sm:p-6 md:p-20">
				<HeadlessUiTransitionChild
					as="template"
					enter="ease-out duration-300"
					enter-from="opacity-0 scale-95"
					enter-to="opacity-100 scale-100"
					leave="ease-in duration-200"
					leave-from="opacity-100 scale-100"
					leave-to="opacity-0 scale-95"
				>
					<LoadingContent v-if="loading" />
					<HeadlessUiDialogPanel
						v-else
						class="mx-auto max-w-2xl transform transition-all"
					>
						<RoundedBox>
							<div class="divide-y divide-gray-100 rounded-md bg-white dark:bg-gray-800 dark:divide-gray-700 overflow-hidden">
								<!-- eslint-disable vue/v-on-event-hyphenation -->
								<HeadlessUiCombobox
									@update:modelValue="action"
								>
									<div class="relative">
										<i
											:class="[
												'pointer-events-none absolute left-4 top-3.5 text-gray-400',
												(searching) ? 'fa fa-spinner fa-spin' : 'fa fa-search'
											]"
											aria-hidden="true"
										/>
										<input
											class="h-12 w-full border-0 bg-transparent pl-11 pr-4 text-gray-900 dark:text-gray-200 placeholder:text-gray-400 focus:ring-0 sm:text-sm ring-primary-400 focus:outline-none"
											:placeholder="`${$l10n.translate('search.placeholder')}…`"
											:value="query"
											@keyup="search($event.target.value)"
											@disabled="searching"
										>
										<i
											v-if="query.length > 0"
											class="cursor-pointer absolute right-4 top-3.5 text-gray-400 fa fa-times-circle"
											aria-hidden="true"
											@click="resetSearch"
										/>
										<div
											v-if="menu.instances.length > 1"
											class="flex flex-row items-center px-4 pb-1 gap-x-1"
										>
											<ButtonComponent
												v-for="(instance, index) in menu.instances"
												:key="index"
												:color="(instance.key === menu.selected_instance) ? 'primary' : 'default'"
												class="font-medium"
												size="small"
												@click="changeInstance(instance.key)"
											>
												{{ instance.text }}
											</ButtonComponent>
										</div>
									</div>
									<HeadlessUiComboboxOptions
										v-if="query === '' || (result && result.hits > 0)"
										static
										class="max-h-80 scroll-py-2 divide-y divide-gray-100 dark:divide-gray-700 overflow-y-auto"
									>
										<li class="p-2">
											<h2
												v-if="query === ''"
												class="mb-2 mt-4 px-3 text-xs font-semibold text-gray-500"
											>
												{{ $l10n.translate('search.recent') }}
											</h2>
											<div
												v-if="query === '' && menu.recent.length === 0"
												class="mb-2 mt-4 px-3 text-xs text-gray-500 text-center"
											>
												{{ $l10n.translate('search.recent.empty') }}
											</div>
											<ul class="text-sm text-gray-700 dark:text-gray-200">
												<HeadlessUiComboboxOption
													v-for="(node, index) in (result) ? result.rows : []"
													v-show="query !== ''"
													:key="index"
													v-slot="{ active }: { active: boolean }"
													:value="node.action"
													as="template"
												>
													<li
														:class="[
															'flex cursor-default select-none items-center rounded-md px-3 py-2',
															(active) ? buildPrimaryColorElementCssClasses() : ''
														]"
													>
														<i
															:class="[
																'flex-none',
																(node.action.icon) ? node.action.icon : 'fa fa-folder-open',
																{'text-gray-200': !active}
															]"
															aria-hidden="true"
														/>
														<!-- eslint-disable vue/no-v-html -->
														<span
															class="ml-3 flex-auto truncate"
															v-html="outputHighlightedResult(node.action.text, node.matches, ' / ')"
														/>
														<i
															v-if="active"
															class="fa fa-chevron-right ml-3 flex-none"
														/>
													</li>
												</HeadlessUiComboboxOption>
												<HeadlessUiComboboxOption
													v-for="(node, index) in menu.recent"
													v-show="query === ''"
													:key="index"
													v-slot="{ active }: { active: boolean }"
													:value="node"
													as="template"
												>
													<li
														:class="[
															'flex cursor-default select-none items-center rounded-md px-3 py-2',
															(active) ? buildPrimaryColorElementCssClasses() : ''
														]"
													>
														<i
															:class="[
																'flex-none',
																(node.icon) ? node.icon : 'fa fa-folder-open',
																{'text-gray-200': !active}
															]"
															aria-hidden="true"
														/>
														<!-- eslint-disable vue/no-v-html -->
														<span
															class="ml-3 flex-auto truncate"
															v-html="node.text.join(' / ')"
														/>
														<i
															v-if="active"
															class="fa fa-chevron-right ml-3 flex-none"
														/>
													</li>
												</HeadlessUiComboboxOption>
											</ul>
										</li>
										<li
											v-if="query === ''"
											class="p-2"
										>
											<ul class="text-sm text-gray-700 dark:text-gray-200">
												<HeadlessUiComboboxOption
													v-for="(quickAction, index) in menu.quick_actions"
													:key="index"
													v-slot="{ active }: { active: boolean }"
													:value="quickAction"
													as="template"
												>
													<li
														:class="[
															'flex cursor-default select-none items-center rounded-md px-3 py-2',
															(active) ? buildPrimaryColorElementCssClasses() : ''
														]"
													>
														<i
															:class="[
																'flex-none',
																(quickAction.icon) ? quickAction.icon : 'fa fa-plus',
																(active) ? '' : 'text-gray-200'
															]"
															aria-hidden="true"
														/>
														<span class="ml-3 flex-auto truncate">
															{{ quickAction.text.join(' / ') }}
														</span>
													</li>
												</HeadlessUiComboboxOption>
											</ul>
										</li>
									</HeadlessUiComboboxOptions>
									<div
										v-if="result && result.hits === 0"
										class="px-6 py-14 text-center sm:px-14"
									>
										<i
											class="fa fa-search mx-auto h-6 w-6 text-gray-400"
											aria-hidden="true"
										/>
										<p class="mt-4 text-sm text-gray-900 dark:text-gray-200">
											{{ $l10n.translate('search.result.empty') }}
										</p>
									</div>
								</HeadlessUiCombobox>
							</div>
						</RoundedBox>
					</HeadlessUiDialogPanel>
				</HeadlessUiTransitionChild>
			</div>
		</HeadlessUiDialog>
	</HeadlessUiTransitionRoot>
</template>
