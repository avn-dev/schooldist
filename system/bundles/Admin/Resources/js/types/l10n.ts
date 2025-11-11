export type L10NCollection = Record<string, string>

export class L10N {
	translations: Map<string, string>

	constructor() {
		this.translations = new Map<string, string>()
	}

	addTranslation(key: string, translation: string) {
		this.translations.set(key, translation)
	}

	async addTranslations(translations: L10NCollection) {
		Object.keys(translations).forEach((key: string) => {
			this.addTranslation(key, translations[key])
		})
	}

	translate(key: string): string {
		return this.translations.get(key) ?? key
	}
}