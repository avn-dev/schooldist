// TODO weiter ausbauen (z.b. Als Modal öffnen)
export function useFileViewer() {
	const openFile = (file: string|File) => {
		if (file instanceof File) {
			file = URL.createObjectURL(file)
		}
		window.open(file)
	}

	return {
		openFile,
	}
}