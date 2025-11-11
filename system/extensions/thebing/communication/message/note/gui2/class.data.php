<?php

class Ext_Thebing_Communication_Message_Note_Gui2_Data extends Ext_TC_Communication_Message_Notice_Gui2_Data {

	/**
	 * Liefert ein Array aller Felder, die enkodierte Werte haben
	 * Wird im JS und in der Ext_TA_Communication_Message verwendet.
	 *
	 * @return array
	 */
	public static function getEncodedCorrespondantFields() {
		return array('customer_contact', 'agency_contact');
	}
	
}