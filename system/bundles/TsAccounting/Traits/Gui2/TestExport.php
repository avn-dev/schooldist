<?php

namespace TsAccounting\Traits\Gui2;

trait TestExport {

	public function requestAsUrlTestExport($aVars) {

		$entities = [];
		foreach($aVars['id'] as $id) {
			$entities[] = call_user_func_array([$this->_oGui->class_wdbasic, 'getInstance'], [$id]);
		}

		\TsAccounting\Service\BookingStackService::outputTestExport($entities);
	}

}
