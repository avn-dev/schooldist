<?php

class Ext_TC_Flexible_Gui2_Format_Fieldtype extends Ext_Gui2_View_Format_Abstract {

    public function format($mValue, &$oColumn = null, &$aResultData = null){

		$aTypeOptions	= Ext_TC_Flexibility::getFlexFieldTypes();

		if( array_key_exists( $mValue, (array)$aTypeOptions ) ){
			return $aTypeOptions[$mValue];
		}

		return $mValue;
	}

}

?>
