
export function useLocalStorage() {
	/* eslint-disable @typescript-eslint/no-explicit-any */
	const setStoredValue = (key: string, value: any) => {
		localStorage.setItem(key, JSON.stringify(value))
	}

	/* eslint-disable @typescript-eslint/no-explicit-any */
	const getStoredValue = (key: string, defaultValue: any = null) => {
		const storedValue = localStorage.getItem(key)
		return storedValue ? JSON.parse(storedValue) : defaultValue
	}

	return {
		getStoredValue,
		setStoredValue
	}
}