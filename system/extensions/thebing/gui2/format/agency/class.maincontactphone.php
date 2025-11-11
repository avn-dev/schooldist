<?

class Ext_Thebing_Gui2_Format_Agency_MainContactPhone extends Ext_Thebing_Gui2_Format_Format
{
	public function format($mValue, &$oColumn = null, &$aResultData = null)
	{
		$oAgency = Ext_Thebing_Agency::getInstance($mValue);

		$aContacts = (array)$oAgency->contacts;

		foreach($aContacts as $aContact)
		{
			if($aContact['master_contact'])
			{
				return $aContact['phone'];
			}
		}

		return '';
	}
}
