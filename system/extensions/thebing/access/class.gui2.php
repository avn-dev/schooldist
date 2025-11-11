<?php


class Ext_Thebing_Access_Gui2 extends Ext_Thebing_Gui2_Data
{

	/**
	 * @var array
	 */
	protected $_aAccessList = array();

	/**
	 * @param array $aAccessList
	 */
	public static function getAccessList()
    {

		$oAccess = new Ext_Thebing_Access();
		$aAccessList = $oAccess->getAccessSortRightList();

		// Liste noch einmal nach Position sortieren
		$aAccessList = Ext_Thebing_Access::sortRightAccessList($aAccessList);

		$aAccessListPrepared = array();
		foreach($aAccessList as $sGroupKey => $aGroupedAccessData) {
			foreach($aGroupedAccessData as $aAccessData) {
				$aAccessListPrepared[$sGroupKey][$aAccessData['section']][$aAccessData['access']] = $aAccessData;
			}
		}

		return $aAccessListPrepared;
	}

	static public function getAccessDialog(\Ext_Thebing_Gui2 $oGui)
    {

		$oDialog = $oGui->createDialog(L10N::t('Zugriffsrechte von "{description}"', $oGui->gui_description));

		$oDialog->width = 1100;
		$oDialog->height = 550;

		$oDialog->sDialogIDTag = 'ACCESS_';

		$aAccessListMain = static::getAccessList();

		foreach($aAccessListMain as $sTitle => $aDataMain) {

			$oTab = $oDialog->createTab($sTitle);
			$oDiv = $oDialog->create('div');
			$oDiv->setElement('<a href="javascript:void(0);" class="markCheckbox">'.$oGui->t('alle wählen').'</a>');
			$oDiv->style = 'text-align:right;';
			$oTab->setElement($oDiv);

			foreach($aDataMain as $sSection => $aSection) {

				foreach($aSection as $sAccessRight=>$aAccessRight) {

					$sAccessName = $aAccessRight['section'];

					if($aAccessRight['name'] !== 'Dummy') {
						$sAccessName .= ' &raquo; '.$aAccessRight['name'];
					}

					$oTab->setElement($oDialog->createRow(
						$sAccessName,
						'checkbox',
						array(
							'db_column'	=> 'licence',
							'db_alias'	=> $sAccessRight,
							'inputdiv_style' => 'float:right;'
						)
					));
				}
			}

			$oDialog->setElement($oTab);
		}

		return $oDialog;
	}

	protected function getDialogHTML(&$sIconAction, &$oDialogData, $aSelectedIds = array(), $sAdditional = false)
	{
		$aData = parent::getDialogHTML($sIconAction, $oDialogData, $aSelectedIds, $sAdditional);
		if($sIconAction=='access'){

			$aSelectedIds	= (array)$aSelectedIds;
			$oAccessLicence	= $this->_getAccessWdBasicObject($aSelectedIds);
			$aAccessList	= $oAccessLicence->getAccessList();

			$aValues = array();
			foreach($aAccessList as $iAccessId){
				$aValues[] = array(
					'db_column'	=> 'licence',
					'db_alias'	=> $iAccessId,
					'value'		=> 1,
				);
			}

			$aData['values'] = $aValues;
		}

		return $aData;
	}

	/**
	 * @param string $sAction
	 * @param array $aSelectedIds
	 * @param array $aData
	 * @param bool $sAdditional
	 * @param bool $bSave
	 * @return array|mixed
	 */
	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional = false, $bSave = true)
	{
		if($sAction=='access')
		{
			$aSelectedIds	= (array)$aSelectedIds;
			$iSelectedId	= (int)reset($aSelectedIds);

			if($iSelectedId > 0)
			{
				$oAccessLicence	= $this->_getAccessWdBasicObject($aSelectedIds);

				if($oAccessLicence->id > 0)
				{
					$aSaveDataLicence = $aData['licence'];

					$oAccessLicence->saveAccessList($aSaveDataLicence);
					// Auch speichern, da eine Änderung an diesen Datensatz stattgefunden hat,
					// jedoch wurde nur indirekt der Datensatz verändert somit muss man zusätzlich das Objekt
					// abspeichern.
					$oAccessLicence->save();
				}
			}

			\WDCache::deleteGroup(\Admin\Helper\Navigation::CACHE_GROUP_KEY);

			$aData['id']			= 'ACCESS_'.implode('_', $aSelectedIds);
			$aData['save_id']		= $iSelectedId;
			$aTransfer				= array();
			$aTransfer['action'] 	= 'saveDialogCallback';
			$aTransfer['error'] 	= array();
			$aTransfer['data'] 		= $aData;

			return $aTransfer;
		}
		else
		{
			return parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
		}
	}

	// Übersetzungen
	public function getTranslations($sL10NDescription)
	{
		$aData = parent::getTranslations($sL10NDescription);

		$aData['access_licence_select_all']			= L10N::t('alle wählen', $sL10NDescription);
		$aData['access_licence_unselect_all']		= L10N::t('alle abwählen', $sL10NDescription);

		return $aData;
	}

	protected function _getAccessWdBasicObject($aSelectedIds){
		if(!is_object($this->oWDBasic)){
			$this->_getWDBasicObject($aSelectedIds);
		}

		$oAccessLicence	= $this->oWDBasic;

		return $oAccessLicence;
	}

	static public function getOrderby(){

		return ['kag.name' => 'ASC'];
	}

	static public function getWhere() {

		return ['client_id' => \Ext_Thebing_Client::getClientId()];
	}

	static public function getDialog(\Ext_Thebing_Gui2 $oGui) {

		$oDialog			= $oGui->createDialog(L10N::t('Benutzergruppe editieren', $oGui->gui_description).' - {name}', L10N::t('Neue Benutzergruppe anlegen', $oGui->gui_description));
		$oDialog->width		= 900;
		$oDialog->height	= 650;
		$oDialog->setElement($oDialog->createRow(L10N::t('Bezeichnung', $oGui->gui_description), 'input', array('db_alias' => 'kag', 'db_column'=>'name','required' => 1)));

		return $oDialog;

	}

}