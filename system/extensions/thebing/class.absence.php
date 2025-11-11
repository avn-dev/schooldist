<?php

/**
 * @todo statt über die Session den Item-type herauszufinden, setTableData benutzen oder anders da rankommen
 */
class Ext_Thebing_Absence extends Ext_Thebing_Basic {
	use \Core\Traits\WdBasic\MetableTrait;

	protected $_iDays;

	protected $_aItems = array();

	protected $_sTable = 'kolumbus_absence';

	/**
	 * @TODO ENTFERNEN
	 *
	 * @param int $iDataID
	 * @param null $sTable
	 */
	public function  __construct($iDataID = 0, $sTable = null) {
		global $_VARS;
		
		parent::__construct($iDataID, $sTable);

		// Default values
		if($iDataID == 0) {
			$this->_iDays = 1;
			if(isset($_VARS['date'])) {
				$this->from = $_VARS['date'];
			}
			if(isset($_VARS['parent_gui_id'])) {
				// wegen unterkunftsblockierung rausgenommen
				//$this->item_id = (int)$_VARS['parent_gui_id'];
			}
			if(
				isset($_VARS['hash']) &&
				isset($_SESSION['thebing']['absence'][$_VARS['hash']]['item'])
			) {
				$this->item = $_SESSION['thebing']['absence'][$_VARS['hash']]['item'];
			}
		} else {
			$oDate = new WDDate($this->until, WDDate::DB_DATE);
			$this->_iDays = $oDate->getDiff(WDDate::DAY, $this->from, WDDate::DB_DATE);
			$this->_iDays++;
		}

	}

	public function __set($sName, $mValue){

		if($sName == 'days') {
			$this->_iDays = (int)$mValue;
		} elseif($sName == 'name') {

		} else {
			parent::__set($sName, $mValue);
		}

	}

	public function __get($sName){

		Ext_Gui2_Index_Registry::set($this);
		
		$mValue = '';

		if($sName == 'days') {
			$mValue = $this->_iDays;
		} elseif($sName == 'name') {
			if(
				$this->item &&
				$this->item_id
			) {
				$sClassName = 'Ext_Thebing_'.ucfirst($this->item);
				$oItem = call_user_func(array($sClassName, 'getInstance'), $this->item_id);
				$mValue = $oItem->name;
			}
		} else {
			$mValue = parent::__get($sName);
		}

		return $mValue;

	}

	public function setUntil() {

		if(
			$this->from &&
			$this->_iDays > 0
		) {
			$oDate = new WDDate($this->from, WDDate::DB_DATE);
			$oDate->add(($this->_iDays-1), WDDate::DAY);
			$this->until = $oDate->get(WDDate::DB_DATE);
		}

	}

	public function setItems($aItems) {
		$this->_aItems = (array)$aItems;
	}

	/**
	 * Get absences list data for every employee
	 *
	 * @param int $iMonth
	 * @param int $iYear
	 */
	public function getAbsencesList($iMonth, $iYear) {

		$oSchool = Ext_Thebing_School::getSchoolFromSession();

		$aTemp = Ext_Thebing_Absence_Category::getList();
		$aCategories = array();
		foreach((array)$aTemp as $aCategory) {
			$aCategories[$aCategory['id']] = $aCategory;
		}

		$iMonth = str_pad($iMonth, 2, '0', STR_PAD_LEFT);
		$sDateFrom = $iYear . '-' . $iMonth . '-01 00:00:00';

		$oDate = new WDDate($sDateFrom, WDDate::DB_TIMESTAMP);
		$oOriginalFrom	= new WDDate($sDateFrom, WDDate::DB_TIMESTAMP);

		//$oDate->sub(1, WDDate::MONTH);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Create days array

		$oTempDate = new WDDate($oDate);
		$oUntilDate = new WDDate($oDate);
		$oUntilDate->add(3, WDDate::MONTH);
		$oUntilDate->sub(1, WDDate::SECOND);

		$iTemp = 0;

		$aData = $aIndex = $aMonth = array();

		//Prüfen ob in dieser Liste Schulferien angezeigt werden dürfen oder nicht
		$bShowSchoolHolidays = true;

		if(
			isset($_SESSION['thebing']['absence']['item']) &&
			$_SESSION['thebing']['absence']['item'] == 'holiday'
		){
			$bShowSchoolHolidays = false;
		}

		$aFullHolidays = $oSchool->getHolidays($oDate->get(WDDate::TIMESTAMP), $oUntilDate->get(WDDate::TIMESTAMP), $bShowSchoolHolidays);

		// Ferientage umformatieren da jährliche Feiertage immer markiert werden müssen
		$iYearFrom = $oDate->get(WDDate::YEAR);
		$iYearUntil = $oUntilDate->get(WDDate::YEAR);
		$aHolidays = array();
		foreach((array)$aFullHolidays as $aFullHoliday) {

			$sDateKey = $aFullHoliday['date'];
			$sType = $aFullHoliday['category_id'];

			$oDateHoliday = new WDDate($sDateKey, WDDate::DB_DATE);
			$iHolidayYear = (int)$oDateHoliday->get(WDDate::YEAR);

			if($aFullHoliday['annual'] == 1) {
				$oDateHoliday->set($iYearFrom, WDDate::YEAR);
				$sConvertedDate = $oDateHoliday->get(WDDate::DB_DATE);
				$aHolidays[$sConvertedDate] = $sType;
				$oDateHoliday->set($iYearUntil, WDDate::YEAR);
				$sConvertedDate = $oDateHoliday->get(WDDate::DB_DATE);
				$aHolidays[$sConvertedDate] = $sType;
			} else {
				$aHolidays[$sDateKey] = $sType;
			}

		}

		while($iTemp != 3) {

			$iMonth	= $oDate->get(WDDate::MONTH);
			$iYear	= $oDate->get(WDDate::YEAR);
			$sColor	= '';
			$aLimit = $oDate->getMonthLimits();

			if($oDate->get(WDDate::WEEKDAY) == 6 || $oDate->get(WDDate::WEEKDAY) == 7)
			{
				$sColor = $aCategories['-3']['color'];
			}

			if(array_key_exists($oDate->get(WDDate::DB_DATE), (array)$aHolidays))
			{
				$sColor = $aCategories[$aHolidays[$oDate->get(WDDate::DB_DATE)]]['color'];
			}

			$aMonth[] = array('day' => $oDate->get(WDDate::DAY), 'date_f' => Ext_Thebing_Format::LocalDate($oDate->get(WDDate::TIMESTAMP)), 'date' => $oDate->get(WDDate::DB_DATE), 'color' => $sColor);

			$oDate->add(1, WDDate::DAY);

			if($iMonth != $oDate->get(WDDate::MONTH))
			{
				$aData[$iTemp] = array(
					'year'	=> $iYear,
					'month'	=> $iMonth,
					'from'	=> $aLimit['start'],
					'till'	=> $aLimit['end'],
					'days'	=> $aMonth
				);
				$aIndex[$iYear.$iMonth] = $iTemp;

				$aMonth = array();

				$iTemp++;
			}

		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Get free dates

		$oFromDate = new WDDate($oTempDate);

		$aItemIds = array_keys($this->_aItems);

		// Alle Eintrage für alle Items in diesem Zeitraum holen
		$aEntries = $this->getEntries($oFromDate, $oUntilDate, $aItemIds);

		$aItems = array();

		foreach((array)$this->_aItems as $iKey => $sName)
		{
			$aItem = array();
			$aItem['id'] = $iKey;
			$aItem['name'] = $sName;

			$aTempData = $aData;

			$aDates = $aEntries[$aItem['id']] ?? [];

			// Each all free dates of 3 month
			foreach((array)$aDates as $iIndex => $aDate) {

				$sColor = $aCategories[$aDate['category_id']]['color'];

				$oDateFrom = new WDDate($aDate['from'], WDDate::DB_DATE);
				$oDateUntil = new WDDate($aDate['until'], WDDate::DB_DATE);

				$iLastMonth = $oDateFrom->get(WDDate::MONTH);

				$iDays = $oDateUntil->getDiff(WDDate::DAY, $oDateFrom);
				$iDays++;

				while($oDateFrom->compare($oDateUntil->get(WDDate::DB_DATE), WDDate::DB_DATE) <= 0) {

					$iTempM = $aIndex[$oDateFrom->get(WDDate::YEAR).$oDateFrom->get(WDDate::MONTH)];

					if(
						$oDateFrom->getDiff(WDDate::DAY, $oOriginalFrom->get(WDDate::TIMESTAMP), WDDate::TIMESTAMP) < 0
					){
						//Fix falls Ferien letzes Jahr anfangen und bis ins nächste Jahr gehen && der Monat vom letzen Jahr sich nicht in aIndex befindet
						$iTempM		= $aIndex[$oOriginalFrom->get(WDDate::YEAR).$oOriginalFrom->get(WDDate::MONTH)];
						$oDateFrom	= new WDDate($sDateFrom, WDDate::DB_TIMESTAMP);
						$iDays		= (int)$oDateUntil->getDiff(WDDate::DAY, $oDateFrom->get(WDDate::TIMESTAMP), WDDate::TIMESTAMP) + 1;
					}

					$iTempDay = $oDateFrom->get(WDDate::DAY) - 1;

					if(!isset($aTempData[$iTempM]['days'][$iTempDay])) {
						break;
					}

					if(
						isset($aTempData[$iTempM]['days'][$iTempDay])
					) {

						$aEntry = array('days'=>$iDays, 'quote' => 100, 'color' => $sColor, 'category_id'=>$aDate['category_id'], 'id'=>$aDate['id']);
						$aTempData[$iTempM]['days'][$iTempDay]['entry'] = $aEntry;

					}

					$oDateFrom->add(1, WDDate::DAY);

					if($iLastMonth != $oDateFrom->get(WDDate::MONTH))
					{
						$iLastMonth = $oDateFrom->get(WDDate::MONTH);
						$iTempM++;
					}
				}
			}

			$aItem['months'] = array_values($aTempData);
			$aItems[] = $aItem;
			
		}

		$aReturn = array(
			'head'	=> $aData,
			'data'	=> $aItems
		);

		return $aReturn;

	}

	public function getEntries($oFrom, $oUntil, $aItemIds, $sItem = '') {
		
		global $_VARS;

		$sWhereAddon = '';
		
		$aSql = array();

		if(
			isset($_VARS['hash']) &&
			$sItem == ''
		) {
			$sItem = (string)$_SESSION['thebing']['absence'][$_VARS['hash']]['item'];
		}

		if($sItem == ''){
			$sItem = $_SESSION['thebing']['absence']['item'];
		}

		$aSql['item'] = $sItem;
		$aSql['item_ids'] = (array)$aItemIds;
		
		if(is_numeric($oFrom))
		{
			$oFrom = Ext_TC_Util::getDateTimeByUnixTimestamp($oFrom);
		}
		
		if(is_numeric($oUntil))
		{
			$oUntil = Ext_TC_Util::getDateTimeByUnixTimestamp($oUntil);
		}

		if(
			is_object($oFrom) && 
			is_object($oUntil)
		){
			if($oFrom instanceof DateTime){
				$aSql['from']	= $oFrom->format('Y-m-d');
			} else {
				$aSql['from']	= $oFrom->get(WDDate::DB_DATE);
			}
			if($oUntil instanceof DateTime){
				$aSql['until']	= $oUntil->format('Y-m-d');
			} else {
				$aSql['until']	= $oUntil->get(WDDate::DB_DATE);
			}
			$sWhereAddon .= ' AND `kab`.`from` <= :until ';
			$sWhereAddon .= ' AND `kab`.`until` >= :from ';
		}
		
		$sSql = "
				SELECT
					`kab`.*,
					UNIX_TIMESTAMP(`kab`.`from`) `from_timestamp`,
					UNIX_TIMESTAMP(`kab`.`until`) `until_timestamp`,
					`kabc`.`name` `category`
				FROM
					`kolumbus_absence` `kab` LEFT JOIN
					`kolumbus_absence_categories` `kabc` ON
						`kabc`.`id` = `kab`.`category_id` AND
						`kabc`.`active` = 1
				WHERE
					`kab`.`active` = 1 AND
					`kab`.`item` = :item AND
					`kab`.`item_id` IN (:item_ids)
					".$sWhereAddon."
				ORDER BY
					`from` ASC
			";

		$aItems = DB::getQueryRows($sSql, $aSql);

		$aEntries = array();
		foreach((array)$aItems as $aItem) {		
			$aEntries[$aItem['item_id']][] = $aItem;
		}

		return $aEntries;
	}

	/**
	 * Liefert alle Abwesenheitstage zu einem Item die einen Zeitpunkt berühren
	 * Sollte NICHT mehr verwendet werden, da viel zu unperformant!
	 * @param type $sItem
	 * @param type $iItem
	 * @param WDDate $mFrom
	 * @param WDDate $mUntil
	 * @return type 
	 */
	/*
	public static function getDays($sItem, $iItem, $mFrom = false, $mUntil = false){
		
		$oAbsence = new self();

		if($mFrom >= 0){
			$mFrom = new WDDate($mFrom);
		}

		if($mUntil >= 0){
			$mUntil = new WDDate($mUntil);
		}

		$sFrom			= $mFrom->get(WDDate::TIMESTAMP);
		$sUntil			= $mUntil->get(WDDate::TIMESTAMP);
		
		// Caching
		if(!isset(self::$aCache['days'][$sItem][$iItem][$sFrom][$sUntil])) {

			$aEntries = $oAbsence->getEntries($mFrom, $mUntil, array((int)$iItem), $sItem);

			$aBack = array();

			$oWDDateFrom	= new WDDate();
			$oWDDateUntil	= new WDDate();
				
			foreach((array)$aEntries[$iItem] as $iKey => $aEntry){

				$oWDDateFrom->set($aEntry['from'], WDDate::DB_DATE);
				$oWDDateUntil->set($aEntry['until'], WDDate::DB_DATE);

				$iFrom			= $oWDDateFrom->get(WDDate::TIMESTAMP);
				$iUntil			= $oWDDateUntil->get(WDDate::TIMESTAMP);

				do {

					$aBack[$iFrom] = $iFrom;
					$oWDDateFrom->add(1, WDDate::DAY);
					$iFrom = $oWDDateFrom->get(WDDate::TIMESTAMP);

				} while ($iFrom <= $iUntil);

			}

			sort($aBack, SORT_NUMERIC);

			self::$aCache['days'][$sItem][$iItem][$sFrom][$sUntil] = $aBack;
			
		}

		return self::$aCache['days'][$sItem][$iItem][$sFrom][$sUntil];

	}
	 */
	
	
	public function validate($bThrowExceptions = false, $bIgnoreThebingValidate = false) {

		$this->setUntil();

		$mValidate = parent::validate($bThrowExceptions);

		if($mValidate === true) {

			$mValidate = array();

			if(!is_numeric($this->days) || $this->days < 1)
			{
				$mValidate['days'] = 'INVALID_DAYS_NUMBER';
			}
			else if(!WDDate::isDate($this->from, WDDate::DB_DATE))
			{
				$mValidate['from'] = 'INVALID_DATE';
			}
			else if(!WDDate::isDate($this->until, WDDate::DB_DATE))
			{
				$mValidate['until'] = 'INVALID_DATE';
			}

			if(empty($mValidate)) {
				return true;
			}

		}

		return $mValidate;

	}

	public function checkIgnoringErrors() {

		if($this->item === 'teacher') {

			if(
				!Core\Helper\DateTime::isDate($this->from, 'Y-m-d') ||
				!Core\Helper\DateTime::isDate($this->until, 'Y-m-d')
			) {
				return false;
			}

			$aBlocks = $this->getTeacherTuitionBlocks();
			if(!empty($aBlocks)) {
				return [$this->item_id => 'teacher_absence_allocation_found'];
			}

		}

		return true;
	}

	public function save($bLog = true) {

		$mSave = parent::save($bLog);

		if(
			!is_array($mSave) &&
			$this->item === 'teacher'
		) {
			$aBlocks = $this->getTeacherTuitionBlocks();
			foreach($aBlocks as $oBlock) {
				if($oBlock->teacher_id == $this->item_id) {
					if($this->active) {
						$oBlock->state |= Ext_Thebing_School_Tuition_Block::STATE_TEACHER_ABSENCE;
					} else {
						$oBlock->state &= ~Ext_Thebing_School_Tuition_Block::STATE_TEACHER_ABSENCE;
					}
					$oBlock->save();
				}
			}
		}

		return $mSave;

	}

	/**
	 * Lehrerabwesenheit: Blöcke des Lehrers (plus Vertretungen), welche den Zeitraum dieser Abwesenheit schneiden
	 *
	 * @return Ext_Thebing_School_Tuition_Block[]
	 */
	protected function getTeacherTuitionBlocks() {

		if($this->item !== 'teacher') {
			throw new BadMethodCallException('Wrong item type for this method');
		}

		$dFrom = new DateTime($this->from);
		$dUntil = new DateTime($this->until);

		// Wg. DatePeriod ist das notwendig sonst wird der letzte Tag nicht gezählt
		$dUntil->modify('+1 day');
		
		$aWeeks = [];
		$aWhere = [];

		$oDatePeriod = new DatePeriod($dFrom, new DateInterval('P1D'), $dUntil);
		foreach($oDatePeriod as $dDate) {
			/** @var DateTime $dDate */
			$dWeek = clone $dDate;
			if($dDate->format('N') != 1) {
				$dWeek = $dWeek->modify('last monday');
			}

			$aWeeks[] = $dWeek->format('Y-m-d');
			$aWhere[] = " (`ktb`.`week` = '".$dWeek->format('Y-m-d')."' AND `ktbd`.`day` = ".$dDate->format('N').") ";
		}

		$sWhere = join(' OR ', $aWhere);

		$sSql = "
			SELECT		
				`ktb`.*
			FROM
				`kolumbus_tuition_blocks` `ktb` INNER JOIN
				`kolumbus_tuition_blocks_days` `ktbd` ON
					`ktbd`.`block_id` = `ktb`.`id` LEFT JOIN
				`kolumbus_tuition_blocks_substitute_teachers` `ktbst` ON
					`ktbst`.`block_id` = `ktbd`.`block_id` AND
					`ktbst`.`teacher_id` = :teacher_id AND
					`ktbst`.`day` = `ktbd`.`day` AND
					`ktbst`.`active` = 1
			WHERE
			    `ktb`.`active` = 1 AND
			    `ktb`.`week` IN (:weeks) AND (
			        {$sWhere}
			    ) AND (
			    	`ktb`.`teacher_id` = :teacher_id OR
			    	`ktbst`.`teacher_id` = :teacher_id
				)
			GROUP BY
				`ktb`.`id`
		";

		$aResult = (array)DB::getQueryRows($sSql, [
			'teacher_id' => $this->item_id,
			'weeks' => array_unique($aWeeks)
		]);

		$aResult = array_map(function(array $aBlock) {
			return Ext_Thebing_School_Tuition_Block::getObjectFromArray($aBlock);
		}, $aResult);

		return $aResult;

	}

}
