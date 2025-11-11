<?php

class Ext_Thebing_Pdf_Template_Search {

	/**
	 * Methode sucht nach Template-Typen
	 * Optional kann hier auch ein Array mit mehreren Templates übergeben werden
	 *
	 * @param string $mType
	 * @param string $sLanguage
	 * @param int|int[]|bool $mSchool
	 * @param int $iInbox
	 * @param bool $bPrepareForSelect
	 * @return array
	 */
	static public function s($mType = 'document_invoice_customer', $sLanguage = 'en', $mSchool = null, $iInbox = null, $bPrepareForSelect = false) {

		$sWhere = "";
		$aSql = array(
			'inbox_id' => (int)$iInbox
		);

		if($mSchool !== false) {
			// Altes Verhalten!
			if($mSchool === null) {
				$mSchool = \Core\Handler\SessionHandler::getInstance()->get('sid');
			}

			$aSql['schools'] = (array)$mSchool;

			$sWhere .= " AND `kpts`.`school_id` IN ( :schools ) ";
		}

		if($sLanguage !== false) {
			if(is_array($sLanguage)) {
				$sWhere .= " AND `kptl`.`iso_language` IN(:iso_language) ";
			} else {
				$sWhere .= " AND `kptl`.`iso_language` = :iso_language ";
			}

			$aSql['iso_language'] = $sLanguage;
		}

		if(!empty($iInbox)) {
			$sWhere .= " AND `kpti`.`inbox_id` = :inbox_id";
		}

		if(is_array($mType)) {
			$sSearch = "'" . implode("', '", $mType) . "'";
		} else {
			$sSearch = "'" . $mType . "'";
		}
		$sSql = "
			SELECT
				`kpt`.*
			FROM
				`kolumbus_pdf_templates` `kpt` INNER JOIN
				`kolumbus_pdf_templates_schools` `kpts` ON
					`kpts`.`template_id` = `kpt`.`id` LEFT JOIN
				`kolumbus_pdf_templates_inboxes` `kpti` ON
					`kpti`.`template_id` = `kpt`.`id` INNER JOIN
				`kolumbus_pdf_templates_languages` `kptl` ON
					`kptl`.`template_id` = `kpt`.`id`
			WHERE
				`kpt`.`type` IN (" . $sSearch . ")   AND
				`kpt`.`active` = 1
				".$sWhere."
			ORDER BY
				`kpt`.`name`
		";

		$aResult = (array)DB::getQueryRows($sSql, $aSql);
		$aTemplates = array();

		foreach($aResult as $aTemplate) {
			$aTemplates[] = Ext_Thebing_Pdf_Template::getObjectFromArray($aTemplate);
		}

		// Benutzer muss Recht auf Inbox haben, wenn diese Rechteprüfung aktiv ist
		if(
			!empty($iInbox) &&
			System::d('ts_check_inbox_rights_for_document_templates')
		) {
			foreach($aTemplates as $iKey => $oTemplate) {

				// Da jedes Template mehrere Inboxen haben kann, müssen auch alle geprüft werden
				foreach((array)$oTemplate->inboxes as $iInboxId) {
					if(Ext_Thebing_Access::hasRight('thebing_invoice_inbox_'.$iInboxId)) {
						continue 2;
					}
				}

				unset($aTemplates[$iKey]);
			}
		}

		$aReturn = array();
		if($bPrepareForSelect) {
			foreach($aTemplates as $oTemplate) {
				$aReturn[$oTemplate->id] = $oTemplate->getName();
			}
		} else {
			$aReturn = $aTemplates;
		}

		return $aReturn;
	}
}
