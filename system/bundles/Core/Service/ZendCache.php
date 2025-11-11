<?php

namespace Core\Service;

class ZendCache extends \Zend_Cache_Backend implements \Zend_Cache_Backend_Interface {
	
	public function setDirectives($directives) {
		
	}
	
	public function load($id, $doNotTestCacheValidity = false) {
		\WDCache::get($id);
	}
	
	public function test($id) {
		$mValue = \WDCache::get($id);
		
		if($mValue !== null) {
			return true;
		} else {
			return false;
		}
		
	}
	
	public function save($data, $id, $tags = array(), $specificLifetime = false) {
		
		\WDCache::set($id, $specificLifetime, $data);
		
	}
	
	public function remove($id) {
		
		\WDCache::delete($id);
		
	}
	
	public function clean($mode = \Zend_Cache::CLEANING_MODE_ALL, $tags = array()) {

		$oLog = \Log::getLogger();
		
		switch ($mode) {
            case \Zend_Cache::CLEANING_MODE_ALL:
                return \WDCache::flush();
                break;
            case \Zend_Cache::CLEANING_MODE_OLD:
                $oLog->addError('Core\Service\ZendCache::clean() : CLEANING_MODE_OLD is unsupported by WDCache');
                break;
            case \Zend_Cache::CLEANING_MODE_MATCHING_TAG:
            case \Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG:
            case \Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG:
                $oLog->addError('Core\Service\ZendCache::clean() : tags are unsupported by WDCache');
                break;
            default:
				Zend_Cache::throwException('Invalid mode for Core\Service\ZendCache::clean() method');
				break;
        }

	}

}