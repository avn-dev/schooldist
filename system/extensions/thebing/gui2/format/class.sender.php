<?

class Ext_Thebing_Gui2_Format_Sender extends Ext_Thebing_Gui2_Format_Name {

	protected $_bGetById;

	public function __construct($bGetById=false)
	{
		$this->_bGetById = $bGetById;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if(
			(
				!isset($aResultData['lastname']) &&
				!isset($aResultData['firstname'])
			) ||
			$this->_bGetById
		) {
			if($mValue > 0) {
				$oUser = Ext_Thebing_User::getInstance((int)$mValue);
				$aResultData['lastname'] = $oUser->lastname;
				$aResultData['firstname'] = $oUser->firstname;
			} else {
				$aResultData['lastname'] = L10N::t('Automatische E-Mail', 'Thebing » Communication');
				$aResultData['firstname'] = '';
			}
		}

		$sName = parent::format($mValue, $oColumn, $aResultData);

		return $sName;

	}

}
