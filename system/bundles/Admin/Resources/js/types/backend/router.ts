import { Component } from 'vue3'
import { Axios, AxiosRequestConfig } from 'axios'
import { VisitOptions } from '@inertiajs/core'
import { Emitter } from 'mitt'
import { ChatState, Gui2DialogPayload, ModalPayload, PagePayload, SlideOverPayload, TabPayload, User } from './app'
import { L10NCollection } from '../l10n'
import { Events } from '../../utils/backend/app'
import { ColorSchemeSetting } from '../interface'
import { useUser } from '../../composables/user'
import { useSupport } from '../../composables/support'

export enum LoadingState {
	none = 'none',
	loaded = 'loaded',
	loading = 'loading',
	failed = 'failed',
	warning = 'warning',
	reload = 'reload',
	unauthorized = 'unauthorized',
	forbidden = 'forbidden',
}

export type ContentLoadingState = { state: LoadingState, text?: string|number }

export enum RouterTarget {
	tab = 'tab',
	page = 'page',
	slideOver = 'slideOver',
	modal = 'modal',
	gui2Dialog = 'gui2_dialog'
}

export enum ContentType { html = 'html', iframe = 'iframe', component = 'component' }

export type UrlContentPayload = { url: string }

export type IframeContentPayload = { url?: string, html?: string, sandbox?: string }

/* eslint-disable @typescript-eslint/no-explicit-any */
export type ComponentContentPayload = {
	api_key?: string,
	component: string | Component | (() => string | Component),
	payload?: Record<string, any>
}


export type Content = (
	{ type: ContentType.html, payload: UrlContentPayload } |
	{ type: ContentType.iframe, payload: IframeContentPayload } |
	{ type: ContentType.component, payload: ComponentContentPayload, initialized?: boolean }
)

export type ContentParameters = string | null

export type ContentSource = (
	{ source: RouterTarget.tab, payload: { id: string } } |
	{ source: RouterTarget.modal, payload: { id: string } } |
	{ source: RouterTarget.slideOver, payload: { id: string } } |
	{ source: RouterTarget.gui2Dialog, payload: { hash: string, instance_hash: string } }
)

/* eslint-disable @typescript-eslint/no-explicit-any */
type CloseEventPayload = Record<string, any> &
	({ source: RouterTarget.tab, payload: { id: string } }) |
	({ source: RouterTarget.slideOver, payload: { id: string } }) |
	({ source: RouterTarget.modal, payload: { id: string } }) |
	({ source: RouterTarget.gui2Dialog, payload: { hash: string, instance_hash: string, other_dialogs: number } })

export enum EventAction { CLOSE = 'CLOSE', ACTION = 'ACTION', SOURCE = 'SOURCE', COLOR_SCHEME = 'COLOR_SCHEME' }

/* eslint-disable @typescript-eslint/no-explicit-any */
export type AdminMessageEvent = Record<string, any> &
	({ action: EventAction.CLOSE, payload: CloseEventPayload }) |
	({ action: EventAction.ACTION, payload: RouterAction })

export type IframeMessageEvent = Record<string, any> &
	({ action: EventAction.SOURCE, payload: ContentSource }) |
	({ action: EventAction.COLOR_SCHEME, payload: ColorSchemeSetting })

/* eslint-disable @typescript-eslint/no-explicit-any */
export type RouterAction = ({ payload_storable: null | RouterActionStorePayload, _l10n?: L10NCollection, payload_additional?: RouterActionAdditionalPayload } & (
	{ target: RouterTarget.tab, payload: TabPayload } |
	{ target: RouterTarget.modal, payload: ModalPayload } |
	{ target: RouterTarget.slideOver, payload: SlideOverPayload } |
	{ target: RouterTarget.page, payload: PagePayload } |
	{ target: RouterTarget.gui2Dialog, payload: Gui2DialogPayload }
))

export type RouterActionStorePayload = undefined | null | {
	source: string,
	payload: RouterAction,
	key: string,
	parameters: ContentParameters
}

/* eslint-disable @typescript-eslint/no-explicit-any */
export type RouterActionAdditionalPayload = undefined | null | Record<string, any>

/* eslint-disable  @typescript-eslint/no-empty-interface */
export interface RequestConfig extends AxiosRequestConfig {

}

export interface ComponentApiInterface {
	debug(debug: boolean): void
	action<T>(action: string, config?: RequestConfig): Promise<T>
}

export interface RouterInterface {
	client(): Axios

	component(component: string, parameters?: ContentParameters): ComponentApiInterface

	visit(url: string | URL, options?: VisitOptions): void

	get<T>(url: string | URL, config?: RequestConfig): Promise<T>

	post<T>(url: string | URL, data?: any, config?: RequestConfig): Promise<T>

	put<T>(url: string | URL, data?: any, config?: RequestConfig): Promise<T>

	delete<T>(url: string | URL, config?: RequestConfig): Promise<T>

	action<T>(action: RouterAction): Promise<T | null>

	actions(actions: RouterAction[]): Promise<Awaited<any>[]>

	openTab<T>(payload: TabPayload): Promise<T | null>

	openModal<T>(payload: ModalPayload): Promise<T | null>

	openSlideOver<T>(payload: SlideOverPayload): Promise<T | null>

	openUserBoard(): Promise<{ _actions: RouterAction[] }>

	openSupport(): Promise<{ _actions: RouterAction[] }>

	openBookmarks(): Promise<{ _actions: RouterAction[] }>

	confirm(title: string, message?: string): Promise<boolean | null>
}

type ExternApiEvents = {
	on: (event: string, callable: () => void) => void
}

type ExternApiSupport = {
	setChatState: (state: ChatState) => void
}

export class ExternApi {
	public user: User | null
	public events: ExternApiEvents
	public support: ExternApiSupport

	constructor(emitter: Emitter<Events>) {
		this.user = useUser().user.value
		this.events = {
			// @ts-ignore
			on: (event: string, callable: () => void) => emitter.on(event, callable)
		}
		this.support = {
			setChatState: (state: ChatState): void => useSupport().setChatState(state)
		}
	}
}