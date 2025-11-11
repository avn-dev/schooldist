export enum ColorSchemeSetting { auto = 'auto', dark = 'dark', light = 'light' }
export enum ContrastMode { text = 'text', content = 'content' }

export enum AlertType { success = 'success', error = 'error', warning = 'warning' }
export type Alert = { type: AlertType, heading?: string, message: string, icon?: string }