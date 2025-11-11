<?php

class Ext_TC_Exchangerate_Table_Source extends Ext_TC_Basic {

	protected $_sTable = 'tc_exchangerates_tables_sources';
	protected $_sTableAlias = 'tc_ets';

	protected $_aRateCache = array();
	
	protected $_aJoinedObjects = array(
		'rates' => array(
			'class' => 'Ext_TC_Exchangerate_Table_Source_Rate',
			'key' => 'source_id',
			'type' => 'child',
			'orderby' => 'date',
			'orderby_type' => 'DESC',
			'cloneable' => false
		)
	);

	/**
	 * Liest die Daten aus dem übergebenen XML anhand den gemachten Einstellungen aus
	 * @todo Fehler abfangen
	 * @param string $sXml
	 * @return array 
	 */
	public function readXml($sXml) {
			
		$oXML = new SimpleXMLElement($sXml);

		if(isset($_REQUEST['debug'])) {
			__out($oXML);
		}

		// Datum holen
		eval('$sDate = (string)$oXML'.$this->date_position.';');
		
		// Datum formatieren
		$oDate = new WDDate($sDate, WDDate::STRFTIME, $this->date_format);

		//Container Position
		
		$aLastNodeParts = array();
		$bContainerPosition = preg_match("/\[(.*?)=(.*?)\]/", $this->container, $aLastNodeParts);

		if($bContainerPosition) {
			$aParts = explode('->', $this->container);

			$aLastNode = array_pop($aParts);

			$sContainerPrefix = implode('->', $aParts);

			if(!empty($sContainerPrefix)) {				
				$sContainerPrefix = '->'.$sContainerPrefix;
				eval('$oContainer = $oXML'.$sContainerPrefix.';');
			} else {
				$oContainer = $oXML;
			}

			foreach($oContainer->children() as $oItem) {
				if((string)$oItem[$aLastNodeParts[1]] == $aLastNodeParts[2]) {
					$oContainer = $oItem;
					break;
				}
			}

		} else {
			eval('$oContainer = $oXML'.$this->container.';');
		}

		//Quellwährung Position
		
		$sSourceCurrencyPosition = false;
		if(!preg_match("/^[A-Z]{3}$/", $this->source_currency)) {
			$sSourceCurrencyPosition = preg_replace("/.*?(\->|\[)/", "$1", $this->source_currency);
		} else {
			$sSourceCurrency = $this->source_currency;
		}

		//Zielwährung Position
		
		$sTargetCurrencyPosition = false;
		if(!preg_match("/^[A-Z]{3}$/", $this->target_currency)) {
			$sTargetCurrencyPosition = preg_replace("/.*?(\->|\[)/", "$1", $this->target_currency);
		} else {
			$sTargetCurrency = $this->target_currency;
		}

		//Divisor Position
		
		$sDivisorPosition = false;
		if(!preg_match("/^[0-9]$/", $this->divisor)) {
			$sDivisorPosition = preg_replace("/.*?(\->|\[)/", "$1", $this->divisor);
		} else {
			$iDivisor = (float) $this->divisor;
		}

		//Kurs Position
		$sRatePosition = preg_replace("/.*?(\->|\[)/", "$1", $this->rate);
				
		$aPriceLastNodeParts = array();
		$bPricePosition = preg_match("/\[(.*?)=(.*?)\]/", $this->rate, $aPriceLastNodeParts);
		
		// Prüfen ob Container ein Kindlement ist

		if($this->child_element == 1){
			$mContainer = $oContainer;
		}else{
			$mContainer = $oContainer->children();
		}

		$aRates = array();
		foreach($mContainer as $oItem) {

			//Kurs auslesen		

			if($bPricePosition) {				
				$sNode = preg_replace("/(\[.*?\])/", '', $this->rate);				
				eval('$oRate = $oItem'.$sNode.';');

				foreach($oRate as $oSubRate) {
					$mKey = reset($oSubRate[$aPriceLastNodeParts[1]]);
					if($mKey === $aPriceLastNodeParts[2]) {
						$oRate = $oSubRate;
						break;
					}					
				}
			} else {
				eval('$oRate = $oItem'.$sRatePosition.';');
			}
			
			//Quellwährung auslesen
			
			if($sSourceCurrencyPosition) {
				eval('$sSourceCurrency = (string)$oItem'.$sSourceCurrencyPosition.';');
				if($this->source_currency_searchterm) {
					preg_match("/".$this->source_currency_searchterm."/", $sSourceCurrency, $aMatch);
					$sSourceCurrency = mb_strtoupper($aMatch[1]);
				}
			}

			//Zielwährung auslesen
			
			if($sTargetCurrencyPosition) {
				eval('$sTargetCurrency = (string)$oItem'.$sTargetCurrencyPosition.';');
				if($this->target_currency_searchterm) {
					preg_match("/".$this->target_currency_searchterm."/", $sTargetCurrency, $aMatch);
					$sTargetCurrency = mb_strtoupper($aMatch[1]);
				}
			}

			//Divisor auslesen
			
			if($sDivisorPosition) {
				eval('$iDivisor = (string)$oItem'.$sDivisorPosition.';');
				if($this->divisor_searchterm) {
					preg_match("/".$this->divisor_searchterm."/", $iDivisor, $aMatch);
					$iDivisor = (float) $aMatch[1];
				}
			}			

			// Wenn Trennzeichen nicht "." ist
			
			if($this->separator == ''){
				$this->separator = '.';
			}
						
			if($this->separator != '.'){								
				$oRate[0] = str_replace($this->separator, '.', $oRate[0]);
			}
			
			$fRate = (float)$oRate;

			
			// Wenn Divisor vorhanden			
			if(
				$iDivisor &&
				$iDivisor != 0
			){
				$fRate = $fRate/$iDivisor;
			}

			// Wenn Kurs umgekehrt werden soll			
			if(
				$this->reverse == 1 &&
				$fRate > 0
			) {
				$fRate = 1/$fRate;
			}

			// Keine 0 Werte speichern
			if(empty($fRate)) {
				Ext_TC_Util::sendErrorMessage(['xml'=>$oItem, 'source'=>$this->aData], 'Empty exchangerate');
				continue;
			}

			// Originalwert des Wechselkurses ohne Aufschlag
			$fOriginalRate = $fRate;
			
			// Wechselkursaufschlag (#5122)
			$fFactor = (float) $this->factor;
			if($fFactor > 0) {
				$fRate *= $fFactor;
			}			
			
			$sArrayKey = $sSourceCurrency.'_'.$sTargetCurrency;
			
			/**
			 * @todo Prüfen, ob Quelle und Ziel gültige ISO-Codes sind und ob $fRate > 0 ist 
			 */
			
			$aRates[$sArrayKey] = array(
				'source_id' => (int)$this->id,
				'table_id' => (int)$this->table_id,
				'date' => $oDate->get(WDDate::DB_DATE),
				'rate' => $fOriginalRate,
				'factor' => $fFactor,
				'price' => $fRate,
				'currency_iso_from' => $sSourceCurrency,
				'currency_iso_to' => $sTargetCurrency
			);

		}
		
		return $aRates;

	}

	/**
	 * gibt die Wechselkursdaten der Quelle zurück
	 * @return array 
	 */
	public function readData() {
		
		try {
			$sXml = $this->_getXml();
		} catch (Exception $e) {
			__pout($e);
			return false;
		}
		
		$aData = $this->readXml($sXml);
		
		return $aData;
	}
	
	/**
	 * Prüft, ob die Xml-Datei erreicht werden konnte
	 * @return string
	 * @throws Exception 
	 */
	protected function _getXml() {
		
		$bUrlExists = Util::checkUrl($this->url);
		// Url nicht vorhanden
		if($bUrlExists === false) {
			throw new Ext_TC_Exchangerate_Exception('Unable to open xml-file');
		}
		
		// Kein Inhalt
		$sXml = @file_get_contents($this->url);
		if(!$sXml){
			throw new Ext_TC_Exchangerate_Exception('Unable to load xml-file');
		}
		
		return $sXml;

	}
	
	/**
	 * Gibt alle Wechselkurs der Quelle zurück
	 * @return array Ext_TC_Exchangerate_Table_Source_Rate 
	 */
	public function getRates() {
		$aRates = (array) $this->getJoinedObjectChilds('rates', true);
		return $aRates;
	}

}