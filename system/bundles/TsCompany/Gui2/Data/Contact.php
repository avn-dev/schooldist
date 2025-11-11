<?php

namespace TsCompany\Gui2\Data;

use TsCompany\Traits\Gui2\DialogBuild;

class Contact extends \Ext_Thebing_Gui2_Data {
	use DialogBuild;

	public static function getWhere(\Ext_Gui2 $gui2) {
		return ['active' => 1];
	}

	public static function getOrderby(){
		return [
			'ts_ac.lastname' => 'ASC',
			'ts_ac.firstname' => 'ASC'
		];
	}

}
