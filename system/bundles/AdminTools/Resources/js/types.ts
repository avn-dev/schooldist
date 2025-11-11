// @ts-ignore
import { User } from "@Admin/types/backend/app"
// @ts-ignore
import { ColorScheme } from "@Admin/types/interface"

export type NavigationNode = {
	text: string,
	url: string,
	icon: string,
	active: boolean,
	submenu?: Array<NavigationNode>
}

export type Navigation = {
	nodes: Array<NavigationNode>
}

export type Interface = {
	color_scheme: ColorScheme,
	title: string,
	user: User,
	navigation: Navigation
}