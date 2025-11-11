<?php

namespace TsFrontend\Service\Placeholders;

use Tc\Service\LanguageAbstract;

class FeedbackLinksService
{
	const URL_PARAMETER = 'r';

	/**
	 * Kopiert und angepasst aus \Ext_Thebing_Communication
	 *
	 * TODO Keine Ahnung ob das so richtig läuft
	 *
	 * @param LanguageAbstract $l10n
	 * @param \Ext_TS_Inquiry $inquiry
	 * @param string $email
	 * @param string $content
	 * @return array
	 */
	public function generateProcessesForPlaceholders(LanguageAbstract $l10n, \Ext_TS_Inquiry $inquiry, string &$content, \Ext_TC_Contact $contact = null, string $email = null): array
	{
		$processes = $errors = [];

		if ($contact === null) {
			$contact = $inquiry->getCustomer();
		}

		if ($email === null) {
			$email = $contact->getFirstEmailAddress()->email;
		}

		preg_match_all('/(?=\[FEEDBACKLINK:(\d+):(\d+)\])/', $content, $aPlaceholderIds, PREG_SET_ORDER);
		foreach($aPlaceholderIds as $aPlaceholder) {
			/** @var $oQuestionary \Ext_TC_Marketing_Feedback_Questionary */
			$oQuestionary = \Factory::getInstance(\Ext_TC_Marketing_Feedback_Questionary::class, $aPlaceholder[1]);
			$bCheckSubobjectValid = $oQuestionary->checkSubObjectsByJourneyId($aPlaceholder[2]);
			if(!$bCheckSubobjectValid) {
				$errors[] = $l10n->translate('Die Einstellungen des Fragebogens stimmen nicht mit den gebuchten Leistungen überein!');
				break;
			}
		}

		if(empty($errors)) {

			foreach($aPlaceholderIds as $aPlaceholder) {
				// Da Kunden lustig den Feedback-Platzhalter mit IDs kopieren können, können die Kunden ohne Prüfung ziemlichen Müll in der Datenbank erzeugen #10125
				$aJourneyIds = array_map(fn (\Ext_TS_Inquiry_Journey $oJourney) => $oJourney->id, $inquiry->getJourneys());
				if(!in_array($aPlaceholder[2], $aJourneyIds)) {
					$errors[] = $l10n->translate('Der Platzhalter für den Fragebogen ist für diese Buchung nicht gültig.');
					continue;
				}
				// TODO: Wird in manchen Fällen (keine Ahnung welche Bedingung) doppelt aufgerufen
				// (zwei Einträge pro E-Mail in der Datenbank)
				$oFeedbackProcess = new \Ext_TS_Marketing_Feedback_Questionary_Process();
				$oFeedbackProcess->active = 0;
				// TODO wofür?
				$oFeedbackProcess->contact_id = $contact->id; // Achtung, das muss nicht der Bucher sein (Agenturkontakt z.B.)
				$oFeedbackProcess->journey_id = $aPlaceholder[2];
				$oFeedbackProcess->invited = time();
				$oFeedbackProcess->link_key = $oFeedbackProcess->getUniqueKey();
				$oFeedbackProcess->questionary_id = $aPlaceholder[1];
				// TODO wofür?
				$oFeedbackProcess->email = $email;

				if(
					empty($aErrors) &&
					$oFeedbackProcess->validate()
				) {
					$sFeedbackUrl = $inquiry->getSchool()->url_feedback;
					if(strpos($sFeedbackUrl, '?') === false) {
						$sFeedbackUrl .= '?'.self::URL_PARAMETER.'=';
					} else {
						$sFeedbackUrl .= '&'.self::URL_PARAMETER.'=';
					}
					$content = str_replace('[FEEDBACKLINK:'.$aPlaceholder[1].':'.$aPlaceholder[2].']', $sFeedbackUrl . $oFeedbackProcess->link_key, $content);
					$processes[] = $oFeedbackProcess;
				}
			}

		}

		if (empty($errors)) {
			// Erst speichern wenn es auch wirklich keinen Fehler gab
			array_walk($processes, fn ($process) => $process->save());
			return [$errors, $processes];
		}

		return [$errors, []];
	}
}