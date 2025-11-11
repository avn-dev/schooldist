<?php

class Ext_Thebing_System_Checks_Templates_CleanOptionValues extends GlobalChecks {

	public function getTitle() {
		return 'Clean old PDF template options';
	}

	public function getDescription() {
		return 'Clean old PDF template options which are not assigned to a selected school anymore.';
	}

	public function executeCheck() {

		Util::backupTable('kolumbus_pdf_templates_options');

		// Wurde mal vor einer Ewigkeit auf eine andere Tabelle umgestellt (steht auch immer nur Array drin)
		DB::executeQuery("DELETE FROM `kolumbus_pdf_templates_options` WHERE `option` = 'attachments'");
		$iDeletedRows = DB::fetchAffectedRows();

		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_pdf_templates`
		";

		$aTemplates = DB::getQueryRows($sSql);

		foreach($aTemplates as $aTemplate) {

			$oTemplate = Ext_Thebing_Pdf_Template::getObjectFromArray($aTemplate);

			$sSql = "
				DELETE FROM
					`kolumbus_pdf_templates_options`
				WHERE
					`template_id` = :template_id AND
					`school_id` NOT IN (:school_ids)
			";

			DB::executePreparedQuery($sSql, [
				'template_id' => $oTemplate->id,
				'school_ids' => $oTemplate->schools
			]);

			$iDeletedRows += DB::fetchAffectedRows();

		}

		$this->logInfo('Deleted '.$iDeletedRows.' old PDF template options (school not longer assigned to template)');

		return true;

	}

}
