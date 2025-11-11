<?php

namespace TsTuition\Gui2\Icon;

class ClassActive extends \Ext_Gui2_View_Icon_Abstract
{

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement)
	{

		if (
			!empty($aSelectedIds) &&
			$oElement->action === 'confirm_class'
		) {
			if (!\Ext_Thebing_Tuition_Class::getInstance((int)reset($aSelectedIds))?->isConfirmed()) {
				return true;
			}

			return false;
		} else if (
			$oElement->action === 'new' ||
			!empty($aSelectedIds)
		) {
			return true;
		}

		return false;
	}

}
