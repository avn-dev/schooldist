import { createApp } from 'vue3'
import LogViewer from './components/LogViewer.vue'

const app = createApp(LogViewer, { fileOptions: window.__FILE_OPTIONS__ })

app.provide('level_colors', {
	'DEBUG': 'text-info',
	'INFO': 'text-info',
	'NOTICE': 'text-info',
	'WARNING': 'text-warning',
	'ERROR': 'text-danger',
	'CRITICAL': 'text-danger',
	'ALERT': 'text-danger',
	'EMERGENCY': 'text-danger'
})

app.mount('#app')
