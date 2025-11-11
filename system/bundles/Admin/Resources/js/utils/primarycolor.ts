import { readCssVariable } from './util'
import { ColorSchemeSetting, ContrastMode } from '../types/interface'

const base = parseInt(readCssVariable('--primary-color-base', 500))
const lightness = parseInt(readCssVariable('--primary-color-lightness', 0.5))
const shades: number[] = readCssVariable('--primary-color-shades', '50,100,200,300,400,500,600,700,800,900,950')
	.split(',')
	.map((shape: string) => parseInt(shape))

const primaryColor = {
	base: base,
	lightness: lightness,
	shades: shades,
}

const getPrimaryColor = () => primaryColor

const buildPrimaryColorCssClass = (cssClass: string, shade?: number, opacity?: number) => {
	if (!shade) {
		shade = primaryColor.base
	}
	return _buildCssClass(cssClass, shade, opacity)
}

const buildPrimaryColorColorSchemeCssClass = (cssClass: string, colorScheme: ColorSchemeSetting, opacity?: number) => {
	const contrastShade = getPrimaryColorColorSchemeContrastShade(colorScheme)
	return buildPrimaryColorCssClass(cssClass, contrastShade, opacity)
}

const buildPrimaryColorContrastCssClass = (cssClass: string, contrast: ContrastMode = ContrastMode.content, opacity?: number) => {
	return [
		buildPrimaryColorCssClass(cssClass, getPrimaryColorColorSchemeContrastShade(ColorSchemeSetting.light, contrast), opacity),
		buildPrimaryColorCssClass(`dark:${cssClass}`, getPrimaryColorColorSchemeContrastShade(ColorSchemeSetting.dark, contrast), opacity)
	].join(' ')
}

const buildPrimaryColorElementCssClasses = (prefix?: string) => {
	const contrastShade = getPrimaryColorColorSchemeContrastShade(ColorSchemeSetting.light)
	const contrastShadeText = getPrimaryColorContrastShade(ContrastMode.text, contrastShade)
	const contrastShadeDark = getPrimaryColorColorSchemeContrastShade(ColorSchemeSetting.dark)
	const contrastShadeDarkText = getPrimaryColorContrastShade(ContrastMode.text, contrastShadeDark)

	const complete = (cssClass: string) => (prefix) ? `${prefix}${cssClass}` : cssClass

	return [
		buildPrimaryColorCssClass(complete('bg'), contrastShade),
		buildPrimaryColorCssClass(complete('text'), contrastShadeText),
		buildPrimaryColorCssClass(`dark:${complete('bg')}`, contrastShadeDark),
		buildPrimaryColorCssClass(`dark:${complete('text')}`, contrastShadeDarkText),
	].join(' ')
}

const getPrimaryColorContrastShade = (contrast?: ContrastMode, shade?: number) => {

	if (!shade) {
		shade = primaryColor.base
	}

	const postfix =[]
	if (contrast === ContrastMode.text) postfix.push('text')
	postfix.push(shade)

	return readCssVariable(`--primary-color-contrast-${postfix.join('-')}`, 500)
}

const getPrimaryColorColorSchemeContrastShade = (colorScheme: ColorSchemeSetting, contrast: ContrastMode = ContrastMode.content) => {

	if (colorScheme === ColorSchemeSetting.auto) {
		console.warn(`Given color scheme is type "${ColorSchemeSetting.auto}" and needs to be "${ColorSchemeSetting.dark}" or "${ColorSchemeSetting.light}"`)
		colorScheme = ColorSchemeSetting.light
	}

	const postfix =[]
	if (contrast === ContrastMode.text) postfix.push('text')
	postfix.push(colorScheme)

	return readCssVariable(`--primary-color-contrast-${postfix.join('-')}`, 500)
}

const highlightText = (haystack: string, matches: string[]) => {

	if (matches.length > 0) {
		const placeholders: Array<{ placeholder: string, value: string }> = []
		matches.forEach((text: string) => {
			const partMatches: RegExpMatchArray | null = haystack.match(new RegExp(text, "ig"))
			if (partMatches) {
				partMatches.forEach((match: string) => {
					const placeholder = `{|${placeholders.length}|}`
					placeholders.push({ placeholder: placeholder, value: `<span class="${buildPrimaryColorElementCssClasses()} rounded px-0.5">${match}</span>` })
					haystack = haystack.replace(match, placeholder)
				})
			}
		})

		placeholders.forEach((obj) => {
			haystack = haystack.replace(obj.placeholder, obj.value)
		})
	}

	return haystack
}

const _buildCssClass = (cssClass: string, shade: number, opacity?: number) => {
	let className = `${cssClass}-primary-${shade}`

	if (opacity && opacity !== 0) {
		className += `/${opacity}`
	}

	return className
}

/*const _searchPrimaryColorShadeIndex = (shade: number) => {
	return primaryColor.shades.findIndex((loop: number) => loop == shade)
}

const _isLightPrimaryColor = (): boolean => {
	return primaryColor.lightness > 0.72
}

const _isDarkPrimaryColor = (): boolean => {
	return primaryColor.lightness < 0.4
}

const _searchPrimaryColorShade = (steps?: number, shade?: number) => {

	if (!shade) {
		shade = primaryColor.base
	}

	if (!steps || steps === 0) {
		return shade
	}

	//if (_isLightPrimaryColor() || _isDarkPrimaryColor()) {
	//	steps = steps * -1
	//}

	const baseIndex = _searchPrimaryColorShadeIndex(shade)

	let shadeIndex = baseIndex + steps

	if (shadeIndex < 0) {
		shadeIndex = 0
	} else if (shadeIndex >= primaryColor.shades.length) {
		shadeIndex = primaryColor.shades.length - 1
	}

	//if (steps !== 0 && shadeIndex === baseIndex) {
	//	if (shadeIndex === 0) {
	//		shadeIndex = primaryColor.shades.length - 1;
	//	} else {
	//		shadeIndex = 0;
	//	}
	//}

	return primaryColor.shades[shadeIndex]
}*/

export {
	ContrastMode,
	getPrimaryColor,
	buildPrimaryColorCssClass,
	buildPrimaryColorColorSchemeCssClass,
	buildPrimaryColorContrastCssClass,
	buildPrimaryColorElementCssClasses,
	getPrimaryColorContrastShade,
	highlightText
}