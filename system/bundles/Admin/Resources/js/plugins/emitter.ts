import { App as Application } from 'vue3'
import { Emitter } from 'mitt'
import { Events } from '../utils/backend/app'

export default {
	install: (app: Application, emitter: Emitter<Events>) => {
		//app.provide('l10n', l10n)
		app.config.globalProperties.$emitter = emitter
		app.provide('emitter', emitter)
	}
}