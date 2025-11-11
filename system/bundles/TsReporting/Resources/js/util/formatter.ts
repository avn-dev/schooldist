
export function formatNumber(value: number, locale: string, style: string|undefined = undefined, currency: string|undefined = undefined) {
	return value.toLocaleString(locale, {
		style: style ?? 'decimal',
		currency: currency ?? undefined
	})
}

export function formatDate(value: string, locale: string, unit: string, labels: Record<string, string>) {
	// UTC erzwingen mit new Date(0, 0, 0) statt new Date('0000-00-00')
	const split = value.split('-')
	const date = new Date(parseInt(split[0]), parseInt(split[1]) - 1, parseInt(split[2]))

	switch (unit) {
		case 'year':
			return date.getFullYear()
		case 'quarter':
			console.log('quarter', value, date, getQuarter(date), date.getFullYear())
			return `${labels.quarter ?? 'Quarter'} ${getQuarter(date)}, ${date.getFullYear()}`
		case 'month':
			return date.toLocaleDateString(locale, { year: 'numeric', month: 'long' })
		case 'week':
			return `${labels.week ?? 'Week'} ${getIsoWeek(date)}, ${date.getFullYear()}`
		default:
			return date.toLocaleDateString(locale, { dateStyle: 'medium' })
	}
}

function getQuarter(date: Date) {
	return Math.floor(date.getMonth() / 3) + 1
}

// https://github.com/knutkirkhorn/week-number/blob/main/index.js (MIT)
function getIsoWeek(date: Date) {
	const millisecondsInDay = 86400000
	const daysInWeek = 7
	const firstDayOfWeek = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()))

	// The first week in the ISO definition starts with a week containing thursday.
	// This means we add 4 (thursday) and minus the current day of the week (UTC day || 7 (if UTC day is 0, which is sunday)) to the current UTC date
	firstDayOfWeek.setUTCDate(firstDayOfWeek.getUTCDate() + 4 - (firstDayOfWeek.getUTCDay() || daysInWeek))

	const firstDayOfYear = new Date(Date.UTC(firstDayOfWeek.getUTCFullYear(), 0, 1))
	const timeDifference = firstDayOfWeek.getTime() - firstDayOfYear.getTime()
	const daysDifference = timeDifference / millisecondsInDay

	return Math.ceil(daysDifference / daysInWeek)
}
