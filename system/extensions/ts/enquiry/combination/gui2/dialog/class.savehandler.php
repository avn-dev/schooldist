<?php

/**
 * Leere Services nicht speichern
 *
 * @property Ext_TS_Inquiry_Journey $parent
 * @property Ext_TS_Inquiry_Journey_Service $child
 */
class Ext_TS_Enquiry_Combination_Gui2_Dialog_SaveHandler extends Ext_Gui2_Dialog_Container_Save_Handler_Abstract {

	public function handle() {

		if ($this->child->isEmpty()) {
			$this->parent->removeJoinedObjectChildByKey($this->containerOptions['joined_object_key'], $this->elementId);
			return;
		}

		if ($this->child instanceof Ext_TS_Inquiry_Journey_Course) {
			$this->child->adjustData();
		}

		if ($this->child instanceof Ext_TS_Inquiry_Journey_Transfer) {
			// Achtung! Passiert nur beim Speichern, beim Reload kommt er hier nicht rein
			// Getter-Part: \Ext_TS_Enquiry_Combination_Gui2_Data::modifiyEditDialogDataRow()
			$this->child->setLocationByMergedString('start', $this->child->start);
			$this->child->setLocationByMergedString('end', $this->child->end);
		}

		// Pragmatische Lösung für einen problemtaschen Dialog: Einfach alle Traveller setzen
		// Ext_TS_Enquiry_Combination_Gui2_Data::createTravellerSelect()
		if (empty($this->child->travellers)) {
			$inquiry = $this->parent->getInquiry();
			$contacts = $inquiry->hasGroup() ? $inquiry->getGroup()->getMembers() : [$inquiry->getFirstTraveller()];
			$contactIds = array_map(function (Ext_TS_Contact $contact) {
				return $contact->id;
			}, $contacts);
			$this->child->travellers = $contactIds;
		}

	}

}