<?php
 
class Ext_Poll_Routing extends WDBasic {

	protected $_sTable = 'poll_routing';

	/**
	 * Gibt den Namen des entsprechenden Items zurück
	 * @return string
	 */	
	public function getItemName() {
		return $this->name;
	}

}