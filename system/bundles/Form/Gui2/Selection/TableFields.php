<?php

namespace Form\Gui2\Selection;

class TableFields extends \Ext_Gui2_View_Selection_Abstract {
	
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		
		$aOptions = [];
		
		$sDbTable = \Util::decodeSerializeOrJson($oWDBasic->getAdditional('db_table'));
		
		if(!empty($sDbTable)) {
			$aDescribe = \DB::describeTable($sDbTable);

			foreach($aDescribe as $aField) {
				$aOptions[$aField['COLUMN_NAME']] = $aField['COLUMN_NAME'];
			}
		}

		return $aOptions;
	}
	
}
