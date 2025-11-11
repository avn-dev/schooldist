<?php

namespace TsFrontend\Service\Placeholders;

use Tc\Service\LanguageAbstract;

class PlacementtestService
{
	const URL_PARAMETER = 'r';

	/**
	 * Kopiert und angepasst aus \Ext_Thebing_Communication
	 *
	 * TODO Keine Ahnung ob das so richtig läuft
	 *
	 * @param LanguageAbstract $l10n
	 * @param \Ext_Thebing_School $school
	 * @param string $content
	 * @return array
	 */
	public function generateProcessesForPlaceholders(LanguageAbstract $l10n, \Ext_Thebing_School $school, string &$content)
	{
		$processes = $errors = [];

		preg_match_all('/\[PLACEMENTTESTHALLOAI:(\d+):(\d+)\]/', $content, $aPlaceholderIds, PREG_SET_ORDER);

		foreach($aPlaceholderIds as $aPlaceholder) {

			$inquiryId = $aPlaceholder[2];

			$courseLanguageId = $aPlaceholder[1];

			if($inquiryId !== null) {

				$sLinkKey = '';
				$dNow = new \DateTime();

				$oPlacementTestResult = \Ext_Thebing_Placementtests_Results::getResultByInquiryAndCourseLanguage($inquiryId, $courseLanguageId);

				$placementtest = \TsTuition\Entity\Placementtest::getPlacementtestByCourseLanguage($courseLanguageId);
				$courseLanguageName = \Ext_Thebing_Tuition_LevelGroup::getInstance($courseLanguageId)->getName();

				$courseLanguageErrorMessage = sprintf($l10n->translate('Es existiert kein Einstufungstest für "%s".'), $courseLanguageName);

				if($oPlacementTestResult === null) {

					$oPlacementTestResult = new \Ext_Thebing_Placementtests_Results();
					$sLinkKey = $oPlacementTestResult->getUniqueKey();

					if ($placementtest->id != 0) {
						$oPlacementTestResult->active = 1;
						$oPlacementTestResult->inquiry_id = $inquiryId;
						$oPlacementTestResult->invited = $dNow->format('Y-m-d H:i:s');
						$oPlacementTestResult->key = $sLinkKey;
						$oPlacementTestResult->level_id = 0;
						$oPlacementTestResult->placementtest_date = '0000-00-00';
						$oPlacementTestResult->placementtest_id = $placementtest->id;
						$oPlacementTestResult->courselanguage_id = $courseLanguageId;

						$processes[] = $oPlacementTestResult;

					} else {
						// Bei alten PlacementtestResults, bei denen die Buchung neu eingeladen wird, kommt man hier nicht rein
						// wegen der Abfrage oben, damals gab es diesen Error aber noch nicht, also könnte es sein, dass
						// es Einladungen gibt mit Links zu Placementtests ohne Einträgen
						$errors[$courseLanguageId] = $courseLanguageErrorMessage;
					}

				} else {

					if($oPlacementTestResult->isAnswered()) {
						$inquiry = \Ext_TS_Inquiry::getInstance($inquiryId);
						$errors[$courseLanguageId] = sprintf(
							$l10n->translate('Der Einstufungstest für "%s" wurde von "%s" bereits ausgefüllt.'),
							$courseLanguageName,
							$inquiry->getTraveller()->getName()
						);
					} else {

						if ($placementtest->id == 0) {
							$errors[$courseLanguageId] = $courseLanguageErrorMessage;
						} else {

							$sLinkKey = $oPlacementTestResult->key;

							// TODO: Das müsste irgendwann entfernt werden
							if (empty($sLinkKey)) {
								$sLinkKey = $oPlacementTestResult->getUniqueKey();
								$oPlacementTestResult->key = $sLinkKey;
							}

							$oPlacementTestResult->invited = $dNow->format('Y-m-d H:i:s');

							$processes[] = $oPlacementTestResult;
						}
					}

				}

				if(empty($errors)) {
					try {
						$sPlacementTestUrl = $this->generateUrl($school, $oPlacementTestResult);
						$content = str_replace('[PLACEMENTTESTHALLOAI:' . $courseLanguageId . ':' . $inquiryId . ']', $sPlacementTestUrl . $sLinkKey, $content);
					} catch(\Exception $e) {
						$errors[] = sprintf('%s: %s',
							$l10n->translate('Bei der Generierung der Einstufungstest-Url ist ein Fehler aufgetreten'),
							$e->getMessage()
						);
					}
				}
			}
		}

		if (empty($errors)) {
			// Erst speichern wenn es auch wirklich keinen Fehler gab
			array_walk($processes, fn ($process) => $process->save());
			return [[], $processes];
		}

		return [array_values($errors), []];
	}

	protected function generateUrl(\Ext_Thebing_School $school, \Ext_Thebing_Placementtests_Results $result): string
	{
		$sPlacementTestUrl = $school->url_placementtest;
		if(strpos($sPlacementTestUrl, '?') === false) {
			$sPlacementTestUrl .= '?'.self::URL_PARAMETER.'=';
		} else {
			$sPlacementTestUrl .= '&'.self::URL_PARAMETER.'=';
		}

		return $sPlacementTestUrl;
	}
}