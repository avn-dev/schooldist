<?php
/**
 * UML - https://redmine.thebing.com/redmine/issues/278
 */
class Ext_TC_Frontend_Form_Field_Select extends Ext_TC_Frontend_Form_Field_Abstract {
		
	const EMPTY_OPTION_KEY = "";
	
	protected $aSelectionSettings = [];
	
	protected $_bSetLabelAsEmptyOption = false;
	
	protected $_sTemplateType = 'select';
	
	public function getValue($bFormated = true, $sLanguage = null) {

		$sValue = parent::getValue($bFormated, $sLanguage);

		if ($bFormated) {
			$aOptions = $this->getOptions(true, false, $sLanguage);

			if (is_array($sValue)) {
				$sValue = implode(', ', array_intersect_key($aOptions, array_flip($sValue)));
			} else if (isset($aOptions[$sValue])) {
				$sValue = $aOptions[$sValue];
			} else {
				$sValue	= '';
			}
		}

		return $sValue;
	}
	
	/**
	 * set the first Value of the Select into the Entity
	 */
	public function setFirstValue(){
		$mValue = $this->getFirstValue();
		$this->setValue($mValue);
		$this->setEntityValue();
	}
	
	/**
	 * get the first Value of the Select
	 * @return string 
	 */
	public function getFirstValue(){
		$aOptions = $this->getOptions(true);       
		$mValue = key($aOptions);
		return $mValue;
	}
	
	/**
	 * get an array with the Select options
	 * @return array 
	 */
	public function getOptions($bUnsetEmptyOption = false, $bGrouped = false, $sLanguage = null){

		$aOptions = $this->_getOptionsBySelection($bUnsetEmptyOption, $bGrouped, $sLanguage);
		
		reset($aOptions);
		$sTemp = (string)key($aOptions);
		// Wenn kein leereintrag (oder gar keine auswahlmöglichkeiten) dann immer den ersten auswählen falls noch kein wert in der WDBasic steht
		if(
			!empty($sTemp) || 
			empty($aOptions)
		){
			$sEntityValue = $this->getEntityValue();
			// Wenn noch nicht gesetzt oder wert nicht im select vorhanden
			if(
				empty($sEntityValue) ||
				empty($aOptions) ||
				!key_exists($sEntityValue, $aOptions)
			){
				$this->setValue($sTemp);
				$this->setEntityValue();
			}
		}

        if($bUnsetEmptyOption === true) {
			$this->unsetEmptyOption($aOptions);
        }
        		
		return $aOptions;
	}

	protected function _getOptionsBySelection($bUnsetEmptyOption = false, $bGrouped = false, $sLanguage = null) {
		
		$aOptions = array();
		$oSelection = $this->getSelection();

		if($oSelection) {
			$oWDBasic = $this->_oForm->getEntity();
		
			if($oWDBasic) {
				$oSelection->frontend_form = $this->_oForm;
				$oSelection->frontend_field = $this;

				if($oSelection instanceof Ext_TC_Frontend_Form_Field_Select_Selection) {
					$oSelection->initialize();
					$oSelection->setSelectionSettings($this->aSelectionSettings);
				}

				$sInterfaceLanguage = Ext_TC_System::getInterfaceLanguage();		
				if($sLanguage !== null) {
					Ext_TC_System::setInterfaceLanguage($sLanguage);
				}
				
				if(
					$bGrouped === true &&
					$oSelection instanceof Ext_TC_Frontend_Form_Field_Select_Selection
				) {
					$aOptions = (array) $oSelection->getGroupedOptions(array(), array(), $oWDBasic);
				} else {
					$aOptions = (array) $oSelection->getOptions(array(), array(), $oWDBasic);
				}
					
				Ext_TC_System::setInterfaceLanguage($sInterfaceLanguage);
				
				if($bUnsetEmptyOption === false) {
					$this->setFirstOptionEmpty($aOptions);			

					if($this->_checkFlag()) {
						$aOptions = $this->setLabelInEmptyOption($aOptions);
					}
				}
			}
		}
		
		if(!empty($this->aSelectionSettings['id']) || !empty($this->aSelectionSettings['exclude_id'])) {
			return $this->filterOptionsBySelectionSettings($aOptions);
		}
				
		return $aOptions;
	}

	protected function filterOptionsBySelectionSettings($aOptions) {
		$aReturn = $aOptions;
		
		if(!empty($this->aSelectionSettings['id'])) {
			$aReturn = array_intersect_key($aReturn, array_flip((array)$this->aSelectionSettings['id']));
		}

		if(!empty($this->aSelectionSettings['exclude_id'])) {
			$aReturn = array_diff_key($aReturn, array_flip((array)$this->aSelectionSettings['exclude_id']));
		}

		return $aReturn;
	}

	/**
	 * Set label as empty option
	 * 
	 * @param bool $bFlag
	 */
	public function setLabelAsEmptyOption($bFlag = true)
	{
		$this->_bSetLabelAsEmptyOption = $bFlag;

		if($bFlag)
		{
			$sFlag = $this->_oTemplate->placeholder;

			if(!empty($sFlag))
			{
				$oParent = $this->_oForm->searchFirstParent();

				$oParent->setSelectionFlag($sFlag);
			}
		}
	}

	protected function _checkFlag() {
		$oParent = $this->_oForm->searchFirstParent();

		$sFlag = $this->_oTemplate->placeholder;

		$bCheck = $oParent->hasSelectionFlag($sFlag);

		return $bCheck;
	}
	
	protected function setLabelInEmptyOption($aOptions) {
		
		$aTmp = $aOptions;
		$aOptions = array(self::EMPTY_OPTION_KEY => $this->getLabel());
		foreach($aTmp as $sKey => $sValue) {
			if($sKey !== self::EMPTY_OPTION_KEY) {
				$aOptions[$sKey] = $sValue;
			}
		}
		
		return $aOptions;
	}
	
	protected function setFirstOptionEmpty(&$aOptions) {
		
		if(
			isset($aOptions[0]) && 
			empty($aOptions[0])
		) {
			$aFirst = array(self::EMPTY_OPTION_KEY => '');
			$aOptions = $aFirst + $aOptions;
			unset($aOptions[0]);
		}
	}
	
	/**
	 * Entfernt den Leereintrag aus den Options
	 * 
	 * @param array $aOptions
	 */
	protected function unsetEmptyOption(&$aOptions) {
		
		if(isset($aOptions[0])) {
			unset($aOptions[0]);
		} else if(isset($aOptions[self::EMPTY_OPTION_KEY])) {
			unset($aOptions[self::EMPTY_OPTION_KEY]);
		}
		
	}
	
	public function getSelection() {
		$oSelection = $this->_oMapping->getSelection();
		
		if($oSelection instanceof Ext_TC_Frontend_Form_Field_Select_Selection) {
			$oSelection->setSelectionSettings($this->aSelectionSettings);
		}
		
		return $oSelection;
	}
	
	public function setSelectionSettings(array $aSelectionSettings) {
		$this->aSelectionSettings = $aSelectionSettings;
	}
	
}
