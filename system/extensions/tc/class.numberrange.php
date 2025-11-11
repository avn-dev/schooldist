<?php

/**
 * Beschreibung der Klasse
 */

class Ext_TC_NumberRange extends Ext_TC_Basic {

	const LOCK_DURATION = 60;

	/**
	 * Die Variable macht genau das, was sie aussagt:
	 * Doppelte Nummern ermöglichen, dafür aber die Exception in acquireLock() überspringen.
	 *
	 * In manchen Stellen der Software kamen doppelte Nummern nie vor,
	 * aber die Anpassung bzgl. Transaktionen ist zeitaufwändig. Daher wurde das hier eingebaut.
	 *
	 * @var bool
	 */
	public $bAllowDuplicateNumbers = false;

	/**
	 * Benötigte Entität für super spezielle Platzhalter
	 *
	 * @var Ext_TC_Basic
	 */
	protected $oDependencyEntity;

	// Tabellenname
	protected $_sTable = 'tc_number_ranges';
	protected $_sTableAlias = 'tc_nr';

	protected $_sNumberTable = '';
	protected $_sNumberField = 'number';

	/**
	 * Eigene Platzhalter pro Ableitung definieren
	 *
	 * @var array
	 */
	protected $_aPlaceholders = [];

	protected $_aFormat = [
		'format' => [
			'validate' => 'REGEX',
			'validate_value' => '[[:alnum:]%.\/-]+' // Keine problematischen Zeichen für Elasticsearch-QueryString, vor allem keine Whitespaces!
		]
	];

	public function __construct($iDataID=0, $sTable=null) {

		$this->_aJoinedObjects['sets'] = array(
			'class' => Ext_TC_Factory::getClassName('Ext_TC_NumberRange_Allocation_Set'),
			'key' => 'numberrange_id',
			'type' => 'child'
		);

		parent::__construct($iDataID, $sTable);
	}

	public function setConfig($sTable, $sField = 'number') {
        $this->_sNumberTable = $sTable;
        $this->_sNumberField = $sField;
    }

    public static function getNumberLockedError()
	{
		return L10N::t('Es wird gerade eine andere Nummer generiert! Bitte versuchen Sie es gleich nochmal.');
	}
    
	/**
	 * Gibt das Numberrange Object zurück
	 *
	 * @param string $sApplication
	 * @param int $iObjectId
	 * @param int|null $iInvoiceNumberrangeId Nur relevant, wenn die Quittungsnummern abhängig von Rechnungsnummern sind
	 * @return Ext_TC_NumberRange
	 */
	public static function getByApplicationAndObject($sApplication, $iObjectId, $iInvoiceNumberrangeId = null) {
		
		$aApplications = Ext_TC_Factory::executeStatic('Ext_TC_NumberRange_Gui2_Data', 'getApplications');

		// Sonderfall: Quittungsnk. abhängig von Rechnungsnk.
		if(
			array_key_exists($sApplication, $aApplications['receipt'])
		) {

			$oConfig = \Factory::getInstance('Ext_TC_Config');
			if($oConfig->getValue('receipt_dependingon_invoice')) {
				$aAllocations = static::getReceiptAllocations();
				$iNumberrangeId = $aAllocations[$iInvoiceNumberrangeId];
			}
			
		}

		if(!isset($iNumberrangeId)) {

			$sSql = "
				SELECT
					`tc_nras`.`numberrange_id`
				FROM
					`tc_number_ranges_allocations_objects` `tc_nrao` JOIN
					`tc_number_ranges_allocations_sets` `tc_nras` ON
						`tc_nrao`.`allocation_id` = `tc_nras`.`allocation_id` AND
						`tc_nras`.`active` = 1 JOIN
					`tc_number_ranges_allocations_sets_applications` `tc_nrasa` ON
						`tc_nras`.`id` = `tc_nrasa`.`set_id` INNER JOIN
					`tc_number_ranges_allocations` `tc_nra`	ON
						`tc_nra`.`id` = `tc_nras`.`allocation_id` AND
						`tc_nra`.`active` = 1 INNER JOIN
					`tc_number_ranges` `tc_nr` ON
						`tc_nr`.`id` = `tc_nras`.`numberrange_id` AND
						`tc_nr`.`active` = 1
				WHERE
					`tc_nrao`.`object_id` = :object_id AND
					`tc_nrasa`.`application` = :application
				ORDER BY
					`tc_nra`.`position`
				LIMIT 1
			";
			
			$aSql = array(
				'application' => $sApplication,
				'object_id' => (int)$iObjectId
			);
			
			static::manipulateSqlNumberRangeQuery($sSql, $aSql);

			$iNumberrangeId = DB::getQueryOne($sSql, $aSql);

		}

		$oNumberrange = call_user_func(array(get_called_class(), 'getInstance'), $iNumberrangeId);

		return $oNumberrange;
	}
	
	public static function getReceiptAllocations() {
		$aAllocations = Ext_TC_NumberRange_Allocation::getReceiptAllocations();
		return $aAllocations;
	}

	final protected function buildLatestNumberQuerySelect() {

		return "
			(
				SUBSTRING(
					SUBSTRING(#number_field, :prefix_length),
					1,
					(
						LENGTH(
							SUBSTRING(#number_field, :prefix_length)
						) - :postfix_length
					)
				) + 0
			) `last_number`
		";

	}

	final protected function _searchLatestNumber($sPrefix, $sPostfix, $bOnlyCountPlaceholder) {

		$sQuery = "
			SELECT
				{$this->buildLatestNumberQuerySelect()}
			FROM
				#table
			WHERE
				`numberrange_id` = :numberrange_id AND
			  ".($bOnlyCountPlaceholder ? "#number_field REGEXP '^[0-9]+$'" : "#number_field LIKE :pattern")."
			ORDER BY
				`last_number` DESC
			LIMIT 1
		";

		$iPostfixLength = mb_strlen($sPostfix);

		$aSql = array(
			'numberrange_id' => (int)$this->id,
			'number_field' => $this->_sNumberField,
			'table' => $this->_sNumberTable,
			'pattern' => $sPrefix.'%'.$sPostfix,
			'prefix_length' => mb_strlen($sPrefix)+1,
			'postfix_length' => $iPostfixLength
		);

		return $this->executeSearchLatestNumber($sQuery, $aSql);

	}

	protected function executeSearchLatestNumber($sSql, $aSql) {

		return DB::getQueryOne($sSql, $aSql);

	}

	public function bindPlaceholder(string $placeholder, callable $callable): static {
		$this->_aPlaceholders[\Illuminate\Support\Str::start($placeholder, '%')] = $callable;
		return $this;
	}

	/**
	 * Nummer generieren
	 * Achtung: IMMER mit acquireLock() den Nummernkreis zuvor sperren!
	 *
	 * @return string
	 */
	public function generateNumber() {

		$sFormat = $this->format;

		if(mb_strpos($sFormat, '%count') === false) {
			$sFormat .= '%count';
		}

		$sTempFormat = str_replace('%count', '', $sFormat);

		if(
			is_null($iTimestamp) ||
			empty($iTimestamp)
		) {
			$iTimestamp = time();
		}

		if(
			$this->oDependencyEntity !== null &&
			mb_strpos($sFormat, '%count') !== false
		) {
			$sFormat = str_replace('%parentnumber', $this->oDependencyEntity->getNumber(), $sFormat);
//			$sName = $oEntity->getName();
//			// Name ersetzen
//			$sFormat = str_replace('%name', $sName, $sFormat);
//			$sFormat = str_replace('%NAME1', mb_substr(strtoupper($sName), 0, 1), $sFormat);
//			$sFormat = str_replace('%NAME3', mb_substr(strtoupper($sName), 0, 3), $sFormat);
		}

		preg_match_all('/(\%[a-zA-Z0-9]*)/', $sTempFormat, $aParts);

		$aTemp = array();
		$bCountOk = false;
		foreach((array)$aParts[0] as $index => $sPart) {
			if (!empty($intersect = array_intersect([$sPart, strtolower($sPart)], array_keys($this->_aPlaceholders)))) {
				$aTemp[$sPart] = call_user_func_array($this->_aPlaceholders[\Illuminate\Support\Arr::first($intersect)], [$sPart, $this]);
			} else {
				$aTemp[$sPart] = strftime($sPart, $iTimestamp);
			}
		}

		foreach((array)$aTemp as $sPlaceholder => $sData) {
			$sFormat = str_replace($sPlaceholder, $sData, $sFormat);
		}

		list($sPrefix, $sPostfix) = explode('%count', $sFormat, 2);

		$bOnlyCountPlaceholder = empty($sPrefix) && empty($sPostfix);

		$iLatestNumber = $this->_searchLatestNumber($sPrefix, $sPostfix, $bOnlyCountPlaceholder);

		if($iLatestNumber !== null) {

            $iDigits = (int)$this->digits;
			$sCount = str_pad($iLatestNumber + 1, $iDigits, '0', STR_PAD_LEFT);

		} else {

			$sAnyNumber = $this->_searchLatestNumber('', '', $bOnlyCountPlaceholder);

			// Wenn noch gar keine Nummer gesetzt wurde, dann Offset verwenden
			if($sAnyNumber === null) {
				$iOffset = $this->offset_abs;
			} else {
				$iOffset = $this->offset_rel;
			}

			$iDigits = (int)$this->digits;
			$sCount = str_pad($iOffset, $iDigits, '0', STR_PAD_LEFT);

		}

		$sNumber = str_replace('%count', $sCount, $sFormat);

		return $sNumber;

	}
	
	/**
	 * @return string
	 */
	protected function getLockKey() {
		return 'numberrange_lock_'.$this->id;
	}

	/**
	 * Nummernkreis sperren: Lock holen
	 *
	 * Das eigentliche Sperren des Nummernkreises findet in einer (Memcache-)Transaktion statt,
	 * da get() und set() nacheinander keine atomare Aktion sind, man hier aber eine atomare
	 * Aktion benötigt, damit zwischen den beiden Aufrufen keine mögliche Race Condition durch
	 * einen anderen Request auftreten kann. Damit diese Transaktion nicht gleich fehl schlägt,
	 * wenn diese schon existiert, findet diese zusätzlich in einer for-Schleife mit usleep()
	 * statt, damit kein Dead-Lock verursacht wird, da der direkte Aufruf von set() den Wert
	 * in Memcache einfach überschreibt (und somit auch die Ablaufzeit).
	 *
	 * Der Nummernkreis sollte gesperrt werden, BEVOR die Datenbank-Transaktion beginnt, da
	 * eine Transaktion natürlich nach dem ACID-Prinzip arbeitet und nur die Werte ausliest,
	 * die zum Beginn der Transaktion auch in der Datenbank standen. Wenn nun also zwei
	 * gleichzeitige Transaktionen laufen und unter Umständen aufeinander warten
	 * (Row-Locks oder so), der Nummernkreis aber erst in der Transaktion gesperrt wird,
	 * werden beide Transaktionen jeweils die gleiche letzte Nummer ermitteln, da die
	 * zweite unmittelbar nach der ersten Transaktion ausgeführt werden (wegen den Locks),
	 * aber beide Transaktion die selbe letzte Nummer finden. Bei einer falschen Implementierung
	 * von dieser Methode bringt also auch das Sperren des Nummernkreises nichts.
	 *
	 * Bei langen Tasks (Gruppen…) muss der Nummernkreis zusätzlich mit renewLock() immer wieder
	 * erneuert werden, da der Lock nach LOCK_DURATION ausläuft. LOCK_DURATION ist dafür da,
	 * damit im Fehlerfall der Nummernkreis irgendwann entsperrt wird, aber nicht dauerhaft
	 * gesperrt ist und somit das System unbrauchbar werden würde.
	 *
	 * @return bool
	 */
	public function acquireLock() {
		global $_VARS;

		$sKey = $this->getLockKey();
		$sTransactionKey = $sKey.'_transaction';
		$mStatus = null;
		$sLogStatus = '';

		$oLog = self::getLogger();
		$oLog->addInfo('Locked numberrange "'.$this->getName().'" ('.$this->id.'), sKey: '.$sKey);
		
		// Atomare Transaktion für Memache get/set: Lock holen (und mehrfach versuchen)
		for($i = 0; $i < 11; $i++) {
			$iTransactionStatus = WDCache::set($sTransactionKey, 10, 1);
			
			$oLog->addInfo('Locked numberrange "'.$this->getName().'" ('.$this->id.'), iTransactionStatus: '.$iTransactionStatus);
			
			if($iTransactionStatus === WDCache::ADDED) {
				// Request hat Lock: Schleife abbrechen und fortfahren
				break;
			} else {

				// Dead Lock verhindern: Abbruch (letzter Durchlauf)
				if($i === 10) {
					$mStatus = 1;
					$sLogStatus = 'DEAD_LOCK';
					break;
				}

				usleep(500000); // 500ms
			}
		}

		// Tatsächliches Sperren des Nummernkreises und Aufhebung der obigen Transaktion
		if($sLogStatus !== 'DEAD_LOCK') {
			$mStatus = WDCache::get($sKey);
			if($mStatus === null) {
				// Wenn Nummernkreis nicht gesperrt: Sperren
				WDCache::set($sKey, self::LOCK_DURATION, 1);
				$sLogStatus = 'SUCCESS';
			} else {
				$sLogStatus = 'FAIL';
			}

			// Transaktion beenden
			WDCache::delete($sTransactionKey);
		}

		// Loggen
		// Erst DANACH ausführen wegen File-I/O
		$oLog->addInfo('Locked numberrange "'.$this->getName().'" ('.$this->id.'), status: '.$sLogStatus, array('transaction_iteration' => $i, '_VARS' => $_VARS));

		// Siehe Methoden-Kommentar
		if(
			!$this->bAllowDuplicateNumbers &&
			!empty($this->_oDb->getLastTransactionPoint())
		) {
			throw new RuntimeException('acquireLock() called while in active database transaction! ('.$this->_oDb->getLastTransactionPoint().')');
		}

		if($mStatus === null) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Lock erneuern (für lange Tasks)
	 *
	 * Vor diesem Aufruf MUSS sichergestellt werden, dass der Lock dem selben Request gehört!
	 */
	public function renewLock() {
		global $_VARS;

		WDCache::set($this->getLockKey(), self::LOCK_DURATION, 1);

		$oLog = self::getLogger();
		$oLog->addInfo('Renewed numberrange lock "'.$this->getName().'" ('.$this->id.')', array('_VARS' => $_VARS));
	}

	/**
	 * Nummernkreis freigeben
	 *
	 * Vor diesem Aufruf MUSS sichergestellt werden, dass der Lock dem selben Request gehört!
	 * Außerdem darf der Lock erst nach BEENDIGUNG einer Transaktion gelöscht werden,
	 * denn ansonsten fehlt die Nummer noch in der Datenbank, aber der Nummernkreis ist wieder offen!
	 */
	public function removeLock() {
		global $_VARS;

		$sKey = $this->getLockKey();
		WDCache::delete($sKey);

		$oLog = self::getLogger();
		$oLog->addInfo('Unlocked numberrange "'.$this->getName().'" ('.$this->id.')', array('_VARS' => $_VARS));

		// Siehe Methoden-Kommentar
		if(
			!$this->bAllowDuplicateNumbers &&
			!empty($this->_oDb->getLastTransactionPoint())
		) {
			throw new RuntimeException('removeLock() called while in active database transaction! ('.$this->_oDb->getLastTransactionPoint().')');
		}

	}

	public function save($bLog = true) {
		
		$bNew = false;
		if($this->id == 0) {
			$bNew = true;
		}
		
		parent::save($bLog);

		if(
			$bNew === true &&
			$this->active == 1
		) {
			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Create access right

			$oAccessMatrix = new Ext_TC_Numberrange_AccessMatrix;
			$oAccessMatrix->createOwnerRight($this->id);

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
		}
		
		return $this;
		
	}
	
	/**
	 * Hook-Methode zum Manipulieren
	 * 
	 * @param string $sSql
	 * @param array $aSql
	 */
	public static function manipulateSqlNumberRangeQuery(&$sSql, &$aSql) {
		 
	}

	/**
	 * @param $oEntity
	 */
	public function setDependencyEntity($oEntity) {
		$this->oDependencyEntity = $oEntity;
	}

	public static function getLogger() {
		return Log::getLogger('default', 'numberrange');
	}
	
}
