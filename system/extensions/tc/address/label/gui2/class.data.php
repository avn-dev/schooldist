<?php

class Ext_TC_Address_Label_Gui2_Data extends Ext_TC_Gui2_Data
{

	public static $aFields = [
		'company' => ['label' => 'Firmenname'],
		'address' => ['label' => 'Straße'],
		'address_addon' => ['label' => 'Adresszusatz'],
		'zip' => ['label' => 'PLZ'],
		'city' => ['label' => 'Stadt'],
		'state' => ['label' => 'Bundesland'],
		'country_iso' => ['label' => 'Land'],
		'address_additional' => ['label' => 'Zusatz']
	];

	/**
	 * {@inheritdoc}
	 */
	protected function getEditDialogHTML(&$oDialog, $aSelectedIds, $sAdditional = false)
	{
		$aSelectedIds = (array)$aSelectedIds;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		if(!$this->oWDBasic instanceof WDBasic)
		{
			$this->_getWDBasicObject($aSelectedIds);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Get languages from selected objects

		$aSelectedLanguages = $aLanguages = array();

		$aSelectedObjects = (array)$this->oWDBasic->objects;

		foreach($aSelectedObjects as $iObjectId)
		{
			$oSubObject = Ext_TC_Factory::getInstance('Ext_TC_SubObject', $iObjectId);

			$aTemp = (array)$oSubObject->getCorrespondenceLanguagesOptions();

			$aSelectedLanguages = $aSelectedLanguages + $aTemp;
		}

		foreach($aSelectedLanguages as $sKey => $sLanguage)
		{
			$aLanguages[] = array(
				'iso'	=> $sKey,
				'name'	=> $sLanguage
			);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Reset save data

		$oDialog->aSaveData = $oDialog->aUniqueFields = array();

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oTab_1 = $oDialog->createTab($this->_oGui->t('Name'));

		$oTab_1->setElement(
			$oDialog->createRow(
				Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getSubObjectLabel'),
				'select',
				array(
					'required'				=> true,
					'db_alias'				=> 'objects',
					'db_column'				=> 'objects',
					'select_options'		=> Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getSubObjects', array(true)),
					'multiple'				=> 5, 
					'jquery_multiple'		=> true,
					'searchable'			=> true,
					'events'				=> array(
						array(
							'event' 	=> 'change',
							'function' 	=> 'reloadDialogTab',
							'parameter'	=> 'aDialogData.id, 0'
						)
					)
				)
			)
		);

		$oTab_1->setElement(
			$oDialog->createI18NRow(
				$this->_oGui->t('Bezeichnung'),
				array(
					'required'				=> true,
					'db_alias'				=> 'i18n',
					'db_column'				=> 'name',
					'i18n_parent_column'	=> 'label_id'
				),
				$aLanguages
			)
		);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oTab_2 = $oDialog->createTab($this->_oGui->t('Felder'));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDialog->aElements = array($oTab_1, $oTab_2);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Set save data

		if(!empty($aSelectedIds))
		{
			$this->aIconData['edit']['dialog_data'] = $oDialog;
		}
		else
		{
			$this->aIconData['new']['dialog_data'] = $oDialog;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$aData = parent::getEditDialogHTML($oDialog, $aSelectedIds, $sAdditional);

		return $aData;
	}


	/**
	 * Get the array of fields
	 * 
	 * @return array
	 */
	public function getFields()
	{
		$aFields = [];
		foreach (self::$aFields as $sField => $aFieldConfig) {
			$aFields[$sField] = $this->_oGui->t($aFieldConfig['label']);
		}

		return $aFields;
	}


	/**
	 * See parent
	 */
	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab = false, $sAdditional = false, $bSaveSuccess = true)
	{
		$aData = parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		if($sIconAction == 'new' || $sIconAction == 'edit')
		{
			$aData['tabs'][1]['html'] = $this->_writeFields();
		}

		return $aData;
	}


	/**
	 * See parent
	 */
	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional = false, $bSave = true)
	{
		global $_VARS;

		$aTransfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);

		if($bSave)
		{
			$mErrors = $this->_validateFields($_VARS['fields']);

			if(
				$mErrors !== true &&
				!$_VARS['ignore_errors']
			)
			{
				$aTransfer['data']['show_skip_errors_checkbox'] = 1;

				$aTransfer['error'] = array(
				    0 => array(
			           'message'	=> $this->_oGui->t('Bitte beachten Sie, dass durch das Umstellen von mehrzeilig auf einzeilig Daten evtl. verloren gehen können oder nur in einer Zeile dargestellt werden.'),
			           'type'		=> 'hint'
			        )
				);
			}

			if(
				$mErrors === true ||
				(
					$mErrors !== true &&
					$_VARS['ignore_errors']
				)
			)
			{
				asort($_VARS['fields']['position']);

				$aFields = array();

				foreach($_VARS['fields']['position'] as $sField => $iPosition)
				{
					$aFields[] = array(
						'label_id'		=> (int)$this->oWDBasic->id,
						'field'			=> (string)$sField,
						'display'		=> (int)$_VARS['fields']['display'][$sField],
						'type'			=> (string)$_VARS['fields']['type'][$sField],
						'position'		=> (int)$iPosition
					);
				}

				$this->oWDBasic->fields = $aFields;

				$this->oWDBasic->save();

				if($mErrors !== true)
				{
					$this->_updateData($mErrors, $this->oWDBasic->id);
				}

				$aTransfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
			}
		}

		return $aTransfer;
	}


	/**
	 * Update data in fields from textarea to input
	 * 
	 * @param array $aFields
	 */
	protected function _updateData(array $aFields, $iLabelID)
	{
		foreach($aFields as $sField)
		{
			switch($sField)
			{
				case 'address':
				case 'address_addon':
				case 'address_additional':
				{
					$sSQL = "
						UPDATE
							`tc_addresses`
						SET
							`" . $sField . "` = REPLACE(`" . $sField . "`, '\n', ' ')
						WHERE
							`label_id` = " . (int)$iLabelID . "
					";
					DB::executeQuery($sSQL);

					break;
				}
				default:
					continue;
			}
		}
	}


	/**
	 * Validate the switch from textarea to input
	 * 
	 * @return bool
	 */
	protected function _validateFields($aVars)
	{
		$aFields = (array)$this->oWDBasic->fields;

		if(empty($aFields))
		{
			return true;
		}

		$aErrors = array();

		foreach($aFields as $aField)
		{
			if(
				$aField['type'] === 'textarea' &&
				$aVars['type'][$aField['field']] === 'input' &&
				in_array($aField['field'], array('address', 'address_addon', 'address_additional'))
			)
			{
				$aErrors[$aField['field']] = $aField['field'];
			}
		}

		if(empty($aErrors))
		{
			return true;
		}

		return $aErrors;
	}


	/**
	 * Write the fields from smarty template
	 * 
	 * @return string
	 */
	protected function _writeFields()
	{
		$sPath = \Util::getDocumentRoot().'system/legacy/admin/extensions/tc/addresslabels/templates/tab_fields.tpl';

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$aData		= $this->oWDBasic->fields;
		$aFields	= $this->getFields();

		if(empty($aData))
		{
			foreach($aFields as $sKey => $sTitle)
			{
				$aData[] = array(
					'field' 	=> $sKey,
					'display'	=> 1
				);
			}
		}

		$aTranslations = array(
			'input'		=> $this->_oGui->t('einzeilig'),
			'textarea'	=> $this->_oGui->t('mehrzeilig'),
			'display'	=> $this->_oGui->t('Anzeigen'),
			'postbox'	=> $this->_oGui->t('Postfach')
		);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oSmarty = new SmartyWrapper();

		$oSmarty->assign('aData', $aData);
		$oSmarty->assign('aFields', $aFields);
		$oSmarty->assign('aTranslations', $aTranslations);

		$sCode = $oSmarty->fetch($sPath);

		return $sCode;
	}
}
