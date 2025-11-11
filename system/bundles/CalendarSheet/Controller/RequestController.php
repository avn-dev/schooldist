<?php

namespace CalendarSheet\Controller;

use Ext_CalendarSheet;
use Ext_CalendarSheet_GarbageCollector;

/**
 * v6
 */
class RequestController extends \MVC_Abstract_Controller {

	public function handle() {

		if($this->_oRequest->exists('hash')) {

			// Ekelhaft aber notwendig (Abwärtskompatibilität)
			global $_VARS, $user_data;
			$_VARS = $this->_oRequest->getAll();
			$user_data = \Access::getInstance()->getUserData();
			
			$sHash = $this->_oRequest->get('hash');
			$sInstanceHash = $this->_oRequest->get('instance_hash');

			$oCalender = Ext_CalendarSheet::getClass($sHash, $sInstanceHash);
			$oCalender->switchAjaxRequest($_VARS);

			if(
				$this->_oRequest->exists('instance_hash') &&
				ctype_alnum($sHash) &&
				ctype_alnum($sInstanceHash)
			) {
				Ext_CalendarSheet_GarbageCollector::touchSession($sHash, $sInstanceHash);
			}

		} else {
			echo "No calendar hash or calendar hash is expired!";
		}
		
		die();
	}

	
}