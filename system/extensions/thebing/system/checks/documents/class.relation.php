<?php

/**
 * Verbindungen zwischen Dokumente schaffen (z.B. zwischen Gutschrift und das Dokument das gutgeschrieben wurde)
 * siehe auch #3267
 *
 * Aktualisiert in Ticket #7534
 */
class Ext_Thebing_System_Checks_Documents_Relation extends GlobalChecks
{
	public function getDescription()
	{
		return 'Set relations between documents.';
	}
	
	public function getTitle()
	{
		return 'Document relations';
	}
	
	public function executeCheck()
	{
		/*// Tabelle für Dokument-Verbindungen erstellen
		$sSql = '
			CREATE TABLE IF NOT EXISTS `ts_documents_to_documents` (
			  `parent_document_id` int(11) NOT NULL,
			  `child_document_id` int(11) NOT NULL,
			  `type` varchar(20) NOT NULL,
			  PRIMARY KEY (`parent_document_id`,`child_document_id`,`type`),
			  KEY `parent_id` (`parent_document_id`),
			  KEY `child_id` (`child_document_id`),
			  KEY `type` (`type`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;	
		';

		DB::executeQuery($sSql);
		
		// Tabelle leeren, falls der Check mehrmals ausgeführt wird
		//$sSql = 'TRUNCATE `ts_documents_to_documents`';

		// Darf NICHT mehr geleert werden, da ansonsten creditnote-Verknüpfungen ganz weg wären
		//DB::executeQuery($sSql);

		// Creditnotes in die neue Tabelle bringen
		// Tabelle gibt es nicht mehr und wurde auch nicht mehr benutzt
		//$this->_makeCreditnoteRelations();*/

		// Alle Buchungen finden die Dokumente haben
		$sSql = '
			SELECT
				`ts_i`.`id`
			FROM
				`ts_inquiries` `ts_i` INNER JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kid`.`inquiry_id` = `ts_i`.`id` AND
					`kid`.`active` = 1
			WHERE
				`ts_i`.`active`
			GROUP BY
				`ts_i`.`id`
			ORDER BY 
				`ts_i`.`id` DESC
		';
		
		$aDocTypes = Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice_without_proforma');
		
		$oDB = DB::getDefaultConnection();
		$oCollection = $oDB->getCollection($sSql, array());

		$oRelationInsertStatement = DB::getPreparedStatement("
			REPLACE INTO
				`ts_documents_to_documents`
			SET
				`parent_document_id` = ?,
				`child_document_id` = ?,
				`type` = ?
		");

		DB::begin(__CLASS__);

		foreach($oCollection as $aRowData)
		{
			$iInquiryId = $aRowData['id'];
			
			// Alle für die Rechnungsfreigabe relevanten Dokumente finden für die Buchung
			$sSql = '
				SELECT
					*
				FROM
					`kolumbus_inquiries_documents`
				WHERE
					`inquiry_id` = :inquiry_id AND
					`type` IN(:doc_types)
				ORDER BY
					`created` DESC
			';
			
			$aSql = array(
				'inquiry_id'	=> $iInquiryId,
				'doc_types'		=> $aDocTypes,
			);
			
			$aDocuments = DB::getPreparedQueryData($sSql, $aSql);
			
			$aTemp = $aDocuments;

			foreach($aDocuments as $iKey => $aDocumentData) {
				unset($aTemp[$iKey]);
				
				// Verbindungen suchen
				$aRelations = $this->_searchRelations($aDocumentData, $aTemp);

				foreach($aRelations as $aRelationData) {

					// Verbindung abspeichern
					DB::executePreparedStatement($oRelationInsertStatement, array(
						(int)$aRelationData['id'],
						(int)$aDocumentData['id'],
						$aRelationData['relation_key']
					));

				}
			}
		}

		DB::commit(__CLASS__);

		return true;
	}

	/**
	 * Creditnotes haben schon Verbindungen gehabt, nur in einer seperaten Tabelle,
	 * wir übernehmen die Daten der Tabelle in die neue documents_to_documents Tabelle
	 *
	 * @return bool
	 */
	/*protected function _makeCreditnoteRelations()
	{
		$sSql = '
			SELECT
				*
			FROM
				`kolumbus_inquiries_documents_creditnote`
		';

		$oDB = DB::getDefaultConnection();

		$oCollection = $oDB->getCollection($sSql, array());

		foreach($oCollection as $aRowData)
		{
			$aInsert = array(
				'parent_document_id'	=> $aRowData['document_id'],
				'child_document_id'		=> $aRowData['creditnote_id'],
				'type'					=> 'creditnote',
			);

			DB::insertData('ts_documents_to_documents', $aInsert);
		}

		return true;
	}*/

	/**
	 * Für ein Dokument aus einer Reihe an Dokumenten die Verbindungen suchen
	 * 
	 * @param array $aCurrent
	 * @param array $aOtherDocuments
	 * @return array 
	 */
	protected function _searchRelations($aCurrent, $aOtherDocuments)
	{
		if($aCurrent['is_credit'] == 1)
		{
			// Beim Gutschreiben hat die Gutschrift immer den selben Typen, nur mit dem is_credit Flag
			$aSearchTypes = array(
				$aCurrent['type'] => 1
			);
			
			// Auf eine Gutschrift kann man keine Gutschrift anlegen
			$bFilterCredit	= true;
			
			// Zur einer Gutschrift gibt es nur eine Verbindung
			$bMany			= false;
			
			$sRelationKey	= 'credit';
			
			$aOptions		= array(
				'relation_key'	=> 'credit',
			);
		}
		else
		{
			$aSearchTypes = Ext_Thebing_Inquiry_Document::getRelationSearchTypesForType($aCurrent['type']);
			
			switch($aCurrent['type'])
			{
				case 'brutto_diff':
				case 'netto_diff':
				case 'brutto_diff_special':

					// Auf eine Gutschrift kann man eine Differenzrechnung anlegen
					$bFilterCredit = false;
					
					// Zur einer Diff Rechnung gibt es nur eine Verbindung
					$bMany = false;
					
					$aOptions		= array(
						'relation_key'	=> 'diff',
					);
					
					break;
				
				case 'storno':
					
					// Storno darf keine Gutschriften enthalten
					$bFilterCredit = true;
					
					// Für Storno kann es mehrere Verbindungen geben
					$bMany = true;
					
					$aOptions		= array(
						'relation_key'	=> 'cancellation',
					);
					
					break;

				default:
					
					return array();
					
					break;
			}
		}
		
		$aRelations = Ext_Thebing_Inquiry_Document::searchByTypes($aOtherDocuments, $aSearchTypes, $bFilterCredit, $bMany, $aOptions);

		return $aRelations;
	}
}