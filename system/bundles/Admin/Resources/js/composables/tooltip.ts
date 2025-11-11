import { reactive, readonly, ref, nextTick, type Ref } from 'vue'
import { Tooltip } from '../types/backend/app'

const domElement: Ref<HTMLElement|null> = ref(null)
const tooltip: Tooltip = reactive({
	open: false,
	visible: false,
	text: '',
	x: 0,
	y: 0
})

const showTooltip = async (text: string, event: MouseEvent, position: 'top' | 'left' | 'bottom' | 'right' = 'top') => {

	tooltip.text = text
	tooltip.open = true

	await nextTick()

	const targetRect = (event.currentTarget as HTMLElement)?.getBoundingClientRect()
	const tooltipRect = domElement.value?.getBoundingClientRect()

	if (targetRect && tooltipRect) {
		if (position === 'bottom') {
			tooltip.y = targetRect.y + targetRect.height + 5
			tooltip.x = (targetRect.x + (targetRect.width / 2)) - (tooltipRect.width / 2)
		} else if (position === 'top') {
			tooltip.y = targetRect.y - 5 - tooltipRect.height
			tooltip.x = (targetRect.x + (targetRect.width / 2)) - (tooltipRect.width / 2)
		} else if (position === 'right') {
			tooltip.x = targetRect.x + targetRect.width + 5
			tooltip.y = targetRect.y + (targetRect.height / 2) - (tooltipRect.height / 2)
		} else if (position === 'left') {
			tooltip.x = targetRect.x - 5 - tooltipRect.width
			tooltip.y = targetRect.y + (targetRect.height / 2) - (tooltipRect.height / 2)
		}
	} else {
		console.warn('No bounding client for tooltip')
		tooltip.x = event.clientX
		tooltip.y = event.clientY
	}

	if (tooltipRect) {
		// TODO Überschneidungen mit targetRect korrigieren
		if (tooltip.y <= 0) {
			tooltip.y = 5
		} else if ((tooltip.y + tooltipRect.height + 5) >= window.innerHeight) {
			tooltip.y = window.innerHeight - tooltipRect.height - 5
		}

		if (tooltip.x <= 0) {
			tooltip.x = 5
		} else if ((tooltip.x + tooltipRect.width + 5) >= window.innerWidth) {
			tooltip.x = window.innerWidth - tooltipRect.width - 5
		}

		await nextTick()

		tooltip.visible = true
	} else {
		tooltip.visible = true
	}

	event.currentTarget?.addEventListener('mouseleave', hideTooltip)
	event.currentTarget?.addEventListener('click', hideTooltip)
}

// Versteckt den Tooltip
const hideTooltip = () => {
	tooltip.visible = false
}

export function useTooltip() {
	return {
		domElement,
		tooltip: readonly(tooltip),
		showTooltip,
		hideTooltip,
	}
}