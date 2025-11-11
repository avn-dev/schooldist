<?php

/**
 * @property Ext_TC_Communication_EmailAccount $oWDBasic
 */
class Ext_TC_Communication_EmailAccount_Gui2_Data extends Ext_TC_Gui2_Data {

	protected function _getErrorMessage($sError, $sField, $sLabel='', $sAction=null, $sAdditional=null) {

		if($sField === 'SMTP_FAILED') {
			$sMessage = $this->t('Es konnte keine SMTP-Verbindung hergestellt werden.').'<br>'.$sError;
		} elseif($sField === 'IMAP_FAILED') {
			$sMessage = $this->t('Es konnte keine IMAP-Verbindung hergestellt werden.').'<br>'.$sError;
		} elseif($sError === 'ACCOUNT_IN_USE') {
			$sMessage = $this->t('Der ausgewählte Eintrag wird noch verwendet.');
		} else {
			$sMessage = parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
		}

		return $sMessage;

	}

	public function switchAjaxRequest($_VARS) {

		if($_VARS['task'] == 'check') {

			$aData = $_VARS['save'];
			$sError = null;

			if($_VARS['type'] == 'check_smtp') {

				if(\Util::checkEmailMx($aData['email']['tc_ce'])) {

					$oEmailAccount = new Ext_TC_Communication_EmailAccount;
					$oEmailAccount->smtp_host = $aData['smtp_host']['tc_ce'];
					$oEmailAccount->smtp_port = $aData['smtp_port']['tc_ce'];
					$oEmailAccount->smtp_connection = $aData['smtp_connection']['tc_ce'];
					$oEmailAccount->smtp_user = $aData['smtp_user']['tc_ce'];
					$oEmailAccount->smtp_pass = $aData['smtp_pass']['tc_ce'];
					$oEmailAccount->email = $aData['email']['tc_ce'];

					$mCheck = $oEmailAccount->checkSmtp();

					if($mCheck !== true) {
						$sError = $mCheck;
					}

				} else {
					$sError = $this->t('Die E-Mail-Adresse ist nicht valide!');
				}
				
			} elseif($_VARS['type'] == 'check_imap') {
				
				if($aData['imap']['tc_ce']) {

					$oEmailAccount = new Ext_TC_Communication_Imap;
					$oEmailAccount->imap_host = $aData['imap_host']['tc_ce'];
					$oEmailAccount->imap_port = $aData['imap_port']['tc_ce'];
					$oEmailAccount->imap_connection = $aData['imap_connection']['tc_ce'];
					$oEmailAccount->imap_user = $aData['imap_user']['tc_ce'];
					$oEmailAccount->imap_pass = $aData['imap_pass']['tc_ce'];
					$oEmailAccount->email = $aData['email']['tc_ce'];
					$oEmailAccount->imap_append_sent_mail = $aData['imap_append_sent_mail']['tc_ce'];
					$oEmailAccount->imap_sent_mail_folder = $aData['imap_sent_mail_folder']['tc_ce'];
					//$oEmailAccount->imap_sent_mail_folder_root = $aData['imap_sent_mail_folder_root']['tc_ce'];
					$oEmailAccount->imap_folder = $aData['imap_folder']['tc_ce'];

					$mCheck = $oEmailAccount->checkConnection();
					
					if($mCheck !== TRUE) {
						$sError = $mCheck;
					}

				}
				
			}

			$aTransfer = array();

			// Rückgabe prüfen
			if(empty($sError)) {
				$aTransfer['success'] = 1;
				//$aTransfer['save_row'] = (int)$_VARS['save_row'];
				$aTransfer['message']	= $this->t('Alle Tests wurden erfolgreich ausgeführt.');
				$aTransfer['action']	= 'showSuccess';
				$aTransfer['error']		= array();
				$aTransfer['data']		= array();
			} else {		
				$aTransfer['success']	= 0;
				$aTransfer['error']		= array($this->t('Die Tests sind fehlgeschlagen.'), $sError);
				$aTransfer['action']	= 'showError';
				$aTransfer['data']		= array();
			}

			echo json_encode($aTransfer);

			$this->_oGui->save();
			
			die();

		} else {
			parent::switchAjaxRequest($_VARS);
		}

	}

	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional = false, $bSave = true) {

		if($sAction == 'openAccessDialog') {

			$oMatrix = new Ext_TC_Communication_EmailAccount_AccessMatrix;

			$oMatrix->saveAccessData($aData['access']);

			$aData = $this->_getAccessDialog($aSelectedIds);

			$aTransfer = array(
				'data'		=> $aData,
				'error'		=> array(),
				'task'		=> 'openDialog',
				'action'	=> 'saveDialogCallback'
			);

		} else {
		
			$aTransfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
			
		}

		return $aTransfer;

	}

	protected function deleteRow($iRowId) {

		$this->_getWDBasicObject([$iRowId]);
		$this->oWDBasic->bValidateSettings = false;

		return parent::deleteRow($iRowId);

	}

	protected function _getAccessDialog($aSelectedIds) {

		$oMatrix = new Ext_TC_Communication_EmailAccount_AccessMatrix;

		$aMatrix = $oMatrix->aMatrix;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDialog = $this->_oGui->createDialog($this->_oGui->t('Zugriffsrechte'), $this->_oGui->t('Zugriffsrechte'));

		$aData = $oDialog->generateAjaxData($aSelectedIds, $this->_oGui->hash);
		$aData['bSaveButton'] = 1;
		$aData['aMatrixData'] = $aMatrix;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$aData['aMatrixCellColors'] = array(
			'red'	=> Ext_TC_Util::getColor('red'),
			'green'	=> Ext_TC_Util::getColor('green')
		);

		$aData['html'] = $oMatrix->generateHTML($this->_oGui->gui_description);
		
		$aData['action'] = 'openAccessDialog';
		$aData['task'] = 'saveDialog';
		
		return $aData;

	}
	
	/**
	 * 
	 * @param \Ext_Gui2_Dialog $oDialogData
	 * @param type $aSelectedIds
	 * @param type $sAdditional
	 * @return type
	 */
	protected function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false) {

		if(!empty($sAdditional)) {
			return parent::getEditDialogHTML($oDialogData, $aSelectedIds, $sAdditional);
		}

		if(!$this->oWDBasic) {
			$this->_getWDBasicObject($aSelectedIds);		
		}
		
		$bHasEmail = false;
		if(
			!empty($this->oWDBasic->email) && 
			\FideloSoftware\Mailing\Email::validate($this->oWDBasic->email)
		) {
			$bHasEmail = true;
		}
		
		$oDialogData->aElements = [];
				
		$oDialogData->setElement($oDialogData->createRow($this->_oGui->t('E-Mail-Adresse'), 'input', array(
			'db_alias'			=> 'tc_ce',
			'db_column'			=> 'email',
			'required'			=> true,
			'autocomplete'		=> 'off'
		)));
		
		
		if(!$bHasEmail) {
			$oDialogData->setElement($oDialogData->createRow($this->_oGui->t('Passwort'), 'input', array(
				'db_alias'			=> 'tc_ce',
				'db_column'			=> 'smtp_pass',
				'type'				=> 'password',
				'autocomplete'		=> 'off'
			)));
		}
		
		/*
		$oDialog->setElement($oDialog->createRow($oGui->t('Externen SMTP-Server verwenden'), 'checkbox', array(
				'db_alias'			=> 'tc_ce',
				'db_column'			=> 'smtp'
		)));
		*/

		$oDialogData->setElement($oDialogData->createRow($this->_oGui->t('Automatischen E-Mail-Eingang verwenden'), 'checkbox', array(
			'db_alias' => 'tc_ce',
			'db_column' => 'imap'
//				'child_visibility' => [
//					/*
//					 * Das Feld "tc_ce.imap_sent_mail_folder" ein- bzw. ausblenden wenn diese Checkbox angeklickt wird.
//					 * Eigentlich ist das Feld von "tc_ce.imap_append_sent_mail" abhängig, welches wiederum von dieser
//					 * Checkbox abhängig ist. Aber die GUI kann keine Verschachtelten Abhängigkeiten, also wird das Feld hier
//					 * einfach eingebelendet und dann per JS nochmal das Change-Event für "tc_ce.imap_append_sent_mail"
//					 * ausgelöst, um das Feld wenn nötig wieder auszublenden.
//					 * Das ist bei diesem Feld nötig da es ein Pflichtfeld ist und sonst die entsprechenden Pflichtfeld-Klassen
//					 * nicht korrekt gesetzt bzw. entfernt werden. (#9371)
//					 */
//					[
//						'db_alias' => 'tc_ce',
//						'db_column' => 'imap_sent_mail_folder',
//						'on_values' => [1]
//					],
//				]
		)));

		if($bHasEmail) {
							
			$oEmail = new \FideloSoftware\Mailing\Email($this->oWDBasic->email);

			$aOutgoingConnections = Ext_TC_Communication_EmailAccount::getConnectionTypes();
			$aIncomingConnections = Ext_TC_Communication_EmailAccount::getConnectionTypes('imap');

			$bImap = (bool) $this->oWDBasic->imap;

			if(
				empty($this->oWDBasic->smtp_host) ||
				$this->oWDBasic->getOriginalData('email') !== $this->oWDBasic->email ||	
				(
					$bImap	&&
					empty($this->oWDBasic->imap_host)											
				)					
			) {

				$oConfig = (new \FideloSoftware\Mailing\AutoConfig\MailServer())
						->discover($oEmail);

				$oOutgoingServer = $oConfig->getOutgoingServer();
				$oIncomingServer = $oConfig->getImapIncomingServer();
				// nicht überschreiben
				if(empty($this->oWDBasic->smtp_host)) {
					$this->oWDBasic->smtp_host = $oOutgoingServer->getHostname();
					$this->oWDBasic->smtp_port = $oOutgoingServer->getPort();
					if(empty($this->oWDBasic->smtp_user)) {
						$this->oWDBasic->smtp_user = ($oOutgoingServer->getUserName() === '%EMAILADDRESS%') ? $oEmail->getFull() : '';
					}
					$this->oWDBasic->smtp_connection = ($oOutgoingServer->getSocketType() === 'STARTTLS') ? 'TLS' : $oOutgoingServer->getSocketType();
				}

				if($oIncomingServer) {						
					$sConnection = '';
					if($oIncomingServer->getSocketType() === 'SSL') {
						$sConnection = '/imap/ssl';
					} else if($oIncomingServer->getSocketType() === 'TLS') {
						$sConnection = '/imap/tls';
					}

					// nicht überschreiben
					if(empty($this->oWDBasic->imap_host)) {
						$this->oWDBasic->imap_host = $oIncomingServer->getHostname();
						$this->oWDBasic->imap_port = $oIncomingServer->getPort();
						if(empty($this->oWDBasic->imap_user)) {
							$this->oWDBasic->imap_user = ($oIncomingServer->getUserName() === '%EMAILADDRESS%') ? $oEmail->getFull() : '';
						}
						$this->oWDBasic->imap_connection = $sConnection;
					}
				}					
			}

			if($oConfig && $oConfig->isVerified() === false) {
				$oDialogData->setElement($oDialogData->createNotification(
					$this->_oGui->t('Achtung'), 
					$this->_oGui->t('Es konnten keine eindeutigen automatischen Einstellungen für diese E-mail-Adresse gefunden werden! Wir können nicht garantieren das folgende Einstellungen korrekt sind'), 
				'info'));
			}

			$oSettings = $oDialogData->create('div');
			$oSettings->id = 'mail_account_settings';				

			// Ausgangsserver

			$oH2 = $oDialogData->create('h4');
			$oH2->setElement($this->_oGui->t('Einstellungen für E-Mail-Ausgang').' (SMTP)');
			$oSettings->setElement($oH2);

			$oOutgoingServerDiv = $oDialogData->create('div');
			$oOutgoingServerDiv->class = 'mailserver';

			$oOutgoingServerDiv->setElement($oDialogData->createRow($this->_oGui->t('Benutzername'), 'input', array(
				'db_alias'			=> 'tc_ce',
				'db_column'			=> 'smtp_user',
				'events'				=> array(
					array(
						'event' 	=> 'change',
						'function' 	=> 'reloadDialogTab',
						'parameter'	=> 'aDialogData.id, 0'
					)
				)
			)));

			$oOutgoingServerDiv->setElement($oDialogData->createRow($this->_oGui->t('Passwort'), 'input', array(
				'db_alias'			=> 'tc_ce',
				'db_column'			=> 'smtp_pass',
				'type'				=> 'password',
			)));

			$oOutgoingServerSettings = $oDialogData->create('div');
			$oOutgoingServerSettings->class = 'GUIDialogRow form-group form-group-sm fix-settings';
			$oOutgoingServerSettings->setElement('
				<label class="GUIDialogRowLabelDiv control-label col-sm-3">'.$this->_oGui->t('Mailserver').'</label>
				<div class="GUIDialogRowInputDiv col-sm-9" style="padding-top: 5px;">
					SMTP, '.$this->oWDBasic->smtp_host.' (Port '.$this->oWDBasic->smtp_port.'), '.$this->oWDBasic->smtp_connection.' 
					<button type="button" class="btn btn-primary btn-xs">'.$this->_oGui->t('Bearbeiten').'</button>
				</div>
			');

			$oOutgoingServerDiv->setElement($oOutgoingServerSettings);

			$oOutgoingServerFields = $oDialogData->create('div');
			$oOutgoingServerFields->class = 'manual-server-settings';
			$oOutgoingServerFields->style = 'display: none;';

			$oOutgoingServerFields->setElement($oDialogData->createRow($this->_oGui->t('Postausgangsserver'), 'input', array(
				'db_alias'			=> 'tc_ce',
				'db_column'			=> 'smtp_host',
			)));

			$oOutgoingServerFields->setElement($oDialogData->createRow($this->_oGui->t('Serveranschlussnummer (Port)'), 'input', array(
				'db_alias'			=> 'tc_ce',
				'db_column'			=> 'smtp_port',
			)));

			$oOutgoingServerFields->setElement($oDialogData->createRow($this->_oGui->t('Verschlüsselten Verbindungstyp wählen'), 'select', array(
				'db_alias'			=> 'tc_ce',
				'db_column'			=> 'smtp_connection',
				'select_options'	=> $aOutgoingConnections,
			)));

			$oOutgoingServerDiv->setElement($oOutgoingServerFields);

			$oSettings->setElement($oOutgoingServerDiv);

			// Eingangsserver

			if($bImap == true) {

				if(empty($this->oWDBasic->imap_pass)) {
					$this->oWDBasic->imap_pass = $this->oWDBasic->smtp_pass;
				}

				$oIncomingServerDiv = $oDialogData->create('div');
				$oIncomingServerDiv->id = 'container-imap';
				$oIncomingServerDiv->class = 'mailserver';

				$oH2 = $oDialogData->create('h4');
				$oH2->setElement($this->_oGui->t('Einstellungen für E-Mail-Eingang').' (IMAP)');
				$oIncomingServerDiv->setElement($oH2);

				$oIncomingServerDiv->setElement($oDialogData->createRow($this->_oGui->t('Benutzername'), 'input', array(
					'db_alias'			=> 'tc_ce',
					'db_column'			=> 'imap_user',
					'events'				=> array(
						array(
							'event' 	=> 'change',
							'function' 	=> 'reloadDialogTab',
							'parameter'	=> 'aDialogData.id, 0'
						)
					)
				)));

				$oIncomingServerDiv->setElement($oDialogData->createRow($this->_oGui->t('Passwort'), 'input', array(
					'db_alias'			=> 'tc_ce',
					'db_column'			=> 'imap_pass',
					'type'				=> 'password',
					'events'				=> array(
						array(
							'event' 	=> 'change',
							'function' 	=> 'reloadDialogTab',
							'parameter'	=> 'aDialogData.id, 0'
						)
					)
				)));

				$oIncomingServerSettings = $oDialogData->create('div');
				$oIncomingServerSettings->class = 'GUIDialogRow form-group form-group-sm fix-settings';
				if(empty($this->oWDBasic->imap_host)) {
					$oIncomingServerSettings->style = 'display: none;';
				}

				$oIncomingServerSettings->setElement('
					<label class="GUIDialogRowLabelDiv control-label col-sm-3">'.$this->_oGui->t('Mailserver').'</label>
					<div class="GUIDialogRowInputDiv col-sm-9" style="padding-top: 5px;">
						IMAP, '.$this->oWDBasic->imap_host.' (Port '.$this->oWDBasic->imap_port.'), '.$aIncomingConnections[$this->oWDBasic->imap_connection].' 
						<button type="button" class="btn btn-primary btn-xs">'.$this->_oGui->t('Bearbeiten').'</button>
					</div>
				');

				$oIncomingServerDiv->setElement($oIncomingServerSettings);

				$oIncomingServerFields = $oDialogData->create('div');
				$oIncomingServerFields->class = 'manual-server-settings';
				if(!empty($this->oWDBasic->imap_host)) {
					$oIncomingServerFields->style = 'display: none;';
				}

				$oIncomingServerFields->setElement($oDialogData->createRow($this->_oGui->t('Posteingangsserver'), 'input', array(
					'db_alias'			=> 'tc_ce',
					'db_column'			=> 'imap_host',
					'events'				=> array(
						array(
							'event' 	=> 'change',
							'function' 	=> 'reloadDialogTab',
							'parameter'	=> 'aDialogData.id, 0'
						)
					)
				)));

				$oIncomingServerFields->setElement($oDialogData->createRow($this->_oGui->t('Serveranschlussnummer (Port)'), 'input', array(
					'db_alias'			=> 'tc_ce',
					'db_column'			=> 'imap_port',
					'events'				=> array(
						array(
							'event' 	=> 'change',
							'function' 	=> 'reloadDialogTab',
							'parameter'	=> 'aDialogData.id, 0'
						)
					)
				)));

				$oIncomingServerFields->setElement($oDialogData->createRow($this->_oGui->t('Verbindungstyp wählen'), 'select', array(
					'db_alias'			=> 'tc_ce',
					'db_column'			=> 'imap_connection',
					'select_options'	=> $aIncomingConnections,
					'events'				=> array(
						array(
							'event' 	=> 'change',
							'function' 	=> 'reloadDialogTab',
							'parameter'	=> 'aDialogData.id, 0'
						)
					)
				)));

				$oIncomingServerDiv->setElement($oIncomingServerFields);

				$oIncomingServerDiv->setElement($oDialogData->createRow($this->_oGui->t('Welche E-Mail sollen eingelesen werden?'), 'select', array(
					'db_alias'			=> 'tc_ce',
					'db_column'			=> 'imap_filter',
					'select_options'	=> Ext_TC_Communication_EmailAccount::getImapFilter(),
				)));

				$oIncomingServerDiv->setElement($oDialogData->createRow($this->_oGui->t('Aus welchem Ordner sollen E-Mails eingelesen werden?'), 'select', array(
					'db_alias' => 'tc_ce',
					'db_column' => 'imap_folder',
					'selection' => new Ext_TC_Communication_EmailAccount_Gui2_Selection_Folder()
				)));

				$oIncomingServerDiv->setElement($oDialogData->createRow($this->_oGui->t('Was soll nach dem Einlesen mit den E-Mails passieren?'), 'select', array(
					'db_alias'			=> 'tc_ce',
					'db_column'			=> 'imap_closure',
					'select_options'	=> Ext_TC_Communication_EmailAccount::getClosureOptions(),
				)));

				$oIncomingServerDiv->setElement($oDialogData->createRow($this->_oGui->t('E-Mails in den Ordner "Gesendete Elemente" verschieben?'), 'checkbox', array(
					'db_alias'			=> 'tc_ce',
					'db_column'			=> 'imap_append_sent_mail'
				)));

				$oHidden = $oDialogData->create('div');
				$oHidden->style = 'display: none';
				$oHidden->setElement($oDialogData->createRow($this->_oGui->t('Ordner "Gesendete Elemente" parallel zum Ordner Inbox'), 'checkbox', array(
					'db_alias'			=> 'tc_ce',
					'db_column'			=> 'imap_sent_mail_folder_root',
				)));
				$oIncomingServerDiv->setElement($oHidden);

				$oIncomingServerDiv->setElement($oDialogData->createRow($this->_oGui->t('Ordner "Gesendete Elemente"'), 'select', array(
					'db_alias' => 'tc_ce',
					'db_column' => 'imap_sent_mail_folder', 						
					'selection' => new Ext_TC_Communication_EmailAccount_Gui2_Selection_Folder()			
				)));
				
				$oIncomingServerDiv->setElement($oDialogData->createRow($this->_oGui->t('E-Mails aus dem Ordner "Gesendete Elemente" synchronisieren?'), 'checkbox', array(
					'db_alias'			=> 'tc_ce',
					'db_column'			=> 'imap_sync_sent_mail'
				)));
				
				$oSettings->setElement($oIncomingServerDiv);
			}

			$oDialogData->setElement($oSettings);
			
		} else if(!empty($this->oWDBasic->email)) {
			$oError = Ext_TC_Error_Handler::createError('no_valid_email');
			$oError->setMessage('Bitte geben Sie eine gültige E-Mail-Adresse ein');
		}
		
		$oDialogData->height = 800;
		$oDialogData->save_button = false;
		
		$aButtons[] = [
			'label'	=> $this->_oGui->t('E-Mail-Ausgang testen'),
			'task'	=> 'check_smtp',
			'id'	=> 'smtp_button'
		];
		$aButtons[] = [
			'label'	=> $this->_oGui->t('E-Mail-Eingang testen'),
			'task'	=> 'check_imap',
			'id'	=> 'imap_button'
		];
		$aButtons[] = [
			'label'	=> $this->_oGui->t('Weiter'),
			'task'	=> 'load_settings',
			'id'	=> 'next_button'
		];		
		// Save-Button so hinzufügen wegen id (wird im js ein-/ausgeblendet)
		$aButtons[] = [
			'label'			=> $this->_oGui->t('Speichern'), 
			'task'			=> 'saveDialog',
			'id'			=> 'save_button'
		];
		
		$oDialogData->aButtons = $aButtons;
				
		$aData = parent::getEditDialogHTML($oDialogData, $aSelectedIds, $sAdditional);
				
		return $aData;
	}

	protected function getDialogHTML(&$sIconAction, &$oDialog, $aSelectedIds = array(), $sAdditional=false) {

		if($sIconAction == 'openAccessDialog') {

			$aData = $this->_getAccessDialog($aSelectedIds);

		} else {
			
			$aData = parent::getDialogHTML($sIconAction, $oDialog, $aSelectedIds, $sAdditional);
			
		}
			
		return $aData;

	}
	
	public function addAdditionalIcons(&$oBar) {
		// TA
	}

}
