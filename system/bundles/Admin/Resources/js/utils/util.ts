import { safe } from "./promise"

/* eslint-disable @typescript-eslint/no-explicit-any */
const readCssVariable = (variable: string, defaultValue?: any) => {
	const value: string = getComputedStyle(document.documentElement).getPropertyValue(variable)
	return value.length > 0 ? value : defaultValue
}

const generateRandomString = (length: number): string => {
	return [...Array(length)].map(() => (~~(Math.random() * 36)).toString(36)).join('')
}

const sleep = (ms: number) => {
	return new Promise(resolve => setTimeout(resolve, ms))
}

/* eslint-disable @typescript-eslint/no-explicit-any */
const checkEnum = <T extends Record<string, string | number>>(value: any, enumObject: T): boolean => {
	return Object.values(enumObject).includes(value)
}

const toUrl = (url: string | URL): URL => {
	return typeof url === 'string' ? new URL(url, window.location.toString()) : url
}

const isExternalFideloUrl = (url: string | URL): boolean => {
	try {
		const u = toUrl(url)
		// HTTPS required
		if (u.protocol !== 'https:') return false
		// Normalise hostname: lowercase, remove trailing dot
		let host = u.hostname.toLowerCase()
		if (host.endsWith('.')) host = host.slice(0, -1)
		// Valid if exactly fidelo.com or a subdomain of fidelo.com
		return host === 'fidelo.com' || host.endsWith('.fidelo.com')
	} catch (e) {
		console.error(e)
	}
	return false
}

const shouldSandboxUrl = (url: string | URL): boolean => {
	try {
		// HTTPS required
		if (['http:', 'https:'].includes(toUrl(url).protocol)) {
			return toUrl(url).origin !== location.origin
		}
	} catch (e) {
		console.error(e)
	}

	return true
}

const pingUrl = async (url: string | URL, config?: RequestInit): Promise<number> => {
	const [error, response] = await safe(fetch(toUrl(url), {
		method: 'HEAD',
		mode: 'no-cors',
		...config ?? {}
	}))

	if (error) {
		return 500
	}

	return response.status === 0 ? 200 : response.status
}

const checkOrigin = (origin: string)=> {

	const appOrigin = toUrl(document.documentURI).origin

	if (document.documentURI === 'about:srcdoc' || appOrigin === origin) {
		return true
	}

	console.error('No cross domain communication allowed', appOrigin, origin)

	return false
}

function formatNumber(value: number | string, decimals = 2, thousandSep = '.', decimalSep = ',') {
	const num = Number(value)

	if (isNaN(num)) {
		console.warn('formatNumber: value is not a number')
		return value
	}

	const [intPart, decPart] = num
		.toFixed(decimals)
		.split('.')

	const withSep = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, thousandSep)
	return withSep + (decPart ? decimalSep + decPart : '')
}

export {
	readCssVariable,
	generateRandomString,
	sleep,
	checkEnum,
	toUrl,
	isExternalFideloUrl,
	shouldSandboxUrl,
	pingUrl,
	checkOrigin,
	formatNumber
}