import { createApp } from 'vue3'
import SchedulingIndex from './components/SchedulingIndex.vue'
import '../scss/scheduling.scss'

// @ts-ignore
const gui = window.SchedulingGUI
// @ts-ignore
const emitter = window.__FIDELO__.EMITTER

const app = createApp(SchedulingIndex, { gui, emitter })
app.mount('#app')
