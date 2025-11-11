<?php

class Ext_TC_System_Checks_Marketing_Feedback_CorrectOverallSatisfaction extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Correct overall satisfaction';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Correct overall satisfaction in questionary results';
		return $sDescription;
	}

	public function executeCheck() {

		if(!Util::backupTable('tc_feedback_questionaries_processes')) {
	    	throw new Exception('Backup error!');
		}

		$sSql = "
			SELECT
				`tc_fqpr`.*,
				`tc_fq`.`question_type`,
				`tc_fq`.`quantity_stars`,
				`tc_fqcpgq`.`rating_id`
			FROM
				`tc_feedback_questionaries_processes` `tc_fqp` INNER JOIN
				`tc_feedback_questionaries_processes_results` `tc_fqpr` ON
					`tc_fqpr`.`questionary_process_id` = `tc_fqp`.`id` AND
					`tc_fqpr`.`active` = 1 INNER JOIN
				`tc_feedback_questionaries_childs_questions_groups_questions` `tc_fqcpgq` ON
					`tc_fqcpgq`.`id` = `tc_fqpr`.`questionary_question_group_question_id` AND
					`tc_fqcpgq`.`active` = 1 INNER JOIN
				`tc_feedback_questions` `tc_fq` ON
					`tc_fq`.`id` = `tc_fqcpgq`.`question_id` AND
					`tc_fq`.`overall_satisfaction` = 1 AND
					`tc_fq`.`active` = 1
			WHERE
				`tc_fqp`.`active` = 1 AND
				`tc_fqp`.`answered` != '0000-00-00'
		";
		$aQuestionaryResults = DB::getPreparedQueryData($sSql, []);

		$aTotalStatisfactionValue = [];
		$aTotalStatisfactionQuestionQty = [];

		foreach($aQuestionaryResults as $aQuestionaryResult) {
			switch($aQuestionaryResult['question_type']) {
				case 'stars':
					// Ein Stern soll als 0% gewertet werden
					$answer = ($aQuestionaryResult['answer'] - 1) * (100 / ($aQuestionaryResult['quantity_stars'] - 1));
					break;
				case 'rating':
					$oRating = Ext_TC_Marketing_Feedback_Rating::getInstance($aQuestionaryResult['rating_id']);
					$answer = ($aQuestionaryResult['answer'] - 1) * (100 / ($oRating->getMaxValue() - 1));
					break;
			}
			$aTotalStatisfactionValue[$aQuestionaryResult['questionary_process_id']] += (int)$answer;
			$aTotalStatisfactionQuestionQty[$aQuestionaryResult['questionary_process_id']] += 1;
		}

		foreach($aTotalStatisfactionValue as $iQuestionaryProcessId => $iTotalStatisfactionValue) {
			$oQuestionaryProcess = Ext_TC_Marketing_Feedback_Questionary_Process::getInstance($iQuestionaryProcessId);
			$oQuestionaryProcess->overall_satisfaction = $iTotalStatisfactionValue / $aTotalStatisfactionQuestionQty[$iQuestionaryProcessId];
			$oQuestionaryProcess->save();
		}

		return true;
	}

}
