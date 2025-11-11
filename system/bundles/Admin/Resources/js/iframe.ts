import { createApp } from 'vue'
import { ContentSource, EventAction, IframeMessageEvent, RouterAction } from './types/backend/router'
import { L10NCollection } from './types/l10n'
import { checkOrigin } from './utils/util'
import { useInterface } from './composables/interface'
import mitt from 'mitt'
import AppSlim from './iframe/AppSlim.vue'
import l10nPlugin from './plugins/l10n'
import emitterPlugin from './plugins/emitter'
import l10n from './l10n'
import router from './router'
import registrar from './build'

let slimAppUsed = false

class AdminIframe {
	source: null | ContentSource = null

	setSource(source: ContentSource) {
		this.source = source
	}

	receiveMessage = async (e: MessageEvent)=> {

		if (!checkOrigin(e.origin) || typeof e.data !== 'object' || !e.data['action']) {
			return
		}

		const event = e.data as IframeMessageEvent

		switch (event.action) {
			case EventAction.SOURCE:
				this.setSource(event.payload)
				break
			case EventAction.COLOR_SCHEME: {
				const { setColorScheme } = useInterface()

				const elements = document.querySelectorAll<HTMLElement>('[data-mode]')
				Array.from(elements).forEach((element) => element.dataset.mode = event.payload)

				await setColorScheme(event.payload, false)
				break
			}
		}
	}

	/* eslint-disable @typescript-eslint/no-explicit-any */
	async sendMessage(action: string, payload?: any) {
		if (!window.parent) {
			console.warn('No parent window')
			return
		}

		try {
			window.parent.postMessage({ action: action, payload: payload })
		} catch (e) {
			console.error(e)
		}
	}

	/* eslint-disable @typescript-eslint/no-explicit-any */
	async close(payload?: Record<any, any>) {
		if (!this.source) {
			console.error('Unknown iframe source')
			return
		}

		const source = this.source

		if (payload) {
			source.payload = { ...source.payload, ...payload }
		}

		await this.sendMessage('CLOSE', JSON.parse(JSON.stringify(this.source)))
	}

	async action(routerAction: RouterAction, local: boolean) {
		if (local || window.parent === window) {
			if (!local) {
				console.warn('No parent window for router action')
			} else if (!slimAppUsed) {
				console.error('Missing slim app (createAdminSlimApp())')
			}
			return await router.action(routerAction)
		}
		return await this.sendMessage('ACTION', routerAction)
	}
}

const createAdminSlimApp = (translations: L10NCollection) => {
	const app = createApp(AppSlim, {  })

	l10n.addTranslations(translations)

	app.use(l10nPlugin, l10n)
	app.use(emitterPlugin, mitt())

	registrar.boot(app)

	slimAppUsed = true

	return app
}

const instance = new AdminIframe()

window.addEventListener('message', (event: MessageEvent) => instance.receiveMessage(event))

export { instance, createAdminSlimApp }