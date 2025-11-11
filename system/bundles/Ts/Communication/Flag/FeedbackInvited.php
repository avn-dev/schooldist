<?php

namespace Ts\Communication\Flag;

use Communication\Interfaces\Flag;
use Communication\Interfaces\Model\HasCommunication;
use Illuminate\Support\Arr;
use Tc\Service\LanguageAbstract;
use TsFrontend\Service\Placeholders\FeedbackLinksService;

class FeedbackInvited implements Flag
{
	public static function getTitle(LanguageAbstract $l10n): string
	{
		return $l10n->translate('Feedbackformular gesendet');
	}

	public static function getRecipientKeys(): array
	{
		return ['customer', 'agency'];
	}

	public function validate(bool $used, LanguageAbstract $l10n, HasCommunication $model, \Ext_TC_Communication_Message $message, bool $finalOutput, array $confirmedErrors): array
	{
		$content = (string)$message->content;

		$errors = [];
		if ($finalOutput && str_contains($content, '[FEEDBACKLINK:')) {
			// TODO sollte das nicht eigentlich die Platzhalterklasse machen?
			[$content, $errors] = $this->replaceLegacyPlaceholder($l10n, $message, $model, $content);
		}

		if (empty($errors)) {
			if ($finalOutput) {
				// TODO klappt nicht wenn Feedbacklink und Placementtestlink gleichzeitig benutzt werden weil beide denselben URL-Parameter benutzen
				$hasLink = (bool)preg_match('/[?&]'.FeedbackLinksService::URL_PARAMETER.'=([A-Z0-9]+)/', $content, $matches);
				if ($hasLink) {
					// Gültiger Link?
					// ->withTrashed() siehe FeedbackLinksService (wird mit active=0 ?! gespeichert)
					$hasLink = \Ext_TS_Marketing_Feedback_Questionary_Process::query()->withTrashed()->where('link_key', $matches[1])->first() !== null;
				}
			} else {
				$hasLink = str_contains($content, '{link_feedback_') || str_contains($content, '[FEEDBACKLINK:');
			}

			if ($used && !$hasLink) {
				$errors[] = sprintf($l10n->translate('Es wurde die Markierung "%s" gesetzt, allerdings enthält die Nachricht keinen Platzhalter für den Link zu einem Feedbackformular.'), self::getTitle($l10n));
			} else if (!$used && $hasLink) {
				$errors[] = sprintf($l10n->translate('Die Nachricht enthält einen Link zu einem Feedbackformular, allerdings wurde die Markierung "%s" nicht gewählt.'), self::getTitle($l10n));
			}
		}

		return $errors;
	}

	public function save(\Ext_TC_Communication_Message $message): void
	{
		preg_match('/[?&]'.FeedbackLinksService::URL_PARAMETER.'=([A-Z0-9]+)/', $message->content, $matches);

		if (!empty($matches[1])) {
			$process = \Ext_TS_Marketing_Feedback_Questionary_Process::query()->withTrashed()->where('link_key', $matches[1])->first();
			if ($process) {
				$process->active = 1;
				$process->message_id = $message->id;
				$process->save();
			}
		}
	}

	private function replaceLegacyPlaceholder(LanguageAbstract $l10n, \Ext_TC_Communication_Message $message, \Ext_TS_Inquiry $inquiry, string $text): array
	{
		// TODO Ob das so richtig ist?
		$to = $message->getAddresses('to')	;
		$contact = $email = null;
		foreach ($to as $address) {
			$contactRelation = Arr::first($address->relations, fn ($relation) => is_a($relation['relation'], \Ext_TC_Contact::class, true));
			if ($contactRelation) {
				$contact = \Factory::getInstance($contactRelation['relation'], $contactRelation['relation_id']);
				$email = $address->address;
				break;
			}
		}

		[$processErrors, $processes] = (new FeedbackLinksService())
			->generateProcessesForPlaceholders($l10n, $inquiry, $text, $contact, $email);

		if (empty($processErrors)) {
			$message->content = $text;
			$message->addRelations($processes);
		}

		return [$text, $processErrors];
	}
}