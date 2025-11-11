<?php

/**
 * @author Mehmet Durmaz
 */
class Ext_TC_Email_Address_Mapping extends Ext_TC_Index_Mapping_Abstract
{	
	/**
	 *
	 * @todo Überprüfen warum das mit Wildcard nicht geht, sobald es wieder geht einkommentieren :)
	 */
	protected $_aLikeSearch = array(
		#'email',
	);	

	/**
	 * Bei E-Mail benutzen wir einen manuell definierten Analyser
	 */
	protected function _configure()
	{
		$oField = $this->getField('email_original');
		$this->_addAnalyser($oField, 'email');
	}
}