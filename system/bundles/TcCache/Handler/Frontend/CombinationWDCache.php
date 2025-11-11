<?php

namespace TcCache\Handler\Frontend;

class CombinationWDCache {

	const GROUP_CACHE_KEY = 'tc_frontend_combination_group';

	/**
	 * @var \Ext_TC_Frontend_Combination 
	 */
	private $oFrontendCombination;
	
	/**
	 * @param \Ext_TC_Frontend_Combination $oCombination
	 */
	public function __construct(\Ext_TC_Frontend_Combination $oCombination) {
		$this->oFrontendCombination = $oCombination;
	}
	
	/**
	 * @param string $sCacheKey
	 * @return mixed|null
	 */
	public function get($sCacheKey) {
		return \WDCache::get($sCacheKey);
	}
	
	/**
	 * @param string $sCacheKey
	 * @param int $iExpiration
	 * @param mixed $mData
	 */
	public function set($sCacheKey, $iExpiration, $mData) {
		\WDCache::set($sCacheKey, $iExpiration, $mData, false, $this->buildCacheGroupKey());		
	}
	
	/**
	 * 
	 */
	public function deleteGroup() {
		\WDCache::deleteGroup($this->buildCacheGroupKey());
	}
	
	/**
	 * @return string
	 */
	public function buildCacheGroupKey() {
		
		$aCacheData = [
			self::GROUP_CACHE_KEY,
			$this->oFrontendCombination->getId()
		];
		
		return implode('_', $aCacheData);
	}
	
}
