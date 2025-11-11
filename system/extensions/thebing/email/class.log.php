<?php

/**
 * @deprecated
 */
class Ext_Thebing_Email_Log extends Ext_Thebing_Basic {

	protected $_aMainData			= array();
	protected $_aRelationData		= array();
	protected $_sApplication;

	// Tabellenname
	protected $_sTable = 'kolumbus_email_log';

	protected $_sTableAlias = 'kel';

	protected $_aJoinTables = [
		'relations' => [
			'table' => 'kolumbus_email_log_relations',
			'foreign_key_field' => ['object_id', 'object'],
			'primary_key_field' => 'log_id'
		]
	];

	protected $_aFormat = array(
		'created'	=> array('format'=>'DATE_TIME'),
	);

	public function __get($sField) {

		Ext_Gui2_Index_Registry::set($this);

		if($sField === 'created_index') {
			// Datum der letzten Nachricht für Elasticsearch
			$mValue = (new DateTime($this->created))->format('Y-m-d');
		} elseif($sField == 'sender_name') {
			if($this->_aData['sender_id'] > 0) {
				$oUser = Ext_Thebing_User::getInstance($this->_aData['sender_id']);
				$mValue = $oUser->name;
			} else {
				$mValue = '';
			}
		} elseif($sField == 'user_name') {
			if($this->_aData['user_id'] > 0) {
				$oUser = Ext_Thebing_User::getInstance($this->_aData['user_id']);
				$mValue = $oUser->name;
			} else {
				$mValue = '';
			}
		} elseif(
			$sField == 'recipients' ||
			$sField == 'documents' ||
			$sField == 'attachments' ||
			$sField == 'school_files' ||
			$sField == 'flags'
		) {
			$mValue = json_decode($this->_aData[$sField], true);
		} else {
			$mValue = parent::__get($sField);
		}

		return $mValue;

	}

	public function __set($sField, $mValue) {
		
		if(
			$sField == 'recipients' ||
			$sField == 'documents' ||
			$sField == 'attachments' ||
			$sField == 'school_files' ||
			$sField == 'flags'
		) {
			$this->_aData[$sField] = (string)json_encode($mValue);
		} else {
			parent::__set($sField, $mValue);
		}

	}

	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		$sApplication = $this->_sApplication;

		$aSqlParts['select'] .= ",
				`su`.`firstname`,
				`su`.`lastname`,
				UNIX_TIMESTAMP(`kel`.`created`) `created`,
				'out' `direction`, /* Bisher immer nur ausgehend */
				(
					(
						`kel`.`attachments` != '' AND
						`kel`.`attachments` != '[]'
					) OR
					(
						`kel`.`school_files` != '' AND
						`kel`.`school_files` != '[]'
					) OR (
						`kel`.`documents` REGEXP '[0-9]+' AND
						`kel`.`documents` != ''
					)
				) `has_attachments`
		";

		$aSqlParts['from'] .= " LEFT JOIN
				`system_user` `su` ON
					`su`.`id` = `kel`.`user_id` LEFT JOIN
				`kolumbus_email_log_relations` `kelr` ON
					`kelr`.`log_id` = `kel`.`id`
		";

		$aMainData		= (array)$this->_aMainData;
		$aRelationData	= (array)$this->_aRelationData;

		if(
			!empty($aMainData) || 
			!empty($aRelationData)
		) {
			$aSqlParts['where'] .= '(';
		}

		$iCounter	= 1;

		foreach($aMainData as $sObject => $aIds)
		{
			$aSqlParts['where'] .= " (
					`kel`.`object` = :main_object_".$iCounter." AND
					`kel`.`object_id` IN(:main_ids_".$iCounter.")
			";
			if($iCounter==1 && !empty($sApplication))
			{
				$aSqlParts['where'] .= " AND
					`kel`.`application` = '".$sApplication."'
				";
			}
			$aSqlParts['where'] .= ')OR ';

			$iCounter++;
		}

		$iCounter		= 1;

		foreach($aRelationData as $sObject => $aIds) {
			$aSqlParts['where'] .= " (
					`kelr`.`object` = :relation_object_".$iCounter." AND
					`kelr`.`object_id` IN(:relation_ids_".$iCounter.")
				) OR";

			$iCounter++;
		}

		$aSqlParts['where'] = substr($aSqlParts['where'],0,-3);

		if(!empty($aMainData) || !empty($aRelationData)){
			$aSqlParts['where'] .= ')';
		}

		// Da es mehr als eine Relation geben kann, muss hier auch ein GROUP BY rein
		$aSqlParts['groupby'] = "
			`kel`.`id`
		";

	}

	public function setMainData($aMainData){
		$this->_aMainData = $aMainData;
	}

	public function setRelationData($aRelationData){
		$this->_aRelationData = $aRelationData;
	}

	public function setApplication($sApplication)
	{
		$this->_sApplication = $sApplication;
	}

	/**
	 * Inhalt für Vorschau bereinigen und zurückgeben
	 */
	public function getContent() {
		
		$sContent = $this->content;
		
		/**
		 * Spalte content_type ist neu, daher alte Logs abfangen
		 * Check nicht erstellt, weil Tabelle riesig und Check sehr lange dauern würde. 
		 */
		if(empty($this->content_type)) {
			
			// Geprüft wird auf die Tags br, p und div
			$mTest1 = strpos($sContent, '<br');
			$mTest2 = strpos($sContent, '<p');
			$mTest3 = strpos($sContent, '<div');
			
			// Wenn keiner dieser tags vorkommt, dann text
			if(
				$mTest1 === false &&
				$mTest2 === false &&
				$mTest3 === false
			) {
				$this->content_type = 'text';
			} else {
				$this->content_type = 'html';
			}
			
		}

		// Bei Text automatische Umbrüche einbauen
		if($this->content_type == 'text') {
			$sContent = nl2br($sContent);
		}

		return $sContent;
		
	}
	
	/**
	 * Formatiert den jeweiligen Adresstyp
	 * @param string $sType
	 * @return string
	 */
	public function getFormattedAddresses($sType) {
		
		$aAddresses = $this->recipients;

		$aAddresses = $aAddresses[$sType];

		$aFormattedAddresses = array();
		foreach((array)$aAddresses as $sAddress) {

			$aFormattedAddresses[] = $sAddress;

		}
		
		$sReturn = implode('; ', $aFormattedAddresses);
		
		return $sReturn;
		
	}

	/**
	 * Gibt formatiert alle Anhänge zurück
	 * @return string 
	 */
	public function getFormattedAttachments() {
		
		$oFormatDocuments = new Ext_Thebing_Gui2_Format_Communication_Documents('; ', true);
		$oFormatAttachments = new Ext_Thebing_Gui2_Format_Communication_Attachments('; ', true);

		$aDocuments = $oFormatDocuments->format($this->documents);
		$aAttachments = array_merge($this->attachments, $this->school_files);
		$aAttachments = $oFormatAttachments->format($aAttachments);

		$aFormattedAttachments = array_merge($aDocuments, $aAttachments);

		$sReturn = implode('; ', $aFormattedAttachments);
		
		return $sReturn;

	}

	/**
	 * Gibt formatiert alle verwendeten Markierungen zurück 
	 * @return string
	 */
	public function getFormattedFlags()
	{
		$aReturn = array();
		$aFlags = (array)$this->flags;

		$aAllFlags = Ext_Thebing_Communication::getFlags();

		foreach($aFlags as $sFlag) {

			$sReturnFlag = $aAllFlags[$sFlag];
			
			$aReturn[] = $sReturnFlag;

		}

		$sReturn = implode('; ', $aReturn);

		return $sReturn;

	}

	/**
	 * @param string $sEntity
	 * @param int $iEntityId
	 * @return static[]
	 */
	public static function searchByEntityRelation($sEntity, $iEntityId) {

		$sSql = "
			SELECT
				`kel`.*
			FROM
				`kolumbus_email_log` `kel` LEFT JOIN
				`kolumbus_email_log_relations` `kelr` ON
					`kelr`.`log_id` = `kel`.`id` AND
					`kelr`.`object` = :class AND
					`kelr`.`object_id` = :id
			WHERE
				(
					`kel`.`object` = :class AND
					`kel`.`object_id` = :id
				) OR
				`kelr`.`log_id` IS NOT NULL
		";

		$aResult = (array)DB::getQueryRows($sSql, [
			'class' => $sEntity,
			'id' => $iEntityId,
		]);

		$aResult = array_map(function(array $aLog) {
			return static::getObjectFromArray($aLog);
		}, $aResult);

		return $aResult;

	}

}