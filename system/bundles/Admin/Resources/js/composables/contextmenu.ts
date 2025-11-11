import { readonly, reactive, nextTick, ref, type Ref } from 'vue3'
import { ContextMenu } from '../types/backend/app'
import { ComponentContentPayload } from '../types/backend/router'
import { useInterface } from './interface'

const domElement: Ref<HTMLElement|null> = ref(null)
const contextMenu: ContextMenu = reactive({
	open: false,
	visible: false,
	component: null,
	level: 0,
	x: 0,
	y: 0,
})

const handleOutsideClick = <E extends MouseEvent | PointerEvent | FocusEvent>(event: E) => {

	if (!contextMenu.open) {
		return
	}

	const contextMenuElement = (event.target instanceof HTMLElement)
		? event.target.closest('.context-menu')
		: null

	if (!contextMenuElement) {
		closeContextMenu()
	}
}

const openContextMenu = async <T>(event: MouseEvent, content: ComponentContentPayload, x?: number, y?: number) => {

	const { increaseZIndex } = useInterface()

	if (typeof content.component !== 'function') {
		// TODO Prevent vue warning for performance issue [Vue received a Component which was made a reactive object]
		const component = content.component
		content.component = () => component
	}

	contextMenu.component = content
	contextMenu.x = (x) ? x : event.clientX
	contextMenu.y = (y) ? y : event.clientY
	contextMenu.level = increaseZIndex()
	contextMenu.open = true

	await nextTick()

	if (domElement.value) {
		const contextMenuRect = domElement.value.getBoundingClientRect()

		// TODO check all sides
		if ((contextMenuRect.top + contextMenuRect.height) >= window.innerHeight) {
			contextMenu.y = contextMenu.y - contextMenuRect.height - 10
		}
		if ((contextMenuRect.left + contextMenuRect.width) >= window.innerWidth) {
			contextMenu.x = window.innerWidth - contextMenuRect.width - 10
		}

		await nextTick()

		contextMenu.visible = true
	} else {
		contextMenu.visible = true
	}

	const eventsWindow: HTMLElement|null = document.querySelector('.events-window')

	window.addEventListener('click', handleOutsideClick)
	window.addEventListener('blur', handleOutsideClick) // Iframes

	if (eventsWindow) {
		eventsWindow.addEventListener('click', handleOutsideClick)
		eventsWindow.addEventListener('blur', handleOutsideClick) // Iframes
	}

	event.stopPropagation()

	return new Promise<T>(resolve => contextMenu.promise = resolve)
}

/* eslint-disable @typescript-eslint/no-explicit-any */
const closeContextMenu = (data?: any) => {

	const { removeZIndex } = useInterface()

	if (contextMenu.promise) {
		contextMenu.promise(data)
	}

	contextMenu.open = false
	removeZIndex(contextMenu.level)

	delete contextMenu.promise

	const eventsWindow: HTMLElement|null = document.querySelector('.events-window')

	window.removeEventListener('click', handleOutsideClick)
	window.removeEventListener('blur', handleOutsideClick) // Iframes

	if (eventsWindow) {
		eventsWindow.removeEventListener('click', handleOutsideClick)
		eventsWindow.removeEventListener('blur', handleOutsideClick) // Iframes
	}
}

export function useContextMenu() {
	return {
		domElement,
		contextMenu: readonly(contextMenu),
		openContextMenu,
		closeContextMenu
	}
}