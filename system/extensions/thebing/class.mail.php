<?php

/**
 * Klassenname steht in tc_communication_messages_relations und tc_communication_messages_addresses_relations!
 */
class Ext_Thebing_Mail extends Ext_TC_Communication_EmailAccount {

	public static $oSchool;

	protected $_aFormat = array(
		'email' => [
			'required'=>true,
			'validate'=>'MAIL'
		],
		'smtp_pass' => [
			'format' => 'ENCRYPTED',
		],
		'imap_pass' => [
			'format' => 'ENCRYPTED',
		],
		'oauth2_data' => [
			'format' => 'ENCRYPTED',
		]
	);
	
	public function __get($sVariable) {

		Ext_Gui2_Index_Registry::set($this);

		switch($sVariable) {
			case 'sName':
			case 'sFromName':
				return $this->sFromName;
			case 'sPass':
				return $this->smtp_pass;
			default:
				return parent::__get($sVariable);
		}

	}

	public function __set($sVariable, $mValue) {

		switch($sVariable) {
			case 'sName':
			case 'sFromName':
				$this->sFromName = $mValue;
				break;
			case 'sPass':
				$this->smtp_pass = $mValue;			
				break;
			default:
				parent::__set($sVariable, $mValue);
				break;
		}

	}

	public function save($bLog = true) {

		WDCache::deleteGroup('email_identities');

		return parent::save($bLog);
	}

	/**
	 * @inheritdoc
	 */
	public function validate($bThrowExceptions = false) {

		if(
			$this->active == 0 &&
			$this->checkUse()
		) {
			return ['tc_r.id' => ['ACCOUNT_IN_USE']];
		}

		return parent::validate($bThrowExceptions);

	}

	/**
	 * Returns an array with data about schools and users where the email account is used.
	 * Provides additional information on dependencies that would make a delete fail.
	 * @return array|null
	 */
	public function getUse(): array|null {

		$sqlCustomers = "
			SELECT
				`id`,
				`ext_1` as `label`
			FROM
				`customer_db_2`
			WHERE
				`email_account_id` = {$this->id} AND 
				`active` = 1 ";

		$sqlUsers = "
			SELECT
				`su`.`id`,
				`su`.`firstname`,
				`su`.`lastname`,
				`cdb2`.`id` as `customer_id`,
				CONCAT(`su`.`firstname`, ' ', `su`.`lastname`, ' - ', `cdb2`.`ext_1`) as `label`
			FROM
				`ts_system_user_schoolsettings` `ts_sus` INNER JOIN
				`system_user` `su` ON
					`su`.`id` = `ts_sus`.`user_id` AND
					`su`.`active` = 1 INNER JOIN
				`customer_db_2` `cdb2` ON
					`cdb2`.`id` = `ts_sus`.`school_id` AND
					`cdb2`.`active` = 1
			WHERE
				`ts_sus`.`use_setting` = 1 AND
				`ts_sus`.`emailaccount_id` = {$this->id}";

		$dependencies = array_merge(DB::getQueryData($sqlCustomers), DB::getQueryData($sqlUsers));

		if(empty($dependencies)) {
			return null;
		}

		return $dependencies;

	}

	/**
	 * True if email account is in use and must not be deleted
	 * @return bool
	 */
	protected function checkUse(): bool {

		$inUse = $this->getUse();
		if($inUse) {
			return true;
		}

		return false;

	}

	/**
	 * Diese Methode ermittelt das Absenderkonto für E-Mails
	 * 
	 * @param string $sDefaultFrom
	 * @param Ext_Thebing_User $oUser
	 * @return \self
	 */
	public static function g($sDefaultFrom = '', $oUser = null) {

		$mEmail = '';
		$sName = '';

		if(!empty($sDefaultFrom)) {
			if(is_array($sDefaultFrom)) {
				$sName = reset($sDefaultFrom);
				$mEmail = key($sDefaultFrom);
			} else {
				$iMatch = preg_match("/(From: )?(.*?) ?<(.*?)>/", $sDefaultFrom, $aMatch);
				if($iMatch > 0) {
					$sName = $aMatch[2];
					$mEmail = $aMatch[3];
				} else {
					$mEmail = $sDefaultFrom;
				}
			}
		}

		$oSchool = self::$oSchool;
		if(!$oSchool instanceof Ext_Thebing_School) {
			$oSchool = Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
		}

		// Einstellungen des Users verwenden (sofern auch beim User eingestellt)
		if(
			$oUser instanceof Ext_Thebing_User &&
			$oUser->getSchoolSetting($oSchool->id)['use_setting']
		) {

			$iAccountId = $oUser->getSchoolSetting($oSchool->id)['emailaccount_id'];
			if($iAccountId > 0) {
				$mEmail = $iAccountId;
			} else {
				$mEmail = $oUser->email;
			}

			// Format: Name der Schule - Vorname Nachname (vom jeweiligen User)
			$sName = $oSchool->getField('ext_1') . ' - ' . $oUser->firstname . ' ' . $oUser->lastname;

		} else {

			// Einstellungen der Schule verwenden (global)
			self::setSchoolAndEmailNameByRef($oSchool, $sName, $mEmail);

		}

		if(is_numeric($mEmail)) {
			$oMail = self::getInstance($mEmail);
			$oMail->sFromName = $sName;
		} else {
			$oMail = new self();
			$oMail->sFromName = $sName;
			$oMail->email = $mEmail;
			$oMail->smtp = 0;
		}

		return $oMail;
	}

	public static function setSchoolAndEmailNameByRef($oSchool, &$sName, &$mEmail) {

		if($oSchool instanceof Ext_Thebing_School) {
			$iAccountId = $oSchool->email_account_id;
			if($iAccountId > 0) {
				$mEmail = $iAccountId;
			} else {
				$mEmail = $oSchool->getField('email');
			}
			// Format: Name der Schule
			$sName = $oSchool->getField('ext_1');
		} else {
			// Wenn dieser Fall eintritt, dann wird »admin_email« aus der system_config (in WDMail) benutzt
			$sMessage = print_r($sName, true).print_r($mEmail, true).print_r($oSchool, true);
			Ext_TC_Util::reportMessage('TS Communication setSchoolAndEmailNameByRef: Schule konnte nicht ermittelt werden!', $sMessage);
		}

	}

	/**
	 * Wrapper für die self::g-Methode
	 * Wird beim Hook wdmail_send verwendet
	 *
	 * @param WDMail $oMail
	 * @param Ext_Thebing_User|null $oUser
	 * @return self
	 */
	public static function getUserOptions(WDMail $oMail, $oUser=null) {

		$sFrom = $oMail->from;

		// Wenn kein FROM gesetzt, dann im Header schauen
		if(empty($sFrom)) {
			$aHeaders = preg_split("/\r?\n/", $oMail->header);	
			foreach($aHeaders as $sHeader) {
				$aHeader = explode(":", $sHeader);
				$sHeaderName = trim($aHeader[0]);
				$sHeaderValue = trim($aHeader[1]);
				if($sHeaderName == 'From') {
					$sFrom = $sHeaderValue;
					break;
				}
			}
		}

		$oMailAddress = self::g($sFrom, $oUser);

		return $oMailAddress;
	}

	/**
	 * Array mit Mailinformationen zusammensetzen für self::sendAutoMails()
	 *
	 * @see sendAutoMail()
	 * @deprecated \Ts\Service\Email nutzen
	 * 
	 * @param Ext_TS_Inquiry_Contact_Abstract $oCustomer
	 * @param Ext_Thebing_School $oSchool
	 * @param Ext_Thebing_Email_Template $oTemplate
	 * @param array $aAttachments
	 * @param Ext_TS_Inquiry_Abstract|Ext_TS_Inquiry|Ext_TS_Enquiry $oObject
	 * @return array|null
	 */
	 public static function createMailDataArray($oObject, $oCustomer, $oSchool, $oTemplate, $aAttachments) {

		 $oLog = \Core\Service\NotificationService::getLogger(static::class);

		 // Sprachen des Layouts
		$aLayoutLanguages = (array)$oTemplate->__get('languages');

		// Sprache für Mail
		$sLanguage = '';
		if(in_array($oCustomer->getLanguage(), $aLayoutLanguages)) {
			$sLanguage = $oCustomer->getLanguage();
		} elseif(in_array($oSchool->getLanguage(), $aLayoutLanguages)) {
			$sLanguage = $oSchool->getLanguage();
		}

		if($sLanguage != '') {

			$iObjectId = $oObject->id;
			$sObjectClass = get_class($oObject);

			$sSubject = $oTemplate->__get('subject_'.$sLanguage);
			$sContent = $oTemplate->__get('content_'.$sLanguage);
			$iLayoutId = $oTemplate->__get('layout_'.$sLanguage);

			// Signatur bei eingesteller Standard-Identität im Template
			$oUser = $oTemplate->getDefaultIdentityUser();
			$sContentSignature = '';
			if($oUser !== null) {
				if($oTemplate->html == 1) {
					$sSignatureKey = 'signature_email_html_'.$sLanguage.'_'.$oSchool->id;
				} else {
					$sSignatureKey = 'signature_email_text_'.$sLanguage.'_'.$oSchool->id;
				}

				// Signatur an den Inhalt anhängen
				$sContentSignature = \Ext_Thebing_Communication::getMailContentSignature($oUser->$sSignatureKey);
			}

			Ext_Thebing_Communication::setLayoutAndSignature($oTemplate, $sLanguage, $sContent, $sContentSignature);

			if($iObjectId > 0) {
				if($sObjectClass === 'Ext_TS_Enquiry') {
					$oReplace = new Ext_TS_Enquiry_Placeholder($oObject);
				} else {
					$oReplace = new Ext_Thebing_Inquiry_Placeholder($iObjectId, $oCustomer->id);
				}
				$oReplace->sTemplateLanguage = $sLanguage;
				$sSubject = $oReplace->replace($sSubject, 0);
				$sContent = $oReplace->replace($sContent, 0);
				$sContent = $oReplace->replaceFinalOutput($sContent);
			}

			if(!empty($aAttachments['_'])) {
				$aAttachments[$sLanguage] = array_merge($aAttachments['_'], (array)$aAttachments[$sLanguage]);
			}

			if(!empty($iObjectId)) {
				$aData = array();
				$aData['html'] = $oTemplate->html;
				$aData['subject'] = $sSubject;
				$aData['content'] = $sContent;
				$aData['cc'] = self::splitEmails($oTemplate->cc);
				$aData['bcc'] = self::splitEmails($oTemplate->bcc);
				$aData['to'] = $oCustomer->getEmail();
				$aData['object'] = $sObjectClass;
				$aData['object_id'] = $iObjectId;
				$aData['selected_id_single'] = $iObjectId; // Für Feedback benötigt
				$aData['sender_id'] = 0;
				$aData['attachments'] = (array)$aAttachments[$sLanguage];
				$aData['template_id'] = $oTemplate->id;
				$aData['school_id'] = (int)$oSchool->id;
				return $aData;
			} else {
				$oLog->error('Empty object' , ['object' => $sObjectClass, 'object_id' => $iObjectId]);
				return null;
			}
		} else {
			$oLog->error('Missing language' , ['customer_id' => $oCustomer->id, 'school_id' => $oSchool->id]);
		}

		return null;
	 }

	/**
	 * @TODO Diese Methode sollte dringend entsorgt werden, da hier viel mit der Kommunikation redundant ist
	 *
	 * E-Mail verschicken
	 *
	 * $aData wird aufgebaut durch Methode self::createMailDataArray().
	 *
	 * Methode war zuvor in der Klasse Ext_Thebing_Email.
	 *
	 * @see createMailDataArray()
	 * @see \Ts\Service\Email
	 * 
	 * @deprecated \Ts\Service\Email nutzen
	 * 
	 * @param array $aData
	 * @param string $sApplication
	 * @return bool
	 */
	public static function sendAutoMail(array $aData, $sApplication) {

		if(is_array($aData['object_id'])) {
			throw new InvalidArgumentException('object_id array not supported');
		}

		$oSchool = Ext_Thebing_School::getInstance($aData['school_id']);

		// Schulobjekt setzen für den Absender
		Ext_Thebing_Mail::$oSchool = $oSchool;

		$sCommunicationCode = null;

		$sSendMode = $aData['send_mode'] ?? \Ext_TC_Communication::SEND_MODE_AUTOMATIC;

		// Mail-Code Platzhalter ersetzen
		if(
			strpos($aData['subject'], '[#]') !== false || 
			strpos($aData['content'], '[#]') !== false
		) {					
			$sCommunicationCode = Ext_TC_Communication::generateCode();
			$sCommunicationCodeTag = '[#'.$sCommunicationCode.']';
			$aData['subject'] = str_replace('[#]', $sCommunicationCodeTag, $aData['subject']);
			$aData['content'] = str_replace('[#]', $sCommunicationCodeTag, $aData['content']);
		}

		$oMail = new WDMail();
		$oMail->subject = $aData['subject'];

		// Nicht anders möglich, da $sApplication immer nur eine einzige Klasse sein kann
		$oInquiry = null;
		if(
			$aData['object'] === 'Ext_TS_Inquiry' ||
			$aData['object'] === 'Ext_TS_Enquiry'
		) {
			$oInquiry = $aData['object']::getInstance($aData['object_id']);
		}

		$oTemplate = Ext_Thebing_Email_Template::getInstance($aData['template_id']);

		if($oInquiry instanceof Ext_TS_Inquiry) {
			$aErrors = [];
			Ext_Thebing_Communication::_prepareFlags($oTemplate->flags, $aData, $aErrors, $oSchool, $oInquiry);
			if(!empty($aErrors)) {
				throw new RuntimeException('Ext_Thebing_Communication::_prepareFlags() error: '.print_r($aErrors, true));
			}
		}

		$oUser = $oTemplate->getDefaultIdentityUser();

		if($oUser !== null) {
			// Absender für self::g()
			$oMail->from_user = $oUser;
		}

		if($aData['html']) {
			$oMail->html = $aData['content'];
		} else {
			$oMail->text = $aData['content'];
		}

		$aRecipients = [];
		foreach (['to', 'cc', 'bcc'] as $sType) {
			if (empty($aData[$sType])) {
				continue;
			}
			$aMails = array_filter((array)$aData[$sType], fn($sMail) => \Util::checkEmailMx($sMail));
			$aRecipients[$sType] = $aMails;
			// TODO sollte keine Auswirkung haben da $_aTo in send() komplett überschrieben wird
			$oMail->{$sType} = $aMails;
		}

		if(!empty($aData['attachments'])) {
			$aAttachments = [];
			foreach($aData['attachments'] as $aFile) {
				$aAttachments[$aFile['path']] = $aFile['name'];
			}
			$oMail->attachments = $aAttachments;
		}

		if ($sSendMode === \Ext_TC_Communication::SEND_MODE_AUTOMATIC) {
			$bSuccess = $oMail->send($aRecipients['to'] ?? []);
		} else {
			// TODO nicht optimal - $oMail->sender_object füllen
			\Ext_TC_Communication_WDMail::setMailSenderObject($oMail);
			$bSuccess = true;
		}

		if($bSuccess) {

			$aRelations = [];
			$aRelations[] = ['relation' => $aData['object'], 'relation_id' => (int)$aData['object_id']];
			if(!empty($aData['template_cronjob_id'])) {
				$aRelations[] = ['relation' => Ext_Thebing_Email_TemplateCronjob::class, 'relation_id' => $aData['template_cronjob_id']];
			}
			if(!empty($process = $aData['event_manager_process'])) {
				$aRelations[] = ['relation' => $process['class'], 'relation_id' => $process['id']];
			}

			if(!empty($task = $aData['event_manager_task'])) {
				$aRelations[] = ['relation' => $task['class'], 'relation_id' => $task['id']];
			}

			$oLog = new Ext_TC_Communication_Message();
			$oLog->date = date('Y-m-d H:i:s');
			$oLog->direction = 'out';
			if($aData['html']) {
				$oLog->content_type = 'html';
			} else {
				$oLog->content_type = 'text';
			}
			$oLog->sent = ($sSendMode === \Ext_TC_Communication::SEND_MODE_AUTOMATIC) ? 1 : 0;

			$oFromUser = $oMail->from_user;
			$oSender = $oMail->sender_object;

			if(!empty($oFromUser)) {
				$aRelations[] = ['relation' => $oFromUser::class, 'relation_id' => $oFromUser->id];
			}

			if(!empty($oSender)) {

				$aRelations[] = ['relation' => get_class($oSender), 'relation_id' => $oSender->id];

				$oSenderLog = $oLog->getJoinedObjectChild('addresses');
				$oSenderLog->type = 'from';
				$oSenderLog->address = $oSender->email;
				$oSenderLog->name = $oSender->sFromName;
				if($oSender->id > 0) {
					$senderRelations = [
						['relation' => get_class($oSender), 'relation_id' => $oSender->id]
					];
					if(!empty($oFromUser)) {
						// Wichtig für Mail-Spool um dort auch wieder dieses ->from_user setzen zu können
						$senderRelations[] = ['relation' => $oFromUser::class, 'relation_id' => $oFromUser->id];
					}

					$oSenderLog->relations = $senderRelations;
				}

			} else {
				// Muss mal ausprobiert werden, da der Account eigentlich immer da sein muss
				throw new RuntimeException('No sender for auto mail!');
			}

			if(!empty($sCommunicationCode)) {
				$oLog->codes = [$sCommunicationCode];
			}

			$oLogTemplate = $oLog->getJoinedObjectChild('templates');
			$oLogTemplate->template_id = $oTemplate->id;

			foreach($aRecipients as $sType=>$aRecipientAddresses) {
				foreach($aRecipientAddresses as $sRecipientAddress) {
					$oRecipient = $oLog->getJoinedObjectChild('addresses');
					$oRecipient->type = $sType;
					$oRecipient->address = $sRecipientAddress;
				}
			}

			foreach($aData['attachments'] as $aFile) {
				$oFile = $oLog->getJoinedObjectChild('files');
				$oFile->file = str_replace(Util::getDocumentRoot(false), '', $aFile['path']);
				$oFile->name = $aFile['name'];
				if(!empty($aFile['relation'])) {
					$oFile->relations = [[
						'relation' => $aFile['relation'],
						'relation_id' => $aFile['relation_id']
					]];
				}
			}

			$oLog->relations = $aRelations;
			$oLog->subject = (string)$aData['subject'];
			$oLog->content = (string)$aData['content'];

			if($oMail->message instanceof \Swift_Message) {
				$oLog->imap_message_id = '<'.$oMail->message->getId().'>';
				$oLog->unseen = 0;
				if(!empty($oSender)) {
					$oLog->account_id = $oSender->id;
				}
			}

			$oLog->save();

			// Nicht anders möglich, da $sApplication immer nur eine einzige Klasse sein kann
			if($oInquiry instanceof Ext_TS_Inquiry) {
				Ext_Thebing_Communication::_setFlags($oTemplate->flags, $sApplication, $aData, $oLog->id, $oInquiry);
				Ext_Gui2_Index_Stack::add('ts_inquiry', $oInquiry->id, 0);
			} elseif($oInquiry instanceof Ext_TS_Enquiry) {
				Ext_Gui2_Index_Stack::add('ts_enquiry', $oInquiry->id, 0);
			}

		}

		// Spalten: Datum und Benutzer der letzten Nachricht
		Ext_Gui2_Index_Stack::executeCache();

		return $bSuccess;

	}

}
