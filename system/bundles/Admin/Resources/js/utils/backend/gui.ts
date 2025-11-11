import { Gui2SessionInstance } from "../../types/backend/gui"
import { getFrames } from "../interface"

export const getGui2Instances = (window: Window): Gui2SessionInstance[] => {
	const frames: Window[] = getFrames(window)
	const instances: Gui2SessionInstance[] = []

	frames.forEach((window: Window) => {
		try {
			// @ts-ignore
			if (window.aGUI) {
				// @ts-ignore
				Object.keys(window.aGUI).forEach((hash: string) => {
					// @ts-ignore
					const gui2 = window.aGUI[hash]
					const instance: Gui2SessionInstance = { instanceHash: gui2.instance_hash, hash: hash, dialogs: [] }
					if (gui2.aDialogs) {
						// @ts-ignore
						gui2.aDialogs.forEach(dialog => instance.dialogs.push(dialog.options.gui_dialog_id))
					}
					instances.push(instance)
				})
			}
		} catch (e) {
			console.log('No access to frame')
		}
	})

	return instances
}

export const buildRequestBodyForGui2Instances = (gui2Instances: Gui2SessionInstance[]) => {
	// eslint-disable-next-line
	const body: Record<string, any> = {}
	gui2Instances.forEach((instance: Gui2SessionInstance) => {
		if (!body[instance.instanceHash]) {
			body[instance.instanceHash] = {}
		}
		if (!body[instance.instanceHash][instance.hash]) {
			body[instance.instanceHash][instance.hash] = {}
		}
		if (instance.dialogs.length > 0) {
			instance.dialogs.forEach((dialogId: string) => {
				body[instance.instanceHash][instance.hash][dialogId] = dialogId
			})
		} else {
			body[instance.instanceHash][instance.hash] = ''
		}
	})
	return body
}