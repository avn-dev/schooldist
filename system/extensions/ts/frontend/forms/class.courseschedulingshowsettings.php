<?php

class Ext_TS_Frontend_Forms_CourseSchedulingShowSettings extends \GlobalChecks
{
	private array $settings = [
		'show_duration' => 1,
		'show_weekdays' => 1,
		'show_time' => 1,
	];

	public function getTitle()
	{
		return 'Frontend forms';
	}

	public function getDescription()
	{
		return 'Sets the default display value for scheduling block elements';
	}

	public function executeCheck()
	{
		$blockIds = $this->getSchedulingCourseBlocks();

		if (empty($blockIds)) {
			return true;
		}

		if (!\Util::backupTable('kolumbus_forms_pages_blocks_settings')) {
			__pout('Backup error');
			return false;
		}

		foreach ($blockIds as $blockId) {

			$settings = $this->getSettings($blockId);

			foreach ($this->settings as $setting => $defaultValue) {
				if (!isset($settings[$setting])) {
					\DB::insertData('kolumbus_forms_pages_blocks_settings', [
						'block_id' => $blockId,
						'setting' => $setting,
						'value' => $defaultValue
					]);
				}
			}
		}

		return true;
	}

	private function getSchedulingCourseBlocks(): array
	{
		$sql = "
			SELECT
				`block_id`
			FROM
			    `kolumbus_forms_pages_blocks_settings`
			WHERE
			    `setting` = 'based_on' AND
			    `value` = 'scheduling'
		";

		return (array)\DB::getQueryCol($sql);
	}

	private function getSettings($blockId): array
	{
		$sql = "
			SELECT
				`setting`,
				`value`
			FROM
			    `kolumbus_forms_pages_blocks_settings`
			WHERE
			    `block_id` = :block_id AND
			    `setting` IN (:settings)
		";

		return (array)\DB::getQueryPairs($sql, ['block_id' => $blockId, 'settings' => array_keys($this->settings)]);
	}
}