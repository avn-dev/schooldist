<?php

class Ext_TS_Accounting_Provider_Grouping_Teacher_Gui2_Format_SchoolBuilding extends Ext_TC_Gui2_Format {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		$aReturn = array();
		$oGrouping = Ext_TS_Accounting_Provider_Grouping_Teacher::getInstance($aResultData['id']);

		$aPayments = (array)$oGrouping->getJoinedObjectChilds('payments');
		foreach($aPayments as $oPayment) {
            /* @var Ext_Thebing_Teacher_Payment $oPayment */

			$oBlock = $oPayment->getBlock();

            $aRooms = $oBlock->getRooms();
			foreach($aRooms as $oRoom) {
                $oFloor = $oRoom->getFloor();
                $oBuilding = $oFloor->getBuilding();

                $sName = $oBuilding->getName();
                if(
                    !empty($sName) &&
                    !in_array($sName, $aReturn)
                ) {
                    $aReturn[] = $sName;
                }
            }

		}

		$sReturn = join(', ', $aReturn);
		return $sReturn;
	}

}
