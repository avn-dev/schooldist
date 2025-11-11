<?php

/**
 * @property Ext_TS_Group_Contact $child
 * @property Ext_TS_Enquiry_Group $parent
 */
class Ext_TS_Enquiry_Gui2_Dialog_GroupContactSaveHandler extends Ext_Gui2_Dialog_Container_Save_Handler_Abstract {

	private $counter;

	/**
	 * Die Instanz der Klasse wird nicht neu erzeugt bei geladener GUI, daher $counter setzen
	 */
	public function setParentObject(WDBasic $parent) {

		parent::setParentObject($parent);

		$this->counter = 1;

	}

	/**
	 * Vorname und Nachname automatisch befüllen, da das keine Pflichtfelder sind
	 * Das gleiche Verhalten gab es früher schon Ext_TS_Group_Contact, mit $counter als statische (sic) Variable.
	 */
	public function handle() {

		if (empty($this->child->lastname)) {
			$this->child->lastname = $this->parent->short;
		}

		if (empty($this->child->firstname)) {
			$this->child->firstname = $this->counter++;
		}

		$contact = $this->parent->getJoinedObject('contact');
		$this->child->nationality = $contact->nationality;
		$this->child->language = $contact->language;
		$this->child->corresponding_language = $contact->corresponding_language;

	}

}
