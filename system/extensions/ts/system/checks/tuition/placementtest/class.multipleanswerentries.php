<?php


class Ext_TS_System_Checks_Tuition_Placementtest_MultipleAnswerEntries extends GlobalChecks
{

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return 'Fixing a bug with multiple entries for an answer..';
	}

	/**
	 * @return string
	 */

	public function getDescription()
	{
		return self::getTitle();
	}

	/**
	 * Bug: Es gibt Antwort-Einträge, wo eine Antwort angeblich mehrmals gegeben wurde, was ja nicht geht, z.B: ["268","268"].
	 * -> Alle Antworten holen, wo der Bug (s.o.) existiert
	 * -> Antworten fixen (keine doppelten Antworten mehr)
	 * @return boolean
	 */
	public function executeCheck()
	{
		set_time_limit(3600);

		ini_set('memory_limit', '8G');

		$success = Ext_Thebing_Util::backupTable('ts_placementtests_results_details');

		if (!$success) {
			return false;
		}

		// Alle Antworten holen, wo der Bug (s.o.) existiert
		$answers = Ext_Thebing_Placementtests_Results_Details::query()
			->where('value', 'LIKE', '%["%",%')
			->get();

		foreach ($answers as $answer) {
			$question = $answer->getJoinedObject('question');

			// Wenn die Antwort nur ein String sein sollte
			if (
				$question->type == $question::TYPE_TEXT ||
				$question->type == $question::TYPE_TEXTAREA ||
				$question->type == $question::TYPE_SELECT
			) {
				$correctValues = reset($answer->value);
			} else {
				// Wenn die Antwort ein Array bleiben soll (Multiselect/Checkbox)
				$correctValues = array_unique($answer->value);
			}

			$sSql = "
				UPDATE 
					ts_placementtests_results_details 
				SET
					`changed` = `changed`,
					`value` = :value	
				WHERE
					`id` = :id 
			";

			$aSql = ['id' => $answer->id, 'value' => json_encode($correctValues)];

			DB::executePreparedQuery($sSql, $aSql);
		}

		return true;
	}

}