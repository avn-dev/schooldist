<?php

/**
 * Beschreibung der Klasse
 */
class Ext_TS_Inquiry_Contact_Detail extends Ext_TC_Contact_Detail{

	/**
	 * Validierung nach ITU-Standard ist in der Schulsoftware nicht gewünscht
	 *
	 * @var bool
	 */
	protected $bValidatePhoneNumber = false;

}