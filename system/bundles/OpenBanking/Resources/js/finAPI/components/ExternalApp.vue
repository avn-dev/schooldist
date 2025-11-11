<script lang="ts">
import { PropType, onMounted, ref } from 'vue3'
import type { Ref } from 'vue3'
import axios from 'axios'

type Option = {
	value: string|number,
	text: string
}

type BankAccount = {
	id: number,
	iban: string,
	account_name: string,
	selected: boolean,
	payment_method: string|number,
	execution_times: number[],
}

type BankConnection = {
	id: number,
	execution_times: number[],
	accounts: BankAccount[]
}

export default {
	name: "ExternalApp",
	props: {
		l10n: { type: Object as PropType<Record<string, string>>, required: true }
	},
	setup(props: { l10n: Record<string, string>}) { // eslint-disable-next-line
		const bankConnections: Ref<BankConnection[]> = ref([])
		const paymentMethods: Ref<Option[]> = ref([])
		const times: Ref<Option[]> = ref([])
		const loading: Ref<boolean> = ref(false)

		const addConnection = async () => {
			window.open(
				'/open-banking/app/finAPI/webform',
				'finAPI',
				'popup=true, width=600, height=700'
			)
		}

		const load = () => {
			loading.value = true
			axios({ method: 'get', url: '/open-banking/app/finAPI' })
				.then((response: { data: { accounts: BankConnection[], payment_methods: Option[], times: Option[] } }) => {
					bankConnections.value = response.data.accounts
					paymentMethods.value = response.data.payment_methods
					times.value = response.data.times
				})
				.finally(() => loading.value = false)
		}

		const toggleAccount = (event: UIEvent, bankAccount: BankAccount) => {
			axios({ method: 'post', url: '/open-banking/app/finAPI/account', data: {
				account_id: bankAccount.id,
				selected: ((event.target as HTMLInputElement).checked) ? 1 : 0
			}})
		}

		const changePaymentMethod = (event: UIEvent, bankAccount: BankAccount) => {
			axios({ method: 'post', url: `/open-banking/app/finAPI/account/${bankAccount.id}/payment-method`, data: {
				payment_method: (event.target as HTMLInputElement).value
			}})
		}

		const addExecutionTime = (bankConnection: BankConnection) => {
			bankConnection.execution_times.unshift(0)
			saveExecutionTimes(bankConnection)
		}

		const deleteExecutionTime = (bankConnection: BankConnection, index: number) => {
			bankConnection.execution_times.splice(index, 1)
			saveExecutionTimes(bankConnection)
		}

		const changeExecutionTime = (event: UIEvent, bankConnection: BankConnection, index: number) => {
			bankConnection.execution_times[index] = parseInt((event.target as HTMLInputElement).value)
			saveExecutionTimes(bankConnection)
		}

		const saveExecutionTimes = (bankConnection: BankConnection) => {
			axios({ method: 'post', url: `/open-banking/app/finAPI/connection/${bankConnection.id}/execution-times`, data: {
				execution_times: bankConnection.execution_times
			}})
		}

		const deleteAccount = async (bankAccount: BankAccount) => {
			if (confirm(props.l10n['text.confirm_delete'])) {
				await axios({ method: 'delete', url: `/open-banking/app/finAPI/account/${bankAccount.id}`})
				load()
			}
		}

		onMounted(() => load())

		return {
			loading,
			bankConnections,
			paymentMethods,
			times,
			load,
			toggleAccount,
			changePaymentMethod,
			addExecutionTime,
			deleteExecutionTime,
			changeExecutionTime,
			addConnection,
			deleteAccount
		}
	}
}
</script>

<template>
	<div
		style="padding: 20px; text-align: center"
	>
		<button
			type="button"
			class="btn btn-primary"
			@click="addConnection"
		>
			{{ l10n['btn.new_connection'] }}
		</button>
		<p style="margin-top: 20px;">
			{{ l10n['text.description'] }}
		</p>
	</div>
	<h3 style="padding: 0 10px">
		{{ l10n['headings.accounts'] }}
		<button
			type="button"
			class="btn btn-default btn-sm"
			:title="l10n['btn.load']"
			:disabled="loading"
			@click="load"
		>
			<i class="fa fa-refresh" />
		</button>
	</h3>
	<div
		v-if="loading"
		style="padding: 20px; text-align: center"
	>
		<i class="fa fa-spinner fa-spin" />
	</div>
	<div v-else>
		<div
			v-if="bankConnections.length === 0"
			style="text-align: center; padding: 20px"
		>
			-- {{ l10n['text.no_accounts'] }} --
		</div>
		<div
			v-for="(bankConnection, bankConnectionIndex) in bankConnections"
			:key="bankConnection.id"
			style="margin: 5px; padding: 5px; background-color: #EEEEEE; border-radius: 3px;"
		>
			<strong>
				{{ l10n['headings.bank_connection'] }} {{ bankConnectionIndex + 1 }}
			</strong>
			<ul
				class="products-list product-list-in-box"
				style="margin-top: 5px;"
			>
				<li
					v-for="bankAccount in bankConnection.accounts"
					:key="bankAccount.id"
					class="item"
				>
					<div
						class="product-img"
						style="padding: 10px 20px"
					>
						<input
							:id="['account_'+bankAccount.id]"
							type="checkbox"
							:checked="bankAccount.selected"
							@change="toggleAccount($event, bankAccount)"
						>
					</div>
					<div style="display: flex; flex-direction: row; margin: 0; align-items: center; padding-right: 10px">
						<div style="flex-grow: 1;">
							<span class="product-title">
								{{ bankAccount.account_name }}
							</span>
							<span class="product-description">
								{{ bankAccount.iban }}
							</span>
						</div>
						<div class="flex: none;">
							<div style="display: flex; flex-direction: row; align-items: center; column-gap: 0.5rem;">
								<select
									class="form-control input-sm"
									style="max-width: 160px; min-width: 160px;"
									@change="changePaymentMethod($event, bankAccount)"
								>
									<option
										value="0"
										:selected="bankAccount.payment_method == 0"
									>
										-- {{ l10n['text.payment_method'] }} --
									</option>
									<option
										v-for="paymentMethod in paymentMethods"
										:key="paymentMethod.value"
										:value="paymentMethod.value"
										:selected="bankAccount.payment_method == paymentMethod.value"
									>
										{{ paymentMethod.text }}
									</option>
								</select>
								<button
									type="button"
									class="btn btn-danger btn-sm"
									@click="deleteAccount(bankAccount)"
								>
									<i class="fa fa-trash" />
								</button>
							</div>
						</div>
					</div>
				</li>
			</ul>
			<div style="margin-top: 5px; display: flex; flex-direction: row-reverse; align-items: center; column-gap: 0.5rem;">
				<div
					v-for="(bankAccountTime, index) in bankConnection.execution_times"
					:key="index"
					style="display: flex; flex-direction: row; align-items: center; column-gap: 0.25rem; background-color: #CDCDCD; border-radius: 3px; padding: 2px"
				>
					<select
						class="form-control input-sm"
						@change="changeExecutionTime($event, bankConnection, index)"
					>
						<option
							v-for="time in times"
							:key="time.value"
							:value="time.value"
							:selected="bankAccountTime === time.value"
						>
							{{ time.text }}
						</option>
					</select>
					<i
						class="fa fa-times"
						style="cursor: pointer; font-size: 12px; padding: 2px;"
						@click="deleteExecutionTime(bankConnection, index)"
					/>
				</div>
				<i
					v-show="bankConnection.execution_times.length <= 6"
					class="fa fa-plus-circle"
					style="cursor: pointer"
					:title="l10n['text.execution_time']"
					@click="addExecutionTime(bankConnection)"
				/>
				<span
					v-show="bankConnection.execution_times.length === 0"
					style="color: #7F7F7F"
				>
					{{ l10n['text.execution_time_hourly'] }}
				</span>
				<span
					v-show="bankConnection.execution_times.length > 0"
					style="color: #7F7F7F"
				>
					{{ l10n['text.execution_time_manual'] }}:
				</span>
			</div>
		</div>
	</div>
</template>