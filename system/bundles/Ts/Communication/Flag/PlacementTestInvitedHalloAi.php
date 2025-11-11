<?php

namespace Ts\Communication\Flag;

use Communication\Interfaces\Flag;
use Communication\Interfaces\Model\HasCommunication;
use Tc\Service\LanguageAbstract;
use TsFrontend\Service\Placeholders\PlacementtestHalloAiService;

class PlacementTestInvitedHalloAi implements Flag
{
	public static function getTitle(LanguageAbstract $l10n): string
	{
		return $l10n->translate('Einstufungstest über Hallo.ai');
	}

	public static function getRecipientKeys(): array
	{
		return ['customer', 'agency'];
	}

	public function validate(bool $used, LanguageAbstract $l10n, HasCommunication $model, \Ext_TC_Communication_Message $message, bool $finalOutput, array $confirmedErrors): array
	{
		$content = (string)$message->content;

		$errors = [];
		if ($finalOutput && str_contains($content, '[PLACEMENTTESTHALLOAI:')) {
			// TODO sollte das nicht eigentlich die Platzhalterklasse machen?
			[, $errors] = $this->replaceLegacyPlaceholder($l10n, $message, $model, $content);
		}

		if (empty($errors)) {
			if ($finalOutput) {
				$hasLink = (bool)preg_match('/https:\/\/[a-zA-Z0-9.-]+\.hallo\.ai\//', $content, $matches);
			} else {
				$hasLink = str_contains($content, '{link_placementtest_halloai}') || str_contains($content, '[PLACEMENTTESTHALLOAI:');
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
		[$processErrors, $processes] = (new PlacementtestHalloAiService())
			->generateProcessesForPlaceholders($l10n, $inquiry->getSchool(), $text);

		if (empty($processErrors)) {
			$message->content = $text;
			$message->addRelations($processes);
		}

		return [$text, $processErrors];
	}
}