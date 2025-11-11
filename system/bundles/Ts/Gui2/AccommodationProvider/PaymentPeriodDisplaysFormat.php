<?php

namespace Ts\Gui2\AccommodationProvider;

class PaymentPeriodDisplaysFormat extends \Ext_Gui2_View_Format_Abstract {

	/**
	 * @param mixed $mValue
	 * @param null $oColumn
	 * @param null|array $aResultData
	 * @return mixed
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		
		$aPeriodsDisplays = explode('{||}', $aResultData['period_displays'], 2);
		$aDisplayOptions = self::getDisplayOptions($this->oGui);

		$mValue = array();
		foreach($aPeriodsDisplays as $sPeriodDisplay) {
			$mValue[] = $aDisplayOptions[$sPeriodDisplay];
		}
		asort($mValue);

		return join('<br />', $mValue);
	}

	/**
	 * @param \Ext_Gui2 $oGui
	 * @return array
	 */
	public static function getDisplayOptions($oGui) {

		$aDisplayOptions = array(
			'week' => $oGui->t('Zuweisungswoche'),
			'allocation' => $oGui->t('Zuweisung'),
			'student' => $oGui->t('Schüler'),
			'provider' => $oGui->t('Anbieter'),
			'single' => $oGui->t('Einzeln')
		);

		return $aDisplayOptions;
	}
	
}