<?php

/**
 * @property Ext_TS_Inquiry_Journey_Course $oDependencyEntity
 */
class Ext_TS_Numberrange_JourneyCourse extends Ext_TS_NumberRange {

	public $bAllowDuplicateNumbers = true;

	protected $_sNumberTable = 'ts_inquiries_journeys_courses';

	protected $_sNumberField = 'number';

	/**
	 * Wenn %countdaily im Format: Flag setzen, damit die Nummern pro Tag generiert werden
	 *
	 * @var bool
	 */
	private $dailyNumber = false;

	public static function getObject(Ext_TS_Inquiry_Journey_Course $journeyCourse) {

		if ($journeyCourse->getCourse()->numberrange_id) {
			$numberrange = new self($journeyCourse->getCourse()->numberrange_id);
			if (strpos($numberrange->format, '%countdaily') !== false) {
				$numberrange->dailyNumber = true;
				$numberrange->format = str_replace('%countdaily', '%count', $numberrange->format);
			}

			return $numberrange;
		}

		return null;

	}

	protected function executeSearchLatestNumber($sSql, $aSql) {

		$dailyPart = "";
		if ($this->dailyNumber) {
			$aSql['from'] = $this->oDependencyEntity->from;
			$dailyPart .= " AND `from` = :from ";
		}

		$sSql = "
			SELECT
				{$this->buildLatestNumberQuerySelect()}
			FROM
				`ts_inquiries_journeys_courses`
			WHERE
				`numberrange_id` = :numberrange_id AND
				`number` LIKE :pattern
				{$dailyPart}
			ORDER BY
				`last_number` DESC
			LIMIT
				1
		";

		return parent::executeSearchLatestNumber($sSql, $aSql);

	}

}
