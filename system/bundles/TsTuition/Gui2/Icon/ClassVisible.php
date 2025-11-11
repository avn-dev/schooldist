<?php

namespace TsTuition\Gui2\Icon;

class ClassVisible extends \Ext_Gui2_View_Icon_Abstract
{

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement)
	{

		if (
			$oElement->action === 'confirm_class' &&
			(
				(int)\System::d('class_auto_confirm') ||
				(
					empty($aSelectedIds) ||
					\Ext_Thebing_Tuition_Class::getInstance((int)reset($aSelectedIds))?->isConfirmed()
				)
			)
		) {
			return false;
		}

		return true;
	}

}
