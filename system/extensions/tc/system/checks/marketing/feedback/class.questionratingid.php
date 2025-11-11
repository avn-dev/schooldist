<?php

class Ext_TC_System_Checks_Marketing_Feedback_QuestionRatingId extends GlobalChecks {

	public function getTitle() {
		return 'Feedback Questions Update';
	}

	public function getDescription() {
		return 'Update structure of feedback question ratings.';
	}

	public function executeCheck() {

		if(!DB::getDefaultConnection()->checkField('tc_feedback_questionaries_childs_questions_groups_questions', 'rating_id', true)) {
			return true;
		}

		if(!Util::backupTable('tc_feedback_questionaries_childs_questions_groups_questions')) {
			throw new Exception('Backup error!');
		}

		DB::addField('tc_feedback_questions', 'rating_id', 'SMALLINT UNSIGNED NOT NULL', 'quantity_stars');

		DB::begin(__CLASS__);

		$sSql = "
			SELECT
				`tc_fqu`.`id`,
				`tc_fqcqgq`.`rating_id`
			FROM
				`tc_feedback_questions` `tc_fqu` INNER JOIN
				`tc_feedback_questionaries_childs_questions_groups_questions` `tc_fqcqgq` ON
					`tc_fqcqgq`.`question_id` = `tc_fqu`.`id` AND
					`tc_fqcqgq`.`active` = 1
			WHERE
				`tc_fqu`.`question_type` = 'rating'
			GROUP BY
				`tc_fqu`.`id`
		";

		$aResult = (array)DB::getQueryRows($sSql);

		foreach($aResult as $aRow) {

			$sSql = "
					UPDATE
						`tc_feedback_questions`
					SET
						`rating_id` = :rating_id
					WHERE
						`id` = :id
				";

			DB::executePreparedQuery($sSql, $aRow);

		}

		DB::commit(__CLASS__);

		DB::executeQuery(" ALTER TABLE `tc_feedback_questionaries_childs_questions_groups_questions` DROP `rating_id` ");

		return true;

	}

}
