<?php

class Ext_TS_System_Checks_Agency_Manual_Creditnote_DocumentRelations extends GlobalChecks {
	
	protected $_aDocuments = array();
	
	public function getTitle() {
		return 'Manual creditnotes';
	}
	
	public function getDescription() {
		return 'Prepares the database entries of canceled manual creditnotes';
	}

	public function executeCheck() {
		
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');
		
		$bBackup = Ext_TC_Util::backupTable('ts_documents_to_documents');
		if(!$bBackup) {
			__pout('Backup error!');
			return false;
		}
		
		$this->_aDocuments = $this->_getAllDocuments();
		
		DB::begin('Ext_TS_System_Checks_Agency_Manual_Creditnote_DocumentRelations');
		
		try {
		
			$aManualCreditnotes = $this->_getManualCreditnotes();			
			
			$aEntries = array();
			
			foreach($aManualCreditnotes as $aManualCreditnote) {

				$iParentDocument	= (int) $this->_getDocument($aManualCreditnote['id']);
				$iChildDocument		= (int) $this->_getDocument($aManualCreditnote['storno_id']);
				
				if(
					$iParentDocument > 0 &&
					$iChildDocument > 0 &&
					$iParentDocument != $iChildDocument
				) {
					$aEntries[] = array(
						'parent_document_id' => $iParentDocument,
						'child_document_id' => $iChildDocument,
						'type' => 'creditnote'
					);
				}
				
			}
		
			DB::insertMany('ts_documents_to_documents', $aEntries, true);
			
		} catch (Exception $e) {
			DB::rollback('Ext_TS_System_Checks_Agency_Manual_Creditnote_DocumentRelations');
			__pout($e);
			return false;
		}

		DB::commit('Ext_TS_System_Checks_Agency_Manual_Creditnote_DocumentRelations');
		
		return true;
	}
	
	protected function _getManualCreditnotes() {
		
		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_agencies_manual_creditnotes`
			WHERE
				`storno_id` > 0
		";
		
		$aData = (array) DB::getQueryData($sSql);
		
		return $aData;		
	}
	
	protected function _getDocument($iManualCreditnote) {
		if(isset($this->_aDocuments[$iManualCreditnote])) {
			return $this->_aDocuments[$iManualCreditnote];
		}
		
		return 0;
	}
	
	protected function _getAllDocuments() {
		$sSql = "
			SELECT
				`manual_creditnote_id`, 
				`document_id`
			FROM
				`ts_manual_creditnotes_to_documents`
		";
		
		$aData = (array) DB::getQueryPairs($sSql);
		
		return $aData;
	}
	
}
