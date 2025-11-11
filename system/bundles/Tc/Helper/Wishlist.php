<?php

namespace Tc\Helper;

class Wishlist {
	
	private $oTemplating;
	
	public function __construct() {
		
		$this->oTemplating = new \Core\Service\Templating;
		
	}
	
	public function getWelcomeBox() {

		/*
		 * TODO Das muss dringend überarbeitet werden_
		 *   1. Wenn es irgendeinen Timeout o.ä. gibt, hängt sich hier der ganze Prozess daran auf
		 *   2. Wenn $sJson eben mal kein JSON ist, löst PHP8 einen TypeError bei array_reverse aus
		 *   3. Wirklich validiert wird hier auch nichts. Damit könnte auch sonst was in das Template injected werden.
		 *
		$sProjectKey = \System::d('wishlist_project_key');
		$sUrl = 'https://wishes.fidelo.com/getwishes/';
		$sJson = file_get_contents($sUrl.$sProjectKey);
		$aWishes = array_reverse(json_decode($sJson));
		*/
		$aWishes = [];
		$oFormat = \Ext_TC_Factory::getObject('Ext_TC_Gui2_Format_Date_Time');
		
		$this->oTemplating->assign('aWishes', $aWishes);
		$this->oTemplating->assign('oFormat', $oFormat);
		
		$sReturn = $this->oTemplating->fetch('system/bundles/Tc/Resources/views/wishlist/welcome.tpl');
		
		return $sReturn;
	}
	
}