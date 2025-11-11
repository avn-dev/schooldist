import { ColorSchemeSetting } from '../interface'

export type InterfaceAuth = {
	color_scheme: ColorSchemeSetting,
	title: string,
	logo: { light: string, dark: string },
	image: string,
	copyright: string
	l10n?: { [key: string]: string }
}