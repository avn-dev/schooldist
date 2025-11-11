<?php

namespace Ts\Entity;

class PointOfSale extends \Ext_Thebing_Basic {
	
	protected $_sTable = 'ts_pos';
	protected $_sTableAlias = 'ts_p';

	static public function getSelectOptions() {
		$oSelf = new self;
		return $oSelf->getArrayList(true);
	}
	
}
