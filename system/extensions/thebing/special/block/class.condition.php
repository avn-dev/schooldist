<?php
/**
 * Beschreibt einen Special-Block
 */
class Ext_Thebing_Special_Block_Condition extends Ext_Thebing_Basic {

	// Tabellenname
	protected $_sTable = 'ts_specials_blocks_conditions';

	/**
	 * 
	 * @param int $iWeeks
	 * @return boolean
	 */
	public function validateWeeks($iWeeks) {
		$sConditionSymbol	= (int) $this->symbol;
		$iConditionWeeks	= (int) $this->weeks;

        if(
			$sConditionSymbol != '0' &&
			$iConditionWeeks > 0
		){
			switch ($sConditionSymbol) {
				case 1: // <
					if($iWeeks < $iConditionWeeks){
						return true;
					}
					break;
				case 2: // =
					if($iWeeks == $iConditionWeeks){
						return true;
					}
					break;
				case 3: // >
					if($iWeeks > $iConditionWeeks){
						return true;
					}
					break;
			}

        } else {
            return true;
        }
        
        return false;
	}
	
}
