const safe = async <T>(promise: Promise<T>): Promise<[Error, null] | [null, T]> => {
	try {
		const result = await promise
		return [null, result]
	} catch (error) {
		console.error(error)
		if (error instanceof Error) {
			return [error, null]
		}
		return [new Error(typeof error === 'string' ? error : 'Unknown error'), null]
	}
}

export {
	safe
}