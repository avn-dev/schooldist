<?php

namespace TsAccounting\Gui2\Selection\Company;

class ServiceTypes extends \Ext_Gui2_View_Selection_Abstract
{

	/**
	 * @param array $aSelectedIds
	 * @param array $aSaveField
	 * @param \WDBasic $oWDBasic
	 * @return array
	 */
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic)
	{

		$aServiceTypes = array(
			'course' => 1,
			'additional_course' => 1,
			'accommodation' => 1,
			'additional_accommodation' => 1,
			'additional_general' => 1,
			'insurance' => 1,
			#'cancellation' => 1,
			#'currency' => 1,
			'activity' => 1
		);

		$options = [];
		foreach ($aServiceTypes as $serviceType => $value) {
			$typeData = \TsAccounting\Gui2\Data\Company::getServiceTypeOptions($serviceType);
			$options[$serviceType] = $this->_oGui->t($typeData['real_name']);
		}

		$options['others'] = $this->_oGui->t('Andere');

		return $options;
	}

}