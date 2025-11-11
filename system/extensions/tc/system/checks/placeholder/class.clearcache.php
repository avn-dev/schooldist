<?php

class Ext_TC_System_Checks_Placeholder_ClearCache extends GlobalChecks {
	
	public function getTitle() {
		return 'Placeholder cache';
	}
	
	public function getDescription() {
		return 'Clears all cache entries.';
	}
	
	public function executeCheck() {
		
		WDCache::deleteGroup(Ext_TC_Placeholder_Abstract::TC_PLACEHOLDER_CACHE_GROUP);
		
		return true;
	}
	
}

