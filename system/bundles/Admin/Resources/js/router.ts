import axios, { Axios, AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios'
import { router as InertiaRouter } from '@inertiajs/vue3'
import { VisitOptions } from '@inertiajs/core'
import {
	ComponentApiInterface,
	ContentParameters,
	ContentType,
	RouterAction,
	RouterInterface,
	RouterTarget
} from './types/backend/router'
import { handleInterfaceResponse, handleRouterAction } from './utils/backend/router'
import { toUrl } from './utils/util'
import { ComponentSize, ModalPayload, SlideOverPayload, TabPayload } from './types/backend/app'
import ConfirmModal from './components/modal/ConfirmModal.vue'

class Router implements RouterInterface {
	http: Axios

	constructor() {
		const http = axios.create({})
		http.defaults.headers.common['X-Admin'] = '1'
		http.interceptors.response.use(
			(response: AxiosResponse) => handleInterfaceResponse(response),
			(errors: AxiosError) => Promise.reject(errors)
		)
		this.http = http
	}

	client() {
		return this.http
	}

	component(component: string, parameters?: ContentParameters): ComponentApi {
		return new ComponentApi(component, this.http, parameters)
	}

	visit(url: string | URL, options?: VisitOptions) {
		InertiaRouter.visit(url, options)
	}

	async get<T>(url: string | URL, config?: AxiosRequestConfig): Promise<T> {
		const response: AxiosResponse<T> = await this.http.get(toUrl(url).href, config)
		return response.data
	}

	/* eslint-disable @typescript-eslint/no-explicit-any */
	async post<T>(url: string | URL, data?: any, config?: AxiosRequestConfig): Promise<T> {
		const response: AxiosResponse<T> = await this.http.post(toUrl(url).href, data, config)
		return response.data
	}

	/* eslint-disable @typescript-eslint/no-explicit-any */
	async put<T>(url: string | URL, data?: any, config?: AxiosRequestConfig): Promise<T> {
		const response: AxiosResponse<T> = await this.http.put(toUrl(url).href, data, config)
		return response.data
	}

	async delete<T>(url: string | URL, config?: AxiosRequestConfig): Promise<T> {
		const response: AxiosResponse<T> = await this.http.delete(toUrl(url).href, config)
		return response.data
	}

	async action<T>(action: RouterAction): Promise<T | null> {
		return await handleRouterAction<T | null>(action)
	}

	/* eslint-disable @typescript-eslint/no-explicit-any */
	async actions(actions: RouterAction[]): Promise<Awaited<any>[]> {
		const promises = actions.map((action: RouterAction) => this.action(action))
		return await Promise.all(promises)
	}

	async openTab<T>(payload: TabPayload): Promise<T | null> {
		return await this.action<T | null>({ target: RouterTarget.tab, payload: payload, payload_storable: null })
	}

	async openModal<T>(payload: ModalPayload): Promise<T | null> {
		return await this.action<T | null>({ target: RouterTarget.modal, payload: payload, payload_storable: null })
	}

	async openSlideOver<T>(payload: SlideOverPayload): Promise<T | null> {
		return await this.action<T | null>({ target: RouterTarget.slideOver, payload: payload, payload_storable: null })
	}

	async openUserBoard(): Promise<{ _actions: RouterAction[] }> {
		return await this.get('/admin/interface/userboard', { headers: { 'Accept': 'application/json' }})
	}

	async openSupport(): Promise<{ _actions: RouterAction[] }> {
		return await this.get('/admin/interface/support', { headers: { 'Accept': 'application/json' }})
	}

	async openBookmarks(): Promise<{ _actions: RouterAction[] }> {
		return await this.get('/admin/interface/bookmarks', { headers: { 'Accept': 'application/json' }})
	}

	async confirm(title: string, message?: string): Promise<boolean | null> {
		return await this.openModal<boolean>({
			title: title,
			content: { type: ContentType.component, payload: { component: ConfirmModal, payload: { message: message } } },
			size: ComponentSize.medium,
			closable: true,
			outer_closable: true
		})
	}

}

class ComponentApi implements ComponentApiInterface {
	debugMode = false
	component: string
	headers: Record<string, string>
	parameters: ContentParameters|null
	http: Axios

	constructor(component: string, http: Axios, parameters?: ContentParameters) {
		this.component = component
		this.http = http
		this.headers = {}
		this.parameters = parameters ?? null
	}

	header(key: string, value: string): void {
		this.headers[key] = value
	}

	debug(debug: boolean): void {
		this.debugMode = debug
	}

	async action<T>(action: string, config?: AxiosRequestConfig): Promise<T> {
		if (!config) config = {}

		config.headers = {
			'Accept': 'application/json',
			...config.headers ?? {},
			'X-Admin-Component': this.component,
			...this.headers
		}

		if (this.parameters) {
			if (this.debugMode) {
				if (!config.params) config.params = {}
				config.params = { ...config.params, ...{ 'init': this.parameters } }
			} else {
				config.headers = { ...config.headers, 'X-Admin-Parameters': this.parameters }
			}
		}

		const axiosConfig = {
			...{ url: `/admin/interface/component/${this.component}/${action}` },
			...config ?? {},
		}

		const response: AxiosResponse = await this.http.request(axiosConfig)
		return response.data as T
	}
}

const instance = new Router()

export default instance