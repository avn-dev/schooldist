<?php

class Ext_TS_System_Checks_Frontend_FormTemplateFix extends GlobalChecks {

	public function getTitle() {
		return 'Fix Form Template Settings';
	}

	public function getDescription() {
		return $this->getTitle();
	}

	public function executeCheck() {

//		Rückgängig machen
//		\DB::executePreparedQuery("UPDATE `kolumbus_forms_schools` SET `tpl_id` = :newTemplateId WHERE `tpl_id` = :oldTemplateId", ['newTemplateId' => $newTemplateId, 'oldTemplateId' => $oldTemplateId]);
//		\DB::executePreparedQuery("UPDATE `kolumbus_forms_schools` SET `offer_template_id` = :newTemplateId WHERE `offer_template_id` = :oldTemplateId", ['newTemplateId' => $newTemplateId, 'oldTemplateId' => $oldTemplateId]);
		
		$originalTable = 'kolumbus_forms_schools';
		
		// Veröffentlichungsdatum des betroffenen Updates
		$minDate = new \DateTime('2025-10-31 00:00:00');
		$latestBackupTable = \Util::getLatestBackupTable($originalTable, $minDate);
		
		if($latestBackupTable !== null) {
			$sql = "
				UPDATE 
					#originalTable AS t JOIN 
					#latestBackupTable AS b ON 
						t.form_id = b.form_id AND
						t.school_id = b.school_id
				SET 
					t.tpl_id = b.tpl_id,
					t.offer_template_id = b.offer_template_id
			";
			\DB::executePreparedQuery($sql, ['originalTable'=>$originalTable,  'latestBackupTable'=>$latestBackupTable]);
		}
		
		return true;
	}

}