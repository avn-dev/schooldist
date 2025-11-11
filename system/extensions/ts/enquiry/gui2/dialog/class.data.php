<?php

/**
 * @property Ext_TS_Inquiry $_oWDBasic
 */
class Ext_TS_Enquiry_Gui2_Dialog_Data extends Ext_Gui2_Dialog_Data {

	public function getEdit($selectedIds, $saveData = [], $additional = false) {

		// Verhindern, dass Table-Where irgendeinen Schwachsinn (school_id) ins Objekt setzt, der wegen Elasticsearch irrelevant ist
		$this->_aInitData = [];

		$data = parent::getEdit($selectedIds, $saveData, $additional);

		$data = array_map(function (array $field) {
			// Fake-Feld befüllen
			if (
				$field['db_column'] === 'is_group' &&
				$this->_oWDBasic->hasGroup()
			) {
				$field['value'] = 1;
			}
			return $field;
		}, $data);

		return $data;

	}

	/**
	 * @see \Ext_TS_Enquiry_Gui2::saveEditDialogData()
	 */
	public function saveEdit(array $selectedIds, $saveData, $save = true, $action = 'edit', $prepareOpenDialog = true) {

		// Standard-Werte für Enquiry setzen
		if (!$this->_oWDBasic->exist()) {
			$this->_oWDBasic->type = Ext_TS_Inquiry::TYPE_ENQUIRY;

			// Journey muss für Schulverknüpfung existieren, darf aber nicht angezeigt werden
			$this->_oWDBasic->getJourney()->type = Ext_TS_Inquiry_Journey::TYPE_DUMMY;
		}

		// Schule geändert: Alle Journeys löschen
		if (
			$this->_oWDBasic->exist() &&
			!empty($saveData['school_id']['ts_ij']) && // Feld ist natürlich disabled und Wert fehlt dann komplett
			$saveData['school_id']['ts_ij'] != $this->_oWDBasic->getSchool()->id
		) {
			$this->_oWDBasic->cleanJoinedObjectChilds('journeys');
			$this->_oWDBasic->setJourneyContext(); // Internen Mist-State löschen
			$this->_oWDBasic->getJourney()->type = Ext_TS_Inquiry_Journey::TYPE_DUMMY;
		}

		if (
			$save &&
			$this->_oGui->getRequest()->filled('save.school_id.ts_ij')
		) {
			// Schule muss übergeben werden, da diese eigentlich erst nach saveEdit existiert, aber saveEdit wieder alles auf einmal macht
			// Der Fall ist nur für All Schools relevant, da ansonsten die Schule mal wieder aus der Session gezaubert wird
			$school = Ext_Thebing_School::getInstance($this->_oGui->getRequest()->input('save.school_id.ts_ij'));
			$customerNumber = new \Ext_Thebing_Customer_CustomerNumber($this->_oWDBasic);
			$customerNumber->setSchool($school);
			$customerNumber->saveCustomerNumber(false, false);
		}

		return parent::saveEdit($selectedIds, $saveData, $save, $action, $prepareOpenDialog);

	}


	protected function getWDBasicJoinedObject(WDBasic $entity, array $saveField): WDBasic {

		// Alle weiteren Objekte laden, weil es früher scheinbar keine Relationen gab
		if ($entity instanceof Ext_TS_Inquiry) {

			/** @var Ext_TS_Inquiry $entity */
			$object = $entity->getObjectByAlias($saveField['db_alias'], $saveField['db_column']);

			if ($object === null) {
				throw new RuntimeException(sprintf('No object for alias: %s.%s', $saveField['db_alias'], $saveField['db_column']));
			}

			return $object;

		}

		return $entity;

	}

}