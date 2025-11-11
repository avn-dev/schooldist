<script lang="ts">
import { defineComponent, type PropType, ref, type Ref, toRefs } from 'vue'
import { ComponentApiInterface, type RouterAction, RouterActionStorePayload } from "@Admin/types/backend/router"
import RoundedBox from '@Admin/components/RoundedBox.vue'
import UserAvatar from '@Admin/components/UserAvatar.vue'
import ButtonComponent from '@Admin/components/ButtonComponent.vue'
import Badge from '@Admin/components/Badge.vue'
import AlertMessage from '@Admin/components/AlertMessage.vue'
import InfoTab from './traveller/InfoTab.vue'
import AllocationsTab from './traveller/AllocationsTab.vue'
import NoticesTab from './traveller/NoticesTab.vue'
import { buildPrimaryColorElementCssClasses } from '@Admin/utils/primarycolor'
import { type Inquiry, Invoice, Service, type Traveller } from '../types'
import { safe } from '@Admin/utils/promise'
import router from '@Admin/router'
import { useUser } from '@Admin/composables/user'
import { useTooltip } from '@Admin/composables/tooltip'

type LocalTraveller = Traveller & {
	edit_student_photo: RouterAction
}

type LocalInquiry = Inquiry & {
	services: Service[],
	invoices?: Invoice[],
	notices?: [],
	tabs: [],
	open_dialog?: RouterAction,
	open_communication?: RouterAction,
	open_invoices_dialog?: RouterAction,
	open_payments_dialog?: RouterAction
}

export default defineComponent({
	name: "Inquiry",
	components: {
		ButtonComponent,
		RoundedBox,
		UserAvatar,
		Badge,
		AlertMessage,
		InfoTab,
		AllocationsTab,
		NoticesTab
	},
	props: {
		api: { type: Object as PropType<ComponentApiInterface>, required: true },
		payloadStorable: { type: Object as PropType<RouterActionStorePayload|null>, default: null },
		student: { type: Object as PropType<LocalTraveller>, required: true },
		inquiries: {
			type: Array as PropType<LocalInquiry[]>, required: true
		},
		inquiryId: { type: Number, required: true },
	},
	emits: ['close'],
	setup(props) {
		const loading: Ref<boolean> = ref(false)
		const { student, inquiries, inquiryId } = toRefs(props)
		const { toggleBookmark, hasBookmark } = useUser()
		const { showTooltip } = useTooltip()

		const studentPayload = ref(student.value)
		const inquiriesPayload = ref(inquiries.value)
		const currentInquiryId = ref(inquiryId.value)
		const currentInquiryTab: Ref<number> = ref(0)

		const reload = async () => {
			loading.value = true
			/* eslint-disable @typescript-eslint/no-unused-vars */
			const [error, response] = await safe<{ student: LocalTraveller, inquiries: LocalInquiry[] }>(props.api.action('reload'))
			loading.value = false

			if (response) {
				studentPayload.value = response.student
				inquiriesPayload.value = response.inquiries
			}
		}

		const action = async (action: RouterAction | null) => {
			if (!action) {
				console.error('Undefined action')
				return false
			}

			const payload = await router.action(action)
			if (payload) {
				await reload()
			}
		}

		const switchInquiry = (inquiry: Inquiry) => {
			currentInquiryId.value = inquiry.id
			currentInquiryTab.value = 0
		}

		return {
			loading,
			action,
			reload,
			studentPayload,
			inquiriesPayload,
			currentInquiryId,
			currentInquiryTab,
			switchInquiry,
			showTooltip,
			hasBookmark,
			toggleBookmark,
			buildPrimaryColorElementCssClasses
		}
	}
})
</script>

<template>
	<div
		v-if="studentPayload"
		class="h-full flex flex-col gap-y-2"
	>
		<RoundedBox class="flex-1">
			<div class="h-full flex flex-col">
				<div class="flex-none">
					<div class="flex w-full items-center gap-x-1 leading-6 p-4">
						<div class="flex-none">
							<UserAvatar
								:class="[
									'group relative text-xl size-16',
									buildPrimaryColorElementCssClasses(),
									{ 'cursor-pointer': studentPayload.edit_student_photo }
								]"
								:user="studentPayload"
								@click="action(studentPayload.edit_student_photo)"
							>
								<div class="hidden group-hover:block absolute top-0 left-0 grid items-center place-content-center text-center rounded-full w-full h-full bg-gray-900/30">
									<i class="fas fa-camera text-white text-sm" />
								</div>
							</UserAvatar>
						</div>
						<div class="flex-col gap-y-0.5 grow text-left">
							<div class="text-gray-800 dark:text-gray-50 font-semibold text-base">
								{{ studentPayload.name }}
							</div>
							<div class="flex flex-row items-center gap-x-0.5 text-gray-600 text-xs dark:text-gray-300">
								<span>{{ studentPayload.birthday }} ({{ studentPayload.age }})</span>
								<span
									v-if="studentPayload.nationality"
									class="font-bold mx-1"
								>&centerdot;</span>
								<span>{{ studentPayload.nationality }}</span>
								<span class="font-bold mx-1">&centerdot;</span>
								<span
									v-if="studentPayload.email"
									class="truncate"
								>
									{{ studentPayload.email }}
								</span>
								<i v-else>{{ $l10n.translate('ts.traveller.no_email') }}</i>
							</div>
						</div>
						<Badge
							v-if="payloadStorable"
							class="flex-none text-sm h-7 px-1.5 cursor-pointer"
							:color="(hasBookmark(payloadStorable)) ? 'primary' : 'default'"
							:filled="hasBookmark(payloadStorable)"
							@click="toggleBookmark(payloadStorable)"
							@mouseenter="showTooltip($l10n.translate(hasBookmark(payloadStorable) ? 'interface.bookmark.remove' : 'interface.bookmark.add'), $event, 'top')"
						>
							<i :class="[hasBookmark(payloadStorable) ? 'fa' : 'far ', 'fa-star']" />
						</Badge>
						<Badge
							class="flex-none text-sm h-7 px-1"
							color="primary"
						>
							{{ studentPayload.customer_number }}
						</Badge>
					</div>
				</div>
				<div class="grow max-h-full">
					<div class="h-full flex flex-col p-2">
						<div class="flex-none">
							<nav class="flex flex-row gap-x-2">
								<button
									v-for="inquiry in inquiriesPayload"
									:key="inquiry.id"
									:class="[
										'rounded-md px-3 py-1 text-sm font-medium',
										(inquiry.id === currentInquiryId) ?
											buildPrimaryColorElementCssClasses() :
											'text-gray-500 hover:bg-gray-50 hover:text-gray-700 dark:hover:bg-gray-700 hover:text-gray-500',
									]"
									@click="switchInquiry(inquiry)"
								>
									<span v-if="inquiry.number">{{ $l10n.translate('ts.traveller.label.booking') }}: {{ inquiry.number }}</span>
									<span v-else>{{ $l10n.translate('ts.traveller.label.booking') }}</span>
								</button>
							</nav>
						</div>
						<div class="grow h-full">
							<div
								v-for="inquiry in inquiriesPayload"
								:key="inquiry.id"
								class="h-full overflow-hidden"
							>
								<div
									v-show="inquiry.id === currentInquiryId"
									class="flex flex-col h-full"
								>
									<div class="flex-none">
										<div class="flex flex-row items-center pt-2 border-b border-gray-100/50">
											<nav class="grow -mb-px flex space-x-2">
												<a
													v-for="(tab, index) in inquiry.tabs"
													:key="index"
													:class="[
														(currentInquiryTab === index) ? 'border-gray-400 text-gray-800' : 'border-transparent text-gray-400 hover:text-gray-800',
														'group inline-flex items-center border-b-2 px-2 py-2 text-sm cursor-pointer font-medium'
													]"
													@click="currentInquiryTab = index"
												>
													<span>{{ tab.text }}</span>
												</a>
											</nav>
											<div class="flex-none">
												<div class="flex gap-x-1 pb-0.5">
													<ButtonComponent
														v-if="inquiry.open_dialog"
														color="gray"
														@click="action(inquiry.open_dialog)"
													>
														<i class="far fa-edit md:hidden" />
														<span class="hidden md:block">{{ $l10n.translate('ts.traveller.inquiry.btn.edit') }}</span>
													</ButtonComponent>
													<ButtonComponent
														v-if="inquiry.open_communication"
														class="hidden md:block"
														@click="action(inquiry.open_communication)"
														@mouseenter="showTooltip($l10n.translate('ts.traveller.label.communication'), $event, 'top')"
													>
														<i class="fa fa-envelope" />
													</ButtonComponent>
													<ButtonComponent
														@click="reload"
														@mouseenter="showTooltip($l10n.translate('ts.traveller.label.reload'), $event, 'top')"
													>
														<i
															v-if="loading"
															class="fa fa-spinner fa-spin"
														/>
														<i
															v-else
															class="fas fa-sync-alt"
														/>
													</ButtonComponent>
												</div>
											</div>
										</div>
									</div>
									<!-- h-64 damit das overflow richtig funktioniert -->
									<div class="h-64 grow max-h-full overflow-auto">
										<div
											v-for="(tab, index) in inquiry.tabs"
											:key="index"
										>
											<component
												:is="tab.component"
												v-if="currentInquiryTab === index"
												:inquiry="inquiry"
												:student="student"
												class="py-2"
												@reload="reload"
											/>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</RoundedBox>
	</div>
</template>
