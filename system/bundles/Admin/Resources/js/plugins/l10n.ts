import { App as Application } from 'vue3'
import { L10N } from "../types/l10n"

export default {
	install: (app: Application, l10n: L10N) => {
		//app.provide('l10n', l10n)
		app.config.globalProperties.$l10n = l10n
		app.provide('l10n', l10n)
	}
}