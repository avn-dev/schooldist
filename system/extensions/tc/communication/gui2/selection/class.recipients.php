<?php

class Ext_TC_Communication_Gui2_Selection_Recipients extends Ext_Gui2_View_Selection_Abstract
{
	
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic)
	{
		$options = [];

		if (!empty($oWDBasic->applications)) {
			$recipients = \Factory::executeStatic(\Ext_TC_Communication::class, 'getSelectApplicationRecipients')
				->only($oWDBasic->applications)
				->flatten()
				->unique();

			$options = \Communication\Facades\Communication::getAllRecipients($this->_oGui->getLanguageObject())
				->only($recipients)
				->toArray();
		}

		return $options;
	}
	
}