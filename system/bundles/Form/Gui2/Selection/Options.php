<?php

namespace Form\Gui2\Selection;

class Options extends \Ext_Gui2_View_Selection_Abstract {
	
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		if($this->oJoinedObject instanceof \Form\Entity\Option\Condition) {

			$oField = \Form\Entity\Option::getInstance($this->oJoinedObject->field);

			$oFieldProxy = new \Form\Proxy\Field($oField);

			$aOptions = $oFieldProxy->getOptions();

			$aReturn = [];
			foreach($aOptions as $sValue) {
				$aReturn[$sValue] = $sValue;
			}
			
			$aReturn = \Util::addEmptyItem($aReturn, '', '');

			return $aReturn;
		}
		
	}
	
}
