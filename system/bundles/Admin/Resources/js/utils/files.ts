export function formatFileSize(file: File) {
	const size = file.size
	const kb = size / 1024
	return kb > 1024
		? (kb / 1024).toFixed(2) + ' MB'
		: kb.toFixed(1) + ' KB'
}