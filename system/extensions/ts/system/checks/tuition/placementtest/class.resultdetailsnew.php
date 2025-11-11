<?php

class Ext_TS_System_Checks_Tuition_Placementtest_ResultDetailsNew extends GlobalChecks {

	/**
	 * @return string
	 */
	public function getTitle() {
		return 'Old placementtest result details conversion v2.0';
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return self::getTitle();
	}

	public function executeCheck()
	{

		set_time_limit(3600);
		ini_set('memory_limit', '8G');

		// Wenn der NewStructure-Check noch nicht ausgeführt wurde
		if(!Util::checkTableExists('ts_placementtests')) {
			return false;
		}

		$now = new DateTime();
		$twoYearsAgo = $now->modify('-2 years')->format('Y-m-d H:i:s');

		$details = \Ext_Thebing_Placementtests_Results_Details::query()->orderBy('id', 'desc')->where('created', '>', $twoYearsAgo)->get();

		foreach ($details as $detail) {

			$detail->evaluateAnswer();

			$sSql = "
					UPDATE 
						ts_placementtests_results_details 
					SET
						`changed` = `changed`,
						`answer_is_right` = :answer_is_right,	
						`answer_correctness` = :answer_correctness
					WHERE
						`id` = :id 
				";

			$aSql = [
				'id' => $detail->id,
				'answer_is_right' => $detail->answer_is_right,
				'answer_correctness' => $detail->answer_correctness,
			];

			DB::executePreparedQuery($sSql, $aSql);
		}

		$placementtestResults = \Ext_Thebing_Placementtests_Results::query()
			->orderBy('id', 'desc')
			->where('created', '>', $twoYearsAgo)
			->withTrashed() // Kein placementtest_id = 0
			->get();

		foreach ($placementtestResults as $placementtestResult) {
			$placementtestResult->placementtest_id = $placementtestResult->getInquiry()->getSchool()->id;

			$placementtestResult->evaluateResult();

			$sSql = "
					UPDATE 
						ts_placementtests_results 
					SET
						`changed` = `changed`,
						`result_summary` = :result_summary,
						`questions_answered` = :questions_answered,
						`questions_answered_correct` = :questions_answered_correct,
						`level_id` = :level_id,
						`placementtest_id` = :placementtest_id
					WHERE
						`id` = :id 
					";

			$aSql = [
				'id' => $placementtestResult->id,
				'placementtest_id' => $placementtestResult->placementtest_id,
				'result_summary' => json_encode($placementtestResult->result_summary),
				'questions_answered' => $placementtestResult->questions_answered,
				'questions_answered_correct' => $placementtestResult->questions_answered_correct,
				'level_id' => $placementtestResult->level_id,
			];

			DB::executePreparedQuery($sSql, $aSql);

		}

		return true;
	}

}