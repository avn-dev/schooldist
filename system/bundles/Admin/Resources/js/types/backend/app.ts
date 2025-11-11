import { App as Application, VNode } from 'vue3'
import {
	ComponentContentPayload,
	Content,
	ContentLoadingState,
	RouterAction, RouterActionAdditionalPayload,
	RouterActionStorePayload
} from './router'
import { ColorSchemeSetting } from '../interface'

export type Process = (vue: Application) => void

export class Registrar {

	private processes: Process[] = []

	booting(binding: Process): void {
		this.processes.push(binding)
	}

	boot(app: Application): void {
		if (this.processes.length === 0) console.error('Component registrar is empty.')
		this.processes.forEach((closure: Process) => closure(app))
	}
}

export type Logo = { framework: string, framework_small: string, system: string, support: string }

export type Tenant = { key: string, label: string, logo: string | null, show_label: boolean, color: string, text: string | null, selected: boolean }

export type InterfaceBackend = {
	language: string,
	debug: boolean,
	color_scheme: ColorSchemeSetting,
	title: string,
	logo: Logo,
	tenants: Tenant[] | null,
	user: User,
	navigation: Navigation,
	support: { helpdesk?: string, support_chat?: string }
	ping_interval: number,
	server: string | null,
	version: string | null,
	_actions?: RouterAction[]
	_l10n?: { [key: string]: string }
}

export enum ComponentSize { small = 'small', medium = 'medium', large = 'large', extra_large = 'extra_large' }

export enum ChatState { online = 'online', offline = 'offline', away = 'away' }

export type SystemButton = {
	key: string,
	icon: string,
	text: string,
	active: boolean,
	/* eslint-disable @typescript-eslint/no-explicit-any */
	options?: Array<{ value: any, text: string }>
}

export type User = {
	name: string,
	email: string,
	initials: string,
	avatar?: string
	icon?: string
}

export type UserBoardTab = {
	key: string,
	icon: string,
	text: string,
	component: string
	active: boolean
}

export enum UserNotificationView { list = 'list', group = 'group' }

export type UserNotificationButton = { key: string, title: string }
export type UserNotificationAttachment = { file: string, name: string, icon: string }

type BaseNotification = {
	id: number,
	type: string,
	date: number,
	date_formatted: string,
	group: string,
	message: string,
	subject?: string,
	read: boolean,
	sort_key: number,
	buttons?: UserNotificationButton[],
	icon?: string,
	sender?: User,
	alert?: 'info' | 'success' | 'warning' | 'danger'
}

export type UserNotification = BaseNotification & {
	/* eslint-disable @typescript-eslint/no-explicit-any */
	data: Record<any, any>
}

export type UserNotificationAnnouncement = BaseNotification & {
	data: {
		image?: string,
		image_height?: number,
		image_width?: number
	}
}

export type UserNotificationToast = BaseNotification & {
	data: {
		type: 'danger' | 'warning' | 'success' | 'info',
		timeout: number
	}
}

export type UserNotificationGroup = {
	text: string
	notifications: UserNotification[]
}

export type UserNotificationToastGroup = {
	type: 'danger' | 'warning' | 'success' | 'info'
	notifications: UserNotificationToast[]
}

export enum NavigationLayout { basic = 'basic', extended = 'extended' }

export type Navigation = {
	layout: NavigationLayout,
	nodes: NavigationNode[]
	open: boolean
}

export type NavigationNode = {
	id: string,
	text: string,
	icon: string,
	active: boolean,
	depth: number,
	action?: RouterAction
	parent?: string
	state?: ContentLoadingState
}

export type ContextMenu = {
	open: boolean,
	visible: boolean,
	component: null|ComponentContentPayload,
	x: number,
	y: number,
	level: number,
	// eslint-disable-next-line
	promise?: (data?: any) => void
}

export type Tooltip = {
	open: boolean,
	visible: boolean,
	text: string,
	x: number,
	y: number,
}

export type SlideOverPanel = {
	id: string,
	active: boolean,
	payload: SlideOverPayload,
	payload_storable?: RouterActionStorePayload,
	payload_additional?: RouterActionAdditionalPayload,
	level: number,
	state: ContentLoadingState,
	// eslint-disable-next-line
	promise?: (data?: any) => void
}

export type SlideOverPayload = {
	content: Content
	size: ComponentSize
	closable: boolean
	outer_closable?: boolean
}

export type ModalPayload = {
	title: string|string[]
	content: Content
	size: ComponentSize
	closable: boolean
	moveable?: boolean
	outer_closable?: boolean
}

export type Modal = {
	id: string,
	payload: ModalPayload
	payload_storable?: RouterActionStorePayload,
	payload_additional?: RouterActionAdditionalPayload,
	level: number,
	// eslint-disable-next-line
	promise?: (data?: any) => void
	state?: ContentLoadingState,
}

export type TabPayload = {
	id: string
	text: string[],
	content: Content,
	active: boolean,
	closable: boolean,
	icon?: string
	navigationNodeId?: string,
	component?: string[]
}

export type Tab = {
	payload: TabPayload
	payload_storable: null|RouterActionStorePayload
	state: ContentLoadingState
	position: number
}

export enum TabContextAction { toggleBookmark = 'toggle_bookmark', refresh = 'refresh', clone = 'clone', close = 'close', closeTabsBefore = 'close_tabs_before', closeTabsAfter = 'close_tabs_after', closeOtherTabs = 'close_other_tabs', saveTabs = 'save_tabs' }
export enum TabOpening { new = 'new', existing = 'existing', reload = 'reload' }

export type Bookmark = {
	id: string,
	text: string,
	action: RouterAction,
	icon?: string
}

export type Gui2DialogPayload = {
	content: Content
}

export type PagePayload = { url: string }

// TODO verschieben

export enum GridLayoutChangedEvent { moved = 'moved', removed = 'removed', added = 'added' }
export type GridLayoutItem = { id: string, x: number, y: number, rows: number, cols: number, minRows: number, minCols: number, element: VNode}
export type GridLayoutItemPayload = { index: number, id: string, x: number, y: number, rows: number, cols: number }

export type DashboardWidget = {
	key: string,
	title: string,
	icon: string|null,
	x: number|null,
	y: number|null,
	rows: number,
	cols: number,
	min_rows: number,
	min_cols: number,
	color?: string,
	cache_timestamp: string|null,
	deletable: boolean
} & (
	{ type: 'component', content: Content, current_state?: ContentLoadingState } |
	{ type: 'html', content: string }
)