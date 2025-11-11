<?php

class Ext_Thebing_Accommodation_Dates {
	
	/**
	 * Liefert alle Unterkunftsraum Blockierungen eines Raumes in einem bestimmten Intervall
	 * @param type $iRoomId
	 * @param type $dFrom
	 * @param type $mTo
	 * @return type 
	 */
	public static function getBlockingPeriods($iRoomId, \DateTime $dFrom = null, \DateTime $dUntil = null) {
		
		$dModifiedFrom = clone $dFrom;
		$dModifiedUntil = clone $dUntil;
		
		$dModifiedUntil->sub(new DateInterval('P1D'));
		$dModifiedFrom->add(new DateInterval('P1D'));
		
		$oAbsence = new Ext_Thebing_Absence();		
		$aBlockingPeriods = $oAbsence->getEntries($dModifiedFrom, $dModifiedUntil, array((int)$iRoomId), 'accommodation');		
		
		$aBlockingPeriods = (array)$aBlockingPeriods[$iRoomId];
		
		// Zeiten für Matching anpassen
		foreach($aBlockingPeriods as $iKey => $aData){
			$aBlockingPeriods[$iKey]['from_timestamp'] = Ext_Thebing_Util::convertToGMT($aData['from_timestamp']);
			$aBlockingPeriods[$iKey]['until_timestamp'] = Ext_Thebing_Util::convertToGMT($aData['until_timestamp']);
		}
		
		return $aBlockingPeriods;
	}
	
}