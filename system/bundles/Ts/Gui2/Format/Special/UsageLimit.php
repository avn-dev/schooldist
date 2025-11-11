<?php

namespace Ts\Gui2\Format\Special;


class UsageLimit extends \Ext_Thebing_Gui2_Format_Int {

	public function format($mValue, &$oColumn = null, &$aResultData = null){
		return $mValue === null ? $this->oGui->t('Unendlich') : $mValue;
	}

}