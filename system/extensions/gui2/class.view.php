<?php

class Ext_Gui2_View {

	protected $_oGui;

	public function __construct(&$oGui){
		$this->_oGui = $oGui;
	}

	public function getColumnTextAlign(&$oColumn){
		$oObject = null;

		if(
			$oColumn->format instanceof Ext_Gui2_View_Format_Interface
		){
			$oObject = $oColumn->format;

		} else if(
			is_string($oColumn->format)
		){
			$sTempView = 'Ext_Gui2_View_Format_'.$oColumn->format;
			$oObject = new $sTempView();
		} else {
			throw new Exception("Please use a Ext_Gui2_View_Format_Interface Interface");
		}

		$mValue = $oObject->align($oColumn);

		return $mValue;
	}

	/**
	 * Inhalt einer Zelle formatieren
	 */
	public function getColumnDisplayValue($mValue, &$oColumn, &$aResultData, $sFlexType = 'list', $bSumRow = false){

		// Regex anwenden
		if($oColumn->regex != ''){
			// Falls ein Regex angegeben wurde wird dieser genutzt
			// das ergebniss wird dann für die weiteren Schritte genutzt
			preg_match($oColumn->regex, $mValue, $mValue);
			$mValue = $mValue[0];
		}

		$oObject = null;

		if(
			$oColumn->format instanceof Ext_Gui2_View_Format_Interface
		){
			$oObject = $oColumn->format;
			
		} else if(
			is_string($oColumn->format)
		){
			if(strtolower($oColumn->format) == 'text'){
				return $mValue;
			}
			$sTempView = 'Ext_Gui2_View_Format_'.$oColumn->format;
			$oObject = new $sTempView();
		} else {
			throw new Exception("Please use a Ext_Gui2_View_Format_Interface Interface");
		}

		if(empty($aResultData)){
			$oObject->aResultData = $this->_oGui->getOneColumnData();
		}
		// Gui object setzten um zugriff darauf zu haben
		$oObject->oGui = $this->_oGui;
		$oObject->sFlexType = $sFlexType;
		
		if($bSumRow){
			// Format der Summenzeile
			$mValue = $oObject->formatSum($mValue, $oColumn, $aResultData);
		}else{
			$mValue = $oObject->format($mValue, $oColumn, $aResultData);
		}


		return $mValue;
	}

	/**
	 * Gibt den Inhalt des title / tooltip zurück
	 * @param type $oColumn
	 * @param type $aResultData
	 * @param type $iType
	 * @return type handelt es sich um einen normalen Tooltip oder um einen MVC-Tooltip
	 */
	public function getColumnDisplayTitle(&$oColumn, &$aResultData) {
		$oObject = null;

		if(
			$oColumn->format instanceof Ext_Gui2_View_Format_Interface
		){
			$oObject = $oColumn->format;

		} else if(
			is_string($oColumn->format)
		){
			$sTempView = 'Ext_Gui2_View_Format_'.$oColumn->format;
			$oObject = new $sTempView();
		} else {
			throw new Exception("Please use a Ext_Gui2_View_Format_Interface Interface");
		}

		$aValue = array();

		$oObject->oGui = $this->_oGui;
		
		// Normales Tooltip (Funktion liefert gleich den Inhalt) oder // MVC Tooltip (Funktion startet Request an MVC-Controller)
		$aValue['data'] = $oObject->getTitle($oColumn, $aResultData);

		return $aValue;

	}

	/**
	 * den "Amount" ohne formatierungen holen ( für summenzeile )
	 */
	public function getColumnValue($mValue, &$oColumn, &$aResultData){

		$oObject = null;

		if(
			$oColumn->format instanceof Ext_Gui2_View_Format_Interface
		){
			$oObject = $oColumn->format;

		} else if(
			is_string($oColumn->format)
		){
			if(strtolower($oColumn->format) == 'text'){
				return $mValue;
			}
			$sTempView = 'Ext_Gui2_View_Format_'.$oColumn->format;
			$oObject = new $sTempView();
		} else {
			throw new Exception("Please use a Ext_Gui2_View_Format_Interface Interface");
		}

		$mValue = $oObject->getSumValue($mValue, $oColumn, $aResultData);

		return $mValue;
	}


	/**
	 * Hintergrundfarbe einer Zelle
	 */
	public function getColumnDisplayStyle($mValue, Ext_Gui2_Head &$oColumn, &$aResultData){
		
		$oObject = null;

		if(
			$oColumn->style instanceof Ext_Gui2_View_Style_Interface
		){
			$oObject = $oColumn->style;
		} else if(
			is_string($oColumn->style)
		){
			$sTempView = 'Ext_Gui2_View_Style_'.$oColumn->style;
			$oObject = new $sTempView();
		} else {
			throw new Exception("Please use a Ext_Gui2_View_Style_Interface Interface");
		}
        
		$mValue = $oObject->getStyle($mValue, $oColumn, $aResultData);

        if(empty($mValue) && $oObject instanceof Ext_Gui2_View_Style_Index_Interface){
            $mValue = $aResultData[$oColumn->select_column.'_style'];
        }

		return $this->applyColorBorderStyle($mValue);
	}

	## START Events die auf einer Zelle liegen
	/**
	 * Event einer Zelle
	 */
	public function getColumnEvent($mValue, &$oColumn, &$aResultData){

		$oObject = null;

		if(
			$oColumn->event instanceof Ext_Gui2_View_Event_Interface
		){
			$oObject = $oColumn->event;

		} elseif($oColumn->event == NULL){

		}else{
			throw new Exception("Please use a Ext_Gui2_View_Event_Interface Interface");
		}

		if($oObject != NULL){
			$mValue = $oObject->getEvent($mValue, $oColumn, $aResultData);
		} else {
			$mValue = '';
		}

		return $mValue;
	}

	/**
	 * Eventfunktion einer Zelle
	 */
	public function getColumnEventFunction($mValue, &$oColumn, &$aResultData){

		$oObject = null;
		$mValue = array();

		if(
			$oColumn->event instanceof Ext_Gui2_View_Event_Interface
		){
			$oObject = $oColumn->event;
		} elseif($oColumn->event == NULL){

		}else {
			throw new Exception("Please use a Ext_Gui2_View_Event_Interface Interface");
		}

		if($oObject != NULL){
			$mValue = $oObject->getFunction($mValue, $oColumn, $aResultData);
		}

		return $mValue;
	}
	## ENDE



	/**
	 * Hintergrundfarbe einer Zeile
	 */
	public function getRowDisplayStyle($iRowId, $aColumnList, &$aResultData){

		$oObject = null;

		if(
			$this->_oGui->row_style instanceof Ext_Gui2_View_Style_Interface
		){
			$oObject = $this->_oGui->row_style;
		} else if(
			is_string($this->_oGui->row_style)
		){
			$sTempView = 'Ext_Gui2_View_Style_'.$this->_oGui->row_style;
			$oObject = new $sTempView();
		} else {
			throw new Exception("Please use a Ext_Gui2_View_Style_Interface Interface");
		}

		$mValue = null;
		$mValue = $oObject->getStyle($mValue, $oColumn, $aResultData);

		return $this->applyColorBorderStyle($mValue);
	}

	protected function applyColorBorderStyle($style): ?string
	{
		if (
			!empty($style) &&
			preg_match('/background(?:-color)?\s*:\s*(#[0-9a-fA-F]{3,6})\b;?/i', $style) &&
			!str_contains($style, 'border-color')
		) {
			$matches = [];
			preg_match('/background(?:-color)?\s*:\s*(#[0-9a-fA-F]{3,6})\b;?/i', $style, $matches);

			if (!empty($matches[1])) {
				$styles = array_map('trim', explode(';', rtrim($style, ';')));
				$styles[] = sprintf('border-color: %s;', \Core\Helper\Color::changeLuminance($matches[1], -0.1));
				$style = implode('; ', $styles);
			}
		}

		return $style;
	}

}