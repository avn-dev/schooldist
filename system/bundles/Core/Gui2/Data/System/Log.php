<?php

namespace Core\Gui2\Data\System;

class Log extends \Ext_Gui2_Data {
	
	public static function getCodeOptions() {
		
		$aOptions = \Log::getLogMessages();
		
		$aOptions = \Util::addEmptyItem($aOptions);
		
		return $aOptions;
	}
	
	public static function getOrderBy() {
		return array('created' => 'DESC');
	}
	
}
