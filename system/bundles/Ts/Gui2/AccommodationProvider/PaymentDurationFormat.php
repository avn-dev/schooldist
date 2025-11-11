<?php

namespace Ts\Gui2\AccommodationProvider;

use Ts\Helper\Accommodation\AllocationCombination;

class PaymentDurationFormat extends \Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		
		$aGroupBy = explode('_', $aResultData['groupby'], 2);
		$sGroupKey = reset($aGroupBy);
		
		$mValue = '';

		if(isset($aResultData['accommodation_allocation_id'])) {
			
			if(!empty($aResultData['additional'])) {
				$aAdditional = json_decode($aResultData['additional'], true);
				if(!empty($aAdditional['combination'])) {
					$oAllocationCombination = AllocationCombination::getFromAllocationIds($aAdditional['combination']);
				}
			}
			
			if(!$oAllocationCombination) {
				// UAB: Bezahlen
				$iAllocationId = $aResultData['accommodation_allocation_id'];

				$oAllocation = \Ext_Thebing_Accommodation_Allocation::getInstance($iAllocationId);
				
				$oAllocationCombination = new AllocationCombination($oAllocation);
			}
			
		} else {
			// UAB: Bezahlte Einträge (untere Liste)
			$iAllocationId = $aResultData['allocation_id'];
			$aResultData['from'] = $aResultData['timepoint'];
			$aResultData['count'] = 1;
		
			$oAllocation = \Ext_Thebing_Accommodation_Allocation::getInstance($iAllocationId);

			$oAllocationCombination = new AllocationCombination($oAllocation);

		}

		// Nur anzeigen, wenn keine Gruppierung oder Gruppierung innerhalb einer Zuweisungskombination (GroupKey = single)
		if(
			$oAllocationCombination->exist() &&
			(
				$sGroupKey === 'single' ||
				$aResultData['count'] == 1
			) &&
			$aResultData['from'] != '0000-00-00' &&
			$aResultData['until'] != '0000-00-00'
		) {

			$dAllocationFrom = new \DateTime($oAllocationCombination->from);
			$dAllocationUntil = new \DateTime($oAllocationCombination->until);
			
			$oPeriod = \Ts\Entity\AccommodationProvider\Payment\Category\Period::getInstance($aResultData['period_id']);

			$dBillingFrom = clone $dAllocationFrom;
			
			$dFrom = new \DateTime($aResultData['from']);
			$dUntil = new \DateTime($aResultData['until']);

			if($oPeriod->period_type == 'absolute_weeks') {

				$iBillingFromWeekDay = $dBillingFrom->format('N');
				// Nur zurückgehen, wenn der Wochentag nicht schon korrekt ist
				if($oPeriod->period_start_day != $iBillingFromWeekDay) {
					$sWeekday = \Ext_Thebing_Util::convertWeekdayToEngWeekday($oPeriod->period_start_day);
					$dBillingFrom->modify('last '.$sWeekday);
				}

				$iFromWeekDay = $dFrom->format('N');
				// Nur zurückgehen, wenn der Wochentag nicht schon korrekt ist
				if($oPeriod->period_start_day != $iFromWeekDay) {
					$sWeekday = \Ext_Thebing_Util::convertWeekdayToEngWeekday($oPeriod->period_start_day);
					$dFrom->modify('last '.$sWeekday);
				}

				$sCountMethod = 'countWeeks';
			} elseif($oPeriod->period_type == 'absolute_month') {
				$dBillingFrom->modify('first day of this month');
				$sCountMethod = 'countMonth';
			} else {
				$sCountMethod = 'countWeeks';
			}

			$iAllocationUnits = \Ext_Thebing_Util::$sCountMethod($dBillingFrom, $dAllocationUntil);
			
			$iPaymentUnits = \Ext_Thebing_Util::$sCountMethod($dFrom, $dUntil);

			$iStartUnits = \Ext_Thebing_Util::$sCountMethod($dAllocationFrom, $dFrom);

			// Wenn Startweek 0, dann ist man in der ersten Woche
			$iStartUnits++;

			$mValue = $iStartUnits;

			if($iPaymentUnits > 1) {
				$mValue .= '-'.($iStartUnits+$iPaymentUnits-1);
			}

			$mValue .= '/'.$iAllocationUnits;
			
		}

		return $mValue;
	}
	
}