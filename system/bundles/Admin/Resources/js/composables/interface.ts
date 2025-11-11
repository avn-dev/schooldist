import { ref, reactive, readonly, type Ref } from 'vue3'
import { ColorSchemeSetting } from '../types/interface'
import { type Tenant, type Logo } from '../types/backend/app'
import { safe } from '../utils/promise'
import { resolveColorScheme } from '../utils/interface'
import router from '../router'

const debug: Ref<boolean> = ref(false)
const language: Ref<string> = ref('en')
const colorScheme: Ref<ColorSchemeSetting> = ref(ColorSchemeSetting.light)
const logo: Ref<Logo> = ref({} as Logo)
const tenants: Ref<Tenant[]> = ref([])
const server: Ref<string|null> = ref(null)
const version: Ref<string|null> = ref(null)
const levelZIndex: Ref<number> = ref(4000)
const scope = reactive({
	height: 0,
	width: 0
})

const init = async (payloadDebug: boolean, payloadLanguage: string, payloadColorScheme: ColorSchemeSetting, payloadLogo: Logo, payloadServer: string | null, payloadVersion: string | null, payloadTenants: Tenant[] | null) => {
	debug.value = payloadDebug
	language.value = payloadLanguage
	colorScheme.value = resolveColorScheme(payloadColorScheme)
	logo.value = payloadLogo
	server.value = payloadServer
	version.value = payloadVersion

	if (payloadTenants) {
		tenants.value = payloadTenants
	}

	initScope()

	//setInterval(() => switchColorScheme(false), 5000)

	return true
}

const initScope = () => {
	function resize(event: Event) {
		const target: EventTarget | null = event.target
		if (target) {
			scope.height = window.innerHeight
			scope.width = window.innerWidth
		}
	}

	window.addEventListener('resize', resize)
	window.addEventListener('load', resize)
}

const setColorScheme = async (payload: ColorSchemeSetting, save = true) => {
	colorScheme.value = payload
	if (save) {
		await safe(router.post('/admin/user/color-scheme/save', { scheme: payload }))
	}
}

const switchColorScheme = async (save = true) => {
	const newColorScheme = (colorScheme.value !== ColorSchemeSetting.dark) ? ColorSchemeSetting.dark : ColorSchemeSetting.light
	await setColorScheme(newColorScheme, save)
}

const switchTenant = async (tenant: Tenant) => {
	/* eslint-disable @typescript-eslint/no-unused-vars */
	const [error, response] = await safe(router.post('/admin/interface/tenant/switch', { tenant: tenant.key }))

	if (response) {
		tenants.value = tenants.value.map((loop: Tenant) => {
			loop.selected = (loop.key === tenant.key)
			return loop
		})
	}
}

const increaseZIndex = () => {
	levelZIndex.value = levelZIndex.value + 10
	return levelZIndex.value
}

const removeZIndex = (level: number) => {
	if (level === levelZIndex.value) {
		levelZIndex.value = levelZIndex.value - 10
	}
}

export function useInterface() {
	return {
		debug: readonly(debug),
		language: readonly(language),
		colorScheme: readonly(colorScheme),
		logo: readonly(logo),
		tenants: readonly(tenants),
		version: readonly(version),
		server: readonly(server),
		scope: readonly(scope),
		levelZIndex: readonly(levelZIndex),
		init,
		initScope,
		setColorScheme,
		switchColorScheme,
		switchTenant,
		increaseZIndex,
		removeZIndex
	}
}