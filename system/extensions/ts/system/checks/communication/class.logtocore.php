<?php

class Ext_TS_System_Checks_Communication_LogToCore extends GlobalChecks {
	
	public function getDescription() {
		return '';
	}
	
	public function getTitle() {
		return 'Move e-mail log to new structure'; 
	}
	
	/**
	 * Kein Backup benötigt, da Tabellen leer
	 * @return boolean
	 */
	public function executeCheck() {

		$oDb = DB::getDefaultConnection();
		
		set_time_limit(14400);
		ini_set('memory_limit', '4G');

		$aOldLog = $oDb->describe('kolumbus_email_log');

		// Alte Tabelle wurde schon entfernt
		if(empty($aOldLog)) {
			return true;
		}
		
		$oDb->_begin(__METHOD__);
		
		$oDb->executeQuery('TRUNCATE TABLE `tc_communication_messages`');
		$oDb->executeQuery('TRUNCATE TABLE `tc_communication_messages_templates`');
		$oDb->executeQuery('TRUNCATE TABLE `tc_communication_messages_subjects`');
		$oDb->executeQuery('TRUNCATE TABLE `tc_communication_messages_creators`');
		$oDb->executeQuery('TRUNCATE TABLE `tc_communication_messages_addresses`');
		$oDb->executeQuery('TRUNCATE TABLE `tc_communication_messages_addresses_relations`');
		$oDb->executeQuery('TRUNCATE TABLE `tc_communication_messages_flags`');
		$oDb->executeQuery('TRUNCATE TABLE `tc_communication_messages_relations`');
		$oDb->executeQuery('TRUNCATE TABLE `tc_communication_messages_files`');
		$oDb->executeQuery('TRUNCATE TABLE `tc_communication_messages_files_relations`');
		
		$sSql = "SELECT * FROM `system_user`";
		$aUsers = DB::getQueryRowsAssoc($sSql);

		$oDummy = new stdClass();

		$oNameFormat = new Ext_Gui2_View_Format_Name;
		
		$sDocumentRoot = Util::getDocumentRoot(false);
		
		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_email_log`
				";
		$aMails = $oDb->getCollection($sSql);
		
		/*
		 * kolumbus_email_log
			-id
			-created
			#client_id
			#application // Wird nicht mehr benötigt in der TC Struktur
			-object
			-object_id
			-user_id
			-sender_id
			-template_id
			-recipients
			-documents
			-school_files
			-attachments
			-flags
			-subject
			-content
			-content_type
		 */
		foreach($aMails as $aMail) {
			
			$iOldMailId = $aMail['id'];
			
			if(empty($aMail['content_type'])) {
				$aMail['content_type'] = 'html';
			}
			
			$aMessage = [
				'changed' => $aMail['created'],
				'created' => $aMail['created'],
				'active' => 1,
				'date' => $aMail['created'],
				'direction' => 'out',
				'content' => $aMail['content'],
				'type' => 'email',
				'content_type' => $aMail['content_type']
			];
			$aMail['id'] = DB::insertData('tc_communication_messages', $aMessage);

			$aMessageTemplate = [
				'message_id' => $aMail['id'],
				'template_id' => $aMail['template_id']
			];
			DB::insertData('tc_communication_messages_templates', $aMessageTemplate);

			$aMessageSubject = [
				'message_id' => $aMail['id'],
				'subject' => $aMail['subject']
			];
			DB::insertData('tc_communication_messages_subjects', $aMessageSubject);
			
			$aMessageCreator = [
				'message_id' => $aMail['id'],
				'creator_id' => $aMail['user_id']
			];
			DB::insertData('tc_communication_messages_creators', $aMessageCreator);

			if(!empty($aUsers[$aMail['sender_id']])) {

				$aAddress = [
					'message_id' => $aMail['id'],
					'type' => 'from',
					'address' => $aUsers[$aMail['sender_id']]['email'],
					'name' => $oNameFormat->format('', $oDummy, $aUsers[$aMail['sender_id']]),
				];
				$iAddressId = DB::insertData('tc_communication_messages_addresses', $aAddress);

				$aAddressRelation = [
					'address_id' => $iAddressId,
					'relation' => 'Ext_Thebing_User',
					'relation_id' => $aMail['sender_id']
				];
				DB::insertData('tc_communication_messages_addresses_relations', $aAddressRelation);

			}

			$aMail['flags'] = json_decode($aMail['flags'], true);
			if(!empty($aMail['flags'])) {
				foreach($aMail['flags'] as $sFlag) {
					
					$aMessageFlag = [
						'message_id' => $aMail['id'],
						'flag' => $sFlag
					];
					DB::insertData('tc_communication_messages_flags', $aMessageFlag);
			
				}
			}
			
			/*
			 * kolumbus_email_log_relations
				log_id
				object_id
				object
			 */
			$sSql = "
				SELECT 
					* 
				FROM 
					`kolumbus_email_log_relations` 
				WHERE 
					`log_id` = :log_id";
			$aSql = [
				'log_id' => $iOldMailId
			];
			$aRelations = (array)$oDb->queryRows($sSql, $aSql);
			
			$aRelations[] = [
				'object' => $aMail['object'],
				'object_id' => $aMail['object_id']
			];

			foreach($aRelations as $aRelation) {
				
				try {

					$aRelationData = [
						'message_id' => $aMail['id'],
						'relation' => $aRelation['object'],
						'relation_id' => $aRelation['object_id']
					];
					DB::insertData('tc_communication_messages_relations', $aRelationData);

				} catch (DB_QueryFailedException $ex) {
					// Nix machen, da hier Duplikate entstehen können und hiermit einfach abgefangen werden
				}

			}

			$aRecipients = json_decode($aMail['recipients'], true);
			
			foreach($aRecipients as $sType=>$aRecipientAddresses) {
				
				foreach((array)$aRecipientAddresses as $sRecipientAddress) {

					try {
						
						$aRecipientAddress = [
							'message_id' => $aMail['id'],
							'type' => $sType,
							'address' => $sRecipientAddress
						];
						$iAddressId = DB::insertData('tc_communication_messages_addresses', $aRecipientAddress);
						
					} catch (DB_QueryFailedException $ex) {
						// Duplikate abfangen
					}

				}

			}
			
			//tc_communication_messages_files
			//tc_communication_messages_files_relations

			$aFiles = [];
			
			//		documents		{"inquiry":["4123"],"contract":[]}		
			$aDocuments = json_decode($aMail['documents'], true);
			
			if(!empty($aDocuments['inquiry'])) {
				foreach($aDocuments['inquiry'] as $iVersionId) {
					
					$oVersion = Ext_Thebing_Inquiry_Document_Version::getInstance($iVersionId);
					if(!$oVersion->exist()) {
						continue;
					}
					
					$aFiles[] = [
						'path' => '/storage'.$oVersion->path,
						'relation' => 'Ext_Thebing_Inquiry_Document_Version',
						'relation_id' => $iVersionId
					];
					
				}
			}
			
			if(!empty($aDocuments['contract'])) {
				foreach($aDocuments['contract'] as $iVersionId) {
					
					$oVersion = Ext_Thebing_Contract_Version::getInstance($iVersionId);
					if(!$oVersion->exist()) {
						continue;
					}
					
					$aFiles[] = [
						'path' => $oVersion->file,
						'relation' => 'Ext_Thebing_Contract_Version',
						'relation_id' => $iVersionId
					];
					
				}
			}
			
			//		school_files	{"\/storage\/clients\/client_1\/school_1\/uploads\/120_schreibblatt_klasse2.pdf":"Anhang 2.pdf"}
			$aSchoolFiles = json_decode($aMail['school_files'], true);
			
			if(!empty($aSchoolFiles)) {
				foreach($aSchoolFiles as $sPath=>$sName) {
					$aFiles[] = [
						'path' => $sPath,
						'name' => $sName
					];
				}
			}
			
			//		attachments		{"\/storage\/clients\/client_1\/school_1\/communication\/attachments\/logo_pro_linguis_neu.pdf":"logo_pro_linguis_neu.pdf","\/storage\/clients\/client_1\/email_templates\/attachments_de_77_schreibblatt_klasse2.pdf":"attachments_de_77_schreibblatt_klasse2.pdf","\/storage\/clients\/client_1\/email_templates\/attachments_de_77_schreibblatt_grundschule.pdf":"attachments_de_77_schreibblatt_grundschule.pdf"}
			$aAttachments = json_decode($aMail['attachments'], true);
			
			if(!empty($aAttachments)) {
				foreach($aAttachments as $sPath=>$sName) {
					$aFiles[] = [
						'path' => $sPath,
						'name' => $sName
					];
				}
			}

			foreach($aFiles as $aFile) {

				// Eventuelles Document-Root entfernen
				$this->removeDocumentRoot($aFile);

				// Nicht vorhandene Dateien überspringen
				if(!is_file($sDocumentRoot.$aFile['path'])) {
					continue;
				}

				if(empty($aFile['name'])) {
					$aPathInfo = pathinfo($aFile['path']);
					$aFile['name'] = $aPathInfo['basename'];
				}

				$aInsert = [
					'message_id' => $aMail['id'],
					'file' => $aFile['path'],
					'name' => $aFile['name']
				];

				try {
					
					$iFileId = $oDb->insert('tc_communication_messages_files', $aInsert);

				} catch (Exception $ex) {

					__out($aInsert);
					__out($aMail);
					__out($ex->getMessage(), 1);
					
				}

				if(!empty($aFile['relation'])) {

					try {

						$aInsert = [
							'file_id' => $iFileId,
							'relation' => $aFile['relation'],
							'relation_id' => $aFile['relation_id']
						];

						$oDb->insert('tc_communication_messages_files_relations', $aInsert);

					} catch (DB_QueryFailedException $ex) {

					}

				}	

			}
			
			/*
			 * Ist bereits mit dem Feld "flags" behandelt
			 * kolumbus_email_flags
				id	
				log_id	
				flag
			 */
			
		}

		$aRenameTables = [
			'kolumbus_emails', // Alte E-Mail-Vorlagen
			'kolumbus_email_log',
			'kolumbus_email_log_relations',
			'kolumbus_email_flags'
		];

		foreach($aRenameTables as $sRenameTable) {
			$sBackupTable = '__'.date('YmdHis').'_'.$sRenameTable;
			$sSql = "RENAME TABLE #from TO #to";
			$aSql = [
				'from' => $sRenameTable,
				'to' => $sBackupTable
			];
			$oDb->preparedQuery($sSql, $aSql);
		}
		
		$oDb->_commit(__METHOD__);
		
		return true;
	}

	protected function removeDocumentRoot(&$aFile) {

		if(strpos($aFile['path'], '/var/www') === 0) {
			
			$aFile['path'] = preg_replace('@/var/www/vhosts/[a-z\.\-]+/(httpdocs|html)@', '', $aFile['path']);
			
		}
		
		if(strpos($aFile['path'], '/media/secure/') === 0) {
			
			$aFile['path'] = str_replace('/media/secure/', '/storage/', $aFile['path']);
			
		}
			
	}
	
}


/*
 * kolumbus_email_log
id
created
client_id
application
object
object_id
user_id
sender_id
template_id
recipients
documents
school_files
attachments
flags
subject
content
content_type
 * 
 * kolumbus_email_log_relations
log_id
object_id
object
 * 
 * 
 * 
 * 
 * tc_communication_messages
 * id	changed	created	active	date	direction	content	type	content_type
 * 
 * tc_communication_messages_addresses
 * id	message_id	type	address	name
 * 
 * tc_communication_messages_addresses_relations
 * address_id	relation	relation_id
 * 
 * tc_communication_messages_codes
 * message_id	code
 * 
 * tc_communication_messages_creators
 * message_id	creator_id
 * 
 * tc_communication_messages_documents
 * message_id	version_id
 * 
 * tc_communication_messages_files
 * id	message_id	file	name
 * 
 * tc_communication_messages_files_relations
 * file_id	relation	relation_id
 * 
 * tc_communication_messages_flags
 * id	message_id	flag
 * 
 * tc_communication_messages_flags_relations
 * flag_id	relation	relation_id
 * 
 * tc_communication_messages_incoming
 * message_id	uid	unseen	answered	flagged	account_id
 * 
 * tc_communication_messages_relations
 * message_id	relation	relation_id
 * 
 * tc_communication_messages_subjects
 * message_id	subject
 * 
 * tc_communication_messages_templates
 * id	message_id	template_id
 * 
 * tc_communication_messages_templates_to_layouts
 * messagetemplate_id	layout_id
 * 
 * tc_communication_messages_to_categories
 * message_id	category_id
 * 
 * tc_communication_messages_to_messages
 * message_id	original_message_id
 * 
 */