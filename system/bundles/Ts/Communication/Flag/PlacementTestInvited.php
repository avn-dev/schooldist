<?php

namespace Ts\Communication\Flag;

use Communication\Interfaces\Flag;
use Communication\Interfaces\Model\HasCommunication;
use Illuminate\Support\Arr;
use Tc\Service\LanguageAbstract;
use TsFrontend\Service\Placeholders\PlacementtestService;

class PlacementTestInvited implements Flag
{
	public static function getTitle(LanguageAbstract $l10n): string
	{
		return $l10n->translate('Einstufungstest gesendet');
	}

	public static function getRecipientKeys(): array
	{
		return ['customer', 'agency'];
	}

	public function validate(bool $used, LanguageAbstract $l10n, HasCommunication $model, \Ext_TC_Communication_Message $message, bool $finalOutput, array $confirmedErrors): array
	{
		$content = (string)$message->content;

		$errors = [];
		if ($finalOutput && str_contains($content, '[PLACEMENTTEST:')) {
			// TODO sollte das nicht eigentlich die Platzhalterklasse machen?
			[$content, $errors] = $this->replaceLegacyPlaceholder($l10n, $message, $model, $content);
		}

		if (empty($errors)) {
			if ($finalOutput) {
				// TODO klappt nicht wenn Feedbacklink und Placementtestlink gleichzeitig benutzt werden weil beide denselben URL-Parameter benutzen
				$hasLink = (bool)preg_match('/[?&]'.PlacementtestService::URL_PARAMETER.'=([A-Z0-9]+)/', $content, $matches);
				if ($hasLink) {
					// Gültiger Link?
					$hasLink = \Ext_Thebing_Placementtests_Results::query()->where('key', $matches[1])->first() !== null;
				}
			} else {
				$hasLink = str_contains($content, '{link_placementtest}') || str_contains($content, '[PLACEMENTTEST:');
			}

			if ($used && !$hasLink) {
				$errors[] = sprintf($l10n->translate('Es wurde die Markierung "%s" gesetzt, allerdings enthält die Nachricht keinen Platzhalter für den Link zu einem Einstufungstest.'), self::getTitle($l10n));
			} else if (!$used && $hasLink) {
				$errors[] = sprintf($l10n->translate('Die Nachricht enthält einen Link zu einem Einstufungstest, allerdings wurde die Markierung "%s" nicht gewählt.'), self::getTitle($l10n));
			}
		}

		return $errors;
	}

	public function save(\Ext_TC_Communication_Message $message): void
	{

	}

	private function replaceLegacyPlaceholder(LanguageAbstract $l10n, \Ext_TC_Communication_Message $message, \Ext_TS_Inquiry $inquiry, string $text): array
	{
		[$processErrors, $processes] = (new PlacementtestService())
			->generateProcessesForPlaceholders($l10n, $inquiry->getSchool(), $text);

		if (empty($processErrors)) {
			$message->content = $text;
			$message->addRelations($processes);
		}

		return [$text, $processErrors];
	}
}