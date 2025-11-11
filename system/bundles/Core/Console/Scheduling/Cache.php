<?php

namespace Core\Console\Scheduling;

class Cache {
	
	const CACHE_GROUP = 'core_scheduler_cache';

	public function __construct(private \Core\Service\Cache $oCache) {}
	
	public function has($sKey) {
		return $this->oCache->exists($sKey);
	}
	
	public function add($sKey, $mData, $iExpiration) {
		return $this->oCache->put($sKey, $iExpiration, $mData, self::CACHE_GROUP);
	}
	
	public function forget($sKey) {
		return $this->oCache->forget($sKey);
	}
	
	public function getStore() {
		return $this->oCache;
	}
	
}

