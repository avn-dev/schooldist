<?php

/**
 * @property int $id
 * @property int $grouping_id
 * @property int $block_id
 * @property int $teacher_id
 * @property int $salary_id
 * @property string $timepoint (DATE)
 * @property string $created (TIMESTAMP)
 * @property string $comment
 * @property string $payment_note
 * @property int $method_id
 * @property int $active
 * @property int $creator_id
 * @property int $user_id
 * @property float $amount
 * @property float $amount_school
 * @property int $payment_currency_id
 * @property int $school_currency_id
 * @property int $transaction_id
 * @property string $payment_type
 * @property string $date (DATE)
 * @property int $imported
 * @property int $parent_id
 * @property int $inquiry_id
 * @property float $salary_lessons
 * @property string $salary_lessons_period
 * @property string $hours
 * @property int $calculation
 * @property string $course_list CSV
 * @property string $block_list CSV
 */
class Ext_Thebing_Teacher_Payment extends Ext_Thebing_Payment_Provider_Abstract {

	protected $_sTable = 'ts_teachers_payments';

	/**
	 * @inheritdoc
	 */
	public function save($bLog = true) {

		if(
			strlen($this->course_list) > 1024 ||
			strlen($this->block_list) > 1024
		) {
			throw new RuntimeException('course_list or block_list too long!');
		}

		return parent::save($bLog);

	}

	/**
	 * @param int[] $aBlockIds
	 * @return self[]
	 */
	public static function searchByBlockIds($aBlockIds) {

		$sSql = "
			SELECT
				*
			FROM
				`ts_teachers_payments`
			WHERE
				`active` = 1 AND
				`block_id` IN(:block_ids)
		";

		$aResult = (array)DB::getQueryRows($sSql, ['block_ids' => $aBlockIds]);

		$aResult = array_map(function($aRow) {
			return static::getObjectFromArray($aRow);
		}, $aResult);

		return $aResult;

	}

	public static function searchByBlockTemplate($iTemplateId) {

		$oSelf		= new self();
		$sTable		= $oSelf->getTableName();

		$sSql = "
			SELECT
				`ktep`.`id`,
				`ktb`.`id` `block_id`
			FROM
				`kolumbus_tuition_blocks` `ktb` INNER JOIN
				#table `ktep` ON
					`ktep`.`block_id` = `ktb`.`id`
			WHERE
				`ktb`.`template_id` = :template_id AND
				`ktb`.`active` = 1 AND
				`ktep`.`active` = 1
		";

		$aSql = array(
			'template_id'	=> (int)$iTemplateId,
			'table'			=> $sTable
		);

		$aResult = (array)DB::getPreparedQueryData($sSql, $aSql);

		return $aResult;
	}

	protected function _getUniqueFields() {
		return array(
			'block_id',
			'teacher_id',
			'timepoint',
		);
	}
	
	/**
	 * @return Ext_Thebing_School_Tuition_Block
	 */
	public function getBlock() {
		$iBlockId = (int)$this->block_id;
		
		$oBlock = Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);
		
		return $oBlock;
	}
	
	public function getFromDate() {

		if($this->payment_type === 'month') {
			$dDate = new DateTime($this->timepoint);
			$dDate->modify('first day of this month');
			return new WDDate($dDate->format('Y-m-d'), WDDate::DB_DATE);
		}

		$oFrom = new WDDate($this->timepoint, WDDate::DB_DATE);
		$oBlock = $this->getBlock();
		$aDays= $oBlock->days;
		if (!empty($aDays)) {
			$iMaxDay = min($aDays);
			$iDayToAdd = $iMaxDay - 1;
			$oFrom->add($iDayToAdd, WDDate::DAY);
		}

		return $oFrom;
	}
	
	public function getUntilDate() {

		if($this->payment_type === 'month') {
			$dDate = new DateTime($this->timepoint);
			$dDate->modify('last day of this month');
			return new WDDate($dDate->format('Y-m-d'), WDDate::DB_DATE);
		}

		$oDateUntil	= new WDDate($this->timepoint, WDDate::DB_DATE);
		$oBlock = $this->getBlock();
		$aDays= $oBlock->days;
		if (!empty($aDays)) {
			$iMaxDay = max($aDays);
			$iDayToAdd = $iMaxDay - 1;
			$oDateUntil->add($iDayToAdd, WDDate::DAY);
		}

		return $oDateUntil;
	}

	/**
	 * Liefert die Kalenderwoche oder den Monat
	 * @return string
	 * @throws Exception
	 */
	public function getTimeFrameInformation() {

		$oDate = new WDDate($this->timepoint, WDDate::DB_DATE);

		if(
			$this->payment_type === 'week' ||
			$this->payment_type === 'fix_week'
		) {
			$sReturn = $oDate->get(WDDate::WEEK);
		} elseif($this->payment_type === 'month') {
			$sReturn = $oDate->get(WDDate::STRFTIME, '%B %Y');
		} else {
			throw new Exception('Unknown payment type!');
		}

		return $sReturn;
	}

	/**
	 * Liefert alle Bezahlmethoden, die zur Schule und Lehrereinstellung passen
	 *
	 * @param bool $bEmptyItem
	 * @param bool $bReturnWithDefault
	 * @return array
	 */
	public static function getMatchingPaymentMethods($bEmptyItem = false, $bReturnWithDefault = false) {
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$oClient = $oSchool->getClient();
		$iCurrencySchoolId = $oSchool->getCurrency();
		$iCurrencyTeacherId	= $oSchool->getTeacherCurrency();
		$aMethods = $oSchool->getPaymentMethodList(true);
		$iDefaultMethod = 0;

		if($bEmptyItem) {
			$aMethods = Ext_Thebing_Util::addEmptyItem($aMethods);
		}

		if(!$bReturnWithDefault) {
			$aReturn = $aMethods;
		} else {
			$aReturn = array(
				'methods' => $aMethods,
				'default_method' => $iDefaultMethod
			);
		}

		return $aReturn;
	}
}
