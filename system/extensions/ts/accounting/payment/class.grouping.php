<?php

class Ext_TS_Accounting_Payment_Grouping extends Ext_Thebing_Basic {

	// Tabellenname
	protected $_sTable = 'ts_inquiries_payments_groupings';
	
	// Tabellenalias
	protected $_sTableAlias = 'ts_ipg'; 
	
	public function generateNumber() {

		$bGenerated = false;
		$mNumber = $this->getNumber();
		$mNumberRange = Ext_TS_Accounting_Payment_Grouping_Numberrange::getObject($this);

		// TODO: Sperre des Nummernkreises?
		if(empty($mNumber) && $mNumberRange != null) {
			$sNumber = $mNumberRange->generateNumber();
			$this->number = $sNumber;
			$this->numberrange_id = $mNumberRange->id;
			$bGenerated = true;
		}

		return $bGenerated;

	}

	
	/**
	 * Liefert die Nummer (aus einem Nummernkreis) der Agentur
	 * @return string|bool
	 */
	public function getNumber() {
		return $this->number;
	}
	
	/**
	 * speichern
	 * @param bool $bLog
	 * @return type
	 */
	public function save($bLog = true) {
		
		// Nummernkreis erzeugen
		$mNumber = $this->getNumber();
		if(empty($mNumber)) {
			$this->generateNumber();
		}

		return parent::save($bLog);
	}
	
}
