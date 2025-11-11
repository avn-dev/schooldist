<?php

/**
 * @property Ext_Thebing_User oWDBasic
 */
class Ext_Thebing_User_Gui2_Data extends Ext_TC_User_Gui2 {

	static $aAccess = array('thebing_admin_users', '');

	/**
	 * @inheritdoc
	 */
	protected function getEditDialogData($aSelectedIds, $aSaveData = array(), $sAdditional = false) {

		if(!$this->oWDBasic) {
			$this->_getWDBasicObject($aSelectedIds);
		}

		$aData = parent::getEditDialogData($aSelectedIds, $aSaveData, $sAdditional);

		foreach($aData as &$aSaveField) {
			if($aSaveField['db_alias'] === 'ts_sus') {
				// E-Mail-Settings lesen
				list($iSchoolId, $sField) = explode('_', $aSaveField['db_column'], 2);
				foreach((array)$this->oWDBasic->school_settings as $aSetting) {
					if((int)$aSetting['school_id'] == $iSchoolId) {
						$aSaveField['value'] = $aSetting[$sField];

						if($aSaveField['element'] === 'checkbox') {
							// "0" ist für gui2.js auch checked
							$aSaveField['value'] = (int)$aSaveField['value'];
						}
					}
				}
			} elseif($aSaveField['db_alias'] === 'kui') {
				// Identitäten
				$iSchoolId = (int)explode('_', $aSaveField['db_column'])[0];
				$aSaveField['value'] = [];
				foreach((array)$this->oWDBasic->sender_identities as $aIdentity) {
					if((int)$aIdentity['school_id'] == $iSchoolId) {
						$aSaveField['value'][] = $aIdentity['identity_id'];
					}
				}
			}
		}

		return $aData;
	}

	public static function getDialog($oGui, $bNew = true){

		$oDialog = $oGui->createDialog($oGui->t('Benutzer "{name}" editieren'), $oGui->t('Neuen Benutzer anlegen'));

		return $oDialog;
	}

	/**
	 * @inheritdoc
	 */
	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave = true, $mAction = 'edit', $bPrepareOpenDialog = true) {

		if($bSave) {

			$this->_getWDBasicObject($aSelectedIds);

			$aSchoolSettings = $aIdentities = [];
			foreach($aSaveData as $sDbColumn => $mValue) {
				if(!is_array($mValue)) {
					continue;
				}

				foreach($mValue as $sDbAlias => $mSaveFieldData) {
					if($sDbAlias === 'ts_sus') {
						// Einstellungen für E-Mail-Versand
						list($iSchoolId, $sField) = explode('_', $sDbColumn, 2);

						if(!isset($aSchoolSettings[$iSchoolId])) {
							$aSchoolSettings[$iSchoolId] = $this->oWDBasic->getSchoolSetting($iSchoolId);
						}

						$aSchoolSettings[$iSchoolId][$sField] = $mSaveFieldData;

					} elseif($sDbAlias === 'kui') {
						// Identitäten
						$iSchoolId = (int)explode('_', $sDbColumn)[0];
						foreach($mSaveFieldData as $sSelectedOptionKey) {
							$aIdentities[] = [
								'user_id' => $this->oWDBasic->id,
								'school_id' => $iSchoolId,
								'identity_id' => $sSelectedOptionKey
							];
						}
					}
				}
			}

			$this->oWDBasic->school_settings = $aSchoolSettings;
			$this->oWDBasic->sender_identities = $aIdentities;

		}

		return parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $mAction, $bPrepareOpenDialog);

	}

	protected function getDialogHTML(&$sIconAction, &$oDialogData, $aSelectedIds = array(), $sAdditional = false) {

		$aData = parent::getDialogHTML($sIconAction, $oDialogData, $aSelectedIds, $sAdditional);

		if($sIconAction == 'access') {

			$aSelectedIds = (array)$aSelectedIds;
			if(count($aSelectedIds) > 1){
				return array();
			}else{
				$iUserId = (int) reset($aSelectedIds);
			}

			foreach($aData['tabs'] as $iTab=>&$aTabData) {
				if($iUserId > 0) {
					if($aTabData['options']['task'] == 'usergroups') {
						$oAccess = new Ext_Thebing_Access_Html();
						$sHtml = $oAccess->getUserGroupDialog($iUserId);
						$aTabData['html'] = $sHtml;
					} elseif($aTabData['options']['task'] == 'accessrights') {
						$oAccess = new Ext_Thebing_Access_Html();
						$sHtml = $oAccess->getSchoolAccessDialog($iUserId, $this->_oGui->hash);
						$aTabData['html'] = $sHtml;
					}
				} else {
					if(
						$aTabData['options']['task'] == 'usergroups' ||
						$aTabData['options']['task'] == 'accessrights'
					) {
						unset($aData['tabs'][$iTab]);
					}
				}
			}

		}

		return $aData;

	}

	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional = false, $bSave = true) {
		global $_VARS;

		$aSelectedIds = (array)$aSelectedIds;
		$iSelectedId = reset($aSelectedIds);

		if($sAction == 'access') {

			$aSelectedIds = (array)$aSelectedIds;
			if(count($aSelectedIds) > 1){
				return array();
			}else{
				$iUserId = (int) reset($aSelectedIds);
			}

			$aAccess = $_VARS['group'];
			$oAccess = new Ext_Thebing_Access_User($iUserId);
			foreach($aAccess as $iSchool => $iGroup) {
				$oAccess->setGroupForSchool($iSchool, $iGroup);
			}

			$aAccess = $_VARS['access'];
			$oAccess = new Ext_Thebing_Access_User($iUserId);
			foreach($aAccess as $iSchool => $aAccessData) {
				foreach($aAccessData as $sAccess => $iStatus) {
					$oAccess->setAccess((int)$iSchool, (string)$sAccess, (int)$iStatus);
				}
			}

			\WDCache::deleteGroup(\Admin\Helper\Navigation::CACHE_GROUP_KEY);
			
			$aData = $this->prepareOpenDialog($sAction, $aSelectedIds, false , $sAdditional);

			$aData['id']			= 'ACCESS_'.implode('_', (array)$aSelectedIds);
			$aData['save_id']		= (int)$iSelectedId;
			$aTransfer				= array();
			$aTransfer['action'] 	= 'saveDialogCallback';
			$aTransfer['error'] 	= array();
			$aTransfer['data'] 		= $aData;

			// Das "Bearbeitet am" bei dem Benutzer abspeichern!
			$oSystemUser = Ext_Thebing_User::getInstance($iUserId);
			$oSystemUser->save();

			return $aTransfer;

		} else {

			$aData = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);

			return $aData;

		}

	}

	static public function getAccessDialog(\Ext_Gui2 $oGui, $aSavedAccess=[], $bOnlyForCurrentAccess = false, $sCurrentType = 'core', $aDataForComparison = null) {

		$oDialog = $oGui->createDialog($oGui->t('Zugriffsrechte von "{name}"'));

		$oTab = $oDialog->createTab($oGui->t('Benutzergruppen'));
		$oTab->aOptions['task'] = 'usergroups';
		$oDialog->setElement($oTab);

		$oTab = $oDialog->createTab($oGui->t('Zugriffsrechte'));
		$oTab->aOptions['task'] = 'accessrights';
		$oDialog->setElement($oTab);

		$oDialog->sDialogIDTag = 'ACCESS_';

		return $oDialog;
	}

	public static function getGuiIconBarHook($oBar, $oGui) {

		$oIcon = $oBar->createIcon(
			'fa-user',
			'request',
			$oGui->t('Vertriebsmitarbeiter')
		);
		$oIcon->access = 'thebing_admin_users_salesperson';
		$oIcon->label = $oGui->t('Vertriebsmitarbeiter');
		$oIcon->action = 'salesperson';
		$oIcon->active = 0;
		$oBar->setElement($oIcon);

	}

	/**
	 * Hook prüft ob der User gelöscht werden darf und kein Master-user ist
	 *
	 * @param int $iRowId
	 * @return array|bool
	 */
	protected function checkDeleteRow($iRowId) {

		$mCheck = parent::checkDeleteRow($iRowId);
		if($mCheck !== true) {
			return $mCheck;
		}

		// TODO Unten werden doch nochmal alle Master-User geholt, da kann man auch suchen
		$sSql = "SELECT
					`kcu`.`master`
				FROM
					`system_user` `su` JOIN
					`kolumbus_clients_users` `kcu` ON
						`su`.`id` = `kcu`.`user_id`
				WHERE
					`su`.`id` = :user_id";
		$aSql = array();
		$aSql['user_id'] = (int)$iRowId;

		$aData = DB::getQueryRow($sSql,$aSql);

		// Prüfen ob es mehrere Masteruser existieren, wenn ja, dann darf so lange der Masteruser gelöscht werden
		// bis nur noch einer vorhanden ist! Ticket #9224
		$oWdbasicRepository = $this->oWDBasic->getRepository(); /** @var Ext_Thebing_UserRepository $oWdbasicRepository */
		$aMasterUsers = $oWdbasicRepository->getMasterUsers();
		$iNumberOfMasterUsers = count($aMasterUsers);

		if(
			$iNumberOfMasterUsers <= 1 &&
			(int)$aData['master'] === 1
		) {
			$aSubError = array(L10N::t('Der Masteruser kann nicht gelöscht werden!', $this->_oGui->gui_description));

			$aError = array();
			$aError[] = $aSubError;
			return $aError;
		}else{
			return true;
		}
	}

	/**
	 * @param array $aVars
	 *
	 * @return array
	 */
	protected function requestSalesperson($aVars) {
		
		$oEntity = $this->_getWDBasicObject($aVars['id']);
		
		$oDialog = new Ext_Gui2_Dialog();
		$oDialog->save_button = false;
		$oDialog->sDialogIDTag = 'SALESPERSON_';
		
		$oIframe = new Ext_Gui2_Html_Iframe();
		$oIframe->src = '/wdmvc/ts/salesperson/setup?id='.(int)$oEntity->id;
		$oIframe->style = 'width: 100%; height: 100%; border: 0;';

		$oDialog->setElement($oIframe);
		
		$aTransfer = [];
		$aTransfer['data'] = $oDialog->getDataObject()->getHtml($aVars['action'], $aVars['id'], $aVars['additional']);
		
		$aTransfer['data']['title'] = str_replace('{name}', $oEntity->getName(), $this->t('Vertriebsmitarbeiter "{name}"'));
		
		$aTransfer['data']['no_scrolling'] = true;
		$aTransfer['data']['no_padding'] = true;
		$aTransfer['data']['full_height'] = true;
		
		$aTransfer['action'] = 'openDialog';
		
		return $aTransfer;
	}

	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab = false, $sAdditional = false, $bSaveSuccess = true) {

		$sIconKey = self::getIconKey($sIconAction, $sAdditional);

		if(!$this->oWDBasic) {
			$this->_getWDBasicObject($aSelectedIds);
		}

		$bNew = false;
		if(empty($aSelectedIds)) {
			$bNew = true;
		}

		$oDialog = $this->getEmployeeDialog($this->_oGui, $bNew);
		$this->aIconData[$sIconKey]['dialog_data'] = $oDialog;

		if($sIconAction === 'new') {

			$sEditIconKey = self::getIconKey('edit', $sAdditional);

			$oDialog = $this->getEmployeeDialog($this->_oGui, false);
			$this->aIconData[$sEditIconKey]['dialog_data'] = $oDialog;

		}

		$aData = parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);

		return $aData;

	}

	public function getEmployeeDialog($oGui, $bNew = true) {

		$aCountries = Ext_Thebing_Data::getCountryList(true, true);
		$aSex = Ext_Thebing_Util::getGenders();
		$aSchools = Ext_Thebing_Client::getSchoolList(true);
		$aSchoolsWithAllSchools = [0 => $oGui->t('Alle Schulen')] + $aSchools;
		$oClient = Ext_Thebing_Client::getInstance();
		$aUsers = $oClient->getUsers(true);
		$aLanguages = Ext_Thebing_Client::getLangList(true);

		$oDialog = $oGui->createDialog(L10N::t('Benutzer "{name}" bearbeiten', $oGui->gui_description), L10N::t('Neuer Benutzer', $oGui->gui_description));

		$oTab = $oDialog->createTab(L10N::t('Daten', $oGui->gui_description));

		if($this->oWDBasic->hasSystemType('user')) {
			$oTab->setElement($oDialog->createRow(L10N::t('Aktiv', $oGui->gui_description), 'checkbox', array('db_alias' => 'su', 'db_column' => 'status', 'default_value' => 1)));
		}

		$aSystemTypes = \Tc\Entity\SystemTypeMapping::getSelectOptions(Ext_TC_User::MAPPING_TYPE);

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Kategorien'),
				'select',
				[
					'db_alias' => '',
					'db_column' => 'system_types',
					'select_options' => $aSystemTypes,
					'multiple' => 5,
					'jquery_multiple' => true,
					'events'=>[
						[
							'event' 	=> 'change',
							'function' 	=> 'reloadDialogTab',
							'parameter'	=> 'aDialogData.id, [0,1,2]'
						]
					]
				]
			)
		);

		if($this->oWDBasic->hasSystemType('user')) {

			$oSmarty = new SmartyWrapper;
			$sForgotPasswordStrength = $oSmarty->fetch('system/bundles/Admin/Resources/views/forgot_password_strength.tpl');

			if ($bNew) {
				$oTab->setElement($oDialog->createRow(L10N::t('Passwort', $oGui->gui_description), 'input', array('db_alias' => 'su', 'db_column' => 'password', 'required' => 1, 'input_div_elements' => [$sForgotPasswordStrength])));
			} else {
				$oTab->setElement($oDialog->createRow(L10N::t('Passwort', $oGui->gui_description), 'input', array('db_alias' => 'su', 'db_column' => 'password', 'input_div_elements' => [$sForgotPasswordStrength])));
			}
		}

		$oTab->setElement($oDialog->createRow(L10N::t('Vorname', $oGui->gui_description), 'input', array('db_column'=>'firstname', 'required'=>1)));
		$oTab->setElement($oDialog->createRow(L10N::t('Nachname', $oGui->gui_description), 'input', array('db_column'=>'lastname', 'required'=>1)));
		$oTab->setElement($oDialog->createRow(L10N::t('Geschlecht', $oGui->gui_description), 'select', array('db_column'=>'sex', 'select_options' => $aSex)));
		$oTab->setElement($oDialog->createRow(L10N::t('Geburtsdatum', $oGui->gui_description), 'calendar', array('db_column'=>'birthday', 'format'=>new Ext_Thebing_Gui2_Format_Date())));
		
		if($this->oWDBasic->hasSystemType('user')) {
			$oTab->setElement($oDialog->createRow($oGui->t('Hauptbenutzer'), 'checkbox', array('db_column' => 'master', 'db_alias' => 'su')));
		}
		
//$oTab->setElement($oDialog->createRow(L10N::t('Homepage', $oGui->gui_description), 'input', array('db_column'=>'homepage')));
		$oH3	= $oDialog->create('h4');
		$oH3	->setElement(L10N::t('Kontaktinformationen', $oGui->gui_description));
		$oTab	->setElement($oH3);
		$oTab->setElement($oDialog->createRow(L10N::t('E-Mail', $oGui->gui_description), 'input', array('db_alias' => 'su', 'db_column'=>'email', 'required'=>1)));
		$oTab->setElement($oDialog->createRow(L10N::t('Telefon', $oGui->gui_description), 'input', array('db_column'=>'phone')));
		$oTab->setElement($oDialog->createRow(L10N::t('Fax', $oGui->gui_description), 'input', ['db_column'=>'fax']));
//$oTab->setElement($oDialog->createRow(L10N::t('Firma', $oGui->gui_description), 'input', array('db_column'=>'company')));
		$oH3	= $oDialog->create('h4');
		$oH3	->setElement(L10N::t('Adresse', $oGui->gui_description));
		$oTab	->setElement($oH3);
		$oTab->setElement($oDialog->createRow(L10N::t('Adresse', $oGui->gui_description), 'input', array('db_column'=>'street')));
		$oTab->setElement($oDialog->createRow(L10N::t('PLZ', $oGui->gui_description), 'input', array('db_column'=>'zip')));
		$oTab->setElement($oDialog->createRow(L10N::t('Ort', $oGui->gui_description), 'input', array('db_column'=>'city')));
		$oTab->setElement($oDialog->createRow(L10N::t('Bundesland', $oGui->gui_description), 'input', array('db_column'=>'state')));
		$oTab->setElement($oDialog->createRow(L10N::t('Land', $oGui->gui_description), 'select', array('db_column'=>'country', 'select_options' => $aCountries)));

		$oH3 = $oDialog->create('h4');
		$oH3->setElement(L10N::t('Sonstiges', $oGui->gui_description));
		$oTab->setElement($oH3);

		if(Ext_Thebing_Access::hasRight('thebing_marketing_material_orders_orders')){
			$oTab->setElement($oDialog->createRow(L10N::t('Verantwortlich für Materialbestellungen', $oGui->gui_description), 'checkbox', array('db_column'=>'thebing_material_orders', 'default_value' => '1')));
		}

		if($this->oWDBasic->hasSystemType('user')) {

			$oTab->setElement(
				$oDialog->createRow(
					$oGui->t('Authentifizierung'),
					'select',
					array(
						'db_column' => 'authentication',
						'select_options' => User::getAuthenticationMethods()
					)
				)
			);
		}

		$oTab->aOptions['section'] = 'admin_users';
		$oTab->aOptions['task'] = 'data';
		$oDialog->setElement($oTab);

		$oTab = $oDialog->createTab(L10N::t('E-Mail-Einstellungen', $oGui->gui_description));
		$oTab->hidden = true;

		if($this->oWDBasic->hasSystemType('user')) {

			$oH2 = $oDialog->create('h2');
			$oH2->setElement($oGui->t('Einstellungen für E-Mail-Versand'));
			$oTab->setElement($oH2);

			foreach ($aSchoolsWithAllSchools as $iSchoolId => $sSchoolName) {
				$oH3 = $oDialog->create('h4');
				$oH3->setElement($sSchoolName);
				$oTab->setElement($oH3);

				$oTab->setElement($oDialog->createRow($oGui->t('Verwende Einstellung zum Versenden von E-Mails (Nicht die E-Mail-Adresse der Schule)'), 'checkbox', [
					'db_column' => $iSchoolId . '_use_setting',
					'db_alias' => 'ts_sus',
					'skip_value_handling' => true
				]));

				$oTab->setElement($oDialog->createRow($oGui->t('Anderes E-Mail-Konto als Absender verwenden'), 'select', [
					'db_column' => $iSchoolId . '_emailaccount_id',
					'db_alias' => 'ts_sus',
					'select_options' => Ext_TC_Communication_EmailAccount::getSelection(),
					'skip_value_handling' => true,
					'dependency_visibility' => ['db_column' => $iSchoolId . '_use_setting', 'db_alias' => 'ts_sus', 'on_values' => [1]],
					'required' => true,
				]));

				$oTab->setElement($oDialog->createRow($oGui->t('Weitere Absender-Identitäten verwenden'), 'select', [
					'db_column' => $iSchoolId . '_identities', // "0" funktioniert in gui2.js natürlich nicht
					'db_alias' => 'kui',
					'select_options' => $aUsers,
					'multiple' => 5,
					'jquery_multiple' => true,
					'searchable' => true,
					'style' => 'height: 70px;',
					'skip_value_handling' => true
				]));
			}

			$oH2 = $oDialog->create('h2');
			$oH2->setElement(L10N::t('Signaturen für Text-E-Mails', $oGui->gui_description));
			$oTab->setElement($oH2);
			foreach ($aSchools as $iSchoolId => $sSchoolName) {
				$oSchool = Ext_Thebing_School::getInstance($iSchoolId);
				$aSchoolLanguages = $oSchool->getLanguageList(true);
				$oH3 = $oDialog->create('h4');
				$oH3->setElement($sSchoolName);
				$oTab->setElement($oH3);
				foreach ((array)$aSchoolLanguages as $sCode => $sLanguage) {
					$sLabel = $sLanguage;
					$oTab->setElement($oDialog->createRow($sLabel, 'textarea', array('db_column' => 'signature_email_text_' . $sCode . '_' . $iSchoolId)));
				}
			}

			$iH2 = $oDialog->create('h2');
			$iH2->setElement(L10N::t('Signaturen für HTML-E-Mails', $oGui->gui_description));
			$oTab->setElement($iH2);
			foreach ($aSchools as $iSchoolId => $sSchoolName) {
				$oSchool = Ext_Thebing_School::getInstance($iSchoolId);
				$aSchoolLanguages = $oSchool->getLanguageList(true);
				$oH3 = $oDialog->create('h4');
				$oH3->setElement($sSchoolName);
				$oTab->setElement($oH3);
				foreach ((array)$aSchoolLanguages as $sCode => $sLanguage) {
					$sLabel = $sLanguage;
					$oTab->setElement($oDialog->createRow($sLabel, 'html', array('db_column' => 'signature_email_html_' . $sCode . '_' . $iSchoolId, 'advanced' => true)));
				}
			}

			$oTab->hidden = false;

		}

		$oTab->aOptions['task'] = 'email_settings';
		$oDialog->setElement($oTab);

		$oTab = $oDialog->createTab(L10N::t('PDF-Einstellungen', $oGui->gui_description));
		$oTab->hidden = true;

		if($this->oWDBasic->hasSystemType('user')) {
			$iH3 = $oDialog->create('h4');
			$iH3->setElement(L10N::t('Signaturen', $oGui->gui_description));
			$oTab->setElement($iH3);
			foreach ((array)$aLanguages as $sCode => $sLanguage) {
				$sLabel = $sLanguage;
				$oTab->setElement($oDialog->createRow($sLabel, 'textarea', array('db_column' => 'signature_pdf_' . $sCode)));
			}
			$iH3 = $oDialog->create('h4');
			$iH3->setElement(L10N::t('Signatur-Bilder', $oGui->gui_description));
			$oTab->setElement($iH3);
			foreach ($aSchools as $iSchoolId => $sSchoolName) {
				$oSchool = Ext_Thebing_School::getInstance($iSchoolId);
				$aFiles = $oSchool->getSchoolFiles(2, null, true);

				$aSigTemp = array();
				$aSigTemp[''] = '';
				foreach ((array)$aFiles as $aFile) {
					$aSigTemp[$aFile['id']] = $aFile['description'];
				}
				$sLabel = $oSchool->ext_1;
				$oTab->setElement($oDialog->createRow($sLabel, 'select', array('db_column' => 'signature_img_' . $iSchoolId, 'select_options' => $aSigTemp)));
			}

			$oTab->hidden = false;

		}

		$oTab->aOptions['task'] = 'pdf_settings';
		$oDialog->setElement($oTab);

		return $oDialog;
	}


	static public function addAdditionalColumns(Ext_Gui2 $oGui) {

		$oColumn = $oGui->createColumn();
		$oColumn->db_column = 'user_group';
		$oColumn->title = L10N::t('Benutzergruppe', $oGui->gui_description);
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('user_name');
		$oColumn->format = new Ext_Thebing_User_Gui2_Format_UserGroup();
		$oColumn->style = new Ext_Thebing_User_Gui2_Style_UserGroup();
		$oColumn->sortable = false;
		$oGui->setColumn($oColumn);

	}

	public static function convertErrorKeyToMessage($sKey) {

		if($sKey === 'WEAK_PASSWORD') {
			$sMessage = 'Das Passwort ist nicht stark genug. Bitte wählen Sie ein komplexeres, längeres Passwort unter der Verwendung von verschiedenen Zeichengruppen wie Buchstaben, Zahlen und Sonderzeichen. Es dürfen keine Bestandteile von Vorname, Nachname und E-Mail-Adresse im Passwort vorkommen und auch keine Wiederholungen oder Sequenzen von Zeichen.';
		} else {
			$sMessage = parent::convertErrorKeyToMessage($sKey);
		}
		
		return $sMessage;
	}
	
}
