<?php

namespace TsAccounting\Gui2\Data;

class Transactions extends \Ext_Thebing_Gui2_Data {
	
	public static function getOrderby() {
		return ['ts_at.due_date'=>'ASC'];
	}
	
	protected function _buildWherePart($aWhere) {

		$aWherePart = parent::_buildWherePart($aWhere);

		if($this->_oGui->sView === 'transactions') {
			
			$aParentIds = $this->request->input('parent_gui_id');
			$iParentId = reset($aParentIds);

			$oParentTransaction = \TsAccounting\Entity\Transaction::getInstance($iParentId);

			$aWherePart['sql'] .= ' WHERE account_type = :account_type AND account_id = :account_id ';
			$aWherePart['data']['account_type'] = $oParentTransaction->account_type;
			$aWherePart['data']['account_id'] = $oParentTransaction->account_id;

		}
				
		return $aWherePart;
	}
	
}
