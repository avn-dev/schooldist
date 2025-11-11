
export type Student = {
	inquiry_id: number,
	name: string,
	group_name: string,
	activity_name: string,
	activity_short: string,
	activity_id: null|number,
	journey_activity_id: null|number,
	block_traveller_id: null|number
	block_id: null|number
}

export type ActivityFilter = Record<string, string|null>

export type ModalMessageLine = [string, string, string?] // type, message, errorCode
export type ModalMessageLines = Array<ModalMessageLine>
