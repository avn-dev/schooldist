<?php

/**
 * Setzt bei den Version Items die Id des Objektes, auf das sich das Item bezieht bsp. Kurs 
 * Ausführlich beschrieben in: https://redmine.thebing.com/redmine/issues/5230
 *
 */
class Ext_TS_System_Checks_Document_Version_Item_TypeObjectId extends GlobalChecks
{
	public function getTitle() {
		return 'Check Document Items';
	}

	public function getDescription() {
		return 'Insert the current object IDs for document items';
	}

	public function executeCheck() {

		set_time_limit(3600 * 4);
		ini_set('memory_limit', '2G');

		// Backup --------------------------------------------------------------
		
		$bBackup = Util::backupTable('kolumbus_inquiries_documents_versions_items');		
		if(!$bBackup) {
			__pout('Backup error!');
			return false;
		}
		
		// Datenbank überprüfen ------------------------------------------------
		
		$this->_checkTableColumns();		
		
		// Werte setzen --------------------------------------------------------

		try {
		
			// Items holen
			$aItems = $this->_getItems();

			foreach($aItems as $iItemId) {
				$oItem = Ext_Thebing_Inquiry_Document_Version_Item::getInstance($iItemId);

				// IDs holen
				$aItemIds				= $this->_getServiceIdsForItem($oItem);
				$iTypeObjectId			= $aItemIds['object_id'];
				$iTypeParentObjectId	= $aItemIds['parent_id'];	

				if(
					$iTypeObjectId > 0 ||
					$iTypeParentObjectId > 0
				) {
					$oItem->type_object_id			= (int) $iTypeObjectId;
					$oItem->type_parent_object_id	= (int) $iTypeParentObjectId;
					$oItem->save();
				}

				$oItem = null;
				unset($oItem);

				// Instanzen leeren, damit der Speicher nicht überläuft
				WDBasic::clearAllInstances();

			}
		
		} catch(Exception $e) {
			__pout($e);
			return false;
		}
		
		return true;
	}

	/**
	 * Funktion liefert alle Items, für die dieser Check noch nicht ausgeführt wurde
	 * 
	 * @return array
	 */
	protected function _getItems() {
		
		$aSql = array(
			'types' => array(
				'course',
				'accommodation',
				'extra_nights',
				'extra_weeks',
				'insurance',
				'additional_course',
				'additional_accommodation',
				'special'
			)
		);
		
		$sSql = "
			SELECT
				`kivi`.`id`
			FROM
				`kolumbus_inquiries_documents_versions_items` `kivi` INNER JOIN
				`kolumbus_inquiries_documents_versions` `kidv` ON
					`kidv`.`id` = `kivi`.`version_id` AND
					`kidv`.`active` = 1	INNER JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kid`.`id` = `kidv`.`document_id` AND
					`kid`.`active` = 1
			WHERE
				`kivi`.`active` = 1 AND
				`kivi`.`type_object_id` = 0 AND
				`kivi`.`type_parent_object_id` = 0 AND
				`kivi`.`type` IN (:types) 
		";
		
		$aItems = (array) DB::getQueryCol($sSql, $aSql);
		
		return $aItems;
	}

	/**
	 * liefert die ID des Objektes, auf welches sich das übergebene Item bezieht (bsp. Kurs)
	 *  
	 * @param Ext_Thebing_Inquiry_Document_Version_Item $oItem
	 * @return array
	 */
	protected function _getServiceIdsForItem(Ext_Thebing_Inquiry_Document_Version_Item $oItem) {
		
		$iTypeObjectId = 0;
		$iTypeParentObjectId = 0;
		$oService = null;
		
		switch($oItem->type) {
			case 'course':
				$oService = Ext_TS_Inquiry_Journey_Course::getInstance($oItem->type_id);
				$iTypeObjectId = $oService->course_id;
				break;
			case 'accommodation':
			case 'extra_nights':
			case 'extra_weeks':
				$oService = Ext_TS_Inquiry_Journey_Accommodation::getInstance($oItem->type_id);
				$iTypeObjectId = $oService->accommodation_id;
				break;
			case 'insurance':
				$oService = Ext_TS_Inquiry_Journey_Insurance::getInstance($oItem->type_id);
				$iTypeObjectId = $oService->insurance_id;
				break;
			case 'additional_course':
			case 'additional_accommodation':
				// In parent_id steht bereits die richtige ID des Objektes drin
				$iTypeParentObjectId = $oItem->parent_id;
				break;
			case 'special':

				if($oItem->parent_type == 'item_id') {
					$oParentItem = Ext_Thebing_Inquiry_Document_Version_Item::getInstance($oItem->parent_id);
					// Funktion rekursiv aufrufen, um an die ID des Parent-Items zu kommen
					$aTemp = $this->_getServiceIdsForItem($oParentItem);					
					$iTypeParentObjectId = $aTemp['object_id'];
				}

				break;
		}
		
		if($oService) {
			$oService = null;
			unset($oService);
		}
		
		return array(
			'object_id' => $iTypeObjectId,
			'parent_id' => $iTypeParentObjectId
		);
		
	}

	/**
	 * prüft, ob die notwendigen Spalten in der Tabelle der Version-Items bereits
	 * existieren.
	 * -> wenn nicht, werden diese angelegt
	 */
	protected function _checkTableColumns() {		
		
		$oDB = DB::getDefaultConnection();
		
		$bCheckField1 = $oDB->checkField('kolumbus_inquiries_documents_versions_items', 'type_object_id', true);
		if(!$bCheckField1) {
			$sSql = "ALTER TABLE `kolumbus_inquiries_documents_versions_items` ADD `type_object_id` INT NOT NULL";
			$oDB->executeQuery($sSql);
		}
		
		$bCheckField2 = $oDB->checkField('kolumbus_inquiries_documents_versions_items', 'type_parent_object_id', true);
		if(!$bCheckField2) {
			$sSql = "ALTER TABLE `kolumbus_inquiries_documents_versions_items` ADD `type_parent_object_id` INT NOT NULL";
			$oDB->executeQuery($sSql);
		}
		
	}
		
}