<?php

namespace TsActivities\Entity\Activity;

/**
 * @property string $id
 * @property string $changed
 * @property string $created
 * @property string $active
 * @property string $editor_id
 * @property string $creator_id
 * @property string $activity_block_id
 * @property string $start_time
 * @property string $end_time
 * @property string $day
 * @property string $place
 * @property string $comment
 */
class BlockDay extends \Ext_Thebing_Basic {

	protected $_sTable = 'ts_activities_blocks_days';

	protected $_sEditorIdColumn = 'editor_id';

	protected $_sTableAlias = 'ts_actbd';

	protected $_sPlaceholderClass = \TsActivities\Service\Placeholder\BlockDay::class;

	protected $_aJoinTables = [
		'companion' => [
			'table' => 'ts_activities_blocks_days_accompanying_persons',
			'foreign_key_field' => 'user_id',
			'primary_key_field' => 'day_id',
			'class' => \User::class
		]
	];

	public function validate($bThrowExceptions = false) {
		$mErrors = parent::validate($bThrowExceptions);
		if($mErrors === true) {
			$mErrors = [];
			if($this->start_time === $this->end_time) {
				$mErrors['ts_actbd.start_time'] = 'START_AND_END_TOO_CLOSE';
			} elseif ($this->start_time > $this->end_time) {
				$mErrors['ts_actbd.start_time'] = 'START_BIGGER_THAN_END';
			}
		}

		return $mErrors;

	}

	public function getDay()
	{

		$days = \Ext_Thebing_Util::getLocaleDays(null, 'wide');

		return $days[$this->day];
	}

	public function getCompanion() {

		$users = $this->getJoinTableObjects('companion');

		if(!empty($users)) {
			foreach ($users as $user) {
				$names[] = $user->getName();
			}
			return implode('; ', $names);
		} else {
			return \L10N::t('Es ist kein Begleiter zu der vorgegebenen Zeit vorhanden');
		}
	}

}
