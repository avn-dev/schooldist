<?php

namespace Gui2\Entity;

use Core\Facade\Cache;

class InfoTextRepository extends \WDBasic_Repository {
	
	public function findLanguageValuesForGuiDialog(\Ext_Gui2 $oGui2, $sDialogId, $sLanguage, $bWithPrivate = false) {

		$sSql = "
			SELECT
				`gui2_di`.`field`,
				`gui2_di_i18n`.`value`,
				`gui2_di`.`private`
			FROM
				`gui2_dialog_infotexts` `gui2_di` INNER JOIN
				`gui2_dialog_infotexts_i18n` `gui2_di_i18n` ON
					`gui2_di_i18n`.`infotext_id` = `gui2_di`.`id` AND
					`gui2_di_i18n`.`language` = :language AND
					`gui2_di_i18n`.`value` != ''
			WHERE
				`gui2_di`.`gui_hash` = :gui_hash AND
				`gui2_di`.`dialog_id` = :dialog_id 
		";

		if($bWithPrivate === false) {
			$sSql .= " AND `gui2_di`.`private` = 0 ";
		}

		$sSql .= "
			GROUP BY
				`gui2_di`.`id`
		";

		return (array) \DB::getPreparedQueryData($sSql, [
			'gui_hash' => $oGui2->hash,
			'dialog_id' => $sDialogId,
			'language' => $sLanguage
		]);
	}
	
}
