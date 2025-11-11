<?php

/**
 * Kalender »der Neuzeit«.
 * 
 * Der Kalender bietet eine kleine Brücke zur Gui2, aber dennoch muss die Brücke über die Gui2 komplett geschlossen werden.
 *
 * @author Dennis G. <dg@plan-i.de>
 * @since 08.07.2011 
 */
class Ext_CalendarSheet {

	/**
	 * Javascript-Klasse, mit der der Kalender instanziert wird
	 * @var string
	 */
	public $class_js = "CalendarSheet";
	
	/**
	 * Standard-Startdatum des Kalenders.
	 * @var string DB_DATE
	 */
	public $sStartDate = '';
	
	/**
	 * Standard-Enddatum des Kalenders.
	 * Der Monatstag ist egal, er wird auf den letzten Tag des Monats gesetzt (28-31).
	 * @var string DB_DATE
	 */
	public $sEndDate = '';
	
	/**
	 * Starttag des Kalenders.
	 * Wenn ein anderer Tag als Montag ausgewählt ist, wird keine KW angezeigt!
	 * 
	 * @var int 1 = Montag, […], 7 = Sonntag
	 */
	public $iStartWeekDay = 1;
	
	/**
	 * Tabellenname
	 * @var string
	 */
	public $sTable = "calendar";
	
	/**
	 * Standard-Umbrechen beim Format: Ob nach der Hälfte der Anzahl der Monate der Kalender umgebrochen werden soll.
	 * @var int 0, 1
	 */
	public $iDefaultBreak = 1;
	
	/**
	 * Ob beim Klicken eines bereits gesetzen Eintrags ein Confirm erscheinen soll oder nicht.
	 * Wenn an dieser Stelle ein Dialog zum Editieren bei einem Kalender in einem Guidialog in Erscheinung tritt, vermag es Sinn machen, den Wert auf 0 zu setzen.
	 * @var int 0, 1
	 */
	public $iShowConfirm = 1;
	
	/**
	 * Die CSS-Klasse, mit dem ein Tag als »Termin« gilt. Hat nur Aufwirkungen, wenn $iShowConfirm auf 1 steht!
	 * @var string
	 */
	public $sActiveClass = 'active';
	
	/**
	 * Typ des Kalenders.
	 * @var string direct, gui
	 */
	public $sType = 'direct';
	
	/**
	 * Gui2-Hash, falls der Kalender vom Typ GUI ist.
	 * @var string Gui2-Hash
	 */
	public $sGuiHash = '';
	
	/**
	 * Name des Elternfeldes einer Tabelle bei einer Gui2-Liste
	 * @var string
	 */
	public $sGuiParentId = 'parent_id';
	
	/**
	 * Flag, welches dazu ist, dem Kalender mitzuteilen, dass er nicht sofort die Struktur laden soll mit der Intialisierung.
	 * Das hat beispielsweise dann Sinn, wenn man den Kalender per requestCallbackHook der Gui andere Datumszeiträume immer wieder anzeigen lassen möchte.
	 * @var int 0, 1
	 */
	public $iPreventInit = 0;
	
	/**
	 * Mögliche zusätzlich Aktion angeben
	 * @var string 
	 */
	public $sAdditionalAction = '';
	
	public function __construct($sHash) {

		// Eindeutiger Wert der Instanz, ermöglicht das gleichzeitige Öffnen einer Liste
		if(
			!isset($GLOBALS['calendarsheet_instance_hash']) ||
			strlen($GLOBALS['calendarsheet_instance_hash']) != 32
		) {
			$GLOBALS['calendarsheet_instance_hash'] = \Util::generateRandomString(32);
		}

		$sInstanceHash = $GLOBALS['calendarsheet_instance_hash'];

		$this->hash = $sHash;
		$this->instance_hash = $sInstanceHash;

		Ext_CalendarSheet_GarbageCollector::touchSession($sHash, $sInstanceHash);
		
	}
		
	/**
	 * speichert die Liste in der Session
	 */
	public function save() {
		
		Ext_CalendarSheet_Session::write($this);

	}

	public function display($sContainerID = 'calendar')
	{
		$sHTML = $this->generateHTML($sContainerID);
		$sHTML .= '
		<script type="text/javascript">
			'.$this->generateJS($sContainerID).'
		</script>
		';
		
		echo $sHTML;
	}
	
	public function generateHTML($sContainerID = 'calendar')
	{
		$sHTML = '';
		
		if($sContainerID == 'calendar') {
			$sHTML = '<div id="calendar"></div>';
		}
		
		return $sHTML;
	}
	
	public function generateJS($sContainerID = 'calendar')
	{
		$sJS = '
				aCalendar[\''.$this->hash.'\'] = new '.$this->class_js.'(\''.$this->hash.'\', \''.$this->instance_hash.'\', \''.$this->sStartDate.'\', \''.$this->sEndDate.'\', 
					{
						iStartWeekDay: '.$this->iStartWeekDay.',
						iDefaultBreak: '.$this->iDefaultBreak.',
						iShowConfirm: '.$this->iShowConfirm.',
						sActiveClass: \''.$this->sActiveClass.'\',
						sContainerID: \''.$sContainerID.'\',
						sGuiHash: \''.$this->sGuiHash.'\',
						iPreventInit: '.$this->iPreventInit.'
					}
				);
		';
		
		$this->save();
		
		return $sJS;
	}
	
	/**
	 * WRAPPER-Methode für Ableitungen
	 * @param array $_VARS 
	 */
	public function switchAjaxRequest($_VARS) {
		$aTransfer = array();

		if($_VARS['task'] == 'updateDay') {
			
			$aTransfer = $this->updateDay($_VARS['date'], $_VARS['action']);
			$aTransfer['action'] = 'updateDay';
			$aTransfer['date'] = $_VARS['date'];
			$aTransfer['objectid'] = $_VARS['objectid'];
			
		} else {
			$aTransfer = $this->_switchAjaxRequest($_VARS);
		}
		
		echo json_encode($aTransfer);
	}
	
	/**
	 * Standard-Requestverhalten
	 * @param array $_VARS
	 */
	final protected function _switchAjaxRequest($_VARS){
		
		$aTransfer = array();
		
		// Init-Array zurückliefern mit den Definitionen
		if($_VARS['task'] == 'getInitData') {
			
			$iGuiId = 0;
			if(
				$this->sType == 'gui' &&
				!empty($_VARS['id'])
			) {
				$aTransfer['id'] = (array)$_VARS['id'];
				$iGuiId = $aTransfer['id'][0];
			}
			
			if(!empty($_VARS['action'])) {
				$this->sAdditionalAction = $_VARS['action'];
			}
			
			$aTransfer['action'] = 'init';
			$aTransfer['i18n'] = $this->getTranslations();
			$aTransfer['data'] = $this->getStructure($_VARS['start'], $_VARS['end'], $iGuiId);
			$aTransfer['start'] = $_VARS['start'];
			$aTransfer['end'] = $_VARS['end'];
			
		}
		
		return $aTransfer;
		
	}
	
	/**
	 * WRAPPER-Methode, um die Tage zu manipulieren.
	 * 
	 * @param array $aData 
	 */
	protected function manipulateStructureData($aData, $iGuiId)
	{
		$aSql = array(
			'table' => $this->sTable,
			'start' => $this->sStartDate,
			'end' => $this->sEndDate
		);
		
		// GUI2 Eintrag-ID setzen, wenn vorhanden und Kalender Typ GUI
		$sWherePart = '';
		if(
			$this->sType === 'gui' &&
			$iGuiId !== 0
		) {
			
			$aSql['gui_parent_id'] = $iGuiId;
			$aSql['gui_parent_field'] = $this->sGuiParentId;
			
			$sWherePart = "
				#gui_parent_field = :gui_parent_id AND
				`active` = 1 AND
			";
			
		}
		
		$sSql = "
			SELECT
				## `date`, `name`
				`date`
			FROM
				#table
			WHERE 
				$sWherePart
				`date` BETWEEN :start AND :end
		";
		
		$aDays = DB::getQueryPairs($sSql, $aSql);
		
		foreach($aData as &$aYears) {
			foreach($aYears['months'] as &$aMonth) {
				foreach($aMonth['days'] as &$aDay) {
					
					if(
						!is_null($aDays) &&
						array_key_exists($aDay['date'], $aDays)
					) {
						$aDay['class'] = $this->sActiveClass;
						//$aDay['tooltip'] = $aDays[$aDay['date']].': Nyan nyan';
					}
					
					// Eventdefinitionen setzen
					if($this->sType == 'gui') {
						$aDay['event']['function'] = 'calendarAction';
					} else {
						$aDay['event']['function'] = 'this.switchAppointment';
					}
					
					$aDay['event']['type'] = $this->sType;
					
				}
			}
		}
		
		
		return $aData;
	}
	
	/**
	 * (WRAPPER-) Methode, um einen Tag zu updaten.
	 * Die Standardmethode hier bietet nur die Funktion, einen Tag anzuwählen und abzuwählen.
	 * Dabei ist dieser »angewähle Status« durch eine CSS-Klasse realisiert.
	 * @param string $sDate DB_DATE
	 */
	protected function updateDay($sDate, $sAction)
	{
		$aTransfer = array();
		$aTransfer['action'] = 'updateDay';
		
		$aSql = array(
			'table' => $this->sTable,
			'date' => $sDate
		);
		
		if($sAction == 'set') {
			
			$sSql = "
				INSERT INTO
					#table
				SET
					`date` = :date
			";
			
			DB::executePreparedQuery($sSql, $aSql);
			$aTransfer['class'] = $this->sActiveClass;
			
		} elseif($sAction == 'remove') {
			
			$sSql = "
				DELETE FROM
					#table
				WHERE
					`date` = :date
			";
			
			DB::executePreparedQuery($sSql, $aSql);
			$aTransfer['class'] = '';
			
		} elseif($sAction == 'check') {
			
			$sSql = "
				SELECT
					`id`
				FROM
					#table
				WHERE
					`date` = :date
			";
			
			$aResult = DB::getQueryOne($sSql, $aSql);
			
			if(!is_null($aResult)) {
				$aTransfer['class'] = $this->sActiveClass;
			}
			
		}

		
		return $aTransfer;
		
	}
	
	public function getStructure($sStartDate, $sEndDate, $iGuiId)
	{
		global $system_data;
		
		$aData = array();

		$this->sStartDate = self::verifyDate($sStartDate, 'start');
		$this->sEndDate = self::verifyDate($sEndDate, 'end');
		
		$oDate = new WDDate($this->sStartDate, WDDate::DB_DATE);
		$oEndDate = new WDDate($this->sEndDate, WDDate::DB_DATE);
		
		if($oDate->get(WDDATE::TIMESTAMP) > $oEndDate->get(WDDATE::TIMESTAMP)) {
			throw new Exception('Date of start is higher than date of end!');
		}

		$bFirstDay = true;
		$iEndDateTS = $oEndDate->get(WDDATE::TIMESTAMP);
		
		while($oDate->get(WDDATE::TIMESTAMP) < $iEndDateTS) {
			
			// Taganzahl des letzten Monats holen, wenn der erste Tag des Monats nicht Montag ist.
			if(
				$bFirstDay &&
				$oDate->get(WDDATE::DAY_OF_WEEK) !== 1
			) {
				$oTmpDate = clone $oDate;
				$oTmpDate->sub(1, WDDate::MONTH);
				$iMonthDaysLastMonth = $oTmpDate->get(WDDate::MONTH_DAYS);
				$bFirstDay = false;
			} else {
				$iMonthDaysLastMonth = $iMonthDays;
			}
				
			$iYear = (int)$oDate->get(WDDATE::YEAR);
			$iMonth = (int)$oDate->get(WDDATE::MONTH);
			
			if(!array_key_exists($iYear, $aData)) {
				$aData[$iYear] = array('months' => array());
			}
			
			if(!array_key_exists($iMonth, $aData[$iYear]['months'])) {
				$aData[$iYear]['months'][$iMonth] = array();
			}
			
			$iMonthDays = $oDate->get(WDDATE::MONTH_DAYS);
			
			$aData[$iYear]['months'][$iMonth]['start_weekday'] = $oDate->get(WDDATE::DAY_OF_WEEK);
			$aData[$iYear]['months'][$iMonth]['start_cw'] = $oDate->get(WDDATE::WEEK);
			$aData[$iYear]['months'][$iMonth]['month_days'] = $iMonthDays;
			$aData[$iYear]['months'][$iMonth]['month_days_lm'] = $iMonthDaysLastMonth;
			
			$aData[$iYear]['months'][$iMonth]['days'] = array();
			
			for($iDay = 1; $iDay <= $iMonthDays; ++$iDay) {
				
				// Datum zusammebauen – die gleiche Version mit nur str_pad() ohne if dauert gerade mal 0,00025 Sekunden länger!
				$sDate = $iYear.'-';
				
				if($iMonth < 10) {
					$sDate .= str_pad($iMonth, 2, '0', STR_PAD_LEFT);
				} else {
					$sDate .= $iMonth;
				}
				
				$sDate .= '-';
				
				if($iDay < 10) {
					$sDate .= str_pad($iDay, 2, '0', STR_PAD_LEFT);
				} else {
					$sDate .= $iDay;
				}
				
				
				$aData[$iYear]['months'][$iMonth]['days'][$iDay] = array(
					'date' => $sDate,
					'class' => '',
					'event' => array(
						'function' => ''
					)
				);
			}
			
			if(\System::d('debugmode')) {
				$aData[$iYear]['months'][$iMonth]['__MONTH'] = $oDate->get(WDDate::STRFTIME, '%x');
			}
			
//			$oTmpDate->set($iYear.'-12-28', WDDate::DB_DATE);
//			$aData[$iYear]['year_weeks'] = $oTmpDate->get(WDDate::WEEK);
			if($oTmpDate) {
				$oTmpDate->set(($iYear - 1).'-12-28', WDDate::DB_DATE);
				$aData[$iYear]['year_weeks_ly'] = $oTmpDate->get(WDDate::WEEK);
			}
			
			$oDate->add(1, WDDATE::MONTH);
			
		}
		
		
		$aData = $this->manipulateStructureData($aData, $iGuiId);
		
		return $aData;
	}
	
	/**
	 *
	 * @global array $_VARS
	 * @param string $sHash
	 * @param string $sInstanceHash
	 * @return Ext_CalendarSheet 
	 */
	public static function getClass($sHash = '', $sInstanceHash = '') {
		global $_VARS;

		$mReturn = Ext_CalendarSheet_Session::load($sHash, $sInstanceHash);

		if(
			$mReturn !== false &&
			$mReturn instanceof Ext_CalendarSheet
		) {
			$oCalendarSheet = $mReturn;
		} else {
			$oCalendarSheet = new Ext_CalendarSheet($sHash);
		}

		$sInstanceHash = $oCalendarSheet->instance_hash;

		Ext_CalendarSheet_GarbageCollector::touchSession($sHash, $sInstanceHash);

		return $oCalendarSheet;

	}
	
	public function getTranslations()
	{
		global $session_data;
		
		$aTranslations = array();
		
		$oLocale = new WDLocale(\System::getInterfaceLanguage(), 'date');
		$aI18N = $oLocale->getData();
		
		$aTranslations = array(
			'days' => $aI18N['a'],
			'months' => $aI18N['B'],
			'cw' => L10N::t('KW', 'Calendarsheet'),
			'delete_appointment' => L10N::t('Soll der Termin wirklich gelöscht werden?', 'Calendarsheet')
		);
		
		return $aTranslations;
	}
	
	/**
	 * Prüft ein Datum für den Kalender. Setzt das Datum bei Typ 'end' auf den letzten Monatstag des Monats.
	 */
	final public static function verifyDate($sDate, $sType)
	{
		$aDate = explode('-', $sDate);
		if(count($aDate) !== 3) {
			throw new Exception('Invalid type of date given! Date: '.$sDate);
		}
		
		if($sType === 'start') {
			$aDate[2] = '01';
		} elseif($sType === 'end') {
			$oDate = new WDDate($sDate, WDDate::DB_DATE);
			$aDate[2] = $oDate->get(WDDate::MONTH_DAYS);
		}
		
		return implode('-', $aDate);
		
	}

}