
export type Planification = {
	changeWeek(sAction: string, bNoReload: boolean): void
	preparePlanification(bCheckEmptyWeek: boolean, callback: any): void
	selectBlock(dummy: any, oElement: HTMLElement, bFocus: boolean): void
	changeWeekDay(iDay: number, bCheckEmptyWeek: boolean, callback: any): void
}

export type Block = {
	name: string
	description: string
	weekday: number,
	container: string
}

export type FilterValues = {
	floor: number;
}