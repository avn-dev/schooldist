<?php

class Ext_RowTest extends Ext_Gui2_View_MultipleCheckbox_Abstract{
	public function getStatus($iRowID, &$aColumnList, &$aResultData)
	{
		if($iRowID==51)
		{
			return 0;
		}

		return 1;
	}
}

?>
