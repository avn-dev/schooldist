<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import { AlertType } from '../types/interface'
import l10n from '../l10n'

type IconTypes = Record<'success' | 'error' | 'warning' | 'info', string>
type MessageTypes = Record<'success' | 'error' | 'warning' | 'info', { bg: string, text: string, border: string }>

const ICONS: IconTypes = {
	success: 'fa fa-check-circle',
	error: 'fa fa-times-circle',
	warning: 'fa fa-exclamation-triangle',
	info: 'fa fa-info-circle',
}

const COLORS: MessageTypes = {
	success: { bg: 'bg-green-100 dark:bg-green-400/10', text: 'text-green-800 dark:text-green-400', border: 'border-green-200 dark:border-green-400' },
	error: { bg: 'bg-red-100 dark:bg-green-500/10', text: 'text-red-800 dark:text-red-400', border: 'border-red-300 dark:border-green-400' },
	warning: { bg: 'bg-yellow-100 dark:bg-yellow-400/10', text: 'text-yellow-800 dark:text-yellow-500', border: 'border-yellow-400 dark:border-green-500' },
	info: { bg: 'bg-blue-100 dark:bg-blue-400/10', text: 'text-blue-800 dark:text-blue-500', border: 'border-blue-400 dark:border-blue-500' }
}

export default defineComponent({
	name: "AlertMessage",
	props: {
		type: { type: String as PropType<AlertType>, default: AlertType.error },
		heading: { type: String, default: null },
		message: { type: String, required: true },
		icon: { type: String, default: null },
		confirm: { type: Boolean, default: false },
		confirmMessage: { type: String, default: () => l10n.translate('interface.global.confirm') },
		confirmKey: { type: String, default: 'alert_confirm' },
	},
	emits: ['confirm'],
	setup(props, { emit }) {
		const confirmAlert = (event: MouseEvent): void => {
			const isChecked = (event.target as HTMLInputElement).checked
			emit('confirm', props.confirmKey, props.type === AlertType.warning && isChecked)
		}

		const css = (types: Array<'bg' | 'text' | 'border'>) => {
			return types
				.map((type) => COLORS[props.type][type] ?? '')
				.join(' ')
		}

		return {
			ICONS,
			confirmAlert,
			css
		}
	}
})
</script>

<template>
	<div :class="['rounded-md', css(['bg', 'text', 'border'])]">
		<div class="flex flex-row items-center">
			<div class="flex-shrink-0">
				<i
					:class="[
						icon ?? ICONS[type],
						{
							'text-green-400': type === 'success',
							'text-red-400': type === 'error',
							'text-yellow-400': type === 'warning',
							'text-blue-400': type === 'info',
						}
					]"
				/>
			</div>
			<div :class="['grow ml-3', css(['text'])]">
				<h3
					v-if="heading"
					class="font-medium"
				>
					{{ heading }}
				</h3>
				<!-- eslint-disable vue/no-v-html -->
				<div v-html="message" />
				<div
					v-if="type === 'warning' && confirm"
					class="mt-2"
				>
					<label class="flex flex-row items-center gap-2 font-semibold">
						<input
							type="checkbox"
							@change="confirmAlert"
						>
						{{ confirmMessage }}
					</label>
				</div>
			</div>
		</div>
	</div>
</template>
