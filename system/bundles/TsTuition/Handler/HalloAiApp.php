<?php

namespace TsTuition\Handler;

class HalloAiApp extends \TcExternalApps\Interfaces\SystemConfigApp
{
	const APP_NAME = 'halloai';

	const CONFIG_API_KEY = 'hallo_ai_api_key';

	/**
	 * Gültigkeitsdauer des Assessment Links
	 * @var string CONFIG_LINK_DURATION
	 */
	const CONFIG_LINK_DURATION = 'hallo_ai_link_duration';

	const CONFIG_EMAIL_NOTIFICATION = 'hallo_ai_email_notification';

	/**
	 * Verbindet System Variablen mit Feldern aus Hallo.ai Assessmentergebnissen.
	 * Die System Variablen enthalten Flex Felder Ids, in denen das Ergebnis gespeichert wird.
	 * @var array|string[]
	 */
	public static array $fieldMapping = [
		'hallo_ai_field_hallo_score' => 'halloScore',
		'hallo_ai_field_cefr_score' => 'cefrScore',
		'hallo_ai_field_coherence_score' => 'subScores.coherenceScore',
		'hallo_ai_field_fluency_score' => 'subScores.fluencyScore',
		'hallo_ai_field_grammar_score' => 'subScores.grammarScore',
		'hallo_ai_field_pronunciation_score' => 'subScores.pronunciationScore',
		'hallo_ai_field_vocabulary_score' => 'subScores.vocabularyScore'
	];

	public function getTitle(): string
	{
		return \L10N::t('Hallo.ai');
	}

	public function getDescription(): string
	{
		return \L10N::t('Hallo.ai - Beschreibung');
	}

	public function getIcon(): string
	{
		return 'fas fa-link';
	}

	public function getCategory(): string
	{
		return \Ts\Hook\ExternalAppCategories::TUITION;
	}

	public static function getApiKey(): string
	{
		return \System::d(self::CONFIG_API_KEY, '');
	}

	/**
	 * Gibt die Gültigkeitsdauer des Assessment Links in Stunden
	 * @return int
	 */
	public static function getLinkDuration(): int
	{
		return (int)\System::d(self::CONFIG_LINK_DURATION, 24);
	}

	public static function getEmailNotification(): int
	{
		return \System::d(self::CONFIG_EMAIL_NOTIFICATION, 0);
	}

	public static function getFlexFieldId(string $apiFieldKey): string
	{
		return \System::d($apiFieldKey, '');
	}

	protected function getConfigKeys(): array
	{
		/**
		 * Api Key
		 */
		$config =  [
			[
				'title' => \L10N::t('API Key'),
				'key' => self::CONFIG_API_KEY
			],
			[
				'title' => \L10N::t('Gültigkeitsdauer des Testlinks (in Stunden)'),
				'key' => self::CONFIG_LINK_DURATION,
				'type' => 'number'
			]
		];
		/**
		 * Auswahl der Flexfelder, die zum Speichern der Assessmentwerte verwendet werden können
		 */
		$flexFields = collect(\Ext_Thebing_Placementtests_Results::getInstance()
			->getFlexibleFields())
			->mapWithKeys(function ($flexField, $flexFieldId) {
				return [$flexFieldId => $flexField->title];
			})
			->prepend("", "")
			->toArray();
		foreach (self::$fieldMapping as $mappingKey => $halloAiField) {
			$config[] = [
				'title' => $this->t('Individuelles Feld für Wert: '.$halloAiField),
				'key' => $mappingKey,
				'type' => 'select',
				'options' => $flexFields
			];
		}
		return $config;
	}
}