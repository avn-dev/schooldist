<script lang="ts">
import { type PropType } from "vue3"
import { buildPrimaryColorElementCssClasses } from '@Admin/utils/primarycolor'
import type { Inquiry, Traveller } from "../../types"
import type { RouterAction } from "@Admin/types/backend/router"
import { useFileViewer } from '@Admin/composables/file_viewer'
import AlertMessage from '@Admin/components/AlertMessage.vue'
import Badge from '@Admin/components/Badge.vue'
import UserAvatar from "@Admin/components/UserAvatar.vue"
import ButtonComponent from '@Admin/components/ButtonComponent.vue'
import router from '@Admin/router'

export default {
	name: "InfoTab",
	components: { ButtonComponent, AlertMessage, Badge, UserAvatar },
	props: {
		student: { type: Object as PropType<Traveller>, required: true },
		inquiry: { type: Object as PropType<Inquiry>, required: true },
	},
	emits: ["reload"],
	// @ts-ignore TODO
	setup(props, { emit }) {
		const { openFile } = useFileViewer()

		const action = async (action: RouterAction | null) => {
			if (!action) {
				console.error('Undefined action')
				return false
			}

			const payload = await router.action(action)
			if (payload) {
				emit('reload')
			}
		}

		const buildDurationString = (duration: number, singular: string, plural: string) => {
			return `${duration} ${(duration === 1) ? singular : plural}`
		}

		return {
			openFile,
			action,
			buildDurationString,
			buildPrimaryColorElementCssClasses
		}
	}
}
</script>

<template>
	<div class="grid grid-cols-4 gap-2">
		<AlertMessage
			v-if="inquiry.cancelled"
			:message="$l10n.translate('ts.traveller.inquiry.label.cancelled').replace('%s', inquiry.cancelled)"
			class="col-span-4 p-4 text-sm"
			icon="fa fa-user-times"
			type="info"
		/>
		<AlertMessage
			v-for="(duePayment, index) in inquiry.due_payments"
			:key="index"
			:message="`${$l10n.translate('ts.traveller.inquiry.label.due_payments')}: ${duePayment.date} → ${duePayment.amount}`"
			class="col-span-4 p-4 text-sm"
			icon="fa fa-money"
			type="error"
		/>
		<div class="flex flex-col gap-2 col-span-4 md:col-span-2">
			<dl class="divide-y divide-gray-100/50">
				<div class="px-2 py-1.5 sm:grid sm:grid-cols-3 sm:gap-4 sm:items-center">
					<dt class="text-sm/6 text-gray-400">
						{{ $l10n.translate('ts.traveller.inquiry.label.created') }}
					</dt>
					<dd class="mt-1 text-sm/6 text-gray-900 sm:col-span-2 sm:mt-0">
						{{ inquiry.created }}
					</dd>
				</div>
				<div
					v-if="inquiry.inbox"
					class="px-2 py-1.5 sm:grid sm:grid-cols-3 sm:gap-4 sm:items-center"
				>
					<dt class="text-sm/6 text-gray-400">
						{{ $l10n.translate('ts.traveller.inquiry.label.inbox') }}
					</dt>
					<dd class="flex flex-wrap mt-1 text-sm/6 text-gray-900 sm:col-span-2 sm:mt-0">
						<Badge
							:title="inquiry.inbox"
							class="gap-1 text-xs px-1 py-0.5 truncate"
							color="primary"
						>
							<i class="fas fa-inbox" />
							<span>{{ inquiry.inbox }}</span>
						</Badge>
					</dd>
				</div>
				<div
					v-if="inquiry.school"
					class="px-2 py-1.5 sm:grid sm:grid-cols-3 sm:gap-4 sm:items-center"
				>
					<dt class="text-sm/6 text-gray-400">
						{{ $l10n.translate('ts.traveller.inquiry.label.school') }}
					</dt>
					<dd class="flex flex-wrap mt-1 text-sm/6 text-gray-900 sm:col-span-2 sm:mt-0">
						<Badge
							:title="inquiry.school"
							class="flex-nowrap gap-1 text-xs px-1 py-0.5 truncate"
							color="primary"
						>
							<i class="fas fa-school" />
							<span>{{ inquiry.school }}</span>
						</Badge>
					</dd>
				</div>
				<div class="px-2 py-1.5 sm:grid sm:grid-cols-3 sm:gap-4 sm:items-center">
					<dt class="text-sm/6 text-gray-400">
						{{ $l10n.translate('ts.traveller.inquiry.label.number') }}
					</dt>
					<dd class="mt-1 text-sm/6 text-gray-900 sm:col-span-2 sm:mt-0">
						<span v-if="inquiry.number">{{ inquiry.number }}</span>
						<span v-else>-</span>
					</dd>
				</div>
				<div
					v-if="inquiry.group"
					class="px-2 py-1.5 sm:grid sm:grid-cols-3 sm:gap-4 sm:items-center"
				>
					<dt class="text-sm/6 text-gray-400">
						{{ $l10n.translate('ts.traveller.inquiry.label.group') }}
					</dt>
					<dd class="flex flex-wrap mt-1 text-sm/6 text-gray-900 sm:col-span-2 sm:mt-0">
						<Badge
							:title="inquiry.group"
							class="flex-nowrap gap-1 text-xs px-1 py-0.5 truncate"
							color="primary"
						>
							<i class="fas fa-users" />
							<span>{{ inquiry.group }}</span>
						</Badge>
					</dd>
				</div>
				<div class="px-2 py-1.5 sm:grid sm:grid-cols-3 sm:gap-4 sm:items-center">
					<dt class="text-sm/6 text-gray-400">
						{{ $l10n.translate('ts.traveller.inquiry.label.timeframe') }}
					</dt>
					<dd class="mt-1 text-sm/6 text-gray-900 sm:col-span-2 sm:mt-0">
						{{ inquiry.service_from }} - {{ inquiry.service_until }}
					</dd>
				</div>
				<div
					v-if="inquiry.agency"
					class="px-2 py-1.5 sm:grid sm:grid-cols-3 sm:gap-4 sm:items-center"
				>
					<dt class="text-sm/6 text-gray-400">
						{{ $l10n.translate('ts.traveller.inquiry.label.agency') }}
					</dt>
					<dd class="flex flex-wrap mt-1 text-sm/6 text-gray-900 sm:col-span-2 sm:mt-0">
						<Badge
							:title="inquiry.agency"
							class="gap-1 text-xs px-1 py-0.5 truncate"
							color="primary"
						>
							<i class="far fa-star" />
							<span>{{ inquiry.agency }}</span>
						</Badge>
					</dd>
				</div>
				<div
					v-if="inquiry.sales_person"
					class="px-2 py-1.5 sm:grid sm:grid-cols-3 sm:gap-4 sm:items-center"
				>
					<dt class="text-sm/6 text-gray-400">
						{{ $l10n.translate('ts.traveller.inquiry.label.sales_person') }}
					</dt>
					<dd class="mt-1 sm:col-span-2 sm:mt-0">
						<UserAvatar
							:class="[
								'bg-gray-100 text-xs size-8'
							]"
							:user="inquiry.sales_person"
						/>
					</dd>
				</div>
				<div
					v-if="inquiry.state"
					class="px-2 py-1.5 sm:grid sm:grid-cols-3 sm:gap-4 sm:items-center"
				>
					<dt class="text-sm/6 text-gray-400">
						{{ $l10n.translate('ts.traveller.inquiry.label.state') }}
					</dt>
					<dd class="flex flex-wrap mt-1 text-sm/6 text-gray-900 sm:col-span-2 sm:mt-0">
						<div>
							<Badge class="text-xs px-1 py-0.5 truncate font-semibold">
								{{ inquiry.state }}
							</Badge>
						</div>
					</dd>
				</div>
			</dl>
			<div
				v-for="(invoice, index) in inquiry.invoices"
				:key="index"
				:class="[
					'group flex flex-row gap-2 items-center text-sm/6 text-gray-800 p-2 border border-gray-100/50 rounded-md',
					(invoice.file) ? 'cursor-pointer hover:bg-gray-100/50' : ''
				]"
				@click="(invoice.file) ? openFile(invoice.file.path) : false"
			>
				<span :class="['flex-none grid items-center place-content-center rounded-full text-sm size-8', buildPrimaryColorElementCssClasses()]">
					<i class="fas fa-file-invoice" />
				</span>
				<div class="grow flex flex-row gap-1">
					<span class="font-medium">{{ invoice.label }}</span>
					<span class="text-gray-500">&centerdot;</span>
					<span class="text-gray-500">{{ invoice.number }}</span>
				</div>
				<span class="flex-none text-gray-500">{{ invoice.amount }}</span>
			</div>
			<div class="flex flex-row justify-end gap-1">
				<ButtonComponent
					v-if="inquiry.open_invoices_dialog"
					color="gray"
					@click="action(inquiry.open_invoices_dialog)"
				>
					{{ $l10n.translate('ts.traveller.inquiry.btn.invoices') }}
				</ButtonComponent>
				<ButtonComponent
					v-if="inquiry.open_payments_dialog"
					color="gray"
					@click="action(inquiry.open_payments_dialog)"
				>
					{{ $l10n.translate('ts.traveller.inquiry.btn.payments') }}
				</ButtonComponent>
			</div>
		</div>
		<div class="col-span-4 md:col-span-2 flex flex-col gap-2 text-sm/6 bg-gray-50 p-4 rounded-xl">
			<div
				v-for="(service, index) in inquiry.services"
				:key="index"
				class="flex flex-row items-center gap-2 bg-white py-3 px-2 rounded-md"
			>
				<span class="bg-gray-100 dark:bg-gray-950 text-gray-500 dark:text-gray-600 grid items-center place-content-center rounded-full font-semibold text-xs size-8">
					<i :class="service.icon ? service.icon : 'fa fa-plus'" />
				</span>
				<div v-if="service.type === 'course' || service.type === 'accommodation'">
					<div class="font-medium">
						{{ service.name }}
					</div>
					<div class="text-gray-400">
						{{ service.from }} – {{ service.until }} ({{ buildDurationString(service.weeks, $l10n.translate('ts.traveller.inquiry.label.week'), $l10n.translate('ts.traveller.inquiry.label.weeks')) }})
					</div>
				</div>
				<div v-else-if="service.type === 'transfer'">
					<div class="text-gray-400">
						{{ service.transfer_type }}
					</div>
					<div class="font-medium">
						{{ service.pick_up }}
						<i class="fas fa-arrow-right" />
						{{ service.drop_off }}
					</div>
					<div class="text-gray-400">
						{{ service.date }}
						<span v-if="service.time">{{ service.time }}</span>
						<span :class="(service.booked) ? 'text-green-500' : 'text-red-500'">
							({{ $l10n.translate((service.booked) ? 'ts.traveller.inquiry.label.booked' : 'ts.traveller.inquiry.label.not_booked') }})
						</span>
					</div>
				</div>
				<div v-else-if="service.type === 'insurance'">
					<div class="font-medium">
						{{ service.name }}
					</div>
					<div class="text-gray-400">
						{{ service.from }} – {{ service.until }}
					</div>
				</div>
				<div v-else-if="service.type === 'activity'">
					<div class="font-medium">
						{{ service.name }}
					</div>
					<div class="text-gray-400">
						{{ service.from }} – {{ service.until }}
						<span v-if="service.weeks">({{ buildDurationString(service.weeks, $l10n.translate('ts.traveller.inquiry.label.week'), $l10n.translate('ts.traveller.inquiry.label.weeks')) }})</span>
						<span v-if="service.blocks">(({{ buildDurationString(service.blocks, $l10n.translate('ts.traveller.inquiry.label.block'), $l10n.translate('ts.traveller.inquiry.label.blocks')) }})</span>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>
