import { ColorSchemeSetting } from "../types/interface"

const resolveColorScheme = (colorScheme: ColorSchemeSetting): ColorSchemeSetting => {
	if (colorScheme === ColorSchemeSetting.auto) {
		return (window.matchMedia && window.matchMedia("(prefers-color-scheme:dark)").matches)
			? ColorSchemeSetting.dark
			: ColorSchemeSetting.light
	}
	return colorScheme
}

const getFrames = (window: Window): Window[] => {
	let frames: Window[] = []

	if (window.frames) {
		for (let i = 0; i < window.frames.length; i++) {
			const frame = window.frames[i]
			frames = [
				...frames,
				...[frame],
				...getFrames(frame)
			]
		}
	}

	return frames
}

export {
	resolveColorScheme,
	getFrames
}