<?php

class Ext_Thebing_Placementtests_ResultsRepository extends \WDBasic_Repository {

    /**
     * Holt ein Einstufungstest anhand des Schlüssels
     *
     * @param $sKey
     * @return Ext_Thebing_Placementtests_Results|null
     */
    public function getPlacementtestPerKey($sKey) {

        $aDataObjects = $this->findOneBy([
            'key' => $sKey
        ]);

        return $aDataObjects;

    }

	/**
	 * Holt ein Einstufungstest anhand der Inquiry Id
	 *
	 * @param int $iId
	 * @return Ext_Thebing_Placementtests_Results|null
	 */
	public function getPlacementtestPerInquiryId($iId) {

		$aDataObjects = $this->findOneBy([
			'inquiry_id' => $iId
		]);

		return $aDataObjects;
	}

}