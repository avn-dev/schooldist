import { createApp } from 'vue3'
import ExternalApp from './components/ExternalApp.vue'

const props = JSON.parse(document.getElementById('app')?.getAttribute('props') ?? '')

createApp(ExternalApp, { l10n: props.l10n })
	.mount('#app')