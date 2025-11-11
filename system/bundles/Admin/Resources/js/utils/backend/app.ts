import { App as Application } from 'vue3'
import { Page } from '@inertiajs/core'
import { Emitter } from 'mitt'
import router from '../../router'
import { InterfaceBackend, Registrar } from '../../types/backend/app'
import { ExternApi } from '../../types/backend/router'
import l10n from '../../l10n'
import l10nPlugin from '../../plugins/l10n'
import emitterPlugin from '../../plugins/emitter'
import { handleMessageEvent } from './router'
import { sleep } from '../util'
import { useUser } from '../../composables/user'
import { useInterface } from '../../composables/interface'
import { useNavigation } from '../../composables/navigation'
import { useSupport } from '../../composables/support'

declare global {
	interface Window {
		Admin: ExternApi,
		/* eslint-disable @typescript-eslint/no-explicit-any */
		zxcvbn: () => Record<any, any>
		tinymce: () => Record<any, any>
	}
}

export type Events = {
	'support.chat.open': void,
	'support.chat.close': void,
}

// @ts-ignore TODO
const setupApp = async (app: Application, registrar: Registrar, page: Page, emitter: Emitter<Events>) => {

	app.use(l10nPlugin, l10n)
	app.use(emitterPlugin, emitter)

	// @ts-ignore
	const interfaceBackend: InterfaceBackend = page.props.interface

	//if (page.component.indexOf('Backend/') !== -1) {

	await initInterface(interfaceBackend)

	if (!registrar) {
		console.warn('No registrar object found')
		registrar = new Registrar()
	}

	// Include all components from @Admin/build.ts
	registrar.boot(app)

	// Give vue some time to render TODO nextTick()
	sleep(500).then(() => router.actions(interfaceBackend._actions ?? []))

	// Extern api to communicate with this app
	window.Admin = new ExternApi(emitter)
	// Iframe communication
	window.addEventListener('message', (event: MessageEvent) => handleMessageEvent(event))

	//}
}

const initInterface = async (payload: InterfaceBackend) => {
	await useUser().init(payload.user, payload.ping_interval)
	await useInterface().init(payload.debug, payload.language, payload.color_scheme, payload.logo, payload.server, payload.version, payload.tenants)
	await useNavigation().init(payload.navigation)
	await useSupport().init(payload.support)
	await l10n.addTranslations(payload._l10n ?? {})
}

export {
	setupApp
}