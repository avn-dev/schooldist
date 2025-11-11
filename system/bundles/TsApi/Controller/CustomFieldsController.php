<?php
declare(strict_types=1);

namespace TsApi\Controller;

use Ext_TC_Flexibility;
use Ext_TC_Flexible_Option;
use Ext_TS_Config;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use TsApi\Handler\Inquiry;

/**
 * Liefert Zapier-kompatible Felder (PlainInputFieldSchema) für section_id = 3 (student_record_general)
 */
final class CustomFieldsController extends AbstractController
{
	private const int CACHE_MAX_AGE = 300;

	/**
	 * Holt alle Custom Fields für den Student Record Bereich
	 */
	public function studentRecordFields(Request $request): JsonResponse
	{
		$lang = (string) $request->get('lang', 'en');
		if (!$this->isSupportedLanguage($lang)) {
			return response()
				->json(['error' => 'Unsupported language'], 400)
				->header('Cache-Control', 'no-store');
		}

		$fields = Inquiry::fetchFlexFields();

		$optionsByFieldId = $this->loadOptionMaps($fields, $lang);

		$payload = [];
		foreach ($fields as $field) {
			$type = (int) $field->type;
			$id = $field->id;

			// wird nicht für Zapier gebraucht
			if ($this->shouldSkipField($type)) {
				continue;
			}

			$choices = $optionsByFieldId[$id] ?? [];
			$mapped = $this->mapFieldToZapier($field, $choices);

			if ($mapped !== null) {
				$payload[] = $mapped;
			}
		}

		return response()
			->json($payload)
			->header('Cache-Control', 'public, max-age=' . self::CACHE_MAX_AGE);
	}

	// Checkt, ob die angegebene Sprache überhaupt erlaubt ist
	private function isSupportedLanguage(string $lang): bool
	{
		$languages = Ext_TS_Config::getInstance()->frontend_languages ?? [];
		return is_array($languages) && in_array($lang, $languages, true);
	}

	/**
	 * Baut eine Map von Feld-ID -> Optionen (nur für Felder mit Auswahlmöglichkeiten)
	 *
	 * @param Ext_TC_Flexibility[] $fields
	 * @return array<int, array<int, array{label:string,value:string}>>
	 */
	private function loadOptionMaps(array $fields, string $lang): array
	{
		$out = [];

		foreach ($fields as $field) {
			$type = (int) $field->type;

			if ($this->isOptionBasedType($type)) {
				$out[$field->id] = $this->mapOptionsToZapier(
					Ext_TC_Flexibility::getOptions($field->id, $lang)
				);
			}
		}

		return $out;
	}

	// Prüft, ob der Feldtyp Optionen hat
	private function isOptionBasedType(int $type): bool
	{
		// Checkbox ist bool, deswegen kein Option-Feld
		return in_array($type, [
			Ext_TC_Flexibility::TYPE_SELECT,
			Ext_TC_Flexibility::TYPE_MULTISELECT,
			Ext_TC_Flexibility::TYPE_YESNO,
		], true);
	}

	// Bestimmte Typen wollen wir gar nicht exportieren (Überschriften etc.)
	private function shouldSkipField(int $type): bool
	{
		return in_array($type, [
			Ext_TC_Flexibility::TYPE_HEADLINE,
			Ext_TC_Flexibility::TYPE_HTML,
			Ext_TC_Flexibility::TYPE_REPEATABLE,
		], true);
	}

	/**
	 * Wandelt Optionen in Zapier-Format um -> [{label, value}, …]
	 * Separatoren werden ignoriert
	 */
	private function mapOptionsToZapier(array $raw): array
	{
		$out = [];

		foreach ($raw as $optionId => $title) {
			if ((string) $optionId === Ext_TC_Flexible_Option::OPTION_SEPARATOR_KEY) {
				continue;
			}

			$label = trim((string) $title);
			if ($label === '') {
				$label = '#' . $optionId . ' TRANSLATION MISSING';
				// __pout($label);
			}

			$out[] = ['label' => $label, 'value' => (string) $optionId];
		}

		return $out;
	}

	/**
	 * Mappt ein einzelnes Flex-Feld auf ein Zapier-kompatibles Feldobjekt
	 */
	private function mapFieldToZapier(Ext_TC_Flexibility $field, array $choices): ?array
	{
		$dbType = (int) $field->type;

		[$zapierType, $finalChoices, $isList, $placeholder] = $this->guessZapierTypeAndChoices($dbType, $choices);

		$out = [
			'key' => $field->id,
			'label' => $this->safeLabel($field),
			'type' => $zapierType,
			'required' => $field->isRequired(),
			'helpText' => trim($field->description ?? ''),
			'placeholder' => $placeholder ?? ($field->placeholder ?? ''),
		];

		if ($finalChoices !== null) {
			$out['choices'] = array_values($finalChoices);
		}
		if ($isList) {
			$out['list'] = true;
		}

		return $out;
	}

	/**
	 * @param Ext_TC_Flexibility $field
	 */
	private function safeLabel(Ext_TC_Flexibility $field): string
	{
		$title = trim($field->title);
		return $title !== '' ? $title : ('Field ' . $field->id);
	}

	/**
	 * Bestimmt, welcher Typ für Zapier passt (& Auswahlmöglichkeiten)
	 */
	private function guessZapierTypeAndChoices(int $dbType, array $choices): array
	{
		$normalizedChoices = !empty($choices) ? array_values($choices) : null;

		return match ($dbType) {
			Ext_TC_Flexibility::TYPE_TEXTAREA => ['text', null, false, null],
			Ext_TC_Flexibility::TYPE_CHECKBOX => [
				'string',
				[
					['label' => 'No', 'value' => '0'],
					['label' => 'Yes', 'value' => '1'],
				],
				false,
				null,
			],
			Ext_TC_Flexibility::TYPE_DATE => ['string', null, false, 'YYYY-MM-DD'],
			Ext_TC_Flexibility::TYPE_SELECT => ['string', $normalizedChoices, false, null],
			Ext_TC_Flexibility::TYPE_YESNO =>
			[
				'string',
				[
					['label' => 'Yes', 'value' => 'yes'],
					['label' => 'No', 'value' => 'no'],
				],
				false,
				null,
			],
			Ext_TC_Flexibility::TYPE_MULTISELECT => ['string', $normalizedChoices, true, null],
			default => ['string', null, false, null],
		};
	}
}