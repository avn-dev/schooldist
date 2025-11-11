<?php

/**
 * Class Ext_Thebing_Email_Template
 *
 * @method static Ext_Thebing_Email_TemplateRepository getRepository()
 */
class Ext_Thebing_Email_Template extends Ext_Thebing_Basic {

	protected $_aAdditional = array();
	protected $_aAdditionalOriginal = array();

	protected $_sTable = 'kolumbus_email_templates';
	protected $_sTableAlias = 'ket';

	protected $_aJoinTables = array(
		'applications'=>array(
			'table'=>'kolumbus_email_templates_applications',
	 		'foreign_key_field'=>'application',
	 		'primary_key_field'=>'template_id'
		),
		'join_schools'=>array(
			'table'=>'kolumbus_email_templates_schools',
	 		'foreign_key_field'=>'school_id',
	 		'primary_key_field'=>'template_id',
			'class' => 'Ext_Thebing_School'
		),
		'languages' => array(
			'table'=>'kolumbus_email_templates_languages',
	 		'foreign_key_field' => array('language', 'layout_id', 'subject', 'content'),
	 		'primary_key_field' => 'template_id',
			'autoload' => false
		)
	);

	public function  __set($sName, $sValue) {

		if(
			$sName == 'schools' ||
			$sName == 'languages' ||
			$sName == 'flags'
		) {
			$this->_aAdditional[$sName] = $sValue;
		} elseif(
			strpos($sName, 'subject') !== false ||
			strpos($sName, 'content') !== false ||
			strpos($sName, 'attachments') !== false ||
			strpos($sName, 'layout') !== false
		) {
			$aParts = explode('_', $sName, 2);
			$this->_aAdditional[$aParts[0]][$aParts[1]] = $sValue;
		} else {
			parent::__set($sName, $sValue);
		}

	}

	public function  __get($sName) {

		if(
			$sName == 'schools' ||
			$sName == 'languages' ||
			$sName == 'flags'
		) {
			$sValue = (array)$this->_aAdditional[$sName];
		} elseif(
			strpos($sName, 'subject') !== false ||
			strpos($sName, 'content') !== false ||
			strpos($sName, 'attachments') !== false ||
			strpos($sName, 'layout') !== false
		) {
			$aParts = explode('_', $sName, 2);
			$sValue = $this->_aAdditional[$aParts[0]][$aParts[1]];
		} else {
			$sValue = parent::__get($sName);
		}

		return $sValue;

	}

	/**
	 * @param int $iDataID
	 */
	protected function _loadData($iDataID) {

		parent::_loadData($iDataID);

		if($iDataID > 0) {

			$aKeys = array(
				'template_id' => (int)$this->id
			);

			$this->_aAdditional['schools'] = DB::getJoinData('kolumbus_email_templates_schools', $aKeys, 'school_id');
			$this->_aAdditional['flags'] = DB::getJoinData('kolumbus_email_templates_flags', $aKeys, 'flag');
			$this->_aAdditional['languages'] = DB::getJoinData('kolumbus_email_templates_languages', $aKeys);

			$aTempLanguages = $this->_aAdditional['languages'];
			$this->_aAdditional['languages'] = array();
			$this->_aAdditional['subject'] = array();
			$this->_aAdditional['content'] = array();

			foreach((array)$aTempLanguages as $aLanguage) {

				$this->_aAdditional['languages'][] = $aLanguage['language'];
				$this->_aAdditional['subject'][$aLanguage['language']] = $aLanguage['subject'];
				$this->_aAdditional['content'][$aLanguage['language']] = $aLanguage['content'];
				$this->_aAdditional['layout'][$aLanguage['language']] = $aLanguage['layout_id'];

				$aKeys['language'] = $aLanguage['language'];
				$this->_aAdditional['attachments'][$aLanguage['language']] = DB::getJoinData('kolumbus_email_templates_languages_attachments', $aKeys, 'attachment');

			}

			$this->_aAdditionalOriginal = $this->_aAdditional;

		}

	}

	/**
	 * @return $this
	 */
	public function save($bLog=true) {

		$aAdditional = $this->_aAdditional;

		$oClient = Ext_Thebing_Client::getFirstClient();
		$this->client_id = $oClient->id;

		parent::save($bLog);

		$this->_aAdditional = $aAdditional;

		$aKeys = array(
			'template_id' => (int)$this->id
		);

		DB::updateJoinData('kolumbus_email_templates_schools', $aKeys, (array)$this->_aAdditional['schools'], 'school_id');
		DB::updateJoinData('kolumbus_email_templates_flags', $aKeys, (array)$this->_aAdditional['flags'], 'flag');

		$aData = array();
		foreach((array)$this->_aAdditional['languages'] as $sLanguage) {
    
			$aItem = array();
			if(!is_array($sLanguage)){
                $aItem['language'] = $sLanguage;
                if(isset($this->_aAdditional['subject'][$sLanguage])) {
                    $aItem['subject'] = $this->_aAdditional['subject'][$sLanguage];
                }
                if(isset($this->_aAdditional['content'][$sLanguage])) {
                    $aItem['content'] = $this->_aAdditional['content'][$sLanguage];
                }
                if(isset($this->_aAdditional['layout'][$sLanguage])) {
                    $aItem['layout_id'] = $this->_aAdditional['layout'][$sLanguage];
                }
            // Beim "als neuen Eintrag anlegen"
            } else {
                $aItem = $sLanguage;
            }

			$aData[] = $aItem;

			$aAttachments = array();
			foreach((array)$this->_aAdditional['attachments'][$sLanguage] as $sAttachment) {
				if(!empty($sAttachment)) {
					$sSql = "
						SELECT
							`id`
						FROM
							#table
						WHERE
							`template_id` = :template_id AND
							language = :language AND
							`attachment` = :attachment
					";
					$iId = DB::getQueryOne($sSql, array(
						'table' => 'kolumbus_email_templates_languages_attachments',
						'template_id' => (int)$this->id,
						'language' => $sLanguage,
						'attachment' => $sAttachment
					));
					$aAttachments[] = array(
						'attachment' => $sAttachment,
						'id' => (int)$iId
					);
				}
			}
			if(!empty($aAttachments)) {
				$aLanguageKeys = $aKeys;
				$aLanguageKeys['language'] = $sLanguage;
				DB::updateJoinData('kolumbus_email_templates_languages_attachments', $aLanguageKeys, $aAttachments);
			} else {
				/* Sofern es keine Attachments mehr gibt,
				 * können alle Attachments gelöscht werden */
				$sSql = "
					DELETE FROM
						#table
					WHERE
						`template_id` = :template_id AND
						`language` = :language
				";
				DB::executePreparedQuery($sSql, array(
					'table' => 'kolumbus_email_templates_languages_attachments',
					'template_id' => (int)$this->id,
					'language' => $sLanguage
				));
			}

		}

		DB::updateJoinData('kolumbus_email_templates_languages', $aKeys, (array)$aData);

		return $this;
	}

	/**
	 * @return Ext_Thebing_School[]
	 */
	public function getSchools() {
		return $this->getJoinTableObjects('join_schools');
	}

	public function getUploadPath($bDocumentRoot=true) {
		$oClient = Ext_Thebing_Client::getFirstClient();

		$sPath = $oClient->getFilePath($bDocumentRoot);

		$sPath .= 'email_templates/';

		return $sPath;
	}

	/**
	 * @todo Umschreiben auf manipulateQueryParts
	 * @return array
	 * @throws Exception
	 */
	public function getListQueryData($oGui=null) {

		$aQueryData = array();

		$sFormat = $this->_formatSelect();

		$aQueryData['data'] = array();

		$sTableAlias = $this->_sTableAlias;

		if(empty($sTableAlias)) {
			$sTableAlias = $this->_sTable;
		}

		$sAliasString = '';
		$sAliasName = '';
		if(!empty($sTableAlias)) {
			$sAliasString .= '`'.$sTableAlias.'`.';
			$sAliasName .= '`'.$sTableAlias.'`';
		}

		$aQueryData['sql'] = "
				SELECT
					".$sAliasString."*,
					GROUP_CONCAT(DISTINCT `keta`.`application` SEPARATOR ',') `applications`,
					GROUP_CONCAT(DISTINCT `join_schools`.`school_id` SEPARATOR ',') `schools`,
					GROUP_CONCAT(DISTINCT `languages`.`language` SEPARATOR ',') `languages`
					{FORMAT}
				FROM
					`{TABLE}` ".$sAliasName." LEFT JOIN
					`kolumbus_email_templates_applications` `keta` ON
						`ket`.`id` = `keta`.`template_id`
			";

		$iJoinCount = 1;

		foreach((array)$this->_aJoinTables as $sJoinAlias => $aJoinData){

			$aQueryData['sql'] .= " LEFT OUTER JOIN
									#join_table_".$iJoinCount." #join_alias_".$iJoinCount." ON
									#join_alias_".$iJoinCount.".#join_pk_".$iJoinCount." = ".$sAliasString."`id`
								";

			$aQueryData['data']['join_table_'.$iJoinCount]	=  $aJoinData['table'];
			$aQueryData['data']['join_pk_'.$iJoinCount]		=  $aJoinData['primary_key_field'];
			$aQueryData['data']['join_alias_'.$iJoinCount]	=  $sJoinAlias;

			$iJoinCount++;
		}

		if(array_key_exists('active', $this->_aData)) {
			$aQueryData['sql'] .= " WHERE ".$sAliasString."`active` = 1 ";
		}

		if(count($this->_aJoinTables) > 0){
			$aQueryData['sql'] .= "GROUP BY ".$sAliasString."`id` ";
		}

		if(array_key_exists('id', $this->_aData)) {
			$aQueryData['sql'] .= "ORDER BY ".$sAliasString."`id` ASC ";
		}

		$aQueryData['sql'] = str_replace('{FORMAT}', $sFormat, $aQueryData['sql']);
		$aQueryData['sql'] = str_replace('{TABLE}', $this->_sTable, $aQueryData['sql']);

		return $aQueryData;

	}

	public function getList($aSchoolIds=[], $aApplication=null, $aLanguages=null) {

		$aReturn = [];

		$aSql = array();
		$sWhere = '';
		$sFrom = '';

		// OR-Bedingung
		if(!is_null($aLanguages)) {
			$sWhere .= ' AND `ketl`.`language` IN (:languages)';
			$aSql['languages'] = (array)$aLanguages;
			$sFrom .= ' LEFT OUTER JOIN
				`kolumbus_email_templates_languages` `ketl` ON
					`ketl`.`template_id` = `ket`.`id` '
			;
		}

		// OR-Bedingung
		if(!is_null($aApplication)) {
			
			if(is_array($aApplication)) {
				$aApplication = (array) $aApplication;
			}
			
			$sWhere .= ' AND `keta`.`application` IN (:application) ';
			$aSql['application'] = $aApplication;
			$sFrom .= ' LEFT OUTER JOIN
				`kolumbus_email_templates_applications` `keta` ON
					`keta`.`template_id` = `ket`.`id` ';
		}

		$sSql = "
			SELECT
				`ket`.`id`,
				`ket`.`name`,
				GROUP_CONCAT(`kets`.`school_id`) `school_ids`
			FROM
				`kolumbus_email_templates` `ket` LEFT JOIN
				`kolumbus_email_templates_schools` `kets` ON
				`kets`.`template_id` = `ket`.`id`
				{$sFrom}
			WHERE
				`ket`.`active` = 1
				{$sWhere}
			GROUP BY
				`ket`.`id`
			ORDER BY
				`ket`.`name`
		";

		$aResult = (array)DB::getQueryRows($sSql, $aSql);

		// Wenn Schul-IDs angegeben: AND-Bedingung auf alle School-IDs
		if(!empty($aSchoolIds)) {
			$aResult = array_filter($aResult, function($aTemplate) use($aSchoolIds) {
				$aTemplateSchoolIds = explode(',', $aTemplate['school_ids']);
				$aDiff = array_diff($aSchoolIds, $aTemplateSchoolIds);
				return empty($aDiff);
			});
		}

		if(is_array($aResult)) {
			foreach($aResult as $aTemplate) {
				$aReturn[$aTemplate['id']] = $aTemplate['name'];
			}
		}

		return $aReturn;
	}

	/**
	 * Liefert alle Anhänge zu dem Template
	 */
	public function getAttachments($sLang = ''){
		$sSql = "SELECT
						*
					FROM
						`kolumbus_email_templates_languages_attachments`
					WHERE
						`template_id` = :template_id AND
						`attachment` != ''
				";
		$aSql = array();
		$aSql['template_id'] = (int)$this->id;

		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		$sPath = $this->getUploadPath();
		$aBack = array();
		foreach((array)$aResult as $aData){
			if(file_exists($sPath . $aData['attachment'])){
				$oAttachment = Ext_Thebing_Email_Template_Attachment::getInstance($aData['id']);
				if(
					$sLang != ''
				){
					// Nur Attachments eine Sprache
					if($oAttachment->language == $sLang){
						$aBack[] = $oAttachment;
					}
				}else{
					$aBack[] = $oAttachment;
				}
			}
		}
		return $aBack;
	}

	//muss abgeleitet werden, weil noch additional Daten verglichen werden müssen
	public function getIntersectionData(){
		
		$aInterSectionData = (array)parent::getIntersectionData();

		$aAdditionalOriginal	= (array)$this->_aAdditionalOriginal;
		$aAdditional			= (array)$this->_aAdditional;

		$sUploadPath			= $this->getUploadPath(false);

		foreach($aAdditional as $sKey => $aData)
		{
			if(is_array($aData))
			{
				if(
					isset($aAdditionalOriginal[$sKey]) &&
					count($aData) == count($aAdditionalOriginal[$sKey])
				)
				{
					foreach($aData as $sKey2 => $mValue)
					{
						if
						(
							isset($aAdditionalOriginal[$sKey][$sKey2])
						)
						{

							$mValue = str_replace($sUploadPath,'',$mValue);

							if
							(
								$mValue != $aAdditionalOriginal[$sKey][$sKey2]
							)
							{
								$aInterSectionData[$sKey][$sKey2] = $mValue;
							}
						}
						else
						{
							$aInterSectionData[$sKey][$sKey2] = $mValue;
						}
					}
				}
				else
				{
					$aInterSectionData[$sKey] = $aData;
				}

			}
		}

		return $aInterSectionData;
	}
	
	/**
	 * Validiert die WDBasic
	 * @param type $bThrowExceptions
	 * @return string 
	 */
	public function validate($bThrowExceptions = false){
		$mError = parent::validate($bThrowExceptions);
		
		// Es muss geprüft werden, ob die Empfänger im korrekten Format angegeben sind, und ob die Mail
		// Adressen existieren
		$sSeperator = ';';
		$aCC	= explode($sSeperator, $this->cc);
		$aBCC	= explode($sSeperator, $this->bcc);
		
		$aMailCheck = array();
		$aMailCheck['cc'] = $aCC;
		$aMailCheck['bcc'] = $aBCC;

		// Alle Mails prüfen aus dem Eingabefeld
		foreach($aMailCheck as $sField => $aMails){
			$bError = false;
			foreach($aMails as $sEmail){
				$sEmail = trim($sEmail);
				
				if(!empty($sEmail)){
					$bValid = \Util::checkEmailMX($sEmail);
				
					if(!$bValid){
						$bError = true;
						break;
					}	
				}
						
			}
			
			if($bError){
				
				if(!is_array($mError)){
					$mError = array();
				}
				
				$mError[$sField] = 'INVALID_MAIL'; #$this->_oGui->t('E-Mail Adressen müssen ";" getrennt eingegeben werden und gültig sein.');
			}
		}		
		

		
		return $mError;
	}

	/**
	 * Gibt den Betreff des Templates wieder.
	 *
	 * @param string $sLanguage
	 * @return string
	 */
	public function getSubject($sLanguage = 'en') {
		$sSubject = '';
		if(isset($this->_aAdditional['subject'][$sLanguage])) {
			$sSubject = $this->_aAdditional['subject'][$sLanguage];
		}
		return $sSubject;
	}

	/**
	 * Gibt den Inhalt des Templates wieder.
	 *
	 * @param string $sLanguage
	 * @return string
	 */
	public function getContent($sLanguage = 'en') {
		$sContent = '';
		if(isset($this->_aAdditional['content'][$sLanguage])) {
			$sContent = $this->_aAdditional['content'][$sLanguage];
		}
		return $sContent;
	}

	/**
	 * Attachment-Array für Kommunikation/WDMail bauen
	 *
	 * @return array
	 */
	public function buildMailAttachmentArray() {

		$aAttachments = array();
		foreach($this->getAttachments() as $oAttachment) {
			$aAttachments[$oAttachment->language][] = [
				'path' => $this->getUploadPath().$oAttachment->attachment,
				'name' => $oAttachment->attachment,
				'relation' => get_class($this),
				'relation_id' => $this->id
			];
		}

		return $aAttachments;

	}

	/**
	 * @return Ext_Thebing_User|null
	 */
	public function getDefaultIdentityUser() {

		if($this->default_identity_id > 0) {
			return Ext_Thebing_User::getInstance($this->default_identity_id);
		}

		return null;

	}

}