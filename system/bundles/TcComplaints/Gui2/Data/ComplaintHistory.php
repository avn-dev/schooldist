<?php

namespace TcComplaints\Gui2\Data;

use Ext_TC_L10N;

abstract class ComplaintHistory extends \Ext_TC_Gui2_Data {

	/**
	 * @param \Ext_Gui2 $oGui
	 * @return \Ext_Gui2_Dialog
	 * @throws \Exception
	 */
	abstract public static function getDialog(\Ext_Gui2 $oGui);

	/**
	 * Gibt die Statuse der Beschwerden zurück
	 *
	 * @param string $sLanguage
	 * @return array
	 */
	public static function getState($sLanguage = '') {

		$aReturn = array(
			'open' => Ext_TC_L10N::t('Offen', $sLanguage),
			'followup' => Ext_TC_L10N::t('Nachhaken', $sLanguage),
			'wait' => Ext_TC_L10N::t('Warten', $sLanguage),
			'resolved' => Ext_TC_L10N::t('Erledigt', $sLanguage)
		);

		return $aReturn;

	}

	/**
	 * Gibt die Kommentararten für ein Select zurück
	 *
	 * @return array
	 */
	public static function getCommentaryTypesFilterOptions() {
		$aOptions = self::getCommentaryType();
		$aOptions = \Ext_TC_Util::addEmptyItem($aOptions);
		return $aOptions;
	}

	/**
	 * Gibt die Kommentartypen zurück
	 *
	 * @param string $sLanguage
	 * @return array $aReturn
	 */
	public static function getCommentaryType($sLanguage = '') {

		$aReturn = array(
			'student_commentary' => Ext_TC_L10N::t('Schülerkommentar', $sLanguage),
			'employee_commentary' => Ext_TC_L10N::t('Mitarbeiterkommentar', $sLanguage),
			'agency_commentary' => Ext_TC_L10N::t('Agenturkommentar', $sLanguage),
			'provider_commentary' => Ext_TC_L10N::t('Anbieterkommentar', $sLanguage)
		);

		return $aReturn;

	}

}