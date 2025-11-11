<?php


class Ext_Thebing_System_Checks_Agency_ManualCreditnotesDocuments extends GlobalChecks
{
	protected $_oClient;

	protected $_aSchoolIds = array();


	public function getDescription()
	{
		return 'Add manual creditnote documents to document list.';
	}
	
	public function getTitle()
	{
		return 'Check manual creditnote documents';
	}
	
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');
		
		Ext_Thebing_Util::backupTable('kolumbus_inquiries_documents');
		Ext_Thebing_Util::backupTable('kolumbus_inquiries_documents_versions');
		
		$this->_oClient		= Ext_Thebing_System::getClient();
		
		$this->_aSchoolIds	= Ext_Thebing_Client::getSchoolList(true);
		
		$iFirstSchoolId		= (int)key($this->_aSchoolIds);
		
		// Die neue Zwischentabelle erstellen
		$sSql = '
			CREATE TABLE IF NOT EXISTS `ts_manual_creditnotes_to_documents` (
			  `manual_creditnote_id` int(11) NOT NULL,
			  `document_id` int(11) NOT NULL,
			  PRIMARY KEY (`manual_creditnote_id`,`document_id`),
			  KEY `manual_creditnote_id` (`manual_creditnote_id`),
			  KEY `document_id` (`document_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		';
		
		DB::executeQuery($sSql);
		
		// Falls der Check ein 2.mal durchläuft, alle vorher angelegten Dokumente löschen
		$this->_clearDocuments();

		// Überprüfen ob Check schonmal durchgelaufen & Standardnumberrange schonmal angelegt wurde...
		$oNumberrange	= Ext_Thebing_Inquiry_Document_Numberrange::getObject('manual_creditnote', false, $iFirstSchoolId);
		
		$iNumberrangeId = (int)$oNumberrange->id;

		if($iNumberrangeId <= 0)
		{
			// Check läuft das erste mal durch, Standard Numberrange erstellen
			$iNumberrangeId = (int)$this->_createNumberrangeId();
		}
		
		if(!$iNumberrangeId)
		{
			__pout('Numberrange Create Error!');
			
			return true;
		}
		
		$sSql = '
			SELECT
				`kcv`.*,
				`kagmc`.`document_number`
			FROM
				`kolumbus_creditnotes_versions` `kcv` INNER JOIN
				`kolumbus_agencies_manual_creditnotes` `kagmc` ON
					`kagmc`.`id` = `kcv`.`creditnote_id` AND
					`kagmc`.`active` = 1
			ORDER BY
				`kcv`.`creditnote_id` DESC,
				`kcv`.`created` DESC
		';
		
		$aSql		= array();
		
		$oDB		= DB::getDefaultConnection();
		
		$aResult	= $oDB->getCollection($sSql, $aSql);
		
		$aDocuments	= array();
		
		foreach($aResult as $aRowData)
		{
			$iCreditnoteId = $aRowData['creditnote_id'];
			
			if(!isset($aDocuments[$iCreditnoteId]))
			{
				$aDocuments[$iCreditnoteId] = array();
			}
			
			$aDocuments[$iCreditnoteId][] = $aRowData;
		}

		foreach($aDocuments as $iCreditnoteId => $aVersions)
		{
			
			// Pro Creditnote kann es zurzeit nur 1 Dokument geben
			$aFirst	= reset($aVersions);

			$aInsertDocument = array(
				'changed_by_user_id'	=> $aFirst['user_id'],
				'document_number'		=> $aFirst['document_number'],
				'numberrange_id'		=> $iNumberrangeId,
				'changed'				=> $aFirst['changed'],
				'created'				=> $aFirst['created'],
				'active'				=> '1',
				'type'					=> 'manual_creditnote',
			);
			
			if(isset($aFirst['creator_id']))
			{
				$aInsertDocument['creator_id'] = $aFirst['creator_id'];
			}

			// Dokument anlegen
			$iDocumentId = (int)DB::insertData('kolumbus_inquiries_documents', $aInsertDocument);
			
			if($iDocumentId > 0)
			{
				// Zwischentablle befüllen (Dokumente <> Manuelle Creditnotes)
				
				$aInsertManualCreditnotesToDocuments = array(
					'manual_creditnote_id'	=> $aFirst['creditnote_id'],
					'document_id'			=> $iDocumentId,
				);
			
				$rRes = DB::insertData('ts_manual_creditnotes_to_documents', $aInsertManualCreditnotesToDocuments);
				
				if($rRes !== false)
				{
					//Versionen anlegen

					$iVersionCounter	= count($aVersions);

					foreach($aVersions as $iCounter => $aRowData)
					{					
						// media/secure wird in den version_items nicht mehr mitgespeichert, hier auch entfernen
						$sPath	= $aRowData['file'];
						$sPath	= str_replace('/media/secure', '', $sPath);
						
						$sDateVersion			= $aRowData['date'];
						
						// Falls kein Datum vorhanden, Standardwert setzen (Erstelldatum)
						if(empty($sDateVersion) || $sDateVersion == '0000-00-00')
						{
							if(WDDate::isDate($aRowData['created'], WDDate::DB_TIMESTAMP))
							{
								$oDate			= new WDDate($aRowData['created'], WDDate::DB_TIMESTAMP);
								$sDateVersion	= $oDate->get(WDDate::DB_DATE);
							}
						}
						
						$aInsertVersion = array(
							'changed'			=> $aRowData['changed'],
							'created'			=> $aRowData['created'],
							'active'			=> $aRowData['active'],
							'document_id'		=> $iDocumentId,
							'version'			=> $iVersionCounter,
							'template_id'		=> $aRowData['template_id'],
							'date'				=> $sDateVersion,
							'txt_address'		=> $aRowData['txt_address'],
							'txt_subject'		=> $aRowData['txt_subject'],
							'txt_intro'			=> $aRowData['txt_intro'],
							'txt_outro'			=> $aRowData['txt_outro'],
							'txt_signature'		=> $aRowData['txt_signature'],
							'signature'			=> $aRowData['signature'],
							'path'				=> $sPath,
							'comment'			=> $aRowData['comment'],
							'user_id'			=> $aRowData['user_id'],
						);

						if(isset($aRowData['creator_id']))
						{
							$aInsertVersion['creator_id'] = $aRowData['creator_id'];
						}

						$iVersionId = (int)DB::insertData('kolumbus_inquiries_documents_versions', $aInsertVersion);

						if($iVersionId > 0)
						{
							if($iCounter == 0)
							{
								// Letzte Version eintragen

								$aUpdateLatestVersion = array(
									'latest_version' => $iVersionId,
								);

								$sWhere = ' id = ' . $iDocumentId;

								DB::updateData('kolumbus_inquiries_documents', $aUpdateLatestVersion, $sWhere);
							}
						}
						else
						{
							__pout($aInsertVersion);
							__pout('failed to create document version!'); 
						}

						$iVersionCounter--;
					}
				}
				else
				{
					__pout($aInsertDocument);
					__pout('failed to create documents to creditnotes!'); 
				}
			}
			else
			{
				__pout($aInsertDocument);
				__pout('failed to create document!'); 
			}
		}
		
		return true;
	}
	
	protected function _createNumberrangeId()
	{
		// Nummernkreis für manuelle Creditnotes anlegen
		
		$oClient				= $this->_oClient;
		
		$aOldNumberrangeData	= Ext_Thebing_School_NumberRange::getValues($oClient->id, 0, array('manual_creditnote'));
		$aOldNumberrangeData	= $aOldNumberrangeData['manual_creditnote'];
		
		// Nummernkreis
		$aInsert = array(
			'active'		=> '1',
			'category'		=> 'document',
			'name'			=> 'Default Manual creditnotes',
			'offset_abs'	=> $aOldNumberrangeData['offset_abs'],
			'offset_rel'	=> $aOldNumberrangeData['offset_rel'],
			'digits'		=> $aOldNumberrangeData['digits'],
			'format'		=> $aOldNumberrangeData['format'],
		);

		$iNumberrangeId		= (int)DB::insertData('tc_number_ranges', $aInsert);
		
		// Zuweisung
		$aInsertAllocation = array(
			'active'		=> '1',
			'category'		=> 'document',
			'name'			=> 'Default Manual creditnotes allocation',
		);
		
		$iAllocationId		= (int)DB::insertData('tc_number_ranges_allocations', $aInsertAllocation);
		
		if($iNumberrangeId > 0 && $iAllocationId > 0)
		{			
			// Set
			$aInsertSet = array(
				'active'			=> '1',
				'allocation_id'		=> $iAllocationId,
				'numberrange_id'	=> $iNumberrangeId,
			);
			
			$iSetId = (int)DB::insertData('tc_number_ranges_allocations_sets', $aInsertSet);
			
			if($iSetId > 0)
			{
				// Application

				$aInsertApplication = array(
					'set_id'		=> $iSetId,
					'application'	=> 'manual_creditnote',
				);
				
				DB::insertData('tc_number_ranges_allocations_sets_applications', $aInsertApplication);

				// Allocation Objects (in jede Schule zuweisen, da die Einstellung eine Mandanteneinstellung ist)
				$aSchoolIds  = $this->_aSchoolIds;

				foreach($aSchoolIds as $iSchoolId => $sSchoolName)
				{
					$aInsertAllocationObjects = array(
						'allocation_id' => $iAllocationId,
						'object_id'		=> $iSchoolId,
					);

					DB::insertData('tc_number_ranges_allocations_objects', $aInsertAllocationObjects);
				}	
			}
			else
			{
				__pout('Couldnt create Set for Numberrange!');
				
				return false;
			}
		}
		else
		{
			__pout('Couldnt create Numberrange or Numberrange allocation!');
			
			return false;
		}
		
		return $iNumberrangeId;
	}
	
	/**
	 * Durch diesen Check angelegte Dokumente & Versionen wieder entfernen
	 * 
	 * @param int $iCreditnoteId 
	 */
	protected function _clearDocuments()
	{
		// Alle angelgenten Dokumente per Check finden
		$sSql = '
			SELECT
				`document_id`
			FROM
				`ts_manual_creditnotes_to_documents`
		';
		
		$aDocumentIds	= (array)DB::getQueryCol($sSql);
		
		$aSql			= array(
			'document_ids' => $aDocumentIds,
		);
		
		// Dokumente löschen
		$sSql = '
			DELETE FROM
				`kolumbus_inquiries_documents`
			WHERE
				`id` IN(:document_ids)
		';
		
		DB::executePreparedQuery($sSql, $aSql);
		
		// Versionen löschen
		$sSql = '
			DELETE FROM
				`kolumbus_inquiries_documents_versions`
			WHERE
				`document_id` IN(:document_ids)
		';
		
		DB::executePreparedQuery($sSql, $aSql);
		
		// Zwischentabelle (Dokumente <> Manuelle Creditnotes) leeren
		$sSql = '
			TRUNCATE 
				`ts_manual_creditnotes_to_documents`
		';
		
		DB::executeQuery($sSql);
	}
}