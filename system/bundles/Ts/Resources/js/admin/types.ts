// @ts-ignore
import { type User } from '@Admin/types/interface'

export type Traveller = {
	name: string
	image: string|null
	email: string
	birthday: string
	nationality: string
	age: number
}

export type Inquiry = {
	id: number
	created: string
	cancelled: string|null
	inbox: string
	school: string
	group?: string
	agency?: string
	number: string
	service_from: string
	service_until: string
	sales_person: User|null
	state: string
	due_payments?: Array<{ amount: string, date: string }>
}

export type Invoice = {
	label: string,
	number: string,
	amount: string,
	file: { name: string, path: string }
}

export type Service = ({ icon: string } & (
	{ type: 'course', name: string, from: string, until: string, weeks: number } |
	{ type: 'accommodation', name: string, from: string, until: string, weeks: number } |
	{ type: 'transfer', transfer_type: string, pick_up: string, drop_off: string, date: string, time?: string, booked: boolean  } |
	{ type: 'insurance', name: string, from: string, until: string } |
	{ type: 'activity', name: string, from: string, until: string, weeks?: number, blocks?: number }
))