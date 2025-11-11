<?php

class Ext_Gui2_Bar_Timefilter_BasedOn extends Ext_Gui2_Bar_Filter {
	
	protected $_aBasedOnColumns = array();
	
	public function __construct($sFilterType = 'select', $mFormat = '') {
		parent::__construct($sFilterType, $mFormat);
		
		// Darf nicht in den Query eingebaut werden
		$this->skip_query = true;
	}
	
	public function addColumn($sColumn, $aColumnSettings) {
		$this->_aBasedOnColumns[$sColumn] = $aColumnSettings;		
	}
	
	public function getColumnConfig($sColumn, $sKey) {
		
		if(!isset($this->_aBasedOnColumns[$sColumn])) {
			throw new Exception('Unknown filter column "'.$sColumn.'"!');
		}
		
		if(!isset($this->_aBasedOnColumns[$sColumn][$sKey])) {
			return null;
		}
		
		return $this->_aBasedOnColumns[$sColumn][$sKey];
	}
	
}
