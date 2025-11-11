<?php

use Communication\Traits\Model\Log\WithModelRelations;

/**
 * @property string|int $id
 * @property string|int $changed
 * @property string|int $created
 * @property string $active
 * @property string|int $date
 * @property string $direction
 * @property string $subject
 * @property string $content
 * @property string $type
 * @property string $content_type
 * @property string $sent
 * @property string $seen
 * @property ?string $seen_at
 * @property ?string $status
 * @property \DateTime $created_index
 * @method static \Ext_TC_Communication_MessageRepository getRepository()
 */
class Ext_TC_Communication_Message extends Ext_TC_Basic
{
	use WithModelRelations;
	
	protected $_sTable = 'tc_communication_messages';
	protected $_sTableAlias = 'tc_cm';
	
	/**
	 * @var Ext_TC_Communication_Imap_Message
	 */
	protected $_oImapMessage = null;

	/**
	 * Dieser Wert wird von der GUI gesetzt, wenn eine neue Notiz angelegt wird.
	 * Dieser Wert wird benötigt:
	 * * Selection für Gesprächspartner bei den Notzen
	 * @var string
	 */
	public $relation;

	/**
	 * Achtung: $_aJoinedObjects gibt es auch in Ext_TA_Communication_Message!
	 * @var array
	 */
	protected $_aJoinedObjects = array(
		'addresses' => array(
			'class' => \Ext_TC_Communication_Message_Address::class,
			'key' => 'message_id',
			'type' => 'child'
		),
		'files' => array(
			'class' => \Ext_TC_Communication_Message_File::class,
			'key' => 'message_id',
			'type' => 'child'
		),
		'templates' => array(
			'class' => \Ext_TC_Communication_Message_Template::class,
			'key' => 'message_id',
			'type' => 'child'
		),
		'flags' => array(
			'class' => \Ext_TC_Communication_Message_Flag::class,
			'key' => 'message_id',
			'type' => 'child'
		),
	);

	/**
	 * Achtung: files gibt es auch als JoinedObject
	 * @var array
	 */
	protected $_aJoinTables = array(
		'subjects' => array(
			'table' => 'tc_communication_messages_subjects',
			'foreign_key_field'=>'subject',
			'primary_key_field'=>'message_id',
			'autoload' => true
		),
		'incoming' => array(
			'table' => 'tc_communication_messages_incoming',
			'foreign_key_field'=>array('imap_message_id','uid','unseen','answered','flagged'),
			'primary_key_field'=>'message_id',
			'autoload' => true
		),
		'codes' => array(
			'table' => 'tc_communication_messages_codes',
			'foreign_key_field'=>'code',
			'primary_key_field'=>'message_id',
			'autoload' => false
		),
		'files' => array(
			'table' => 'tc_communication_messages_files',
			'foreign_key_field'=>array('file', 'name'),
			'primary_key_field'=>'message_id',
			'readonly' => true,
			'autoload' => true
		),
		'flags' => array(
			'table' => 'tc_communication_messages_flags',
			'foreign_key_field'=>array('flag'),
			'primary_key_field'=>'message_id',
			'readonly' => true,
			'autoload' => true
		),
		'documents' => array(
			'table' => 'tc_communication_messages_documents',
			'foreign_key_field'=>'version_id',
			'primary_key_field'=>'message_id',
			'autoload' => true
		),
		'relations' => array(
			'table' => 'tc_communication_messages_relations',
			'foreign_key_field'=>array('relation', 'relation_id'),
			'primary_key_field'=>'message_id'
		),
		'app_index' => array(
			'table' => 'tc_communication_messages_app_index',
			'foreign_key_field'=>array('device_relation', 'device_relation_id', 'thread_relation', 'thread_relation_id'),
			'primary_key_field'=>'message_id'
		),
		'creators' => array(
			'table' => 'tc_communication_messages_creators',
			'foreign_key_field' => 'creator_id',
			'primary_key_field' => 'message_id',
			'autoload' => true
		),
		'categories' => array(
			'table' => 'tc_communication_messages_to_categories',
			'foreign_key_field' => 'category_id',
			'primary_key_field' => 'message_id',
			#'autoload' => true
		)
	);

	protected $_aAttributes = array(
		'notice_type' => array(
			'class' => 'WDBasic_Attribute_Type_Varchar'
		),
		'notice_correspondant_key' => array(
			'class' => 'WDBasic_Attribute_Type_Varchar'
		),
		// Entweder String oder Int (ID)
		'notice_correspondant_value' => array(
			'class' => 'WDBasic_Attribute_Type_Varchar'
		)
	);

	protected $_aFormat = array(
		'created' => array('format'=>'DATE_TIME'),
	);

	public function __get($sName) {
		
		if(
			$sName === 'imap_message_id' ||
			$sName === 'uid' ||
			$sName === 'unseen' ||
			$sName === 'answered' ||
			$sName === 'flagged' ||
			$sName === 'account_id' ||
			$sName === 'folder'
		) {
			$mValue = $this->_getIncomingColum($sName);
		} elseif($sName === 'code') {
			$mValue = reset($this->codes);
		} elseif($sName === 'subject') {
			$mValue = (!empty($this->subjects)) ? reset($this->subjects) : "";
		} elseif($sName === 'relation_id') {

		} elseif($sName === 'creator_id') {
			$mValue = reset($this->creators);
		} elseif($sName === 'category_id') {
			$aCategories = $this->categories;
			if(empty($aCategories)) {
				$mValue = 0;
			} else {
				$mValue = reset($this->categories);
			}
		} elseif($sName === 'date_date') {
			
		} elseif($sName === 'date_time') {
		
		} /*elseif($sName === 'sender_id') {
			
			$aSender = $this->getAddresses('from');
			if(!empty($aSender)) {
				$oSender = reset($aSender);
				$aRelations = $oSender->relations;
				if(!empty($aRelations)) {
					$aRelation = reset($aRelations);
					$mValue = $aRelation['relation_id'];
				}
			}
			
		}*/ elseif($sName === 'created_index') {
			// Datum der letzten Nachricht für Elasticsearch
			$mValue = (new DateTime($this->created))->format('Y-m-d');
		} else {
			$mValue = parent::__get($sName);
		}

		return $mValue;
		
	}

	public function getChannel(): string
	{
		if (empty($this->type)) {
			// TODO alle leeren Einträge in der Datenbank auf "email" setzen? Imap Sync hat nie einen Type angelegt
			return 'mail';
		}

		return ($this->type === 'email') ? 'mail' : $this->type;
	}

	public function isDraft(): bool
	{
		return $this->direction === 'out' &&
			!$this->isSent() &&
			empty($this->status);
	}

	public function isSent(): bool {
		if ($this->direction === 'out') {
			return (bool)$this->sent;
		}
		return false;
	}

	public function isUnseen(): bool {

		if ($this->direction === 'in') {
			return (
				$this->unseen == 1 ||
				$this->seen_at === null
			);
		}

		return false;
	}

	protected function _getIncomingColum($sName)
	{
		$aIncoming = $this->incoming;
		$mValue = null;
		if(isset($this->incoming[0][$sName])) {
			$mValue = $this->incoming[0][$sName];
		}
		
		return $mValue;
	}
	
	public function __set($sName, $mValue) {

		if(
			$sName === 'imap_message_id' ||
			$sName === 'uid' ||
			$sName === 'unseen' ||
			$sName === 'answered' ||
			$sName === 'flagged' ||
			$sName === 'account_id' ||
			$sName === 'folder'
		) {
			$this->_setIncomingColumn($sName, $mValue);
		} elseif($sName === 'code') {
			$this->codes = array($mValue);
		} elseif($sName === 'subject') {
			$this->subjects = array($mValue);
		} elseif($sName === 'relation_id') {
			
		} elseif($sName === 'creator_id') {
			$this->creators = array($mValue);
		} elseif($sName === 'category_id') {
			if($mValue == 0) {
				$this->categories = array();
			} else {
				$this->categories = array($mValue);
			}
		} elseif($sName === 'date_date') {
			$aDate = explode(' ', $this->_aData['date'], 2);
			$aDate[0] = $mValue;
			$this->_aData['date'] = implode(' ', $aDate);
		} elseif($sName === 'date_time') {
			
			$date = new \Carbon\Carbon($this->_aData['date']);
			$date->setTimeFromTimeString($mValue);

			$this->_aData['date'] = $date->toDateTimeString();
						
		} else {
			
			if(
				$sName === 'type' && 
				$mValue === 'notice'
			) {
				$this->content_type = 'html';
			}
			
			parent::__set($sName, $mValue);
		}

	}
	
	protected function _setIncomingColumn($sName, $mValue)
	{
		$aIncoming = $this->incoming;
		if(empty($aIncoming[0])) {
			$aIncoming = array(
				array(
					$sName => $mValue
				)
			);
		} else {
			$aIncoming[0][$sName] = $mValue;
		}
		
		$this->incoming = $aIncoming;
	}

	public function isNotSeen() {
	    return ($this->seen == 0);
    }

	public function checkCode() {
		
		$sRegexp = "/\[(TMC:|#)([A-Z0-9]{8})\]/i";
		
		$bMatch = preg_match($sRegexp, $this->subject, $aMatches);

		if(!$bMatch) {
			$bMatch = preg_match($sRegexp, $this->content, $aMatches);
		}

		// Code gefunden
		if($bMatch) {
			$sCode = $aMatches[2];
			
			// Code in DB Suchen
			$oOriginalMessage = self::getByCode($sCode);

			if($oOriginalMessage instanceof Ext_TC_Communication_Message) {

				$this->relations = $oOriginalMessage->relations;
				$this->save();

			}
			
		}
		
	}
	
	public static function getByCode($sCode) {
		
		$sSql = "
			SELECT
				`message_id`
			FROM
				`tc_communication_messages_codes`
			WHERE
				`code` = :code
			";
		$aSql = array(
			'code' => $sCode
		);
		$iMessageId = DB::getQueryOne($sSql, $aSql);
		
		if($iMessageId > 0) {
			$oMessage = self::getInstance($iMessageId);
			return $oMessage;
		}
		
		return false;

	}
	
	public static function getByMessageId($sMessageId, $iAccountId=null) {
		
		$sSql = "
			SELECT
				`message_id`
			FROM
				`tc_communication_messages_incoming`
			WHERE
				`imap_message_id` = :imap_message_id
			";
		$aSql = array(
			'imap_message_id' => (string)$sMessageId
		);
		
		if($iAccountId !== null) {
			$aSql['account_id'] = (int)$iAccountId;
			$sSql .= " AND `account_id` = :account_id ";
		}
		
		$iMessageId = DB::getQueryOne($sSql, $aSql);
		
		if($iMessageId > 0) {
			$oMessage = self::getInstance($iMessageId);
			return $oMessage;
		}

		return false;

	}
	
	public function setAddresses(array $aAddresses) {
		
		foreach($aAddresses as $aAddress) {
			
			$oAddress = $this->getJoinedObjectChild('addresses');
			$oAddress->type = (string)$aAddress['type'];
			$oAddress->address = (string)$aAddress['email'];
			$oAddress->name = (string)$aAddress['name'];
			
		}
		
	}

	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		$aSqlParts['select'] = "
			`tc_cm`.id , 
			`tc_cm`.direction, 
			`tc_cm`.type,
			IF(`tc_cm`.`type` = 'app' AND `tc_cm`.`direction` = 'out', `tc_cm`.`status`, '') AS `status`,
			UNIX_TIMESTAMP(`tc_cm`.`changed`) AS `changed`, 
			UNIX_TIMESTAMP(`tc_cm`.`date`) AS `date`,
			UNIX_TIMESTAMP(`tc_cm`.`seen_at`) AS `seen_at`,
			`subjects`.`subject`,
			incoming.flagged,
			(COUNT(`files`.`message_id`) + COUNT(`documents`.`message_id`)) `has_attachments`,
			GROUP_CONCAT(CONCAT(`all_relations`.`relation`, '{|}', `all_relations`.`relation_id`) SEPARATOR '{||}') `relations`,
			`creators`.`creator_id` AS `creator_id`
		";

		$aSqlParts['from'] .= " LEFT JOIN
			`tc_communication_messages_relations` `all_relations` ON
				`all_relations`.`message_id` = `tc_cm`.`id` 
		";

	}

	/**
	 * Inhalt bereinigen und zurückgeben
	 */
	public function getContent() {
		
		$sContent = $this->content;
		
		if($this->content_type == 'text') {
			$sContent = nl2br($sContent);
		}
		
		return $sContent;
		
	}
	
	/**
	 * @param string $type
	 * @return Ext_TC_Communication_Message_Address[]
	 */
	public function getAddresses(string $type) {
		return array_filter(
			$this->getJoinedObjectChilds('addresses', true),
			fn ($child) => $child->type === $type
		);
	}
	
	public function getFormattedContacts($sType, $bLong=true, $bShowType=false, $separator = '; ') {
		
		if($this->type === 'notice') {
			return $this->getFormattedContactsNotices($sType, $bLong, $bShowType);
		} else {
			return $this->getFormattedAddresses($this->getChannel(), $sType, $bLong, $bShowType, $separator);
		}
		
	}

	/**
	 * Formatiert Adressen für Kontakte bei E-Mails und SMS
	 *
	 * @param string $channel
	 * @param string $type
	 * @param bool $long
	 * @param bool $showType
	 * @param string $separator
	 * @return string
	 */
	public function getFormattedAddresses(string $channel, string $type, bool $long = true, bool $showType = false, string $separator = '; ') {

		$addresses = collect($this->getAddresses($type));

		$groupedAddresses = $addresses
			->mapToGroups(function (\Ext_TC_Communication_Message_Address $address) {
				$index = !empty($address->name) ? $address->name : $address->address;
				return [$index => $address];
			});

		if (!$long || $channel === 'app') {
			return $groupedAddresses->keys()->implode($separator);
		}

		$formatted = [];

		foreach($groupedAddresses as $nameAddresses) {
			foreach ($nameAddresses as $address) {
				/* @var \Ext_TC_Communication_Message_Address $address */
				$prefix = ($showType && !empty($type = $address->getRelationObjectTypeLabel()))
					? $type
					: '';

				$route = $prefix.$address->address;

				if (!empty($address->name)) {
					$formatted[] = sprintf('%s (%s)', $address->name, $route);
				} else {
					$formatted[] = $route;
				}
			}
		}

		return implode($separator, $formatted);
	}

	/**
	 * Formatiert die »Adressen« für Notizen
	 *
	 * @param $sType
	 * @param bool $bLong
	 * @param bool $bShowType
	 * @return array
	 */
	public function getFormattedContactsNotices($sType, $bLong=true, $bShowType=false) {
		return array();
	}

	public function getFormattedAttachments() {
		
		$aAttachments = $aFormattedAttachments = array();

		$aRawAttachments = (array)$this->getJoinedObjectChilds('files');

		foreach($aRawAttachments as $oAttachment) {
			$aAttachments[] = array(
				'file' => $oAttachment->file,
				'name' => $oAttachment->name
			);
		}

		$aDocuments = (array)$this->documents;

		/**
		 * @TODO
		 */
		foreach($aDocuments as $iVersionId) {
			$oVersion = Ext_TA_Document_Version::getInstance($iVersionId);

			$sPath = $oVersion->getPath(true);
			$aAttachments[] = array(
				'file' => $sPath,
				'name' => basename($sPath)
			);
		}

		foreach($aAttachments as $aAttachment) {
			
			$sIcon = Ext_TC_Util::getFileTypeIcon($aAttachment['file']);
			
			$sDocumentRoot = Ext_TC_Util::getDocumentRoot();

			// Doppelten DocumentRoot vermeiden
			$sPath = str_replace($sDocumentRoot, '', $aAttachment['file']);
			$sPath = $sDocumentRoot.$sPath;

			$sPath = str_replace('//', '/', $sPath);

			if(is_file($sPath)) {
				$sSubPath = str_replace($sDocumentRoot, '/', $sPath);
				$sSubPath = str_replace('//', '/', $sSubPath);

				$sLink = sprintf('
					<button type="button" onclick="window.open(\'%s\'); return false;">
						<img src="%s" />
						<span>%s</span>
						<span>(%s)</span>
					</button>
				', $sSubPath, $sIcon, $aAttachment['name'], Util::formatFilesize(filesize($sPath)));

				$aFormattedAttachments[] = $sLink;

			} else {
				Ext_TC_Util::reportMessage('Communication Messages Attachment missing', print_r($sPath, true).print_r($aAttachment, true));
			}
			
		}	

		$sReturn = implode('', $aFormattedAttachments);
		
		return $sReturn;

	}

	public function getFormattedFlags()
	{
		$aReturn = array();
		$aFlags = (array)$this->getJoinedObjectChilds('flags');

		$aAllFlags = Ext_TC_Factory::executeStatic('Ext_TC_Communication', 'getAllSelectFlags');

		foreach($aFlags as $oFlag) {

			$sReturnFlag = '<span class="flag">';
			$sReturnFlag .= $aAllFlags[$oFlag->flag];
			$aReturnFlagRelations = array();

			$aRelations = (array)$oFlag->relations;

			foreach($aRelations as $aRelation) {
				$oRelationObject = Ext_TC_Factory::getInstance($aRelation['relation'], $aRelation['relation_id']);
				$aReturnFlagRelations[] = $oRelationObject->getName();
			}

			if(!empty($aReturnFlagRelations)) {
				$sReturnFlag .= ' (';
				$sReturnFlag .= implode(', ', $aReturnFlagRelations);
				$sReturnFlag .= ')';
			}

			$sReturnFlag .= '</span>';

			$aReturn[] = $sReturnFlag;

		}

		$sReturn = implode('', $aReturn);
		return $sReturn;
	}

	public function getFormattedType() {

		$sReturn = '';

		if($this->type === 'notice') {
			$aNoticeTypes = Ext_TC_Factory::executeStatic('Ext_TC_Communication_Message_Notice_Gui2_Data', 'getNoticeTypeSelectOptions');
			if(isset($aNoticeTypes[$this->notice_type])) {
				$sReturn = $aNoticeTypes[$this->notice_type];
			}
		}

		return $sReturn;

	}

	/**
	 * Lädt die Imap-Message, sofern vorhanden
	 */
	public function loadImapMessage($oHeader = null) {

		if(
			is_null($this->_oImapMessage) &&
			$this->direction === 'in' &&
			$this->id > 0
		) {
			
			try {

				$oImap = Ext_TC_Communication_Imap::getInstance($this->account_id);

				// @TODO Wenn der E-Mail Account keine IMAP-Einstellungen mehr hat (oder deaktiviert),
				//	dann wirft der Konstruktur eine Exception und hier kommt null raus
				if($oImap instanceof Ext_TC_Communication_Imap) {
					
					$folder = null;
					if($this->folder) {
						$folder = $this->folder;
					} elseif($oImap->imap_folder) {
						$folder = $oImap->imap_folder;
					}

					if(!empty($this->uid)) {
						$this->_oImapMessage = $oImap->getMessageInstance($this->uid, 'uid', $folder);
					} else {
						$this->_oImapMessage = $oImap->getMessageInstance($this->imap_message_id, 'message_id', $folder);	
					}					
				}

			} catch(Exception $e) {
				// Konto hat eventuell kein IMAP mehr
			}

		}

		return $this->_oImapMessage;
	}

	public function setFlag($sName, $mValue, $bImap = false) {

		if($this->direction === 'in') {

			if(
				$sName === 'unseen' ||
				$sName === 'answered' ||
				$sName === 'flagged'
			) {

				$iOldValue = (int)$this->$sName;
				
				if($mValue) {
					$this->$sName = 1;
				} else {
					$this->$sName = 0;
				}
				
				// Wert in IMAP setzen, wenn gewünscht und lokal geändert
				if(
					$this->validate(true) &&
					$iOldValue !== $this->$sName &&
					$bImap
				) {
					$this->loadImapMessage();

					if($this->_oImapMessage instanceof Ext_TC_Communication_Imap_Message) {
						$this->_oImapMessage->setFlag($sName, $mValue);
					}
					
				}

			} else {
				throw new Exception('Unknown Flag "'.$sName.'" in Ext_TC_Communication_Message::setFlag()!');
			}
		
		}
		
	}

	/**
	 * Falls IMAP-Mail vorhanden, dann auch löschen
	 * @return type 
	 */
	public function delete() {
		
		// Inhalt und Anhänge löschen (Datenschutz)
		$this->content = '';
		
		$aRawAttachments = (array)$this->getJoinedObjectChilds('files');

		foreach($aRawAttachments as $oAttachment) {
			$this->deleteJoinedObjectChild('files', $oAttachment);
		}
	
		$aErrors = parent::delete();
		
		$this->loadImapMessage();

		if($this->_oImapMessage instanceof Ext_TC_Communication_Imap_Message) {
			$this->_oImapMessage->delete();
		}
		
		return $aErrors;
		
	}

	/**
	 * Relations erweitern, beispielsweise bei Konversion
	 * @param Ext_TC_Basic $oFrom
	 * @param Ext_TC_Basic $oTo
	 */
	public static function extendRelations(Ext_TC_Basic $oFrom, Ext_TC_Basic $oTo) {
		$aSql = array(
			'from_id' => $oFrom->id,
			'from_class' => get_class($oFrom),
			'to_id' => $oTo->id,
			'to_class' => get_class($oTo)
		);

		$sSql = "
			SELECT
				`message_id`
			FROM
				`tc_communication_messages_relations`
			WHERE
				`relation` = :from_class AND
				`relation_id` = :from_id
		";

		$aResult = (array)DB::getQueryCol($sSql, $aSql);

		foreach($aResult as $iMessageId) {
			$aSql['message_id'] = $iMessageId;
			$sSql = "
				INSERT INTO
					`tc_communication_messages_relations`
				SET
					`message_id` = :message_id,
					`relation` = :to_class,
					`relation_id` = :to_id
			";
			DB::executePreparedQuery($sSql, $aSql);
		}
	}

}
