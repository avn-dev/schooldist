<?php

trait Ext_TS_Transfer_Location_Trait {

	public function validate($bThrowExceptions = false) {

		$mReturn = parent::validate($bThrowExceptions);

		if($mReturn === true) {
			$mReturn = array();
		}

		$sTableAlias = $this->_sTableAlias;
		if(!empty($sTableAlias)) {
			$sTableAlias .= '.';
		}

		$sValidUntilKey = $sTableAlias.'valid_until';

		if(
			$this->valid_until != '0000-00-00' &&
			$this->valid_until != ''
		) {
			$aJourneyTransfers = $this->getInquiryJourneyTransfers($this->valid_until);
			if(!empty($aJourneyTransfers)) {
				if(
					!isset($mReturn[$sValidUntilKey])
				){
					$mReturn[$sValidUntilKey] = array();
				}
				$mReturn[$sValidUntilKey][] = 'JOURNEY_TRANSFER_FOUND';
			}
		}

		if(empty($mReturn)) {
			$mReturn = true;
		}

		return $mReturn;

	}

	public function getInquiryJourneyTransfers($mUseDate = false) {
		return array();
	}

}
