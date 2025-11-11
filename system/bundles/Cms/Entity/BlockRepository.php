<?php

namespace Cms\Entity;

class BlockRepository extends \WDBasic_Repository {
	
	static public $aBlockCache = [];
	
	public function getByKey($sKey) {
		
		if(!isset(self::$aBlockCache[$sKey])) {

			$sSql = "
				SELECT 
					*
				FROM  
					cms_blocks  
				WHERE  
					block = :block
			";
			$aSql = [
				'block' => $sKey
			];

			$aBlock = \DB::getQueryRow($sSql, $aSql);

			self::$aBlockCache[$sKey] = $this->_getEntity($aBlock);

		}
		
		return self::$aBlockCache[$sKey];
	}
	
}
