<?php

namespace TsMobile\Generator\Pages;

use TsMobile\Generator\AbstractPage;
use TsStudentApp\Entity\AppContent;

class Faq extends AbstractPage {

	public function getStorageData() {

		$aData = array();

		$aFaqEntries = $this->getFaqEntries();
		$aData['sets'][0]['items'] = $aFaqEntries;

		return $aData;

	}

	/**
	 * Einträge direkt über Query holen, da Objekte hier keinen Sinn machen (return 1:1)
	 *
	 * @return array
	 */
	public function getFaqEntries() {

		$sSql = "
			SELECT
				`ts_afe`.`id`,
				`ts_afe_i18n`.`title`,
				`ts_afe_i18n`.`content`
			FROM
				`ts_student_app_contents` `ts_afe` LEFT JOIN
				`ts_student_app_contents_i18n` `ts_afe_i18n` ON
					`ts_afe_i18n`.`entry_id` = `ts_afe`.`id` AND
					`ts_afe_i18n`.`language_iso` = :language_iso
			WHERE
				`ts_afe`.`active` = 1 AND
				`ts_afe`.`released` = 1 AND
				`ts_afe`.`type` = :type AND
				`ts_afe`.`school_Id` = :school_id
			ORDER BY
				`ts_afe`.`position`
		";

		$aSql = array(
			'language_iso' => $this->_sInterfaceLanguage,
			'type' => \TsStudentApp\Enums\AppContentType::FAQ->value,
			'school_id' => $this->_oSchool->id
		);

		$aResult = (array)\DB::getQueryRows($sSql, $aSql);

		return $aResult;

	}

}