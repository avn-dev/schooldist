<?php

/**
 * Class Ext_Thebing_Placementtests_Notices
 *
 * @property string id
 * @property string created
 * @property string changed
 * @property string active
 * @property string creator_id
 * @property string editor_id
 * @property string result_details_id
 * @property string answer_id
 * @property string commentary
*/
class Ext_Thebing_Placementtests_Notices extends Ext_Thebing_Basic {

	// Tabellenname
	protected $_sTable = 'ts_placementtests_results_details_notices';

	// Tablealias
	protected $_sTableAlias = 'ts_prdn';

	public static function getNoticeByResultAndQuestion($resultId, $questionId) {
		return Ext_Thebing_Placementtests_Notices::query()
			->where('result_id', $resultId)
			->where('question_id', $questionId)
			->get()
			->first();
	}

}